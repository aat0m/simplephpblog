<?php
require_once __DIR__ . '/../config/database.php';

// 获取文章列表
function getPosts($limit = 10, $offset = 0, $published_only = false) {
    $pdo = getDatabase();
    
    $where = $published_only ? 'WHERE published = 1' : '';
    $sql = "SELECT * FROM posts $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

// 获取文章总数
function getTotalPosts($published_only = false) {
    $pdo = getDatabase();
    
    $where = $published_only ? 'WHERE published = 1' : '';
    $sql = "SELECT COUNT(*) FROM posts $where";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchColumn();
}

// 根据ID获取单篇文章
function getPostById($id) {
    $pdo = getDatabase();
    
    $sql = "SELECT * FROM posts WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch();
}

// 创建新文章
function createPost($title, $content, $excerpt = '', $cover_image = '', $published = 0, $post_type = 'article', $images = null) {
    $pdo = getDatabase();
    
    // 处理图片数据
    $images_json = null;
    if ($images && is_array($images)) {
        $images_json = json_encode($images);
        
        // 如果是图片类型且没有封面图片，使用第一张图片作为封面
        if ($post_type === 'image' && empty($cover_image) && !empty($images)) {
            $cover_image = $images[0]['url'] ?? '';
        }
    } elseif ($images && is_string($images)) {
        // 如果传入的是JSON字符串，先解析再重新编码以确保格式正确
        $parsed_images = json_decode($images, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $images_json = $images;
            
            // 如果是图片类型且没有封面图片，使用第一张图片作为封面
            if ($post_type === 'image' && empty($cover_image) && !empty($parsed_images)) {
                $cover_image = $parsed_images[0]['url'] ?? '';
            }
        }
    }
    
    $sql = "INSERT INTO posts (title, content, excerpt, cover_image, images, published, post_type) 
            VALUES (:title, :content, :excerpt, :cover_image, :images, :published, :post_type)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':title', $title);
    $stmt->bindValue(':content', $content);
    $stmt->bindValue(':excerpt', $excerpt);
    $stmt->bindValue(':cover_image', $cover_image);
    $stmt->bindValue(':images', $images_json);
    $stmt->bindValue(':published', $published, PDO::PARAM_BOOL);
    $stmt->bindValue(':post_type', $post_type);
    
    if ($stmt->execute()) {
        return $pdo->lastInsertId();
    }
    
    return false;
}

// 更新文章
function updatePost($id, $title, $content, $excerpt = '', $cover_image = '', $published = 0, $post_type = 'article', $images = null) {
    $pdo = getDatabase();
    
    // 处理图片数据
    $images_json = null;
    if ($images && is_array($images)) {
        $images_json = json_encode($images);
        
        // 如果是图片类型且没有封面图片，使用第一张图片作为封面
        if ($post_type === 'image' && empty($cover_image) && !empty($images)) {
            $cover_image = $images[0]['url'] ?? '';
        }
    } elseif ($images && is_string($images)) {
        // 如果传入的是JSON字符串，先解析再重新编码以确保格式正确
        $parsed_images = json_decode($images, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $images_json = $images;
            
            // 如果是图片类型且没有封面图片，使用第一张图片作为封面
            if ($post_type === 'image' && empty($cover_image) && !empty($parsed_images)) {
                $cover_image = $parsed_images[0]['url'] ?? '';
            }
        }
    }
    
    $sql = "UPDATE posts SET title = :title, content = :content, excerpt = :excerpt, 
            cover_image = :cover_image, images = :images, published = :published, post_type = :post_type, 
            updated_at = CURRENT_TIMESTAMP WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':title', $title);
    $stmt->bindValue(':content', $content);
    $stmt->bindValue(':excerpt', $excerpt);
    $stmt->bindValue(':cover_image', $cover_image);
    $stmt->bindValue(':images', $images_json);
    $stmt->bindValue(':published', $published, PDO::PARAM_BOOL);
    $stmt->bindValue(':post_type', $post_type);
    
    return $stmt->execute();
}

// 删除文章
function deletePost($id) {
    $pdo = getDatabase();
    
    $sql = "DELETE FROM posts WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    
    return $stmt->execute();
}

// 保存上传的图片信息
function saveImage($filename, $original_name, $file_path, $mime_type, $file_size) {
    $pdo = getDatabase();
    
    $sql = "INSERT INTO images (filename, original_name, file_path, mime_type, file_size) 
            VALUES (:filename, :original_name, :file_path, :mime_type, :file_size)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':filename', $filename);
    $stmt->bindValue(':original_name', $original_name);
    $stmt->bindValue(':file_path', $file_path);
    $stmt->bindValue(':mime_type', $mime_type);
    $stmt->bindValue(':file_size', $file_size, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        return $pdo->lastInsertId();
    }
    
    return false;
}

// 获取图片列表
function getImages($limit = 50) {
    $pdo = getDatabase();
    
    $sql = "SELECT * FROM images ORDER BY created_at DESC LIMIT :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

// 处理文件上传
function uploadImage($file) {
    $upload_dir = __DIR__ . '/../uploads/';
    
    // 创建uploads目录
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // 检查文件类型
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => '不支持的文件类型'];
    }
    
    // 检查文件大小 (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => '文件大小超过5MB'];
    }
    
    // 生成唯一文件名
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $file_path = $upload_dir . $filename;
    
    // 移动文件
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        // 保存到数据库
        $image_id = saveImage($filename, $file['name'], $file_path, $file['type'], $file['size']);
        
        if ($image_id) {
            return [
                'success' => true,
                'id' => $image_id,
                'filename' => $filename,
                'url' => 'uploads/' . $filename,
                'original_name' => $file['name'],
                'size' => $file['size']
            ];
        }
    }
    
    return ['success' => false, 'message' => '文件上传失败'];
}

// 安全地输出HTML内容
function sanitizeOutput($content) {
    // 对于富文本内容，我们需要允许一些HTML标签
    $allowed_tags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote><a><img><pre><code>';
    return strip_tags($content, $allowed_tags);
}

// 生成文章摘要
function generateExcerpt($content, $length = 200) {
    // 移除HTML标签
    $text = strip_tags($content);
    // 如果内容长度超过指定长度，截取并添加省略号
    if (mb_strlen($text) > $length) {
        return mb_substr($text, 0, $length) . '...';
    }
    return $text;
}

// 检查是否为AJAX请求
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// 返回JSON响应
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 验证和清理输入
function cleanInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// ==================== 用户系统相关函数 ====================

// 启动会话
function startSession() {
    // 不再需要这个函数，因为我们在页面开头就启动了session
    // 保留这个函数是为了向后兼容
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// 用户登录验证
function login($username, $password) {
    $pdo = getDatabase();
    
    $sql = "SELECT * FROM users WHERE username = :username AND status = 'active'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':username', $username);
    $stmt->execute();
    
    $user = $stmt->fetch();
    
    if ($user && $password === $user['password']) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // 更新最后登录时间
        updateLastLogin($user['id']);
        
        return ['success' => true, 'user' => $user];
    }
    
    return ['success' => false, 'message' => '用户名或密码错误'];
}

// 用户登出
function logout() {
    session_destroy();
}

// 检查用户是否已登录
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// 获取当前登录用户信息
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $pdo = getDatabase();
    $sql = "SELECT * FROM users WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch();
}

// 检查用户角色权限
function hasRole($required_role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $current_role = $_SESSION['role'];
    
    // 管理员拥有所有权限
    if ($current_role === 'admin') {
        return true;
    }
    
    // 检查是否匹配要求的角色
    return $current_role === $required_role;
}

// 检查是否为管理员
function isAdmin() {
    return hasRole('admin');
}

// 权限验证（重定向到登录页面）
function requireLogin($redirect_url = 'login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect_url");
        exit;
    }
}

// 管理员权限验证
function requireAdmin($redirect_url = 'login.php') {
    if (!isAdmin()) {
        header("Location: $redirect_url");
        exit;
    }
}

// 更新最后登录时间
function updateLastLogin($user_id) {
    $pdo = getDatabase();
    
    $sql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
    
    return $stmt->execute();
}

// 获取所有用户
function getUsers() {
    $pdo = getDatabase();
    
    $sql = "SELECT id, username, email, role, status, last_login, created_at FROM users ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

// 根据ID获取用户
function getUserById($id) {
    $pdo = getDatabase();
    
    $sql = "SELECT id, username, email, role, status, last_login, created_at FROM users WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch();
}

// 创建新用户
function createUser($username, $password, $email = '', $role = 'author') {
    $pdo = getDatabase();
    
    // 检查用户名是否已存在
    $check_sql = "SELECT id FROM users WHERE username = :username";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->bindValue(':username', $username);
    $check_stmt->execute();
    
    if ($check_stmt->fetch()) {
        return ['success' => false, 'message' => '用户名已存在'];
    }
    
    $sql = "INSERT INTO users (username, password, email, role) VALUES (:username, :password, :email, :role)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':username', $username);
    $stmt->bindValue(':password', $password);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':role', $role);
    
    if ($stmt->execute()) {
        return ['success' => true, 'user_id' => $pdo->lastInsertId()];
    }
    
    return ['success' => false, 'message' => '创建用户失败'];
}

// 更新用户密码
function updateUserPassword($user_id, $new_password) {
    $pdo = getDatabase();
    
    $sql = "UPDATE users SET password = :password WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':password', $new_password);
    $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
    
    return $stmt->execute();
}

// 更新用户信息
function updateUser($user_id, $email = null, $role = null, $status = null) {
    $pdo = getDatabase();
    
    $updates = [];
    $params = [':id' => $user_id];
    
    if ($email !== null) {
        $updates[] = 'email = :email';
        $params[':email'] = $email;
    }
    
    if ($role !== null) {
        $updates[] = 'role = :role';
        $params[':role'] = $role;
    }
    
    if ($status !== null) {
        $updates[] = 'status = :status';
        $params[':status'] = $status;
    }
    
    if (empty($updates)) {
        return false;
    }
    
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    return $stmt->execute();
}

// 删除用户
function deleteUser($user_id) {
    $pdo = getDatabase();
    
    $sql = "DELETE FROM users WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
    
    return $stmt->execute();
}

// 根据文章类型获取文章列表
function getPostsByType($post_type = null, $limit = 10, $offset = 0, $published_only = false) {
    $pdo = getDatabase();
    
    $where_conditions = [];
    if ($published_only) {
        $where_conditions[] = 'published = 1';
    }
    if ($post_type) {
        $where_conditions[] = 'post_type = :post_type';
    }
    
    $where = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
    $sql = "SELECT * FROM posts $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    if ($post_type) {
        $stmt->bindValue(':post_type', $post_type);
    }
    $stmt->execute();
    
    return $stmt->fetchAll();
}

// 获取文章类型统计
function getPostTypeStats() {
    $pdo = getDatabase();
    
    $sql = "SELECT post_type, COUNT(*) as count FROM posts WHERE published = 1 GROUP BY post_type";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $stats = [];
    while ($row = $stmt->fetch()) {
        $stats[$row['post_type']] = $row['count'];
    }
    
    return $stats;
}



// 搜索功能
function searchPosts($keyword, $limit = 10, $offset = 0) {
    $pdo = getDatabase();
    
    $sql = "SELECT * FROM posts 
            WHERE published = 1 
            AND (title LIKE :keyword OR content LIKE :keyword OR excerpt LIKE :keyword)
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $searchTerm = '%' . $keyword . '%';
    $stmt->bindValue(':keyword', $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

// 获取搜索结果总数
function getSearchResultsCount($keyword) {
    $pdo = getDatabase();
    
    $sql = "SELECT COUNT(*) FROM posts 
            WHERE published = 1 
            AND (title LIKE :keyword OR content LIKE :keyword OR excerpt LIKE :keyword)";
    
    $stmt = $pdo->prepare($sql);
    $searchTerm = '%' . $keyword . '%';
    $stmt->bindValue(':keyword', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();
    
    return $stmt->fetchColumn();
}



// 获取类型文章总数
function getPostsCountByType($type) {
    $pdo = getDatabase();
    
    $sql = "SELECT COUNT(*) FROM posts WHERE published = 1 AND post_type = :type";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':type', $type, PDO::PARAM_STR);
    $stmt->execute();
    
    return $stmt->fetchColumn();
}

// 获取所有文章类型及其数量
function getPostTypesWithCount() {
    $pdo = getDatabase();
    
    $sql = "SELECT post_type, COUNT(*) as count 
            FROM posts 
            WHERE published = 1 
            GROUP BY post_type 
            ORDER BY count DESC";
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

// 获取按月归档
function getPostsArchive() {
    $pdo = getDatabase();
    
    $sql = "SELECT 
                YEAR(created_at) as year,
                MONTH(created_at) as month,
                COUNT(*) as count,
                DATE_FORMAT(created_at, '%Y-%m') as date_key,
                DATE_FORMAT(created_at, '%Y年%m月') as date_name
            FROM posts 
            WHERE published = 1 
            GROUP BY YEAR(created_at), MONTH(created_at)
            ORDER BY year DESC, month DESC
            LIMIT 12";
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

// 根据年月获取文章
function getPostsByMonth($year, $month, $limit = 10, $offset = 0) {
    $pdo = getDatabase();
    
    $sql = "SELECT * FROM posts 
            WHERE published = 1 
            AND YEAR(created_at) = :year 
            AND MONTH(created_at) = :month
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':year', $year, PDO::PARAM_INT);
    $stmt->bindValue(':month', $month, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

// 根据年月日获取文章
function getPostsByDay($year, $month, $day, $limit = 10, $offset = 0) {
    $pdo = getDatabase();
    
    $sql = "SELECT * FROM posts 
            WHERE published = 1 
            AND YEAR(created_at) = :year 
            AND MONTH(created_at) = :month
            AND DAY(created_at) = :day
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':year', $year, PDO::PARAM_INT);
    $stmt->bindValue(':month', $month, PDO::PARAM_INT);
    $stmt->bindValue(':day', $day, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

// 获取热门文章（根据创建时间和类型加权计算）
function getPopularPosts($limit = 5) {
    $pdo = getDatabase();
    
    $sql = "SELECT *, 
                CASE 
                    WHEN post_type = 'image' THEN 3
                    WHEN post_type = 'text' THEN 2  
                    ELSE 1 
                END as type_weight,
                DATEDIFF(NOW(), created_at) as days_ago
            FROM posts 
            WHERE published = 1 
            ORDER BY (type_weight - (days_ago / 30)) DESC, created_at DESC
            LIMIT :limit";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

// 获取最新文章
function getRecentPosts($limit = 5) {
    $pdo = getDatabase();
    
    $sql = "SELECT * FROM posts 
            WHERE published = 1 
            ORDER BY created_at DESC 
            LIMIT :limit";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

// 获取精选文章（用于轮播图）
function getFeaturedPosts($limit = 4) {
    $pdo = getDatabase();
    
    // 优先选择有封面图片的文章，按创建时间倒序
    $sql = "SELECT * FROM posts 
            WHERE published = 1 
            AND (cover_image IS NOT NULL AND cover_image != '') 
            ORDER BY created_at DESC 
            LIMIT :limit";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $featuredPosts = $stmt->fetchAll();
    
    // 如果有封面图片的文章不足，补充其他文章
    if (count($featuredPosts) < $limit) {
        $remainingLimit = $limit - count($featuredPosts);
        $excludeIds = array_column($featuredPosts, 'id');
        $excludeIdsStr = empty($excludeIds) ? '' : 'AND id NOT IN (' . implode(',', $excludeIds) . ')';
        
        $sql2 = "SELECT * FROM posts 
                 WHERE published = 1 
                 $excludeIdsStr
                 ORDER BY created_at DESC 
                 LIMIT :limit";
        
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->bindValue(':limit', $remainingLimit, PDO::PARAM_INT);
        $stmt2->execute();
        
        $additionalPosts = $stmt2->fetchAll();
        $featuredPosts = array_merge($featuredPosts, $additionalPosts);
    }
    
    return $featuredPosts;
}

// 获取相关文章（基于类型和关键词）
function getRelatedPosts($postId, $postType, $limit = 4) {
    $pdo = getDatabase();
    
    $sql = "SELECT * FROM posts 
            WHERE published = 1 
            AND id != :post_id 
            AND post_type = :post_type
            ORDER BY created_at DESC 
            LIMIT :limit";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
    $stmt->bindValue(':post_type', $postType, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

// 获取网站统计信息
function getSiteStats() {
    $pdo = getDatabase();
    
    try {
        // 总文章数
        $stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE published = 1");
        $totalPosts = $stmt->fetchColumn();
        
        // 各类型文章数
        $stmt = $pdo->query("SELECT post_type, COUNT(*) as count FROM posts WHERE published = 1 GROUP BY post_type");
        $typeStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // 最新发布时间
        $stmt = $pdo->query("SELECT MAX(created_at) FROM posts WHERE published = 1");
        $lastPostDate = $stmt->fetchColumn();
        
        return [
            'total_posts' => $totalPosts,
            'article_count' => $typeStats['article'] ?? 0,
            'image_count' => $typeStats['image'] ?? 0,
            'text_count' => $typeStats['text'] ?? 0,
            'last_post_date' => $lastPostDate
        ];
    } catch (Exception $e) {
        return [
            'total_posts' => 0,
            'article_count' => 0,
            'image_count' => 0,
            'text_count' => 0,
            'last_post_date' => null
        ];
    }
}

// 格式化日期为友好格式
function formatFriendlyDate($date) {
    $now = new DateTime();
    $postDate = new DateTime($date);
    $diff = $now->diff($postDate);
    
    if ($diff->days == 0) {
        if ($diff->h == 0) {
            return $diff->i == 0 ? '刚刚' : $diff->i . '分钟前';
        }
        return $diff->h . '小时前';
    } elseif ($diff->days == 1) {
        return '昨天';
    } elseif ($diff->days < 7) {
        return $diff->days . '天前';
    } elseif ($diff->days < 30) {
        return ceil($diff->days / 7) . '周前';
    } elseif ($diff->days < 365) {
        return ceil($diff->days / 30) . '个月前';
    } else {
        return ceil($diff->days / 365) . '年前';
    }
}

// 生成文章URL
function getPostUrl($post) {
    return 'post.php?id=' . $post['id'];
}

// 获取文章类型标签
function getPostTypeLabel($type) {
    switch ($type) {
        case 'article':
            return ['label' => '图文', 'color' => 'green', 'icon' => 'fas fa-newspaper'];
        case 'image':
            return ['label' => '图片', 'color' => 'blue', 'icon' => 'fas fa-images'];
        case 'text':
            return ['label' => '文字', 'color' => 'purple', 'icon' => 'fas fa-quote-left'];
        default:
            return ['label' => '文章', 'color' => 'gray', 'icon' => 'fas fa-file-alt'];
    }
}
?> 