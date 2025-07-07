<?php
// 获取当前页面信息
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// 如果还没有定义$current_user，尝试获取当前用户信息
if (!isset($current_user)) {
    require_once __DIR__ . '/functions.php';
    $current_user = isLoggedIn() ? getCurrentUser() : null;
}
?>

<style>
    /* 确保glass效果在所有页面都可用 */
    .glass {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(229, 231, 235, 0.5);
    }
    
    .dark .glass {
        background: rgba(55, 65, 81, 0.95);
        border: 1px solid rgba(75, 85, 99, 0.5);
    }
    
    /* 动画效果 */
    .animate-fadeIn {
        animation: fadeIn 0.5s ease-in-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* 搜索结果高亮 */
    .search-highlight {
        background: linear-gradient(120deg, #fbbf24 0%, #f59e0b 100%);
        color: white;
        padding: 2px 4px;
        border-radius: 4px;
        font-weight: 500;
    }
    
    /* 自定义滚动条 */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 3px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    
    /* 响应式文字截断 */
    .line-clamp-1 {
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<!-- 顶部导航栏 -->
<nav class="glass border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50" 
     x-data="{ 
         mobileMenuOpen: false,
         userMenuOpen: false,
         showQuickSearch: false,
         searchQuery: '',
         searchResults: [],
         searching: false 
     }">
    <div class="max-w-full mx-auto px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">
            
            <!-- Logo区域 -->
            <div class="flex items-center space-x-6">
                <a href="index.php" class="flex items-center space-x-3 text-xl font-bold text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                    <div class="w-10 h-10 bg-gradient-to-br from-red-400 to-red-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-heart text-white text-lg"></i>
                    </div>
                    <div class="hidden lg:block">
                        <div class="text-xl font-bold">好朋友博客</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 -mt-1" id="current-time">记录美好时光</div>
                    </div>
                    <span class="lg:hidden">博客</span>
                </a>
                
                <!-- 快速导航（桌面端） -->
                <div class="hidden xl:flex items-center space-x-1 ml-8">
                    <a href="index.php" 
                       class="flex items-center space-x-2 px-4 py-2 rounded-xl text-sm font-medium transition-all hover:scale-105 <?= $currentPage === 'index' ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700' ?>">
                        <i class="fas fa-home"></i>
                        <span>首页</span>
                    </a>
                    
                    <a href="index.php?type=article" 
                       class="flex items-center space-x-2 px-4 py-2 rounded-xl text-sm font-medium transition-all hover:scale-105 <?= ($_GET['type'] ?? '') === 'article' ? 'bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700' ?>">
                        <i class="fas fa-newspaper text-green-500"></i>
                        <span>图文</span>
                    </a>
                    
                    <a href="index.php?type=image" 
                       class="flex items-center space-x-2 px-4 py-2 rounded-xl text-sm font-medium transition-all hover:scale-105 <?= ($_GET['type'] ?? '') === 'image' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700' ?>">
                        <i class="fas fa-images text-blue-500"></i>
                        <span>图片</span>
                    </a>
                    
                    <a href="index.php?type=text" 
                       class="flex items-center space-x-2 px-4 py-2 rounded-xl text-sm font-medium transition-all hover:scale-105 <?= ($_GET['type'] ?? '') === 'text' ? 'bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700' ?>">
                        <i class="fas fa-quote-left text-purple-500"></i>
                        <span>文字</span>
                    </a>
                </div>
            </div>

            <!-- 中间功能区域 -->
            <div class="hidden lg:flex items-center flex-1 max-w-2xl mx-8">
                <!-- 搜索栏 -->
                <div class="relative w-full">
                    <div class="relative">
                        <input type="text" 
                               x-model="searchQuery"
                               @input.debounce.300ms="performQuickSearch()"
                               @keydown.enter="goToSearch()"
                               @focus="showQuickSearch = true"
                               placeholder="搜索文章、标题或内容... (Ctrl+K)" 
                               class="w-full pl-12 pr-20 py-3 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:text-white dark:placeholder-gray-400 transition-all">
                        <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <div x-show="searching" class="absolute right-16 top-1/2 transform -translate-y-1/2">
                            <i class="fas fa-spinner fa-spin text-gray-400"></i>
                        </div>
                        <button @click="goToSearch()" 
                                class="absolute right-2 top-1/2 transform -translate-y-1/2 px-3 py-1.5 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition-colors text-sm">
                            搜索
                        </button>
                    </div>
                    
                    <!-- 快速搜索结果 -->
                    <div x-show="showQuickSearch && searchResults.length > 0" 
                         @click.away="showQuickSearch = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 transform scale-100"
                         x-transition:leave-end="opacity-0 transform scale-95"
                         class="absolute top-full left-0 right-0 mt-2 glass rounded-xl shadow-xl py-2 z-50 max-h-80 overflow-y-auto custom-scrollbar">
                        <div class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-gray-700">
                            搜索结果
                        </div>
                        <template x-for="result in searchResults.slice(0, 5)" :key="result.id">
                            <a :href="'post.php?id=' + result.id" 
                               @click="showQuickSearch = false"
                               class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors group">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center transition-colors"
                                     :class="{
                                         'bg-green-100 dark:bg-green-900/30': result.post_type === 'article',
                                         'bg-blue-100 dark:bg-blue-900/30': result.post_type === 'image', 
                                         'bg-purple-100 dark:bg-purple-900/30': result.post_type === 'text',
                                         'bg-gray-100 dark:bg-gray-700': !result.post_type
                                     }">
                                    <i :class="{
                                         'fas fa-newspaper text-green-500': result.post_type === 'article',
                                         'fas fa-images text-blue-500': result.post_type === 'image',
                                         'fas fa-quote-left text-purple-500': result.post_type === 'text',
                                         'fas fa-file-alt text-gray-500': !result.post_type
                                     }" class="text-sm"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-gray-900 dark:text-white text-sm line-clamp-1 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors" x-text="result.title"></div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 line-clamp-1" x-text="result.excerpt"></div>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <span class="text-xs px-2 py-0.5 rounded-full text-white font-medium"
                                              :class="{
                                                  'bg-green-500': result.post_type === 'article',
                                                  'bg-blue-500': result.post_type === 'image',
                                                  'bg-purple-500': result.post_type === 'text',
                                                  'bg-gray-500': !result.post_type
                                              }"
                                              x-text="result.type_label || '文章'"></span>
                                        <span class="text-xs text-gray-400" x-text="result.friendly_date || result.created_at"></span>
                                    </div>
                                </div>
                            </a>
                        </template>
                        <div x-show="searchResults.length > 5" class="px-4 py-2 border-t border-gray-100 dark:border-gray-700">
                            <button @click="goToSearch()" class="text-sm text-primary-500 hover:text-primary-600 transition-colors">
                                查看全部 <span x-text="searchResults.length"></span> 个结果 →
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 移动端简化搜索按钮 -->
            <div class="lg:hidden flex-1 flex justify-center items-center space-x-3">
                <button @click="mobileMenuOpen = !mobileMenuOpen" 
                        class="flex items-center space-x-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <i class="fas fa-search text-gray-600 dark:text-gray-300"></i>
                    <span class="text-sm text-gray-600 dark:text-gray-300">搜索文章</span>
                </button>
                <!-- 移动端小提示 -->
                <div class="hidden sm:flex items-center space-x-1 text-xs text-gray-500 dark:text-gray-400">
                    <i class="fas fa-info-circle"></i>
                    <span class="page-stats">加载中...</span>
                </div>
            </div>

            <!-- 右侧功能区 -->
            <div class="flex items-center space-x-2">
                
                <!-- 快捷按钮组 -->
                <div class="hidden lg:flex items-center space-x-2">
                    <!-- 随机文章 -->
                    <button @click="goToRandomPost()" 
                            class="p-2.5 rounded-xl bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all hover:scale-105"
                            title="随机文章">
                        <i class="fas fa-random text-gray-600 dark:text-gray-300"></i>
                    </button>
                    
                    <!-- 收藏列表 -->
                    <button @click="toggleFavorites()" 
                            class="p-2.5 rounded-xl bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all hover:scale-105"
                            title="我的收藏">
                        <i class="fas fa-heart text-gray-600 dark:text-gray-300"></i>
                    </button>
                    
                    <!-- 回到顶部 -->
                    <button @click="scrollToTop()" 
                            class="p-2.5 rounded-xl bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all hover:scale-105"
                            title="回到顶部">
                        <i class="fas fa-arrow-up text-gray-600 dark:text-gray-300"></i>
                    </button>
                </div>
                
                <!-- 主题切换按钮 -->
                <button @click="toggleTheme()" 
                        class="p-2.5 rounded-xl bg-gradient-to-br from-orange-100 to-blue-100 dark:from-gray-700 dark:to-gray-600 hover:from-orange-200 hover:to-blue-200 dark:hover:from-gray-600 dark:hover:to-gray-500 transition-all hover:scale-105 shadow-sm"
                        title="切换主题">
                    <i class="fas fa-moon dark:hidden text-gray-700"></i>
                    <i class="fas fa-sun hidden dark:inline text-yellow-400"></i>
                </button>

                <!-- 写文章按钮（登录用户） -->
                <?php if ($current_user): ?>
                    <a href="create.php" 
                       class="hidden md:flex items-center space-x-2 px-4 py-2.5 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-xl transition-all hover:scale-105 shadow-sm font-medium">
                        <i class="fas fa-pen text-sm"></i>
                        <span class="hidden lg:block">写文章</span>
                    </a>
                    
                    <!-- 用户菜单 -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                class="flex items-center space-x-2 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-all hover:scale-105">
                            <div class="w-9 h-9 bg-gradient-to-br from-primary-400 to-primary-600 rounded-xl flex items-center justify-center shadow-sm">
                                <i class="fas fa-user text-white text-sm"></i>
                            </div>
                            <div class="hidden xl:block text-left">
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <?= htmlspecialchars($current_user['username']) ?>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">管理员</div>
                            </div>
                            <i class="fas fa-chevron-down text-xs text-gray-400 transition-transform" :class="{'rotate-180': open}"></i>
                        </button>
                        
                        <!-- 下拉菜单 -->
                        <div x-show="open" 
                             @click.away="open = false"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 mt-3 w-56 glass rounded-xl shadow-xl py-2 z-50 border border-gray-100 dark:border-gray-700">
                            
                            <!-- 用户信息 -->
                            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-primary-400 to-primary-600 rounded-xl flex items-center justify-center">
                                        <i class="fas fa-user text-white"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-900 dark:text-white font-medium">
                                            <?= htmlspecialchars($current_user['username']) ?>
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">博客管理员</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 菜单项 -->
                            <div class="py-1">
                                <a href="create.php" 
                                   class="flex items-center space-x-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-pen text-green-600 dark:text-green-400 text-xs"></i>
                                    </div>
                                    <span>写新文章</span>
                                </a>
                                
                                <a href="index.php?author=<?= $current_user['id'] ?>" 
                                   class="flex items-center space-x-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-file-alt text-blue-600 dark:text-blue-400 text-xs"></i>
                                    </div>
                                    <span>我的文章</span>
                                </a>
                                
                                <button @click="toggleFavorites()" 
                                        class="flex items-center space-x-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors w-full text-left">
                                    <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-heart text-red-600 dark:text-red-400 text-xs"></i>
                                    </div>
                                    <span>我的收藏</span>
                                </button>
                                
                                <a href="#" onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;" 
                                   class="flex items-center space-x-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-cog text-purple-600 dark:text-purple-400 text-xs"></i>
                                    </div>
                                    <span>设置</span>
                                </a>
                            </div>
                            
                            <!-- 分隔线 -->
                            <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                            
                            <!-- 退出登录 -->
                            <a href="logout.php" 
                               class="flex items-center space-x-3 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-sign-out-alt text-red-600 dark:text-red-400 text-xs"></i>
                                </div>
                                <span>退出登录</span>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" 
                       class="flex items-center space-x-2 px-5 py-2.5 bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white rounded-xl transition-all hover:scale-105 shadow-sm text-sm font-medium">
                        <i class="fas fa-sign-in-alt"></i>
                        <span class="hidden sm:block">登录</span>
                    </a>
                <?php endif; ?>

                <!-- 移动端菜单按钮 -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" 
                        class="md:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <i class="fas fa-bars text-gray-600 dark:text-gray-300"></i>
                </button>
            </div>
        </div>

        <!-- 移动端菜单 -->
        <div x-show="mobileMenuOpen" 
             @click.away="mobileMenuOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95"
             class="md:hidden border-t border-gray-200 dark:border-gray-700 py-4"
             x-data="{ 
                 searchQuery: '', 
                 activeSection: 'menu',
                 searchResults: [],
                 searching: false 
             }">
            
            <!-- 菜单切换标签 -->
            <div class="flex border-b border-gray-200 dark:border-gray-700 mb-4">
                <button @click="activeSection = 'menu'" 
                        :class="activeSection === 'menu' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400'"
                        class="flex-1 py-2 px-1 text-center border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-bars mr-2"></i>菜单
                </button>
                <button @click="activeSection = 'search'" 
                        :class="activeSection === 'search' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400'"
                        class="flex-1 py-2 px-1 text-center border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-search mr-2"></i>搜索
                </button>
                <button @click="activeSection = 'categories'" 
                        :class="activeSection === 'categories' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400'"
                        class="flex-1 py-2 px-1 text-center border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-layer-group mr-2"></i>分类
                </button>
            </div>
            
            <!-- 主菜单 -->
            <div x-show="activeSection === 'menu'" class="space-y-2">
                <a href="index.php" 
                   class="flex items-center space-x-3 px-4 py-3 text-base font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors rounded-lg">
                    <i class="fas fa-home w-5 text-primary-500"></i>
                    <span>首页</span>
                </a>
                
                <?php if ($current_user): ?>
                <a href="create.php" 
                   class="flex items-center space-x-3 px-4 py-3 text-base font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors rounded-lg">
                    <i class="fas fa-pen w-5 text-green-500"></i>
                    <span>写文章</span>
                </a>
                <?php endif; ?>
                
                <div class="border-t border-gray-200 dark:border-gray-700 pt-3 mt-3">
                    <p class="px-4 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">快捷操作</p>
                    <a href="#" onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;" 
                       class="flex items-center space-x-3 px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors rounded-lg">
                        <i class="fas fa-arrow-up w-5 text-blue-500"></i>
                        <span>回到顶部</span>
                    </a>
                </div>
            </div>
            
            <!-- 搜索功能 -->
            <div x-show="activeSection === 'search'" class="space-y-3">
                <div class="px-4">
                    <div class="relative">
                        <input type="text" 
                               x-model="searchQuery"
                               @input.debounce.500ms="performMobileSearch()"
                               @keydown.enter="goToMobileSearch()"
                               placeholder="搜索文章..." 
                               class="w-full pl-10 pr-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:text-white dark:placeholder-gray-400">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <div x-show="searching" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                            <i class="fas fa-spinner fa-spin text-gray-400"></i>
                        </div>
                    </div>
                    <button @click="goToMobileSearch()" 
                            class="w-full mt-3 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition-colors">
                        <i class="fas fa-search mr-2"></i>搜索文章
                    </button>
                </div>
                
                <!-- 搜索结果预览 -->
                <div x-show="searchResults.length > 0" class="px-4">
                    <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">搜索结果</p>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        <template x-for="result in searchResults" :key="result.id">
                            <a :href="'post.php?id=' + result.id" 
                               class="block p-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg border border-gray-100 dark:border-gray-600 transition-colors">
                                <div class="font-medium text-gray-900 dark:text-white text-sm line-clamp-1" x-text="result.title"></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-1" x-text="result.excerpt"></div>
                            </a>
                        </template>
                    </div>
                </div>
            </div>
            
            <!-- 分类功能 -->
            <div x-show="activeSection === 'categories'" class="space-y-2">
                <?php
                // 简化移动端分类，不显示统计数字
                $currentType = $_GET['type'] ?? '';
                ?>
                
                <a href="index.php" 
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors hover:bg-gray-50 dark:hover:bg-gray-700 <?= empty($currentType) ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300' ?>">
                    <i class="fas fa-th-large text-gray-400"></i>
                    <span class="font-medium">全部文章</span>
                </a>
                
                <a href="index.php?type=article" 
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors hover:bg-gray-50 dark:hover:bg-gray-700 <?= $currentType === 'article' ? 'bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400' : 'text-gray-700 dark:text-gray-300' ?>">
                    <i class="fas fa-newspaper text-green-500"></i>
                    <span class="font-medium">图文并茂</span>
                </a>
                
                <a href="index.php?type=image" 
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors hover:bg-gray-50 dark:hover:bg-gray-700 <?= $currentType === 'image' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300' ?>">
                    <i class="fas fa-images text-blue-500"></i>
                    <span class="font-medium">图片分享</span>
                </a>
                
                <a href="index.php?type=text" 
                   class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors hover:bg-gray-50 dark:hover:bg-gray-700 <?= $currentType === 'text' ? 'bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400' : 'text-gray-700 dark:text-gray-300' ?>">
                    <i class="fas fa-quote-left text-purple-500"></i>
                    <span class="font-medium">文字分享</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
// 主题切换功能
function toggleTheme() {
    const html = document.documentElement;
    const isDark = html.classList.contains('dark');
    
    if (isDark) {
        html.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    } else {
        html.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    }
}

// 快速搜索功能（桌面端）
function performQuickSearch() {
    const navComponent = document.querySelector('nav[x-data]');
    if (!navComponent) return;
    
    const query = navComponent._x_dataStack[0].searchQuery?.trim();
    
    if (!query || query.length < 2) {
        navComponent._x_dataStack[0].searchResults = [];
        return;
    }
    
    navComponent._x_dataStack[0].searching = true;
    
    // 搜索API调用
    fetch(`api/search.php?q=${encodeURIComponent(query)}&limit=8`)
        .then(response => response.json())
        .then(data => {
            if (navComponent._x_dataStack[0]) {
                if (data.success) {
                    navComponent._x_dataStack[0].searchResults = data.results || [];
                } else {
                    navComponent._x_dataStack[0].searchResults = [];
                    console.warn('搜索失败:', data.message);
                }
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            if (navComponent._x_dataStack[0]) {
                navComponent._x_dataStack[0].searchResults = [];
            }
        })
        .finally(() => {
            if (navComponent._x_dataStack[0]) {
                navComponent._x_dataStack[0].searching = false;
            }
        });
}

// 跳转到搜索页面
function goToSearch() {
    const navComponent = document.querySelector('nav[x-data]');
    if (!navComponent) return;
    
    const query = navComponent._x_dataStack[0].searchQuery?.trim();
    if (query) {
        window.location.href = `index.php?search=${encodeURIComponent(query)}`;
    }
}

// 移动端搜索功能
function performMobileSearch() {
    // 移动端搜索逻辑，可以复用桌面端的
    performQuickSearch();
}

function goToMobileSearch() {
    goToSearch();
}

// 随机文章功能
function goToRandomPost() {
    // 获取随机文章
    fetch('api/random_post.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.postId) {
                window.location.href = `post.php?id=${data.postId}`;
            } else {
                showToast('暂无文章可供随机浏览', 'warning');
            }
        })
        .catch(error => {
            console.error('Random post error:', error);
            showToast('获取随机文章失败', 'error');
        });
}

// 收藏功能
function toggleFavorites() {
    // 打开收藏列表弹窗或跳转到收藏页面
    const favorites = JSON.parse(localStorage.getItem('favorites') || '[]');
    if (favorites.length === 0) {
        showToast('您还没有收藏任何文章', 'info');
        return;
    }
    
    // 这里可以实现收藏列表弹窗或跳转
    console.log('Toggle favorites:', favorites);
    showToast('收藏功能开发中...', 'info');
}

// 回到顶部功能
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Toast 提示功能
function showToast(message, type = 'info') {
    // 创建toast元素
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        type === 'warning' ? 'bg-yellow-500 text-white' :
        'bg-blue-500 text-white'
    }`;
    toast.innerHTML = `
        <div class="flex items-center space-x-2">
            <i class="fas ${
                type === 'success' ? 'fa-check-circle' :
                type === 'error' ? 'fa-exclamation-circle' :
                type === 'warning' ? 'fa-exclamation-triangle' :
                'fa-info-circle'
            }"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // 显示toast
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 100);
    
    // 3秒后隐藏
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}

// Alpine.js 初始化
document.addEventListener('alpine:init', () => {
    // 可以在这里添加全局的Alpine配置
});

// 更新实时时间
function updateCurrentTime() {
    const now = new Date();
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    const timeString = now.toLocaleString('zh-CN', options);
    const timeElement = document.getElementById('current-time');
    if (timeElement) {
        timeElement.textContent = timeString;
    }
}

// 页面统计功能
function updatePageStats() {
    fetch('api/stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const statsElements = document.querySelectorAll('.page-stats');
                statsElements.forEach(element => {
                    element.textContent = data.stats.display_text || '加载完成';
                });
            }
        })
        .catch(error => {
            console.warn('获取统计信息失败:', error);
            const statsElements = document.querySelectorAll('.page-stats');
            statsElements.forEach(element => {
                element.textContent = '统计加载失败';
            });
        });
}

// 键盘快捷键支持
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K 打开搜索
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('[x-model="searchQuery"]');
            if (searchInput) {
                searchInput.focus();
            }
        }
        
        // Ctrl/Cmd + H 回到首页
        if ((e.ctrlKey || e.metaKey) && e.key === 'h') {
            e.preventDefault();
            window.location.href = 'index.php';
        }
        
        // ESC 关闭搜索结果
        if (e.key === 'Escape') {
            const navComponent = document.querySelector('nav[x-data]');
            if (navComponent && navComponent._x_dataStack[0]) {
                navComponent._x_dataStack[0].showQuickSearch = false;
            }
        }
    });
}

// 初始化主题
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
        document.documentElement.classList.add('dark');
    }
    
    // 初始化实时时间
    updateCurrentTime();
    setInterval(updateCurrentTime, 60000); // 每分钟更新一次
    
    // 初始化页面统计
    updatePageStats();
    
    // 设置键盘快捷键
    setupKeyboardShortcuts();
    
    // 添加页面加载完成的动画效果
    document.body.classList.add('animate-fadeIn');
});
</script> 