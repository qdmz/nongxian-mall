<?php
/**
 * 易支付每日对账（2026-10-14b 新增）—— 只读对比、只记录差异，不修改任何账务数据
 *
 * 用法（仅限命令行，nginx 不会把该文件交给 PHP 执行）：
 *   php /var/www/nongxian-mall/api/scripts/reconcile_epay.php            # 对账昨天（北京时间）
 *   php /var/www/nongxian-mall/api/scripts/reconcile_epay.php 2026-10-13 # 对账指定日期
 * 结果：写入表 pay_reconcile_diffs（同日同类同单去重），并输出摘要到 stdout（cron 追加到 /var/log/nongxian-mall/reconcile.log）
 *
 * 说明：该易支付商户号同时被其他站点使用，平台订单中只有 out_trade_no 形如本站支付单号（P + 年月日时分秒...）
 *      或在本地 payments 表中存在的，才参与对账。
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('APP_ROOT', dirname(__DIR__));
define('CRON_MODE', true);
date_default_timezone_set('Asia/Shanghai');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

spl_autoload_register(function (string $class): void {
    foreach (['App\\' => APP_ROOT . '/', 'Core\\' => APP_ROOT . '/core/'] as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix)) {
            $file = $baseDir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) { require $file; return; }
            // 大小写兼容（仓库版补充）：目录段转小写，类文件名保持原样；线上靠 Services→services 等软链接兜底
            $fixed = strtolower(dirname($file)) . '/' . basename($file);
            if (is_file($fixed)) require $fixed;
            return;
        }
    }
});
require APP_ROOT . '/core/Helper.php';

use App\Services\PayService;

$date = $argv[1] ?? date('Y-m-d', strtotime('yesterday'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    fwrite(STDERR, "bad date\n");
    exit(2);
}
$dayStart = strtotime($date . ' 00:00:00');
$dayEnd = $dayStart + 86400;

$out = function (string $msg) use ($date) {
    echo date('Y-m-d H:i:s') . " [reconcile {$date}] {$msg}" . PHP_EOL;
};

if ((string)config('pay.epay_api_url', '') === '' || (string)config('pay.epay_pid', '') === '') {
    $out('易支付未配置，跳过');
    exit(0);
}

$diffs = [];
$addDiff = function (string $type, ?string $paymentNo, ?string $tradeNo, $localAmt, $remoteAmt, $localSt, $remoteSt, string $detail) use (&$diffs) {
    $diffs[] = compact('type', 'paymentNo', 'tradeNo', 'localAmt', 'remoteAmt', 'localSt', 'remoteSt', 'detail');
};

// 1) 平台订单（该接口不支持翻页参数，一次取足够多条；条数打满时提示）
$limit = 2000;
[$ok, $remoteList, $err] = PayService::epayListOrders(1, $limit);
if (!$ok) {
    $out('拉取平台订单失败：' . $err);
    exit(1);
}
if (count($remoteList) >= $limit) {
    $out("警告：平台返回条数达到上限 {$limit}，可能有遗漏，将对本地单逐笔查单兜底");
}

$isOurs = fn(string $no) => (bool)preg_match('/^P20\d{12}\d{3,}$/', $no);
$remoteByNo = [];
foreach ($remoteList as $o) {
    $no = (string)($o['out_trade_no'] ?? '');
    $t = strtotime((string)($o['addtime'] ?? '')) ?: 0;
    if ($t < $dayStart || $t >= $dayEnd) continue;
    $remoteByNo[$no] = $o;
}

// 2) 本地支付单（易支付渠道，当天创建）
$locals = db()->all("SELECT * FROM payments WHERE channel = 'epay' AND created_at >= ? AND created_at < ?", [$dayStart, $dayEnd]);
$localByNo = [];
foreach ($locals as $p) $localByNo[$p['payment_no']] = $p;

// 2a) 平台有、本地异常
$remoteOurs = 0;
foreach ($remoteByNo as $no => $o) {
    $local = $localByNo[$no] ?? db()->one('SELECT * FROM payments WHERE payment_no = ?', [$no]);
    if (!$local && !$isOurs($no)) continue; // 其他站点的订单
    $remoteOurs++;
    $rs = (string)($o['status'] ?? '');
    $rm = (float)($o['money'] ?? 0);
    if (!$local) {
        if ($rs === '1' || $rs === '2') $addDiff('remote_unknown', $no, $o['trade_no'] ?? null, null, $rm, null, $rs, '平台有已付款订单，本地无对应支付单');
        continue;
    }
    $lm = (float)$local['amount'];
    if (abs($lm - $rm) > 0.001) {
        $addDiff('amount_mismatch', $no, $o['trade_no'] ?? null, $lm, $rm, (int)$local['status'], $rs, '金额不一致');
    }
    if ($rs === '1' && (int)$local['status'] !== 1) {
        $addDiff('remote_paid_local_unpaid', $no, $o['trade_no'] ?? null, $lm, $rm, (int)$local['status'], $rs, '平台已付款，本地未入账（回调丢失？）');
    }
    if ($rs === '2') {
        $refunded = db()->value('SELECT COUNT(*) FROM refund_records WHERE payment_no = ? AND status = 1', [$no]);
        if (!$refunded) $addDiff('status_mismatch', $no, $o['trade_no'] ?? null, $lm, $rm, (int)$local['status'], $rs, '平台显示已退款，本地无成功的原路退款记录');
    }
    if ((int)$local['status'] === 1 && $rs === '0') {
        $addDiff('status_mismatch', $no, $o['trade_no'] ?? null, $lm, $rm, 1, $rs, '本地已入账，平台显示未付款');
    }
}

// 2b) 本地已付款，平台列表中没有 → 逐笔查单兜底
foreach ($locals as $p) {
    if ((int)$p['status'] !== 1 || isset($remoteByNo[$p['payment_no']])) continue;
    $q = PayService::epayQueryOrder($p['payment_no']);
    if (!$q) {
        $addDiff('local_paid_remote_missing', $p['payment_no'], $p['trade_no'], (float)$p['amount'], null, 1, null, '本地已入账，平台查无此单');
        continue;
    }
    $rs = (string)($q['status'] ?? '');
    if (abs((float)$p['amount'] - (float)($q['money'] ?? 0)) > 0.001) {
        $addDiff('amount_mismatch', $p['payment_no'], $q['trade_no'] ?? null, (float)$p['amount'], (float)($q['money'] ?? 0), 1, $rs, '金额不一致（查单）');
    }
    if ($rs === '0') {
        $addDiff('status_mismatch', $p['payment_no'], $q['trade_no'] ?? null, (float)$p['amount'], (float)($q['money'] ?? 0), 1, $rs, '本地已入账，平台显示未付款（查单）');
    }
}

// 3) 当天原路退款记录中仍"待人工"的，也列入差异提醒
foreach (db()->all('SELECT * FROM refund_records WHERE status = 3 AND created_at >= ? AND created_at < ?', [$dayStart, $dayEnd]) as $r) {
    $addDiff('refund_manual', $r['payment_no'], $r['trade_no'], (float)$r['amount'], null, null, null, '原路退款待人工核实：' . (string)$r['last_error']);
}

foreach ($diffs as $d) {
    db()->query(
        'INSERT IGNORE INTO pay_reconcile_diffs (bill_date, diff_type, payment_no, trade_no, local_amount, remote_amount, local_status, remote_status, detail, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)',
        [$date, $d['type'], $d['paymentNo'], $d['tradeNo'], $d['localAmt'], $d['remoteAmt'], $d['localSt'], $d['remoteSt'], mb_substr($d['detail'], 0, 490), time()]
    );
}

$localPaid = count(array_filter($locals, fn($p) => (int)$p['status'] === 1));
$out(sprintf('本地易支付单 %d 笔（已付款 %d），平台本站订单 %d 笔，差异 %d 条%s',
    count($locals), $localPaid, $remoteOurs, count($diffs),
    $diffs ? '：' . implode('；', array_map(fn($d) => $d['type'] . ' ' . $d['paymentNo'], $diffs)) : ''));
exit(0);
