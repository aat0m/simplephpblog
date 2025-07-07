<?php
// 启动会话（必须在任何输出之前）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

// 检查用户是否登录
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => '请先登录'], 401);
}

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => '只允许POST请求'], 405);
}

// 检查是否有文件上传
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['success' => false, 'message' => '请选择要上传的图片文件']);
}

$file = $_FILES['image'];

// 文件大小限制检查
$maxSize = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxSize) {
    jsonResponse(['success' => false, 'message' => '文件大小不能超过5MB']);
}

// 文件类型检查
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    jsonResponse(['success' => false, 'message' => '只支持JPG、PNG、GIF、WebP格式的图片']);
}

// 上传图片
$result = uploadImage($file);

if ($result['success']) {
    jsonResponse([
        'success' => true,
        'id' => $result['id'],
        'url' => $result['url'],
        'filename' => $result['filename'],
        'original_name' => $result['original_name'],
        'size' => $result['size']
    ]);
} else {
    jsonResponse(['success' => false, 'message' => $result['message']]);
}
?> 