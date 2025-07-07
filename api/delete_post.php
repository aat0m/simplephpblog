<?php
// 启动会话（必须在任何输出之前）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

// 检查用户是否登录
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// 获取文章ID（支持表单数据和JSON数据）
$id = 0;
if (isset($_POST['post_id'])) {
    // 表单数据
    $id = (int)$_POST['post_id'];
} else {
    // JSON数据（向后兼容）
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input && isset($input['id'])) {
        $id = (int)$input['id'];
    }
}

if ($id <= 0) {
    header('Location: ../index.php?error=invalid_id');
    exit;
}

// 检查文章是否存在
$post = getPostById($id);
if (!$post) {
    header('Location: ../index.php?error=post_not_found');
    exit;
}

// 删除文章
$success = deletePost($id);

if ($success) {
    header('Location: ../index.php?message=delete_success');
} else {
    header('Location: ../index.php?error=delete_failed');
}
exit;
?> 