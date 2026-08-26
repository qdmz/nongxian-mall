<?php

namespace App\Controllers\Admin;

use Core\Controller;

/**
 * 管理后台 - 上传
 */
class UploadController extends Controller
{
    /**
     * POST /admin/upload/image
     */
    public function image(): void
    {
        if (empty($_FILES['file'])) {
            json_error('请选择要上传的文件');
        }
        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            json_error('上传失败，错误码：' . $file['error']);
        }

        $maxSize = (int)config('app.upload_max_size', 10485760);
        if ($file['size'] > $maxSize) {
            json_error('文件大小超出限制');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = config('app.upload_image_ext', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        if (!in_array($ext, $allowed, true)) {
            json_error('不支持的文件格式');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
            json_error('文件内容不是有效图片');
        }

        $dir = APP_ROOT . '/public/uploads/' . date('Y') . '/' . date('m');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
            json_error('保存文件失败');
        }

        $prefix = rtrim(config('app.upload_url_prefix', '/uploads'), '/');
        json_success([
            'url' => $prefix . '/' . date('Y') . '/' . date('m') . '/' . $filename,
        ], '上传成功');
    }
}
