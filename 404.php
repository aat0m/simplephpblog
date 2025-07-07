<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>页面未找到 - 好朋友博客</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
    <style>
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
    <!-- 导航栏 -->
    <nav class="glass border-b border-white/20 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <a href="index.php" class="flex items-center space-x-2 text-xl font-bold text-gray-900 hover:text-primary-600 transition-colors">
                    <i class="fas fa-heart text-red-500"></i>
                    <span>好朋友博客</span>
                </a>

                <!-- 导航菜单 -->
                <div class="flex items-center space-x-6">
                    <a href="index.php" class="flex items-center space-x-1 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-home"></i>
                        <span>首页</span>
                    </a>
                    
                    <a href="create.php" class="flex items-center space-x-1 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-pen"></i>
                        <span>写文章</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="px-4 sm:px-6 lg:px-8 py-16">
        <div class="max-w-4xl mx-auto bg-gradient-to-br from-gray-50 via-white to-blue-50 dark:from-gray-800 dark:via-gray-700 dark:to-gray-800 rounded-3xl p-6 lg:p-8 shadow-sm">
        <div class="text-center">
            <!-- 404图标 -->
            <div class="w-32 h-32 mx-auto mb-8 bg-gradient-to-br from-red-100 to-orange-100 rounded-full flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-5xl text-orange-500"></i>
            </div>

            <!-- 错误信息 -->
            <h1 class="text-6xl font-bold text-gray-900 mb-4">404</h1>
            <h2 class="text-2xl font-semibold text-gray-700 mb-6">页面未找到</h2>
            <p class="text-lg text-gray-600 mb-8 max-w-md mx-auto">
                抱歉，您访问的页面不存在或已被移动。让我们一起回到记录美好时光的地方吧！
            </p>

            <!-- 操作按钮 -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <a href="index.php" 
                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-primary-500 to-purple-500 text-white rounded-xl hover:from-primary-600 hover:to-purple-600 transition-all transform hover:scale-105 shadow-lg">
                    <i class="fas fa-home mr-2"></i>
                    返回首页
                </a>
                
                <a href="create.php" 
                   class="inline-flex items-center px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <i class="fas fa-pen mr-2"></i>
                    写新文章
                </a>
            </div>

            <!-- 友好提示 -->
            <div class="mt-12 p-6 glass rounded-2xl border border-white/20 max-w-md mx-auto">
                <div class="flex items-center justify-center mb-4">
                    <i class="fas fa-lightbulb text-yellow-500 text-2xl"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">小贴士</h3>
                <p class="text-sm text-gray-600">
                    如果您是通过链接访问的，请检查链接是否正确。如果问题持续存在，请联系管理员。
                </p>
            </div>
        </div>
        </div>
    </main>

    <!-- 页脚 -->
    <footer class="glass border-t border-white/20 mt-16">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center text-gray-500">
                <p class="flex items-center justify-center space-x-2">
                    <span>© 2024 好朋友博客</span>
                    <span>•</span>
                    <span>记录美好时光</span>
                    <i class="fas fa-heart text-red-500"></i>
                </p>
            </div>
        </div>
    </footer>

    <script>
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