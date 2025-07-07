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

// 获取当前用户信息
$current_user = isLoggedIn() ? getCurrentUser() : null;

// 处理各种操作的消息
$message = '';
$error = '';

if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'logout_success':
    $message = '已成功登出';
            break;
        case 'delete_success':
            $message = '文章删除成功';
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_id':
            $error = '无效的文章ID';
            break;
        case 'post_not_found':
            $error = '文章不存在';
            break;
        case 'delete_failed':
            $error = '删除文章失败，请重试';
            break;
    }
}

// 获取筛选参数
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';
$year = isset($_GET['year']) ? (int)$_GET['year'] : 0;
$month = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$day = isset($_GET['day']) ? (int)$_GET['day'] : 0;

$limit = 12;
$offset = ($page - 1) * $limit;

// 根据参数获取文章
if (!empty($search)) {
    $posts = searchPosts($search, $limit, $offset);
    $totalPosts = getSearchResultsCount($search);
    $pageTitle = "搜索结果：$search";
} elseif (!empty($type)) {
    $posts = getPostsByType($type, $limit, $offset, true);
    $totalPosts = getPostsCountByType($type);
    $typeLabels = ['article' => '图文并茂', 'image' => '图片分享', 'text' => '文字分享'];
    $pageTitle = $typeLabels[$type] ?? '文章分类';
} elseif ($year && $month && $day) {
    $posts = getPostsByDay($year, $month, $day, $limit, $offset);
    $totalPosts = count(getPostsByDay($year, $month, $day, 1000, 0)); // 获取总数
    $pageTitle = $year . '年' . $month . '月' . $day . '日的文章';
} elseif ($year && $month) {
    $posts = getPostsByMonth($year, $month, $limit, $offset);
    $totalPosts = count($posts); // 简化实现
    $pageTitle = $year . '年' . $month . '月的文章';
} else {
$posts = getPosts($limit, $offset);
$totalPosts = getTotalPosts();
    $pageTitle = '好朋友博客';
}

$totalPages = ceil($totalPosts / $limit);

// 获取精选文章用于轮播图（仅在首页显示）
$featuredPosts = [];
if (empty($search) && empty($type) && !($year && $month) && !($year && $month && $day)) {
    $featuredPosts = getFeaturedPosts(4);
}
?>

<!DOCTYPE html>
<html lang="zh-CN" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> ❤️</title>
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
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        .dark ::-webkit-scrollbar-track {
            background: #374151;
        }
        .dark ::-webkit-scrollbar-thumb {
            background: #6b7280;
        }
        .dark ::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }
        
        /* 卡片悬停效果 */
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .dark .card-hover:hover {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        
        /* 瀑布流布局 - 优化宽屏显示，更宽的卡片 */
        .masonry-grid {
            column-count: 4; /* 超宽屏4列，卡片更宽 */
            column-gap: 2rem;
            column-fill: balance;
        }
        
        @media (max-width: 1920px) {
            .masonry-grid {
                column-count: 3; /* 大屏幕3列 */
                column-gap: 1.5rem;
            }
        }
        
        @media (max-width: 1536px) {
            .masonry-grid {
                column-count: 3; /* 中大屏幕保持3列 */
                column-gap: 1.5rem;
            }
        }
        
        @media (max-width: 1200px) {
            .masonry-grid {
                column-count: 2; /* 中等屏幕2列 */
                column-gap: 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .masonry-grid {
                column-count: 1; /* 手机1列 */
                column-gap: 0;
            }
        }
        
        .masonry-item {
            break-inside: avoid;
            margin-bottom: 2rem;
            page-break-inside: avoid;
        }
        
        @media (max-width: 768px) {
            .masonry-item {
                margin-bottom: 1rem;
            }
        }
        
        /* 文字截断 */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .line-clamp-4 {
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .line-clamp-6 {
            display: -webkit-box;
            -webkit-line-clamp: 6;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .line-clamp-4 {
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* 背景渐变 */
        .bg-gradient-main {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .dark .bg-gradient-main {
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
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
        

    </style>
</head>

<body class="min-h-full bg-white dark:bg-gray-900 font-sans transition-colors duration-300">
    
    <!-- 顶部导航栏 -->
    <?php include 'includes/header.php'; ?>
    
    <!-- 主内容区域 -->
    <main class="min-h-screen">
        
        <!-- Hero区域 -->
        <?php if (!empty($featuredPosts)): ?>
            <!-- 精选文章轮播图 -->
            <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-purple-600 to-pink-600" 
                 x-data="{ 
                     currentSlide: 0, 
                     slides: <?= count($featuredPosts) ?>,
                     autoPlay: true,
                     interval: null,
                     startAutoPlay() {
                         if (this.autoPlay) {
                             this.interval = setInterval(() => {
                                 this.currentSlide = (this.currentSlide + 1) % this.slides;
                             }, 5000);
                         }
                     },
                     stopAutoPlay() {
                         if (this.interval) clearInterval(this.interval);
                     }
                 }"
                 x-init="startAutoPlay()"
                 @mouseenter="stopAutoPlay()"
                 @mouseleave="startAutoPlay()">
                
                <!-- 轮播内容 -->
                <div class="relative h-96 lg:h-[500px]">
                    <?php foreach ($featuredPosts as $index => $featured): ?>
                        <?php 
                        $featuredImages = !empty($featured['images']) ? json_decode($featured['images'], true) : [];
                        $featuredTypeInfo = getPostTypeLabel($featured['post_type']);
                        $backgroundImage = '';
                        
                        if (!empty($featured['cover_image'])) {
                            $backgroundImage = $featured['cover_image'];
                        } elseif (!empty($featuredImages)) {
                            $backgroundImage = $featuredImages[0]['url'];
                        }
                        ?>
                        
                        <div class="absolute inset-0 transition-opacity duration-1000"
                             x-show="currentSlide === <?= $index ?>"
                             x-transition:enter="transition ease-out duration-1000"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-500"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0">
                            
                            <!-- 背景图片 -->
                            <?php if ($backgroundImage): ?>
                                <div class="absolute inset-0 bg-cover bg-center bg-no-repeat"
                                     style="background-image: url('<?= htmlspecialchars($backgroundImage) ?>')"></div>
                                <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-black/40 to-black/60"></div>
                            <?php else: ?>
                                <!-- 渐变背景 -->
                                <div class="absolute inset-0 bg-gradient-to-br 
                                    <?php if ($featuredTypeInfo['color'] === 'green'): ?>
                                        from-green-500 to-emerald-700
                                    <?php elseif ($featuredTypeInfo['color'] === 'blue'): ?>
                                        from-blue-500 to-indigo-700
                                    <?php elseif ($featuredTypeInfo['color'] === 'purple'): ?>
                                        from-purple-500 to-pink-700
                                    <?php else: ?>
                                        from-gray-500 to-slate-700
                                    <?php endif; ?>"></div>
                                <div class="absolute inset-0 bg-black/30"></div>
                            <?php endif; ?>
                            
                            <!-- 内容 -->
                            <div class="relative z-10 h-full flex items-center">
                                <div class="max-w-6xl mx-auto px-6 w-full">
                                    <div class="max-w-3xl">
                                        <!-- 文章类型标签 -->
                                        <div class="inline-flex items-center space-x-2 bg-white/20 backdrop-blur-sm rounded-full px-4 py-2 mb-4">
                                            <i class="<?= $featuredTypeInfo['icon'] ?> text-white"></i>
                                            <span class="text-white font-medium text-sm"><?= $featuredTypeInfo['label'] ?></span>
                                        </div>
                                        
                                        <!-- 文章标题 -->
                                        <h2 class="text-3xl lg:text-5xl font-bold text-white mb-4 leading-tight">
                                            <?php if (!empty($featured['title'])): ?>
                                                <?= htmlspecialchars($featured['title']) ?>
                                            <?php else: ?>
                                                <?php if ($featured['post_type'] === 'image'): ?>
                                                    美好瞬间 • <?= count($featuredImages) ?> 张图片
                                                <?php else: ?>
                                                    生活随记
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </h2>
                                        
                                        <!-- 文章摘要 -->
                                        <?php if (!empty($featured['excerpt'])): ?>
                                            <p class="text-lg lg:text-xl text-white/90 mb-6 line-clamp-2">
                                                <?= htmlspecialchars($featured['excerpt']) ?>
                                            </p>
                                        <?php elseif ($featured['post_type'] === 'text' && !empty($featured['content'])): ?>
                                            <p class="text-lg lg:text-xl text-white/90 mb-6 line-clamp-2">
                                                <?= htmlspecialchars(mb_substr($featured['content'], 0, 100)) ?>...
                                            </p>
                                        <?php endif; ?>
                                        
                                        <!-- 元信息和按钮 -->
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                            <div class="flex items-center space-x-4 text-white/80">
                                                <span class="flex items-center space-x-1">
                                                    <i class="fas fa-calendar-alt"></i>
                                                    <span><?= formatFriendlyDate($featured['created_at']) ?></span>
                                                </span>
                                                <?php if ($featured['post_type'] === 'image' && !empty($featuredImages)): ?>
                                                    <span class="flex items-center space-x-1">
                                                        <i class="fas fa-images"></i>
                                                        <span><?= count($featuredImages) ?> 张图片</span>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <a href="<?= getPostUrl($featured) ?>" 
                                               class="inline-flex items-center space-x-2 bg-white text-gray-900 px-6 py-3 rounded-full font-semibold hover:bg-gray-100 transition-all transform hover:scale-105 shadow-lg">
                                                <span>阅读全文</span>
                                                <i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- 导航点 -->
                <div class="absolute bottom-6 left-1/2 transform -translate-x-1/2 z-20">
                    <div class="flex items-center space-x-3">
                        <?php for ($i = 0; $i < count($featuredPosts); $i++): ?>
                            <button @click="currentSlide = <?= $i ?>; stopAutoPlay(); startAutoPlay();"
                                    class="w-3 h-3 rounded-full transition-all duration-300"
                                    :class="currentSlide === <?= $i ?> ? 'bg-white scale-125' : 'bg-white/50 hover:bg-white/75'">
                            </button>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <!-- 导航箭头 -->
                <button @click="currentSlide = currentSlide === 0 ? slides - 1 : currentSlide - 1; stopAutoPlay(); startAutoPlay();"
                        class="absolute left-2 lg:left-4 top-1/2 transform -translate-y-1/2 z-20 p-2 lg:p-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-full transition-all text-white">
                    <i class="fas fa-chevron-left text-sm lg:text-base"></i>
                </button>
                
                <button @click="currentSlide = (currentSlide + 1) % slides; stopAutoPlay(); startAutoPlay();"
                        class="absolute right-2 lg:right-4 top-1/2 transform -translate-y-1/2 z-20 p-2 lg:p-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-full transition-all text-white">
                    <i class="fas fa-chevron-right text-sm lg:text-base"></i>
                </button>
                        
                <!-- 快捷创建按钮 -->
                <?php if ($current_user): ?>
                    <div class="absolute top-4 lg:top-6 right-4 lg:right-6 z-20">
                        <a href="create.php" 
                           class="inline-flex items-center space-x-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white px-3 lg:px-4 py-2 rounded-full font-medium transition-all text-sm lg:text-base">
                            <i class="fas fa-pen text-xs lg:text-sm"></i>
                            <span class="hidden sm:inline">写文章</span>
                            <span class="sm:hidden">写</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
        <?php else: ?>
            <!-- 简单横幅（无精选文章时） -->
            <div class="bg-gradient-main text-white relative overflow-hidden">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative px-6 py-8 lg:py-12">
                    <div class="max-w-4xl mx-auto text-center">
                        <h1 class="text-2xl lg:text-4xl font-bold mb-4 lg:mb-6 animate-fade-in-up">
                            <?php if (!empty($search)): ?>
                                搜索结果
                            <?php elseif (!empty($type)): ?>
                                <?= htmlspecialchars($pageTitle) ?>
                            <?php elseif ($year && $month && $day): ?>
                                <?= $pageTitle ?>
                            <?php elseif ($year && $month): ?>
                                <?= $pageTitle ?>
                            <?php else: ?>
                                记录美好时光 ✨
                            <?php endif; ?>
                        </h1>
                        
                        <?php if (empty($search) && empty($type) && !($year && $month) && !($year && $month && $day)): ?>
                            <p class="text-base lg:text-xl text-white/90 mb-6 lg:mb-8 animate-fade-in-up" style="animation-delay: 0.2s;">
                                与好朋友分享生活的点点滴滴
                            </p>
                        <?php else: ?>
                            <p class="text-sm lg:text-base text-white/90 mb-6 lg:mb-8">
                                <?php if (!empty($search)): ?>
                                    找到 <span class="font-semibold"><?= $totalPosts ?></span> 篇相关文章
                                <?php else: ?>
                                    共 <span class="font-semibold"><?= $totalPosts ?></span> 篇文章
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if ($current_user): ?>
                            <a href="create.php" 
                               class="inline-flex items-center space-x-2 bg-white text-primary-600 px-8 py-4 rounded-full font-semibold hover:bg-gray-50 transition-all transform hover:scale-105 shadow-lg animate-fade-in-up" 
                               style="animation-delay: 0.4s;">
                                <i class="fas fa-pen"></i>
                                <span>记录新的回忆</span>
                            </a>
                        <?php endif; ?>
                        </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 消息提示 -->
            <?php if ($message): ?>
            <div class="mx-6 mt-6">
                <div class="max-w-4xl mx-auto p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        <span class="text-green-800 dark:text-green-200"><?= htmlspecialchars($message) ?></span>
                    </div>
                    </div>
                </div>
            <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="mx-6 mt-6">
                <div class="max-w-4xl mx-auto p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                        <span class="text-red-800 dark:text-red-200"><?= htmlspecialchars($error) ?></span>
        </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- 文章列表和侧边栏 -->
        <div class="px-6 py-6">
            <!-- 主内容区域背景 -->
            <div class="max-w-full mx-auto bg-gradient-to-br from-gray-50 via-white to-blue-50 dark:from-gray-800 dark:via-gray-700 dark:to-gray-800 rounded-3xl p-6 lg:p-8 shadow-sm">
                <div class="flex flex-col xl:flex-row gap-8 justify-center">
                    
                    <!-- 主要内容区域 -->
                    <div class="xl:w-4/5">
                
                <?php if (empty($posts)): ?>
                    <!-- 空状态 -->
                    <div class="text-center py-8 lg:py-16">
                        <div class="mx-auto w-16 lg:w-24 h-16 lg:h-24 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-6 lg:mb-8">
                            <i class="fas fa-inbox text-2xl lg:text-4xl text-gray-400"></i>
                        </div>
                        <h3 class="text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white mb-3 lg:mb-4">
                            <?php if (!empty($search)): ?>
                                没有找到相关文章
                            <?php else: ?>
                                还没有文章
                            <?php endif; ?>
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-8">
                            <?php if (!empty($search)): ?>
                                试试其他关键词，或者浏览所有文章
                            <?php else: ?>
                                开始记录与好朋友的美好时光吧
                            <?php endif; ?>
                        </p>
                        
                        <?php if ($current_user): ?>
                            <a href="create.php" 
                               class="inline-flex items-center space-x-2 bg-primary-500 text-white px-6 py-3 rounded-lg hover:bg-primary-600 transition-colors">
                                <i class="fas fa-pen"></i>
                                <span>写第一篇文章</span>
                            </a>
                        <?php else: ?>
                            <a href="index.php" 
                               class="inline-flex items-center space-x-2 bg-primary-500 text-white px-6 py-3 rounded-lg hover:bg-primary-600 transition-colors">
                                <i class="fas fa-home"></i>
                                <span>返回首页</span>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                <?php else: ?>
                    <!-- 瀑布流文章卡片 -->
                    <div class="masonry-grid">
                        <?php foreach ($posts as $index => $post): ?>
                            <?php 
                            $typeInfo = getPostTypeLabel($post['post_type']);
                            $images = !empty($post['images']) ? json_decode($post['images'], true) : [];
                            ?>
                            
                            <article class="masonry-item animate-fade-in-up" style="animation-delay: <?= ($index * 0.1) ?>s;">
                                <a href="<?= getPostUrl($post) ?>" class="block glass rounded-2xl overflow-hidden card-hover group hover:no-underline">
                                    
                                    <!-- 文章类型标签 -->
                                    <div class="absolute top-4 left-4 z-10">
                                        <span class="inline-flex items-center space-x-1 text-white px-3 py-1 rounded-full text-sm font-medium shadow-lg
                                            <?php if ($typeInfo['color'] === 'green'): ?>
                                                bg-green-500
                                            <?php elseif ($typeInfo['color'] === 'blue'): ?>
                                                bg-blue-500
                                            <?php elseif ($typeInfo['color'] === 'purple'): ?>
                                                bg-purple-500
                                            <?php else: ?>
                                                bg-gray-500
                                            <?php endif; ?>">
                                            <i class="<?= $typeInfo['icon'] ?> text-xs"></i>
                                            <span><?= $typeInfo['label'] ?></span>
                                        </span>
                                    </div>
                                    
                                    <?php if ($post['post_type'] === 'text'): ?>
                                        <!-- 纯文本类型：简洁的图标展示 -->
                                        <div class="relative h-56 flex items-center justify-center
                                            <?php if ($typeInfo['color'] === 'green'): ?>
                                                bg-gradient-to-br from-green-400 to-green-600
                                            <?php elseif ($typeInfo['color'] === 'blue'): ?>
                                                bg-gradient-to-br from-blue-400 to-blue-600
                                            <?php elseif ($typeInfo['color'] === 'purple'): ?>
                                                bg-gradient-to-br from-purple-400 to-purple-600
                                            <?php else: ?>
                                                bg-gradient-to-br from-gray-400 to-gray-600
                                            <?php endif; ?>">
                                            <i class="<?= $typeInfo['icon'] ?> text-5xl text-white/80"></i>
                                            <div class="absolute inset-0 bg-black/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                        </div>
                                        
                                        <!-- 文章内容 -->
                                        <div class="p-8">
                                            <?php if (!empty($post['title'])): ?>
                                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors line-clamp-2">
                                                    <?= htmlspecialchars($post['title']) ?>
                                                </h2>
                                            <?php endif; ?>
                                            
                                            <!-- 内容预览 -->
                                            <div class="text-gray-600 dark:text-gray-300 line-clamp-6 text-base leading-relaxed mb-4">
                                                <?= nl2br(htmlspecialchars(mb_substr($post['content'], 0, 280))) ?>
                                                <?php if (mb_strlen($post['content']) > 280): ?>...<?php endif; ?>
                                            </div>
                                            
                                            <!-- 文章元信息 -->
                                            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                                                <div class="flex items-center space-x-4">
                                                    <span class="flex items-center space-x-1">
                                                        <i class="fas fa-calendar-alt"></i>
                                                        <span><?= formatFriendlyDate($post['created_at']) ?></span>
                                                    </span>
                                                    <span class="flex items-center space-x-1">
                                                        <i class="fas fa-font"></i>
                                                        <span><?= mb_strlen($post['content']) ?> 字</span>
                                                    </span>
                                                </div>
                                                
                                                <?php if ($current_user): ?>
                                                    <div class="flex items-center space-x-2">
                                                        <a href="edit.php?id=<?= $post['id'] ?>" 
                                                           class="text-blue-500 hover:text-blue-700 transition-colors z-10 relative"
                                                           title="编辑"
                                                           onclick="event.stopPropagation(); event.preventDefault(); window.location.href=this.href;">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button onclick="event.stopPropagation(); event.preventDefault(); deletePost(<?= $post['id'] ?>);" 
                                                                class="text-red-500 hover:text-red-700 transition-colors z-10 relative"
                                                                title="删除">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                    <?php elseif ($post['post_type'] === 'image' && !empty($images)): ?>
                                            <!-- 图片类型：显示图片网格 -->
                                            <div class="relative">
                                                <?php if (count($images) === 1): ?>
                                                    <img src="<?= htmlspecialchars($images[0]['url']) ?>" 
                                                         alt="<?= htmlspecialchars($post['title']) ?>"
                                                         class="w-full h-72 object-cover">
        <?php else: ?>
                                                    <div class="grid grid-cols-2 gap-1 h-72">
                                                        <?php for ($i = 0; $i < min(4, count($images)); $i++): ?>
                                                            <div class="<?= $i === 0 && count($images) === 3 ? 'col-span-2' : '' ?>">
                                                                <img src="<?= htmlspecialchars($images[$i]['url']) ?>" 
                                                                     alt="<?= htmlspecialchars($post['title']) ?>"
                                                                     class="w-full h-full object-cover">
                                                                <?php if ($i === 3 && count($images) > 4): ?>
                                                                    <div class="absolute inset-0 bg-black/50 flex items-center justify-center text-white font-semibold text-lg">
                                                                        +<?= count($images) - 4 ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endfor; ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                            </div>
                                            
                                            <!-- 文章内容 -->
                                            <div class="p-8">
                                                <?php if (!empty($post['title'])): ?>
                                                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors line-clamp-2">
                                                        <?= htmlspecialchars($post['title']) ?>
                                                    </h2>
                                                <?php else: ?>
                                                    <!-- 纯图片文章无标题时，显示友好的占位文本 -->
                                                    <h2 class="text-lg font-medium text-gray-600 dark:text-gray-400 mb-3 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                                                        图片分享 • <?= count($images) ?> 张图片
                                                    </h2>
                                                <?php endif; ?>
                                                
                                                <!-- 文章元信息 -->
                                                <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                                                    <div class="flex items-center space-x-4">
                                                        <span class="flex items-center space-x-1">
                                                            <i class="fas fa-calendar-alt"></i>
                                                            <span><?= formatFriendlyDate($post['created_at']) ?></span>
                                                        </span>
                                                        <span class="flex items-center space-x-1">
                                                            <i class="fas fa-images"></i>
                                                            <span><?= count($images) ?> 张图片</span>
                                                        </span>
                                                    </div>
                                                    
                                                    <?php if ($current_user): ?>
                                                        <div class="flex items-center space-x-2">
                                                            <a href="edit.php?id=<?= $post['id'] ?>" 
                                                               class="text-blue-500 hover:text-blue-700 transition-colors z-10 relative"
                                                               title="编辑"
                                                               onclick="event.stopPropagation(); event.preventDefault(); window.location.href=this.href;">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <button onclick="event.stopPropagation(); event.preventDefault(); deletePost(<?= $post['id'] ?>);" 
                                                                    class="text-red-500 hover:text-red-700 transition-colors z-10 relative"
                                                                    title="删除">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                    <?php else: ?>
                                        <!-- 图文类型：有封面图片或显示图标 -->
                        <?php if (!empty($post['cover_image'])): ?>
                                            <!-- 有封面图片 -->
                                            <div class="relative">
                                <img src="<?= htmlspecialchars($post['cover_image']) ?>" 
                                     alt="<?= htmlspecialchars($post['title']) ?>"
                                     class="w-full h-56 object-cover">
                                                <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                            </div>
                                        <?php else: ?>
                                            <!-- 无封面图片：根据类型显示特色背景 -->
                                            <div class="relative h-56 flex items-center justify-center
                                                <?php if ($typeInfo['color'] === 'green'): ?>
                                                    bg-gradient-to-br from-green-400 to-green-600
                                                <?php elseif ($typeInfo['color'] === 'blue'): ?>
                                                    bg-gradient-to-br from-blue-400 to-blue-600
                                                <?php elseif ($typeInfo['color'] === 'purple'): ?>
                                                    bg-gradient-to-br from-purple-400 to-purple-600
                                                <?php else: ?>
                                                    bg-gradient-to-br from-gray-400 to-gray-600
                                                <?php endif; ?>">
                                                <i class="<?= $typeInfo['icon'] ?> text-5xl text-white/80"></i>
                                                <div class="absolute inset-0 bg-black/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            </div>
                        <?php endif; ?>

                                        <!-- 文章内容 -->
                        <div class="p-8">
                                            <?php if (!empty($post['title'])): ?>
                                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors line-clamp-2">
                                    <?= htmlspecialchars($post['title']) ?>
                                                </h2>
                                            <?php else: ?>
                                                <!-- 纯图片文章无标题时，显示友好的占位文本 -->
                                                <h2 class="text-lg font-medium text-gray-600 dark:text-gray-400 mb-3 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                                                    <?php if ($post['post_type'] === 'image'): ?>
                                                        图片分享 • <?= count($images) ?> 张图片
                                                    <?php else: ?>
                                                        <?= formatFriendlyDate($post['created_at']) ?>
                                                    <?php endif; ?>
                            </h2>
                                            <?php endif; ?>

                                            <!-- 摘要内容 -->
                            <?php if (!empty($post['excerpt'])): ?>
                                                <p class="text-gray-600 dark:text-gray-300 line-clamp-4 mb-4 text-base leading-relaxed">
                                    <?= htmlspecialchars($post['excerpt']) ?>
                                </p>
                                            <?php elseif (!empty($post['content'])): ?>
                                                <!-- 如果没有摘要，显示内容预览 -->
                                                <p class="text-gray-600 dark:text-gray-300 line-clamp-4 mb-4 text-base leading-relaxed">
                                                    <?= htmlspecialchars(mb_substr(strip_tags($post['content']), 0, 200)) ?>
                                                    <?php if (mb_strlen(strip_tags($post['content'])) > 200): ?>...<?php endif; ?>
                                                </p>
                            <?php endif; ?>

                            <!-- 文章元信息 -->
                                            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                                                <div class="flex items-center space-x-4">
                                                    <span class="flex items-center space-x-1">
                                                        <i class="fas fa-calendar-alt"></i>
                                                        <span><?= formatFriendlyDate($post['created_at']) ?></span>
                                                    </span>
                                                    
                                                    <?php if ($post['post_type'] === 'image' && !empty($images)): ?>
                                                        <span class="flex items-center space-x-1">
                                                            <i class="fas fa-images"></i>
                                                            <span><?= count($images) ?> 张图片</span>
                                </span>
                                                    <?php endif; ?>
                            </div>

                                                <?php if ($current_user): ?>
                                    <div class="flex items-center space-x-2">
                                        <a href="edit.php?id=<?= $post['id'] ?>" 
                                                           class="text-blue-500 hover:text-blue-700 transition-colors z-10 relative"
                                                           title="编辑"
                                                           onclick="event.stopPropagation(); event.preventDefault(); window.location.href=this.href;">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                                        <button onclick="event.stopPropagation(); event.preventDefault(); deletePost(<?= $post['id'] ?>);" 
                                                                class="text-red-500 hover:text-red-700 transition-colors z-10 relative"
                                                title="删除">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                                    <?php endif; ?>
                                </a>
                    </article>
                <?php endforeach; ?>
            </div>

                    <!-- 分页导航 -->
            <?php if ($totalPages > 1): ?>
                        <div class="mt-12 flex justify-center">
                            <nav class="flex items-center space-x-2">
                                <?php
                                $baseUrl = '?';
                                $params = [];
                                if (!empty($type)) $params[] = "type=$type";
                                if (!empty($search)) $params[] = "search=" . urlencode($search);
                                if ($year && $month && $day) {
                                    $params[] = "year=$year";
                                    $params[] = "month=$month";
                                    $params[] = "day=$day";
                                } elseif ($year && $month) {
                                    $params[] = "year=$year";
                                    $params[] = "month=$month";
                                }
                                if (!empty($params)) {
                                    $baseUrl .= implode('&', $params) . '&';
                                }
                                ?>
                                
                    <?php if ($page > 1): ?>
                                    <a href="<?= $baseUrl ?>page=<?= $page - 1 ?>" 
                                       class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors border border-gray-200 dark:border-gray-600">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <a href="<?= $baseUrl ?>page=<?= $i ?>" 
                                       class="px-4 py-2 rounded-lg transition-colors border <?= $i === $page 
                                           ? 'bg-primary-500 text-white border-primary-500' 
                                           : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 border-gray-200 dark:border-gray-600' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="<?= $baseUrl ?>page=<?= $page + 1 ?>" 
                                       class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors border border-gray-200 dark:border-gray-600">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                    
                <?php endif; ?>
                    
                    </div>
                    
                    <!-- 侧边栏 -->
                    <?php 
                    try {
                        include 'includes/sidebar.php'; 
                    } catch (Exception $e) {
                        echo '<div class="xl:w-1/5 space-y-6 xl:block hidden">';
                        echo '<div class="glass rounded-2xl p-6 shadow-lg">';
                        echo '<p class="text-gray-500">侧边栏暂时不可用</p>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                    
                </div>
            </div>
        </div>

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
                    <span>© 2024 好朋友博客</span>
                    <span>•</span>
                        <span>共 <?= $totalPosts ?> 篇文章</span>
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
                // 创建表单提交删除请求
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
        
        // 主题初始化
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        });
    </script>
    
</body>
</html> 