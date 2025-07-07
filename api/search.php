<?php
// 启动会话（必须在任何输出之前）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    $query = $_GET['q'] ?? '';
    
    if (empty($query) || strlen($query) < 2) {
        echo json_encode([
            'success' => false,
            'message' => '搜索关键词至少需要2个字符',
            'results' => []
        ]);
        exit;
    }
    
    $results = searchPosts($query, 10, 0);
    
    // 格式化结果
    $formattedResults = [];
    foreach ($results as $post) {
        $typeInfo = getPostTypeLabel($post['post_type']);
        
        $formattedResults[] = [
            'id' => $post['id'],
            'title' => $post['title'],
            'excerpt' => $post['excerpt'] ?: mb_substr(strip_tags($post['content']), 0, 100) . '...',
            'post_type' => $post['post_type'],
            'type_label' => $typeInfo['label'],
            'type_color' => $typeInfo['color'],
            'type_icon' => $typeInfo['icon'],
            'cover_image' => $post['cover_image'],
            'created_at' => $post['created_at'],
            'friendly_date' => formatFriendlyDate($post['created_at'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'results' => $formattedResults,
        'total' => count($formattedResults)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '搜索出错：' . $e->getMessage(),
        'results' => []
    ]);
}
?> 