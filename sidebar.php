<?php
// 获取侧边栏数据
try {
    $siteStats = getSiteStats();
    $recentPosts = getRecentPosts(3);
    $archive = getPostsArchive();
} catch (Exception $e) {
    // 如果获取数据失败，设置默认值
    $siteStats = [
        'total_posts' => 0,
        'article_count' => 0,
        'image_count' => 0,
        'text_count' => 0,
        'last_post_date' => null
    ];
    $recentPosts = [];
    $archive = [];
}

// 获取当前页面信息
$currentType = $_GET['type'] ?? '';
$currentSearch = $_GET['search'] ?? '';

// 小日历数据 - 获取本月文章
$currentMonth = date('n');
$currentYear = date('Y');
$daysInMonth = date('t');
$monthlyPosts = [];

// 获取本月每天的文章数量
try {
    $pdo = getDatabase();
    $stmt = $pdo->prepare("SELECT DAY(created_at) as day, COUNT(*) as count 
                           FROM posts 
                           WHERE published = 1 
                           AND YEAR(created_at) = ? 
                           AND MONTH(created_at) = ?
                           GROUP BY DAY(created_at)");
    $stmt->execute([$currentYear, $currentMonth]);
    while ($row = $stmt->fetch()) {
        $monthlyPosts[$row['day']] = $row['count'];
    }
} catch (Exception $e) {
    // 如果数据库查询失败，保持空数组
    $monthlyPosts = [];
}
?>

<!-- 浮动侧边栏 -->
<aside class="xl:w-1/5 space-y-6 xl:block hidden" 
       x-data="{ 
           searchQuery: '<?= htmlspecialchars($currentSearch) ?>',
           searchResults: [],
           searching: false,
           showCalendar: false
       }">
    
    <!-- 搜索框 -->
    <div class="glass rounded-2xl p-6 shadow-lg">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <i class="fas fa-search text-primary-500 mr-2"></i>
            搜索文章
        </h3>
        
        <div class="relative flex space-x-2">
            <div class="relative flex-1">
                <input type="text" 
                       x-model="searchQuery"
                       @input.debounce.500ms="performSearch()"
                       @keydown.enter="goToSearch()"
                       placeholder="搜索标题或内容..." 
                       class="w-full pl-10 pr-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:text-white dark:placeholder-gray-400 transition-all">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <div x-show="searching" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                    <i class="fas fa-spinner fa-spin text-gray-400"></i>
                </div>
            </div>
            <button @click="goToSearch()" 
                    class="px-4 py-3 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                <i class="fas fa-search"></i>
            </button>
        </div>
        
        <!-- 搜索结果预览 -->
        <div x-show="searchResults.length > 0" 
             class="mt-4 max-h-48 overflow-y-auto">
            <template x-for="result in searchResults" :key="result.id">
                <a :href="'post.php?id=' + result.id" 
                   class="block p-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg border border-gray-100 dark:border-gray-600 mb-2 transition-colors">
                    <div class="font-medium text-gray-900 dark:text-white text-sm line-clamp-1" x-text="result.title"></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-1" x-text="result.excerpt"></div>
                </a>
            </template>
        </div>
    </div>

    <!-- 文章分类 -->
    <div class="glass rounded-2xl p-6 shadow-lg">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <i class="fas fa-layer-group text-primary-500 mr-2"></i>
            文章分类
        </h3>
        
        <div class="space-y-3">
            <a href="index.php" 
               class="flex items-center justify-between p-3 rounded-lg transition-colors hover:bg-gray-50 dark:hover:bg-gray-700 <?= empty($currentType) ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300' ?>">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-th-large text-gray-400"></i>
                    <span class="font-medium">全部文章</span>
                </div>
                <span class="text-xs bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300 px-2 py-1 rounded-full">
                    <?= $siteStats['total_posts'] ?>
                </span>
            </a>
            
            <a href="index.php?type=article" 
               class="flex items-center justify-between p-3 rounded-lg transition-colors hover:bg-gray-50 dark:hover:bg-gray-700 <?= $currentType === 'article' ? 'bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400' : 'text-gray-700 dark:text-gray-300' ?>">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-newspaper text-green-500"></i>
                    <span class="font-medium">图文并茂</span>
                </div>
                <span class="text-xs bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 px-2 py-1 rounded-full">
                    <?= $siteStats['article_count'] ?>
                </span>
            </a>
            
            <a href="index.php?type=image" 
               class="flex items-center justify-between p-3 rounded-lg transition-colors hover:bg-gray-50 dark:hover:bg-gray-700 <?= $currentType === 'image' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300' ?>">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-images text-blue-500"></i>
                    <span class="font-medium">图片分享</span>
                </div>
                <span class="text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-2 py-1 rounded-full">
                    <?= $siteStats['image_count'] ?>
                </span>
            </a>
            
            <a href="index.php?type=text" 
               class="flex items-center justify-between p-3 rounded-lg transition-colors hover:bg-gray-50 dark:hover:bg-gray-700 <?= $currentType === 'text' ? 'bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400' : 'text-gray-700 dark:text-gray-300' ?>">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-quote-left text-purple-500"></i>
                    <span class="font-medium">文字分享</span>
                </div>
                <span class="text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 px-2 py-1 rounded-full">
                    <?= $siteStats['text_count'] ?>
                </span>
            </a>
        </div>
    </div>

    <!-- 最新文章 -->
    <div class="glass rounded-2xl p-6 shadow-lg">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <i class="fas fa-clock text-primary-500 mr-2"></i>
            最新文章
        </h3>
        
        <div class="space-y-4">
            <?php foreach ($recentPosts as $recentPost): ?>
                <?php $typeInfo = getPostTypeLabel($recentPost['post_type']); ?>
                <a href="<?= getPostUrl($recentPost) ?>" 
                   class="block group hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg p-3 transition-colors">
                    <div class="flex items-start space-x-3">
                        <?php if (!empty($recentPost['cover_image'])): ?>
                            <img src="<?= htmlspecialchars($recentPost['cover_image']) ?>" 
                                 alt="<?= htmlspecialchars($recentPost['title']) ?>"
                                 class="w-12 h-12 object-cover rounded-lg border border-gray-200 dark:border-gray-600 flex-shrink-0">
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0
                                <?php if ($typeInfo['color'] === 'green'): ?>
                                    bg-green-100 dark:bg-green-900/30
                                <?php elseif ($typeInfo['color'] === 'blue'): ?>
                                    bg-blue-100 dark:bg-blue-900/30
                                <?php elseif ($typeInfo['color'] === 'purple'): ?>
                                    bg-purple-100 dark:bg-purple-900/30
                                <?php else: ?>
                                    bg-gray-100 dark:bg-gray-900/30
                                <?php endif; ?>">
                                <i class="<?= $typeInfo['icon'] ?> text-sm
                                    <?php if ($typeInfo['color'] === 'green'): ?>
                                        text-green-500
                                    <?php elseif ($typeInfo['color'] === 'blue'): ?>
                                        text-blue-500
                                    <?php elseif ($typeInfo['color'] === 'purple'): ?>
                                        text-purple-500
                                    <?php else: ?>
                                        text-gray-500
                                    <?php endif; ?>"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex-1 min-w-0">
                            <h4 class="font-medium text-gray-900 dark:text-white text-sm line-clamp-2 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                                <?= htmlspecialchars($recentPost['title']) ?>
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <?= formatFriendlyDate($recentPost['created_at']) ?>
                            </p>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 小日历 -->
    <div class="glass rounded-2xl p-6 shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-calendar-alt text-primary-500 mr-2"></i>
                <?= date('Y年n月') ?>
            </h3>
            <button @click="showCalendar = !showCalendar" 
                    class="text-sm text-primary-500 hover:text-primary-600 transition-colors">
                <span x-text="showCalendar ? '收起' : '展开'"></span>
                <i class="fas fa-chevron-down ml-1" :class="{'rotate-180': showCalendar}"></i>
            </button>
        </div>
        
        <div x-show="showCalendar" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95">
            
            <!-- 日历头部 -->
            <div class="grid grid-cols-7 gap-1 mb-2 text-xs text-gray-500 dark:text-gray-400 text-center">
                <div class="py-2 font-medium">日</div>
                <div class="py-2 font-medium">一</div>
                <div class="py-2 font-medium">二</div>
                <div class="py-2 font-medium">三</div>
                <div class="py-2 font-medium">四</div>
                <div class="py-2 font-medium">五</div>
                <div class="py-2 font-medium">六</div>
            </div>
            
            <!-- 日历内容 -->
            <div class="grid grid-cols-7 gap-1">
                <?php
                $firstDayOfWeek = date('w', strtotime("$currentYear-$currentMonth-1"));
                
                // 填充月初空白
                for ($i = 0; $i < $firstDayOfWeek; $i++): ?>
                    <div class="h-8"></div>
                <?php endfor;
                
                // 填充日期
                for ($day = 1; $day <= $daysInMonth; $day++):
                    $hasPost = isset($monthlyPosts[$day]);
                    $isToday = ($day == date('j') && $currentMonth == date('n') && $currentYear == date('Y'));
                ?>
                    <div class="h-8 flex items-center justify-center relative">
                        <?php if ($hasPost): ?>
                            <a href="index.php?year=<?= $currentYear ?>&month=<?= $currentMonth ?>&day=<?= $day ?>" 
                               class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-medium transition-colors
                                   <?= $isToday ? 'bg-primary-500 text-white' : 'bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 hover:bg-primary-200 dark:hover:bg-primary-900/50' ?>"
                               title="<?= $monthlyPosts[$day] ?>篇文章">
                                <?= $day ?>
                            </a>
                        <?php else: ?>
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs
                                <?= $isToday ? 'bg-gray-200 dark:bg-gray-600 text-gray-900 dark:text-white font-medium' : 'text-gray-500 dark:text-gray-400' ?>">
                                <?= $day ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
            
            <!-- 日历说明 -->
            <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-1">
                        <div class="w-3 h-3 bg-primary-100 dark:bg-primary-900/30 rounded-full"></div>
                        <span>有文章</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-3 h-3 bg-primary-500 rounded-full"></div>
                        <span>今天</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 月度归档列表 -->
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">历史归档</h4>
            <div class="space-y-2 max-h-32 overflow-y-auto">
                <?php foreach (array_slice($archive, 0, 4) as $archiveItem): ?>
                    <a href="index.php?year=<?= $archiveItem['year'] ?>&month=<?= $archiveItem['month'] ?>" 
                       class="flex items-center justify-between text-sm p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-folder text-gray-400 text-xs"></i>
                            <span class="text-gray-600 dark:text-gray-300"><?= $archiveItem['date_name'] ?></span>
                        </div>
                        <span class="text-xs bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-gray-400 px-2 py-1 rounded-full">
                            <?= $archiveItem['count'] ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</aside>

<style>
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

<script>
// 定义sidebar方法供x-data使用
window.performSearch = async function() {
    const query = this.searchQuery.trim();
    if (query.length < 2) {
        this.searchResults = [];
        return;
    }
    
    this.searching = true;
    
    try {
        const response = await fetch(`api/search.php?q=${encodeURIComponent(query)}`);
        const data = await response.json();
        
        if (data.success) {
            this.searchResults = data.results;
        } else {
            this.searchResults = [];
        }
    } catch (error) {
        console.error('搜索失败:', error);
        this.searchResults = [];
    } finally {
        this.searching = false;
    }
}

window.goToSearch = function() {
    if (this.searchQuery.trim()) {
        window.location.href = `index.php?search=${encodeURIComponent(this.searchQuery.trim())}`;
    }
}
</script> 