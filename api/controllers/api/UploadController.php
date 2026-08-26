<?php

namespace App\Controllers\Api;

use Core\Controller;

/**
 * 图片上传（用户头像等）
 */
class UploadController extends Controller
{
    /**
     * POST /api/upload/image
     */
    public function image(): void
    {
        $result = $this->handleUpload('image');
        json_success($result, '上传成功');
    }

    private function handleUpload(string $scene): array
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

        // 扩展名白名单
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = config('app.upload_image_ext', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        if (!in_array($ext, $allowed, true)) {
            json_error('不支持的文件格式');
        }

        // MIME 校验
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime, $allowedMimes, true)) {
            json_error('文件内容不是有效图片');
        }

        // 存储目录：uploads/YYYY/MM/
        $dir = APP_ROOT . '/public/uploads/' . date('Y') . '/' . date('m');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $target = $dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            json_error('保存文件失败');
        }

        $prefix = rtrim(config('app.upload_url_prefix', '/uploads'), '/');
        $url = $prefix . '/' . date('Y') . '/' . date('m') . '/' . $filename;

        return [
            'url' => $url,
            'path' => '/uploads/' . date('Y') . '/' . date('m') . '/' . $filename,
        ];
    }
}
