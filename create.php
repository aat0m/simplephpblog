<?php
// 启动会话（必须在任何输出之前）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/functions.php';

// 要求用户登录
requireLogin();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $excerpt = trim($_POST['excerpt'] ?? '');
    $cover_image = trim($_POST['cover_image'] ?? '');
    $published = isset($_POST['published']) ? 1 : 0;
    $post_type = $_POST['post_type'] ?? 'article';
    $images = $_POST['images'] ?? null;
    
    $errors = [];
    
    // 根据文章类型验证必要字段
    if ($post_type === 'image') {
        // 图片类型验证
        $images_data = null;
        if ($images && is_string($images)) {
            $images_data = json_decode($images, true);
        }
        
        if (empty($images_data) || !is_array($images_data) || count($images_data) === 0) {
            $errors[] = '纯图片类型的分享必须上传至少一张图片';
        }
        
        // 图片类型的标题和内容都是可选的
        // 如果没有标题，使用默认标题
        if (empty($title)) {
            $title = '图片分享 ' . date('Y-m-d H:i');
        }
        
        // 如果没有内容，使用空字符串
        if (empty($content)) {
            $content = '';
        }
    } else {
        // 其他类型需要标题和内容
        if (empty($title)) {
            $errors[] = '请填写文章标题';
        }
        
        if (empty($content)) {
            $errors[] = '请填写文章内容';
        }
    }
    
    if (empty($errors)) {
        // 如果没有提供摘要，自动生成
        if (empty($excerpt)) {
            $excerpt = generateExcerpt($content);
        }
        
        $post_id = createPost($title, $content, $excerpt, $cover_image, $published, $post_type, $images);
        
        if ($post_id) {
            header('Location: post.php?id=' . $post_id);
            exit;
        } else {
            $errors[] = '创建文章失败，请重试';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>写文章 - 好朋友博客</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Quill富文本编辑器 -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    
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
        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(229, 231, 235, 0.5);
        }
        
        .dark .glass {
            background: rgba(55, 65, 81, 0.95);
            border: 1px solid rgba(75, 85, 99, 0.5);
        }
        .ql-toolbar {
            border-top-left-radius: 0.75rem;
            border-top-right-radius: 0.75rem;
            border-color: #d1d5db;
        }
        .ql-container {
            border-bottom-left-radius: 0.75rem;
            border-bottom-right-radius: 0.75rem;
            border-color: #d1d5db;
        }
        .ql-editor {
            min-height: 300px;
            font-size: 16px;
            line-height: 1.6;
        }
        .ql-editor.ql-blank::before {
            color: #9ca3af;
            font-style: italic;
        }
        
        /* 暗色模式下的编辑器样式 */
        .dark .ql-toolbar {
            background: #374151;
            border-color: #4b5563;
        }
        .dark .ql-container {
            background: #374151;
            border-color: #4b5563;
        }
        .dark .ql-editor {
            color: #f3f4f6;
        }
        .dark .ql-snow .ql-stroke {
            stroke: #9ca3af;
        }
        .dark .ql-snow .ql-fill {
            fill: #9ca3af;
        }
        .dark .ql-snow .ql-picker-label {
            color: #f3f4f6;
        }
        .dark .ql-snow .ql-picker-options {
            background: #374151;
            border-color: #4b5563;
        }
        .dark .ql-snow .ql-tooltip {
            background: #374151;
            border-color: #4b5563;
            color: #f3f4f6;
        }

    </style>
</head>
<body class="min-h-full bg-white dark:bg-gray-900 font-sans transition-colors duration-300">
    
    <!-- 顶部导航栏 -->
    <?php include 'includes/header.php'; ?>
    
    <!-- 主内容区域 -->
    <main class="min-h-screen">
        <div class="px-6 py-8">
            <div class="max-w-4xl mx-auto bg-gradient-to-br from-gray-50 via-white to-blue-50 dark:from-gray-800 dark:via-gray-700 dark:to-gray-800 rounded-3xl p-6 lg:p-8 shadow-sm">
        <!-- 返回按钮 -->
        <div class="mb-8">
            <a href="index.php" class="inline-flex items-center text-gray-600 hover:text-gray-900 transition-colors group">
                <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform"></i>
                返回列表
            </a>
        </div>

        <!-- 页面标题 -->
        <div class="text-center mb-8">
            <h1 class="text-3xl mobile-title font-bold text-gray-900 mb-4">
                记录与好朋友的美好时光
            </h1>
            <p class="text-gray-600 text-sm sm:text-base">
                用文字和图片记录那些珍贵的回忆 ✨
            </p>
        </div>

        <!-- 错误提示 -->
        <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex">
                    <i class="fas fa-exclamation-circle text-red-400 mt-0.5 mr-3"></i>
                    <div>
                        <h3 class="text-sm font-medium text-red-800">请修正以下错误：</h3>
                        <ul class="mt-2 text-sm text-red-700">
                            <?php foreach ($errors as $error): ?>
                                <li>• <?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 文章表单 -->
        <form method="POST" class="space-y-8" x-data="{ 
            showPreview: false,
            isPublished: false,
            autoSave: true,
            lastSaved: null,
            postType: '<?= $_POST['post_type'] ?? 'article' ?>',
            showCoverImage: true,
            showEditor: true
        }">
            <div class="glass rounded-2xl p-4 sm:p-8 mobile-card border border-white/20">
                <!-- 文章类型选择 -->
                <div class="mb-8">
                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                        <i class="fas fa-layer-group mr-2 text-primary-500"></i>
                        文章类型 *
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <label class="relative cursor-pointer">
                            <input type="radio" 
                                   name="post_type" 
                                   value="article" 
                                   x-model="postType"
                                   class="sr-only peer">
                            <div class="p-4 rounded-lg border-2 border-gray-200 peer-checked:border-primary-500 peer-checked:bg-primary-50 transition-all">
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-newspaper text-2xl text-gray-400 peer-checked:text-primary-500"></i>
                                    <div>
                                        <h3 class="font-semibold text-gray-900">图文并茂</h3>
                                        <p class="text-sm text-gray-600">完整的文章，包含文字、图片</p>
                                    </div>
                                </div>
                            </div>
                        </label>
                        
                        <label class="relative cursor-pointer">
                            <input type="radio" 
                                   name="post_type" 
                                   value="image" 
                                   x-model="postType"
                                   class="sr-only peer">
                            <div class="p-4 rounded-lg border-2 border-gray-200 peer-checked:border-primary-500 peer-checked:bg-primary-50 transition-all">
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-image text-2xl text-gray-400 peer-checked:text-primary-500"></i>
                                    <div>
                                        <h3 class="font-semibold text-gray-900">纯图片</h3>
                                        <p class="text-sm text-gray-600">以图片为主的分享</p>
                                    </div>
                                </div>
                            </div>
                        </label>
                        
                        <label class="relative cursor-pointer">
                            <input type="radio" 
                                   name="post_type" 
                                   value="text" 
                                   x-model="postType"
                                   class="sr-only peer">
                            <div class="p-4 rounded-lg border-2 border-gray-200 peer-checked:border-primary-500 peer-checked:bg-primary-50 transition-all">
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-quote-left text-2xl text-gray-400 peer-checked:text-primary-500"></i>
                                    <div>
                                        <h3 class="font-semibold text-gray-900">纯文字</h3>
                                        <p class="text-sm text-gray-600">类似朋友圈的文字分享</p>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 文章标题 -->
                <div class="mb-8">
                    <label for="title" class="block text-sm font-semibold text-gray-700 mb-3">
                        <i class="fas fa-heading mr-2 text-primary-500"></i>
                        <span x-text="postType === 'text' ? '想说的话' : postType === 'image' ? '标题（可选）' : '文章标题'"></span> 
                        <span x-show="postType !== 'image'" class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           x-bind:required="postType !== 'image'"
                           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                           x-bind:placeholder="postType === 'text' ? '分享一下你的想法...' : postType === 'image' ? '为这次分享起个标题吧（可选）...' : '请输入一个吸引人的标题...'"
                           class="w-full px-4 py-3 text-lg border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all">
                </div>

                <!-- 封面图片 - 仅对图文并茂类型显示 -->
                <div class="mb-8" x-show="postType === 'article'">
                    <label for="cover_image" class="block text-sm font-semibold text-gray-700 mb-3">
                        <i class="fas fa-image mr-2 text-primary-500"></i>
                        封面图片
                    </label>
                    <div class="flex flex-col sm:flex-row sm:space-x-3 space-y-3 sm:space-y-0">
                        <input type="text" 
                               id="cover_image" 
                               name="cover_image" 
                               value="<?= htmlspecialchars($_POST['cover_image'] ?? '') ?>"
                               placeholder="https://example.com/image.jpg 或本地上传"
                               class="flex-1 px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all">
                        <button type="button" 
                                onclick="uploadCoverImage()"
                                class="px-4 py-3 bg-primary-500 text-white rounded-xl hover:bg-primary-600 transition-colors">
                            <i class="fas fa-upload mr-2"></i>
                            <span class="hidden sm:inline">上传</span>
                        </button>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        支持输入图片链接或点击上传按钮选择本地图片
                    </p>
                </div>

                <!-- 图片上传区域 - 仅对图片类型显示 -->
                <div class="mb-8" x-show="postType === 'image'" x-data="{ 
                    selectedImages: [], 
                    dragOver: false,
                    uploading: false
                }">
                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                        <i class="fas fa-images mr-2 text-primary-500"></i>
                        上传图片 <span class="text-red-500">*</span>
                    </label>
                    
                    <!-- 拖拽上传区域 -->
                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center"
                         :class="{'border-primary-500 bg-primary-50': dragOver}"
                         @dragover.prevent="dragOver = true"
                         @dragleave="dragOver = false"
                         @drop.prevent="handleDrop($event); dragOver = false">
                        
                        <div class="space-y-3">
                            <div class="text-gray-400">
                                <i class="fas fa-cloud-upload-alt text-4xl"></i>
                            </div>
                            <div>
                                <p class="text-lg font-medium text-gray-700">点击选择图片或拖拽到此处</p>
                                <p class="text-sm text-gray-500">支持多张图片同时上传，最多9张</p>
                            </div>
                        </div>
                        
                        <input type="file" 
                               id="image-upload" 
                               multiple 
                               accept="image/*"
                               class="hidden"
                               @change="handleFileSelect($event)">
                        
                        <button type="button" 
                                onclick="document.getElementById('image-upload').click()"
                                class="mt-4 px-6 py-3 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"
                                :disabled="uploading">
                            <i class="fas fa-plus mr-2"></i>
                            <span x-text="uploading ? '上传中...' : '选择图片'"></span>
                        </button>
                    </div>
                    
                    <!-- 图片预览区域 -->
                    <div class="mt-4" id="image-preview" style="display: none;">
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            <!-- 图片预览会通过JS动态生成 -->
                        </div>
                        <p class="text-sm text-gray-500 mt-2">
                            已选择 <span>0</span> 张图片，第一张将作为封面
                        </p>
                    </div>
                    
                    <!-- 隐藏的input用于存储图片数据 -->
                    <input type="hidden" name="images" value="">
                </div>

                <!-- 文章摘要 -->
                <div class="mb-8" x-show="postType === 'article'">
                    <label for="excerpt" class="block text-sm font-semibold text-gray-700 mb-3">
                        <i class="fas fa-quote-left mr-2 text-primary-500"></i>
                        文章摘要
                    </label>
                    <textarea id="excerpt" 
                              name="excerpt" 
                              rows="3"
                              placeholder="简短描述这篇文章的内容...（如果不填写，系统会自动生成）"
                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all resize-none"><?= htmlspecialchars($_POST['excerpt'] ?? '') ?></textarea>
                </div>

                <!-- 文章内容 -->
                <div class="mb-8">
                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                        <i class="fas fa-edit mr-2 text-primary-500"></i>
                        <span x-text="postType === 'text' ? '文字内容' : postType === 'image' ? '配文（可选）' : '文章内容'"></span> 
                        <span x-show="postType !== 'image'" class="text-red-500">*</span>
                    </label>
                    
                    <!-- 纯文字类型 -->
                    <div x-show="postType === 'text'">
                        <textarea id="text-content" 
                                  name="text_content" 
                                  rows="6"
                                  placeholder="分享你的想法、感受或者今天发生的有趣事情..."
                                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all resize-none text-lg leading-relaxed"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                    </div>
                    
                    <!-- 图片类型 - 朋友圈风格 -->
                    <div x-show="postType === 'image'">
                        <textarea id="image-content" 
                                  name="image_content" 
                                  rows="4"
                                  placeholder="说点什么吧...分享今天的心情、有趣的事情或者这些照片的故事 📸"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all resize-none text-lg leading-relaxed"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                        <div class="mt-2 flex items-center justify-between text-sm text-gray-500">
                            <span>
                                <i class="fas fa-lightbulb mr-1"></i>
                                就像发朋友圈一样，简单分享你的想法
                            </span>
                            <span id="char-count">0/200</span>
                        </div>
                    </div>
                    
                    <!-- 图文并茂类型 -->
                    <div x-show="postType === 'article'">
                        <div id="editor" class="bg-white dark:bg-gray-800"></div>
                    </div>
                    
                    <input type="hidden" name="content" id="content" value="<?= htmlspecialchars($_POST['content'] ?? '') ?>">
                </div>

                <!-- 发布选项 -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pt-6 border-t border-gray-200 space-y-4 sm:space-y-0">
                    <div class="flex items-center space-x-4">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" 
                                   name="published" 
                                   x-model="isPublished"
                                   <?= isset($_POST['published']) ? 'checked' : '' ?>
                                   class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <span class="text-sm font-medium text-gray-700">立即发布</span>
                        </label>
                        
                        <div class="flex items-center space-x-1 text-sm text-gray-500 mobile-hidden" x-show="autoSave">
                            <i class="fas fa-save"></i>
                            <span>自动保存已开启</span>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <button type="button" 
                                @click="showPreview = !showPreview"
                                class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors mobile-hidden">
                            <i class="fas fa-eye mr-2"></i>
                            <span x-text="showPreview ? '编辑模式' : '预览模式'"></span>
                        </button>
                        
                        <button type="submit" 
                                class="flex-1 sm:flex-none px-6 py-2 bg-gradient-to-r from-primary-500 to-purple-500 text-white rounded-lg hover:from-primary-600 hover:to-purple-600 transition-all transform hover:scale-105 shadow-lg">
                            <i class="fas fa-paper-plane mr-2"></i>
                            <span x-text="isPublished ? '发布文章' : '保存草稿'"></span>
                        </button>
                    </div>
                </div>
            </div>
            </form>
        </div>
        
        <!-- 页脚 -->
        <footer class="glass mt-16 border-t border-gray-200 dark:border-gray-700">
            <div class="max-w-4xl mx-auto px-6 py-12">
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
            </div>
        </div>
    </main>

    <script>
        // 初始化Quill编辑器
        let quill = null;
        
        function initEditor() {
            if (quill) {
                quill.enable();
                return;
            }
            
            quill = new Quill('#editor', {
                theme: 'snow',
                placeholder: '在这里记录与好朋友的美好时光...',
                modules: {
                    toolbar: {
                        container: [
                            [{ 'header': [1, 2, 3, false] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'color': [] }, { 'background': [] }],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            [{ 'indent': '-1'}, { 'indent': '+1' }],
                            [{ 'align': [] }],
                            ['blockquote', 'code-block'],
                            ['link', 'image'],
                            ['clean']
                        ],
                        handlers: {
                            image: imageHandler
                        }
                    }
                }
            });

            // 设置初始内容
            const initialContent = document.getElementById('content').value;
            if (initialContent) {
                quill.root.innerHTML = initialContent;
            }

            // 同步编辑器内容到隐藏字段
            quill.on('text-change', function() {
                document.getElementById('content').value = quill.root.innerHTML;
            });
        }

        // 监听文章类型变化
        document.addEventListener('alpine:init', () => {
            Alpine.store('postType', 'article');
        });

        // 监听postType变化
        document.addEventListener('DOMContentLoaded', function() {
            initEditor();
            
            // 监听文章类型变化
            document.querySelectorAll('input[name="post_type"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const postType = this.value;
                    
                    // 更新content字段
                    if (postType === 'text') {
                        // 纯文字类型，使用textarea
                        const textContent = document.getElementById('text-content');
                        if (textContent) {
                            textContent.addEventListener('input', function() {
                                document.getElementById('content').value = this.value;
                            });
                        }
                    } else {
                        // 其他类型，使用富文本编辑器
                        if (quill) {
                            quill.enable();
                        }
                    }
                    
                    // 更新placeholder
                    const titleInput = document.getElementById('title');
                    if (postType === 'text') {
                        titleInput.placeholder = '分享一下你的想法...';
                    } else {
                        titleInput.placeholder = '请输入一个吸引人的标题...';
                    }
                });
            });
        });

        // 图片上传处理器
        function imageHandler() {
            const input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.click();

            input.onchange = async () => {
                const file = input.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('image', file);

                try {
                    // 显示上传进度
                    const range = quill.getSelection(true);
                    quill.insertText(range.index, '图片上传中...', 'user');

                    const response = await fetch('api/upload_image.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    // 移除临时文本
                    quill.deleteText(range.index, '图片上传中...'.length);

                    if (data.success) {
                        // 插入图片
                        quill.insertEmbed(range.index, 'image', data.url);
                        quill.setSelection(range.index + 1);
                    } else {
                        alert('图片上传失败: ' + data.message);
                    }
                } catch (error) {
                    console.error('Upload error:', error);
                    alert('图片上传失败，请重试');
                    
                    // 移除临时文本
                    const currentText = quill.getText();
                    if (currentText.includes('图片上传中...')) {
                        quill.setText(currentText.replace('图片上传中...', ''));
                    }
                }
            };
        }

        // 封面图片上传
        async function uploadCoverImage() {
            const input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.click();

            input.onchange = async () => {
                const file = input.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('image', file);

                try {
                    const response = await fetch('api/upload_image.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        document.getElementById('cover_image').value = data.url;
                    } else {
                        alert('图片上传失败: ' + data.message);
                    }
                } catch (error) {
                    console.error('Upload error:', error);
                    alert('图片上传失败，请重试');
                }
            };
        }

        // 图片上传相关函数
        function handleFileSelect(event) {
            const files = Array.from(event.target.files);
            uploadImages(files);
        }
        
        function handleDrop(event) {
            const files = Array.from(event.dataTransfer.files);
            uploadImages(files);
        }
        
        // 全局存储图片数据
        window.selectedImages = [];
        
        async function uploadImages(files) {
            const imageFiles = files.filter(file => file.type.startsWith('image/'));
            
            if (imageFiles.length === 0) {
                alert('请选择图片文件');
                return;
            }
            
            const totalImages = window.selectedImages.length + imageFiles.length;
            if (totalImages > 9) {
                alert(`最多只能上传9张图片，当前已有${window.selectedImages.length}张`);
                return;
            }
            
            // 显示上传状态
            const uploadButton = document.querySelector('[x-show="postType === \'image\'"] button');
            if (uploadButton) {
                uploadButton.disabled = true;
                uploadButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>上传中...';
            }
            
            try {
                const uploadPromises = imageFiles.map(file => uploadSingleImage(file));
                const results = await Promise.all(uploadPromises);
                const successResults = results.filter(r => r.success);
                
                if (successResults.length > 0) {
                    // 添加到全局图片数组
                    window.selectedImages = [...window.selectedImages, ...successResults];
                    
                    // 更新隐藏的input值
                    const imagesInput = document.querySelector('input[name="images"]');
                    if (imagesInput) {
                        imagesInput.value = JSON.stringify(window.selectedImages);
                    }
                    
                    // 设置第一张图片为封面
                    if (window.selectedImages.length > 0) {
                        document.getElementById('cover_image').value = window.selectedImages[0].url;
                    }
                    
                    // 手动更新预览区域
                    updateImagePreview();
                    
                    console.log('成功上传', successResults.length, '张图片');
                }
            } catch (error) {
                console.error('上传失败:', error);
                alert('上传失败，请重试');
            } finally {
                // 恢复按钮状态
                if (uploadButton) {
                    uploadButton.disabled = false;
                    uploadButton.innerHTML = '<i class="fas fa-plus mr-2"></i>选择图片';
                }
            }
        }
        
        // 更新图片预览区域
        function updateImagePreview() {
            const previewContainer = document.querySelector('#image-preview .grid');
            const previewSection = document.querySelector('#image-preview');
            
            if (!previewContainer || !previewSection) return;
            
            // 清空现有内容
            previewContainer.innerHTML = '';
            
            if (window.selectedImages.length === 0) {
                previewSection.style.display = 'none';
                return;
            }
            
            // 显示预览区域
            previewSection.style.display = 'block';
            
            // 添加图片预览
            window.selectedImages.forEach((image, index) => {
                const imageDiv = document.createElement('div');
                imageDiv.className = 'relative group';
                imageDiv.innerHTML = `
                    <img src="${image.url}" 
                         alt="${image.name}"
                         class="w-full h-24 object-cover rounded-lg border border-gray-200">
                    <div class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs cursor-pointer hover:bg-red-600 transition-colors"
                         onclick="removeImage(${index})">
                        <i class="fas fa-times"></i>
                    </div>
                    <div class="absolute bottom-1 left-1 bg-black bg-opacity-50 text-white text-xs px-2 py-1 rounded">
                        ${index + 1}
                    </div>
                `;
                previewContainer.appendChild(imageDiv);
            });
            
            // 更新计数显示
            const countElement = document.querySelector('#image-preview .text-sm span');
            if (countElement) {
                countElement.textContent = window.selectedImages.length;
            }
        }
        
        // 删除图片
        function removeImage(index) {
            window.selectedImages.splice(index, 1);
            
            // 更新隐藏的input值
            const imagesInput = document.querySelector('input[name="images"]');
            if (imagesInput) {
                imagesInput.value = JSON.stringify(window.selectedImages);
            }
            
            // 更新封面图片
            if (window.selectedImages.length > 0) {
                document.getElementById('cover_image').value = window.selectedImages[0].url;
            } else {
                document.getElementById('cover_image').value = '';
            }
            
            // 更新预览
            updateImagePreview();
        }
        
        async function uploadSingleImage(file) {
            const formData = new FormData();
            formData.append('image', file);
            
            const response = await fetch('api/upload_image.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                return {
                    success: true,
                    url: result.url,
                    name: result.original_name,
                    id: result.id
                };
            } else {
                throw new Error(result.message);
            }
        }
        
        // 初始化图片上传相关事件
        document.addEventListener('DOMContentLoaded', function() {
            // 字符计数功能
            const imageTextarea = document.getElementById('image-content');
            const charCountElement = document.getElementById('char-count');
            
            if (imageTextarea && charCountElement) {
                imageTextarea.addEventListener('input', function() {
                    const length = this.value.length;
                    charCountElement.textContent = length + '/200';
                    
                    if (length > 200) {
                        charCountElement.style.color = '#ef4444';
                    } else {
                        charCountElement.style.color = '#6b7280';
                    }
                });
            }
        });
        
        // 表单提交前同步内容
        document.querySelector('form').addEventListener('submit', function() {
            const postType = document.querySelector('input[name="post_type"]:checked').value;
            
            if (postType === 'text') {
                const textContent = document.getElementById('text-content');
                if (textContent) {
                    document.getElementById('content').value = textContent.value;
                }
            } else if (postType === 'image') {
                const imageContent = document.getElementById('image-content');
                if (imageContent) {
                    document.getElementById('content').value = imageContent.value;
                }
            } else {
                if (quill) {
                    document.getElementById('content').value = quill.root.innerHTML;
                }
            }
        });

        // 自动保存功能（可选）
        let autoSaveTimer;
        
        // 为富文本编辑器添加自动保存
        if (quill) {
            quill.on('text-change', function() {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(() => {
                    console.log('Auto save triggered');
                }, 2000);
            });
        }
        
        // 为纯文字内容添加自动保存
        document.addEventListener('DOMContentLoaded', function() {
            const textContent = document.getElementById('text-content');
            if (textContent) {
                textContent.addEventListener('input', function() {
                    clearTimeout(autoSaveTimer);
                    autoSaveTimer = setTimeout(() => {
                        console.log('Auto save triggered');
                    }, 2000);
                });
            }
        });
        
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