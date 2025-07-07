<?php
// 启动会话（必须在任何输出之前）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once 'config/database.php';
    require_once 'includes/functions.php';
} catch (Exception $e) {
    die("系统初始化失败: " . $e->getMessage());
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

$post = getPostById($id);

if (!$post) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// 获取当前用户信息（用于权限检查）
$current_user = isLoggedIn() ? getCurrentUser() : null;

// 获取相关文章
$relatedPosts = getRelatedPosts($id, $post['post_type'], 4);

// 解析图片数据
$images = !empty($post['images']) ? json_decode($post['images'], true) : [];

// 获取文章类型信息
$typeInfo = getPostTypeLabel($post['post_type']);
?>

<!DOCTYPE html>
<html lang="zh-CN" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['title']) ?> - 好朋友博客</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        /* 滚动条美化 */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        .dark ::-webkit-scrollbar-track {
            background: #374151;
        }
        .dark ::-webkit-scrollbar-thumb {
            background: #6b7280;
        }
        
        /* 文章内容样式 */
        .article-content {
            line-height: 1.8;
            color: #374151;
        }
        
        .dark .article-content {
            color: #d1d5db;
        }
        
        .article-content h1,
        .article-content h2,
        .article-content h3 {
            color: #1f2937;
            font-weight: 600;
            margin: 2rem 0 1rem 0;
        }
        
        .dark .article-content h1,
        .dark .article-content h2,
        .dark .article-content h3 {
            color: #f9fafb;
        }
        
        .article-content h1 { font-size: 1.875rem; }
        .article-content h2 { font-size: 1.5rem; }
        .article-content h3 { font-size: 1.25rem; }
        
        .article-content p {
            margin: 1.5rem 0;
            line-height: 1.8;
        }
        
        .article-content img {
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            margin: 2rem auto;
            max-width: 100%;
            height: auto;
        }
        
        .article-content blockquote {
            border-left: 4px solid #3b82f6;
            background: #f0f9ff;
            padding: 1.5rem;
            margin: 2rem 0;
            border-radius: 0.5rem;
            font-style: italic;
        }
        
        .dark .article-content blockquote {
            background: #1e3a8a;
            border-left-color: #60a5fa;
        }
        
        .article-content a {
            color: #3b82f6;
            text-decoration: none;
        }
        
        .article-content a:hover {
            text-decoration: underline;
        }
        
        .article-content ul,
        .article-content ol {
            margin: 1.5rem 0;
            padding-left: 2rem;
        }
        
        .article-content li {
            margin: 0.5rem 0;
        }
        
        /* 图片网格 */
        .image-grid {
            display: grid;
            gap: 0.5rem;
            border-radius: 1rem;
            overflow: hidden;
        }
        
        .image-grid.grid-1 {
            grid-template-columns: 1fr;
        }
        
        .image-grid.grid-2 {
            grid-template-columns: 1fr 1fr;
        }
        
        .image-grid.grid-3 {
            grid-template-columns: 2fr 1fr;
            grid-template-rows: 1fr 1fr;
        }
        
        .image-grid.grid-3 .image-item:first-child {
            grid-row: 1 / 3;
        }
        
        .image-grid.grid-4 {
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
        }
        
        .image-grid.grid-many {
            grid-template-columns: 1fr 1fr 1fr;
        }
        
        /* 图片灯箱样式修复 */
        .lightbox-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9999;
            background-color: rgba(0, 0, 0, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .lightbox-container {
            position: relative;
            max-width: 90vw;
            max-height: 90vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .lightbox-image {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            border-radius: 0.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        
        .lightbox-btn {
            position: absolute;
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
            z-index: 10000;
        }
        
        .lightbox-btn:hover {
            background-color: rgba(0, 0, 0, 0.7);
        }
        
        .lightbox-close {
            top: -1rem;
            right: -1rem;
        }
        
        .lightbox-prev {
            left: -4rem;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .lightbox-next {
            right: -4rem;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .lightbox-counter {
            position: absolute;
            bottom: -3rem;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 1rem;
            font-size: 0.875rem;
        }
        
        @media (max-width: 768px) {
            .lightbox-container {
                max-width: 95vw;
                max-height: 85vh;
            }
            
            .lightbox-prev,
            .lightbox-next {
                width: 2.5rem;
                height: 2.5rem;
                left: 1rem;
                right: 1rem;
            }
            
            .lightbox-prev {
                left: 1rem;
            }
            
            .lightbox-next {
                right: 1rem;
            }
            
            .lightbox-close {
                top: 1rem;
                right: 1rem;
            }
            
                         .lightbox-counter {
                 bottom: 1rem;
             }
         }
         
         .image-grid.grid-many .image-item:nth-child(n+10) {
             display: none;
         }
        

        
        /* 文字类型特殊样式 */
        .text-post {
            font-size: 1.25rem;
            line-height: 1.8;
            color: #374151;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .dark .text-post {
            color: #d1d5db;
        }
        
        /* 动画 */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        
        /* 毛玻璃效果 */
        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(229, 231, 235, 0.5);
        }
        
        .dark .glass {
            background: rgba(55, 65, 81, 0.95);
            border: 1px solid rgba(75, 85, 99, 0.5);
        }
        

    </style>
</head>

<body class="min-h-full bg-white dark:bg-gray-900 font-sans transition-colors duration-300">
    
    <!-- 顶部导航栏 -->
    <?php include 'includes/header.php'; ?>
    
    <!-- 阅读进度条 -->
    <div class="fixed top-0 left-0 w-full h-1 bg-gray-200 dark:bg-gray-700 z-50">
        <div id="reading-progress" class="h-full bg-gradient-to-r from-primary-500 to-purple-500 transition-all duration-150 ease-out" style="width: 0%"></div>
    </div>

    <!-- 主内容区域 -->
    <main class="min-h-screen">
        
        <!-- 文章内容 -->
        <div class="px-6 py-8">
                            <div class="max-w-6xl mx-auto bg-gradient-to-br from-gray-50 via-white to-blue-50 dark:from-gray-800 dark:via-gray-700 dark:to-gray-800 rounded-3xl p-6 lg:p-8 shadow-sm">
                <article>
            
            <!-- 返回按钮 -->
            <div class="mb-8 animate-fade-in-up">
                <a href="javascript:history.back()" 
                   class="inline-flex items-center space-x-2 text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors group">
                    <i class="fas fa-arrow-left group-hover:-translate-x-1 transition-transform"></i>
                    <span>返回</span>
                </a>
            </div>
            
            <!-- 文章头部 -->
            <header class="mb-8 animate-fade-in-up" style="animation-delay: 0.1s;">
                <div class="flex items-center space-x-3 mb-6">
                    <span class="inline-flex items-center space-x-2 text-white px-4 py-2 rounded-full text-sm font-medium shadow-lg
                        <?php if ($typeInfo['color'] === 'green'): ?>
                            bg-green-500
                        <?php elseif ($typeInfo['color'] === 'blue'): ?>
                            bg-blue-500
                        <?php elseif ($typeInfo['color'] === 'purple'): ?>
                            bg-purple-500
                        <?php else: ?>
                            bg-gray-500
                        <?php endif; ?>">
                        <i class="<?= $typeInfo['icon'] ?>"></i>
                        <span><?= $typeInfo['label'] ?></span>
                    </span>
                    
                    <?php if ($current_user): ?>
                        <div class="flex items-center space-x-2">
                            <a href="edit.php?id=<?= $post['id'] ?>" 
                               class="inline-flex items-center space-x-1 px-3 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm">
                                <i class="fas fa-edit"></i>
                                <span>编辑</span>
                            </a>
                            <button onclick="deletePost(<?= $post['id'] ?>)" 
                                    class="inline-flex items-center space-x-1 px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors text-sm">
                                <i class="fas fa-trash"></i>
                                <span>删除</span>
                            </button>
                </div>
                    <?php endif; ?>
                </div>

                <h1 class="text-4xl lg:text-5xl font-bold text-gray-900 dark:text-white mb-6 leading-tight">
                    <?= htmlspecialchars($post['title']) ?>
                </h1>

                <!-- 文章统计信息 -->
                <div class="flex flex-wrap items-center gap-4 lg:gap-6 text-sm text-gray-500 dark:text-gray-400">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-calendar-alt"></i>
                        <span><?= date('Y年m月d日', strtotime($post['created_at'])) ?></span>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-clock"></i>
                        <span><?= formatFriendlyDate($post['created_at']) ?></span>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-eye"></i>
                        <span id="reading-time">阅读中...</span>
                    </div>
                    
                    <?php if ($post['post_type'] === 'image' && !empty($images)): ?>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-images"></i>
                            <span><?= count($images) ?> 张图片</span>
                        </div>
                    <?php elseif ($post['post_type'] === 'text'): ?>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-font"></i>
                            <span><?= mb_strlen(strip_tags($post['content'])) ?> 字</span>
                        </div>
                    <?php endif; ?>
                    
                            <div class="flex items-center space-x-2">
                        <i class="fas fa-bookmark"></i>
                        <span>收藏</span>
                    </div>
                </div>
                
                <!-- 分享功能 -->
                <div class="mt-6 flex items-center space-x-4">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">分享到：</span>
                    <div class="flex items-center space-x-3">
                        <button onclick="shareToWeChat()" 
                                class="flex items-center space-x-2 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors text-sm">
                            <i class="fab fa-weixin"></i>
                            <span>微信</span>
                        </button>
                        <button onclick="shareToWeibo()" 
                                class="flex items-center space-x-2 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors text-sm">
                            <i class="fab fa-weibo"></i>
                            <span>微博</span>
                        </button>
                        <button onclick="copyLink()" 
                                class="flex items-center space-x-2 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors text-sm">
                            <i class="fas fa-link"></i>
                            <span>复制链接</span>
                        </button>
                    </div>
                </div>
            </header>
            
            <!-- 根据文章类型显示不同内容 -->
            <div class="animate-fade-in-up" style="animation-delay: 0.2s;">
                
                <?php if ($post['post_type'] === 'image'): ?>
                    <!-- 图片类型：优先显示图片 -->
                    <?php if (!empty($images)): ?>
                        <div class="mb-8">
                            <?php 
                            $imageCount = count($images);
                            $gridClass = 'grid-1';
                            if ($imageCount == 2) $gridClass = 'grid-2';
                            elseif ($imageCount == 3) $gridClass = 'grid-3';
                            elseif ($imageCount == 4) $gridClass = 'grid-4';
                            elseif ($imageCount > 4) $gridClass = 'grid-many';
                            ?>
                            
                            <div class="image-grid <?= $gridClass ?> max-h-96 lg:max-h-[32rem]" 
                                 x-data="{ 
                                     currentImage: 0, 
                                     showModal: false,
                                     images: <?= htmlspecialchars(json_encode($images)) ?>
                                 }">
                                
                                <?php foreach ($images as $index => $image): ?>
                                    <div class="image-item relative cursor-pointer group overflow-hidden <?= $index >= 9 ? 'hidden' : '' ?>"
                                         @click="currentImage = <?= $index ?>; showModal = true">
                                        <img src="<?= htmlspecialchars($image['url']) ?>" 
                                             alt="<?= htmlspecialchars($image['name'] ?? '') ?>"
                                             class="w-full h-full object-cover transition-transform group-hover:scale-105">
                                        
                                        <?php if ($imageCount > 9 && $index === 8): ?>
                                            <div class="absolute inset-0 bg-black/60 flex items-center justify-center text-white font-bold text-2xl">
                                                +<?= $imageCount - 9 ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                                            <i class="fas fa-search-plus text-white opacity-0 group-hover:opacity-100 transition-opacity text-2xl"></i>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <!-- 图片浏览模态框 -->
                                <div x-show="showModal" 
                                     x-transition:enter="transition ease-out duration-300"
                                     x-transition:enter-start="opacity-0"
                                     x-transition:enter-end="opacity-100"
                                     x-transition:leave="transition ease-in duration-200"
                                     x-transition:leave-start="opacity-100"
                                     x-transition:leave-end="opacity-0"
                                     class="lightbox-overlay"
                                     @click="showModal = false"
                                     @keydown.escape.window="showModal = false">
                                    
                                    <div class="lightbox-container" @click.stop>
                                        <img :src="images[currentImage].url" 
                                             :alt="images[currentImage].name"
                                             class="lightbox-image">
                                        
                                        <!-- 关闭按钮 -->
                                        <button @click="showModal = false"
                                                class="lightbox-btn lightbox-close">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        
                                        <!-- 左右导航 -->
                                        <button x-show="images.length > 1 && currentImage > 0"
                                                @click="currentImage--"
                                                class="lightbox-btn lightbox-prev">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                        
                                        <button x-show="images.length > 1 && currentImage < images.length - 1"
                                                @click="currentImage++"
                                                class="lightbox-btn lightbox-next">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                        
                                        <!-- 图片计数 -->
                                        <div x-show="images.length > 1"
                                             class="lightbox-counter">
                                            <span x-text="currentImage + 1"></span> / <span x-text="images.length"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- 图片配文 -->
                    <?php if (!empty($post['content']) && trim(strip_tags($post['content']))): ?>
                        <div class="glass rounded-2xl p-8 mb-8">
                            <div class="text-post text-gray-800 dark:text-gray-200">
                                <?= nl2br(htmlspecialchars($post['content'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                <?php elseif ($post['post_type'] === 'text'): ?>
                    <!-- 纯文字类型：突出文字内容 -->
                    <div class="glass rounded-2xl p-8 lg:p-12 mb-8">
                        <div class="text-post text-gray-800 dark:text-gray-200 text-center">
                            <?= nl2br(htmlspecialchars($post['content'])) ?>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- 图文并茂类型：标准文章布局 -->
                    
                    <!-- 封面图片 -->
                    <?php if (!empty($post['cover_image'])): ?>
                        <div class="mb-8">
                            <img src="<?= htmlspecialchars($post['cover_image']) ?>" 
                                 alt="<?= htmlspecialchars($post['title']) ?>"
                                 class="w-full h-64 lg:h-96 object-cover rounded-2xl shadow-xl">
                        </div>
                    <?php endif; ?>
                    
                    <!-- 文章摘要 -->
                    <?php if (!empty($post['excerpt'])): ?>
                        <div class="glass rounded-2xl p-6 mb-8 border-l-4 border-primary-500">
                            <div class="flex items-start space-x-3">
                                <i class="fas fa-quote-left text-primary-500 text-xl mt-1"></i>
                                <p class="text-lg text-gray-700 dark:text-gray-300 italic leading-relaxed">
                                    <?= htmlspecialchars($post['excerpt']) ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- 文章正文 -->
                    <div class="glass rounded-2xl p-8 lg:p-12 mb-8">
                        <div class="article-content prose prose-lg max-w-none">
                            <?= $post['content'] ?>
                        </div>
                            </div>
                        <?php endif; ?>
                
            </div>
            
            <!-- 文章底部信息 -->
            <footer class="glass rounded-2xl p-6 mb-8 animate-fade-in-up" style="animation-delay: 0.3s;">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-6 text-sm text-gray-500 dark:text-gray-400">
                        <span class="flex items-center space-x-2">
                            <i class="fas fa-heart text-red-500"></i>
                            <span>感谢阅读</span>
                        </span>
                        
                        <span class="flex items-center space-x-2">
                            <i class="fas fa-share-alt"></i>
                            <span>分享给朋友</span>
                        </span>
                    </div>

                    <div class="flex items-center space-x-4">
                        <button onclick="toggleBookmark()" 
                                class="flex items-center space-x-2 px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors">
                            <i class="fas fa-bookmark"></i>
                            <span>收藏文章</span>
                        </button>
                        
                        <button onclick="window.scrollTo({top: 0, behavior: 'smooth'})"
                                class="flex items-center space-x-2 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors">
                            <i class="fas fa-arrow-up"></i>
                            <span>回到顶部</span>
                            </button>
                    </div>
                </div>
            </footer>

                </article>
            </div>
        </div>

        <!-- 浮动操作栏 -->
        <div id="floating-toolbar" class="fixed bottom-6 right-6 flex flex-col space-y-3 z-40 opacity-0 transition-all duration-300">
            <button onclick="window.scrollTo({top: 0, behavior: 'smooth'})" 
                    class="w-12 h-12 bg-primary-500 text-white rounded-full shadow-lg hover:bg-primary-600 transition-all hover:scale-110 flex items-center justify-center">
                <i class="fas fa-chevron-up"></i>
            </button>
            
            <button onclick="toggleBookmark()" 
                    class="w-12 h-12 bg-yellow-500 text-white rounded-full shadow-lg hover:bg-yellow-600 transition-all hover:scale-110 flex items-center justify-center">
                <i class="fas fa-bookmark"></i>
            </button>
            
                <button onclick="copyLink()" 
                    class="w-12 h-12 bg-gray-500 text-white rounded-full shadow-lg hover:bg-gray-600 transition-all hover:scale-110 flex items-center justify-center">
                <i class="fas fa-share-alt"></i>
                </button>
        </div>

        <!-- 相关文章推荐 -->
        <?php if (!empty($relatedPosts)): ?>
            <div class="px-6 py-8">
                <div class="max-w-7xl mx-auto bg-gradient-to-br from-gray-50 via-white to-blue-50 dark:from-gray-800 dark:via-gray-700 dark:to-gray-800 rounded-3xl p-6 lg:p-8 shadow-sm">
                    <section class="animate-fade-in-up" style="animation-delay: 0.4s;">
                        <div class="glass rounded-2xl p-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                        <i class="fas fa-heart text-red-500 mr-2"></i>
                        更多<?= $typeInfo['label'] ?>分享
                    </h2>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
                        <?php foreach ($relatedPosts as $relatedPost): ?>
                            <?php $relatedTypeInfo = getPostTypeLabel($relatedPost['post_type']); ?>
                            <a href="<?= getPostUrl($relatedPost) ?>" 
                               class="group block glass rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-1">
                                
                                <?php if (!empty($relatedPost['cover_image'])): ?>
                                    <div class="aspect-video overflow-hidden">
                                        <img src="<?= htmlspecialchars($relatedPost['cover_image']) ?>" 
                                             alt="<?= htmlspecialchars($relatedPost['title']) ?>"
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                    </div>
                                <?php else: ?>
                                    <div class="aspect-video flex items-center justify-center
                                        <?php if ($relatedTypeInfo['color'] === 'green'): ?>
                                            bg-gradient-to-br from-green-400 to-green-600
                                        <?php elseif ($relatedTypeInfo['color'] === 'blue'): ?>
                                            bg-gradient-to-br from-blue-400 to-blue-600
                                        <?php elseif ($relatedTypeInfo['color'] === 'purple'): ?>
                                            bg-gradient-to-br from-purple-400 to-purple-600
                                        <?php else: ?>
                                            bg-gradient-to-br from-gray-400 to-gray-600
                                        <?php endif; ?>">
                                        <i class="<?= $relatedTypeInfo['icon'] ?> text-4xl text-white/80"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="p-4">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <span class="inline-flex items-center space-x-1 px-2 py-1 rounded-full text-xs font-medium
                                            <?php if ($relatedTypeInfo['color'] === 'green'): ?>
                                                bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300
                                            <?php elseif ($relatedTypeInfo['color'] === 'blue'): ?>
                                                bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300
                                            <?php elseif ($relatedTypeInfo['color'] === 'purple'): ?>
                                                bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300
                                            <?php else: ?>
                                                bg-gray-100 dark:bg-gray-900/30 text-gray-700 dark:text-gray-300
                                            <?php endif; ?>">
                                            <i class="<?= $relatedTypeInfo['icon'] ?>"></i>
                                            <span><?= $relatedTypeInfo['label'] ?></span>
                                        </span>
            </div>
            
                                    <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors line-clamp-2 mb-2">
                                        <?= htmlspecialchars($relatedPost['title']) ?>
                                    </h3>
                                    
                                    <?php if (!empty($relatedPost['excerpt'])): ?>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                            <?= htmlspecialchars($relatedPost['excerpt']) ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="flex items-center space-x-4 mt-3 text-xs text-gray-500 dark:text-gray-400">
                                        <span><?= formatFriendlyDate($relatedPost['created_at']) ?></span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                                    </div>
                    </section>
                </div>
            </div>
        <?php endif; ?>

    <!-- 页脚 -->
        <footer class="glass mt-16 border-t border-gray-200 dark:border-gray-700">
            <div class="max-w-6xl mx-auto px-6 py-12">
                <div class="text-center">
                    <div class="flex items-center justify-center space-x-2 text-xl font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-heart text-red-500"></i>
                        <span>好朋友博客</span>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        记录与好朋友的美好时光 • 分享生活的点点滴滴
                </p>
                    <div class="flex items-center justify-center space-x-6 text-sm text-gray-500 dark:text-gray-400">
                        <a href="index.php" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">返回首页</a>
                        <span>•</span>
                        <span>用 ❤️ 制作</span>
                    </div>
            </div>
        </div>
    </footer>
        
    </main>

    <script>
        // 删除文章确认
        function deletePost(postId) {
            if (confirm('确定要删除这篇文章吗？此操作不可撤销。')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'api/delete_post.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'post_id';
                input.value = postId;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // 阅读进度和时间计算
        function updateReadingProgress() {
            const article = document.querySelector('.article-content');
            if (!article) return;
            
            const scrollTop = window.pageYOffset;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const scrollPercent = (scrollTop / docHeight) * 100;
            
            document.getElementById('reading-progress').style.width = scrollPercent + '%';
            
            // 显示/隐藏浮动工具栏
            const toolbar = document.getElementById('floating-toolbar');
            if (scrollTop > 300) {
                toolbar.style.opacity = '1';
                toolbar.style.transform = 'translateY(0)';
                    } else {
                toolbar.style.opacity = '0';
                toolbar.style.transform = 'translateY(20px)';
            }
        }
        
        // 计算阅读时间
        function calculateReadingTime() {
            const article = document.querySelector('.article-content');
            if (!article) return;
            
            const text = article.textContent || article.innerText;
            const wordsPerMinute = 200; // 中文阅读速度约每分钟200字
            const words = text.length;
            const minutes = Math.ceil(words / wordsPerMinute);
            
            document.getElementById('reading-time').textContent = `约${minutes}分钟阅读`;
        }
        
        // 分享功能
        function shareToWeChat() {
            // 由于微信API限制，这里复制链接到剪贴板
            copyLink();
            alert('链接已复制，请在微信中粘贴分享');
        }
        
        function shareToWeibo() {
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);
            window.open(`https://service.weibo.com/share/share.php?url=${url}&title=${title}`, '_blank');
        }

        function copyLink() {
            if (navigator.clipboard) {
            navigator.clipboard.writeText(window.location.href).then(() => {
                    showToast('链接已复制到剪贴板');
                });
            } else {
                // 兼容旧浏览器
                const textArea = document.createElement('textarea');
                textArea.value = window.location.href;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showToast('链接已复制到剪贴板');
            }
        }
        
        // 收藏功能
        function toggleBookmark() {
            const postId = <?= json_encode($post['id']) ?>;
            const bookmarks = JSON.parse(localStorage.getItem('bookmarks') || '[]');
            
            if (bookmarks.includes(postId)) {
                const index = bookmarks.indexOf(postId);
                bookmarks.splice(index, 1);
                showToast('已取消收藏');
            } else {
                bookmarks.push(postId);
                showToast('已添加到收藏');
            }
            
            localStorage.setItem('bookmarks', JSON.stringify(bookmarks));
        }
        
        // 消息提示
        function showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'fixed top-20 left-1/2 transform -translate-x-1/2 bg-black text-white px-4 py-2 rounded-lg z-50 opacity-0 transition-opacity duration-300';
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => toast.style.opacity = '1', 100);
                setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => document.body.removeChild(toast), 300);
                }, 2000);
        }
        
        // 主题初始化
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
            
            // 初始化功能
            calculateReadingTime();
            updateReadingProgress();
            
            // 滚动事件监听
            window.addEventListener('scroll', updateReadingProgress);
            
            // 键盘导航支持
            document.addEventListener('keydown', function(e) {
                // ESC 键关闭模态框在 Alpine.js 中已处理
                
                // 左右箭头键切换图片（如果模态框打开）
                const modal = document.querySelector('[x-show="showModal"]');
                if (modal && modal.__x && modal.__x.$data.showModal) {
                    if (e.key === 'ArrowLeft' && modal.__x.$data.currentImage > 0) {
                        modal.__x.$data.currentImage--;
                        e.preventDefault();
                    } else if (e.key === 'ArrowRight' && modal.__x.$data.currentImage < modal.__x.$data.images.length - 1) {
                        modal.__x.$data.currentImage++;
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
    
</body>
</html> 