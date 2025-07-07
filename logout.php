<?php
// 启动会话（必须在任何输出之前）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/functions.php';

// 执行登出
logout();

// 重定向到首页
header('Location: index.php?message=logout_success');
exit;
?> 