<!DOCTYPE html>
<html lang="ja" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? h($pageTitle) . ' - ' : '' ?><?= h(SITE_NAME) ?></title>
    <meta name="description" content="<?= h($pageDescription ?? SITE_DESCRIPTION) ?>">
    
    <!-- Preconnect to external domains -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= asset('css/custom.css') ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= asset('images/favicon.ico') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('images/apple-touch-icon.png') ?>">
    
    <!-- Meta Tags for SEO -->
    <meta name="keywords" content="AI,クリエイター,マッチング,デザイン,制作,フリーランス">
    <meta name="author" content="<?= h(SITE_NAME) ?>">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?= h($pageTitle ?? SITE_NAME) ?>">
    <meta property="og:description" content="<?= h($pageDescription ?? SITE_DESCRIPTION) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= h(getCurrentUrl()) ?>">
    <meta property="og:image" content="<?= asset('images/og-image.jpg') ?>">
    <meta property="og:site_name" content="<?= h(SITE_NAME) ?>">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= h($pageTitle ?? SITE_NAME) ?>">
    <meta name="twitter:description" content="<?= h($pageDescription ?? SITE_DESCRIPTION) ?>">
    <meta name="twitter:image" content="<?= asset('images/twitter-card.jpg') ?>">
    
    <!-- Theme Color -->
    <meta name="theme-color" content="#3b82f6">
    <meta name="msapplication-TileColor" content="#3b82f6">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?= generateCsrfToken() ?>">

    <script>
        // Enhanced Tailwind Configuration
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'slide-down': 'slideDown 0.5s ease-out',
                        'scale-in': 'scaleIn 0.3s ease-out',
                        'bounce-gentle': 'bounce 2s infinite',
                        'pulse-gentle': 'pulse 3s infinite',
                    },
                    colors: {
                        'primary': {
                            50: '#eff6ff',
                            100: '#dbeafe', 
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        'secondary': {
                            50: '#faf5ff',
                            100: '#f3e8ff',
                            200: '#e9d5ff', 
                            300: '#d8b4fe',
                            400: '#c084fc',
                            500: '#a855f7',
                            600: '#9333ea',
                            700: '#7c3aed',
                            800: '#6b21a8',
                            900: '#581c87',
                        }
                    },
                    backdropBlur: {
                        xs: '2px',
                    },
                    spacing: {
                        '18': '4.5rem',
                        '88': '22rem',
                    }
                }
            },
            plugins: [
                // Add container queries support
                function({ addUtilities }) {
                    addUtilities({
                        '.text-balance': {
                            'text-wrap': 'balance',
                        },
                    })
                }
            ]
        }
    </script>
    
    <!-- Performance optimization -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdn.tailwindcss.com">
</head>
<body class="bg-gray-50 font-sans antialiased selection:bg-primary-200 selection:text-primary-900">
    <!-- Skip Links for Accessibility -->
    <a href="#main-content" class="skip-link">メインコンテンツへスキップ</a>
    <a href="#navigation" class="skip-link">ナビゲーションへスキップ</a>
    
    <!-- Live Region for Dynamic Content Updates -->
    <div id="live-region" class="sr-live" aria-live="polite" aria-atomic="true"></div>
    
    <!-- Enhanced Header with Glass Morphism -->
    <header class="bg-white/80 backdrop-blur-lg shadow-sm border-b border-gray-200/50 sticky top-0 z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-18">
                <!-- Enhanced Logo -->
                <div class="flex items-center">
                    <a href="<?= url() ?>" class="flex items-center space-x-3 group">
                        <div class="relative">
                            <div class="w-10 h-10 bg-gradient-to-br from-primary-500 via-primary-600 to-secondary-600 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-300 group-hover:scale-105">
                                <span class="text-white font-bold text-lg">AW</span>
                            </div>
                            <div class="absolute inset-0 bg-gradient-to-br from-primary-400 to-secondary-500 rounded-xl opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xl font-bold text-gray-900 group-hover:text-primary-600 transition-colors duration-300"><?= h(SITE_NAME) ?></span>
                            <span class="text-xs text-gray-500 -mt-0.5">AI & Creative Works</span>
                        </div>
                    </a>
                </div>

                <!-- Enhanced Navigation -->
                <nav id="navigation" class="hidden lg:flex items-center space-x-1" role="navigation" aria-label="メインナビゲーション">
                    <?php
                    $navItems = [
                        ['url' => 'works.php', 'label' => '作品を探す', 'icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
                        ['url' => 'creators.php', 'label' => 'クリエイター', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z'],
                        ['url' => 'jobs.php', 'label' => '案件一覧', 'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2V6z']
                    ];
                    
                    // ログイン済みの場合はチャットリンクを追加
                    if (isLoggedIn()) {
                        $navItems[] = ['url' => 'chats.php', 'label' => 'チャット', 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.955 8.955 0 01-4.126-.98L3 20l1.98-5.874A8.955 8.955 0 013 12c0-4.418 3.582-8 8-8s8 3.582 8 8z'];
                    }
                    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
                    ?>
                    
                    <?php foreach ($navItems as $item): ?>
                        <?php $isActive = strpos($currentPath, $item['url']) !== false; ?>
                        <a href="<?= url($item['url']) ?>" 
                           class="group flex items-center space-x-2 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300 hover:bg-primary-50 <?= $isActive ? 'text-primary-600 bg-primary-50' : 'text-gray-700 hover:text-primary-600' ?>">
                            <svg class="w-4 h-4 transition-colors duration-300 <?= $isActive ? 'text-primary-500' : 'text-gray-400 group-hover:text-primary-500' ?>" 
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $item['icon'] ?>" />
                            </svg>
                            <span><?= $item['label'] ?></span>
                        </a>
                    <?php endforeach; ?>
                    
                    <!-- Search Icon for Desktop -->
                    <button class="p-2 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-all duration-300" 
                            onclick="toggleQuickSearch()" 
                            title="クイック検索">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </nav>

                <!-- Enhanced User Menu -->
                <div class="flex items-center space-x-3">
                    <?php if (isLoggedIn()): ?>
                        <?php 
                        $user = getCurrentUser(); 
                        if (!$user) {
                            // ユーザー情報が取得できない場合はログアウト
                            session_destroy();
                            redirect(url('login.php'));
                        }
                        $currentRole = getCurrentRole();
                        $availableRoles = getUserRoles();
                        ?>
                        <!-- Notifications -->
                        <a href="<?= url('chats.php') ?>" class="relative p-2 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-all duration-300" 
                                title="チャット通知" id="notification-button">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.955 8.955 0 01-4.126-.98L3 20l1.98-5.874A8.955 8.955 0 013 12c0-4.418 3.582-8 8-8s8 3.582 8 8z" />
                            </svg>
                            <span id="notification-badge" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center hidden">0</span>
                        </a>
                        
                        <!-- User Avatar Dropdown -->
                        <div class="relative group">
                            <button class="flex items-center space-x-3 p-1.5 rounded-xl hover:bg-primary-50 transition-all duration-300 group">
                                <img src="<?= uploaded_asset(!empty($user['profile_image']) ? $user['profile_image'] : 'assets/images/default-avatar.png') ?>" 
                                     alt="<?= h($user['full_name']) ?>" 
                                     class="w-9 h-9 rounded-xl object-cover ring-2 ring-gray-200 group-hover:ring-primary-300 transition-all duration-300">
                                <div class="hidden md:flex flex-col items-start">
                                    <span class="text-sm font-semibold text-gray-900 group-hover:text-primary-700 transition-colors duration-300"><?= h($user['full_name']) ?></span>
                                    <span class="text-xs text-gray-500"><?= getRoleDisplayName($currentRole) ?></span>
                                </div>
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-primary-500 transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            
                            <!-- Enhanced Dropdown Menu -->
                            <div class="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform translate-y-2 group-hover:translate-y-0">
                                <!-- User Info -->
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <div class="flex items-center space-x-3">
                                        <img src="<?= uploaded_asset(!empty($user['profile_image']) ? $user['profile_image'] : 'assets/images/default-avatar.png') ?>" 
                                             alt="<?= h($user['full_name']) ?>" 
                                             class="w-10 h-10 rounded-xl object-cover">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900"><?= h($user['full_name']) ?></p>
                                            <p class="text-xs text-gray-500"><?= h($user['email'] ?? '') ?></p>
                                            <p class="text-xs text-blue-600 font-medium"><?= getRoleDisplayName($currentRole) ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Role Switch Section -->
                                <?php if (is_array($availableRoles) && count($availableRoles) > 1): ?>
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">ロール切り替え</p>
                                    <div class="space-y-1">
                                        <?php foreach ($availableRoles as $role): ?>
                                            <?php if ($role !== $currentRole): ?>
                                                <a href="<?= url('switch-role.php?role=' . urlencode($role)) ?>" 
                                                   class="flex items-center px-2 py-1.5 text-xs text-gray-600 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors duration-200">
                                                    <svg class="w-3 h-3 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                    </svg>
                                                    <?= getRoleDisplayName($role) ?>に切り替え
                                                </a>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Menu Items -->
                                <div class="py-1">
                                    <a href="<?= url('dashboard.php') ?>" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-700 transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                                        </svg>
                                        ダッシュボード
                                    </a>
                                    <a href="<?= url('profile.php') ?>" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-700 transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        プロフィール
                                    </a>
                                    <a href="<?= url('settings.php') ?>" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-700 transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        設定
                                    </a>
                                </div>
                                
                                <div class="border-t border-gray-100 py-1">
                                    <a href="<?= url('logout.php') ?>" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        ログアウト
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Post Job Button for Logged In Users -->
                        <a href="<?= url('post-job.php') ?>" class="btn btn-success btn-sm btn-shimmer hidden md:inline-flex">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            案件を投稿
                        </a>
                        
                    <?php else: ?>
                        <!-- Login Button -->
                        <a href="<?= url('login.php') ?>" class="text-gray-700 hover:text-primary-600 px-3 py-2 text-sm font-medium transition-colors duration-300 hover:bg-primary-50 rounded-xl">
                            ログイン
                        </a>
                        
                        <!-- Registration Buttons -->
                        <div class="hidden md:flex items-center space-x-2">
                            <a href="<?= url('register.php?type=creator') ?>" class="btn btn-outline btn-sm">
                                クリエイター登録
                            </a>
                            <a href="<?= url('register.php?type=client') ?>" class="btn btn-primary btn-sm btn-shimmer">
                                依頼者登録
                            </a>
                        </div>
                        
                        <!-- Post Job Dropdown for Non-logged Users -->
                        <div class="relative group">
                            <button class="btn btn-success btn-sm">
                                案件を投稿
                                <svg class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            
                            <!-- Enhanced Dropdown Menu -->
                            <div class="absolute right-0 mt-2 w-72 bg-white rounded-2xl shadow-xl border border-gray-100 py-3 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform translate-y-2 group-hover:translate-y-0 z-10">
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <h3 class="text-sm font-semibold text-gray-900">案件投稿にはログインが必要です</h3>
                                    <p class="text-xs text-gray-500 mt-1">アカウントをお持ちでない場合は、新規登録をお願いします</p>
                                </div>
                                
                                <div class="py-2">
                                    <a href="<?= url('login.php') ?>" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-700 transition-colors duration-200">
                                        <svg class="h-5 w-5 mr-3 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                        </svg>
                                        <div>
                                            <div class="font-medium">ログイン</div>
                                            <div class="text-xs text-gray-500">既存のアカウントでログイン</div>
                                        </div>
                                    </a>
                                    <a href="<?= url('register.php?type=client') ?>" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-secondary-50 hover:text-secondary-700 transition-colors duration-200">
                                        <svg class="h-5 w-5 mr-3 text-secondary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                        </svg>
                                        <div>
                                            <div class="font-medium">依頼者として登録</div>
                                            <div class="text-xs text-gray-500">案件を投稿して優秀なクリエイターを見つける</div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Enhanced Mobile menu button -->
                <div class="lg:hidden">
                    <button type="button" 
                            class="p-2 text-gray-700 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-primary-500" 
                            onclick="toggleMobileMenu()"
                            id="mobile-menu-button"
                            aria-expanded="false"
                            aria-controls="mobile-menu"
                            aria-label="メニューを開く">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Enhanced Mobile Navigation -->
        <div id="mobile-menu" class="lg:hidden hidden bg-white/95 backdrop-blur-lg border-t border-gray-200/50" role="navigation" aria-label="モバイルナビゲーション">
            <div class="px-4 pt-4 pb-6 space-y-2">
                <!-- Mobile Navigation Links -->
                <?php foreach ($navItems as $item): ?>
                    <?php $isActive = strpos($currentPath, $item['url']) !== false; ?>
                    <a href="<?= url($item['url']) ?>" 
                       class="flex items-center space-x-3 px-4 py-3 rounded-xl text-base font-medium transition-all duration-300 <?= $isActive ? 'text-primary-600 bg-primary-50' : 'text-gray-700 hover:text-primary-600 hover:bg-primary-50' ?>">
                        <svg class="w-5 h-5 <?= $isActive ? 'text-primary-500' : 'text-gray-400' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $item['icon'] ?>" />
                        </svg>
                        <span><?= $item['label'] ?></span>
                    </a>
                <?php endforeach; ?>
                
                <!-- Mobile Action Buttons -->
                <div class="pt-4 border-t border-gray-200">
                    <?php if (!isLoggedIn()): ?>
                        <div class="space-y-3">
                            <a href="<?= url('login.php') ?>" class="w-full btn btn-outline btn-md">
                                ログイン
                            </a>
                            <div class="grid grid-cols-2 gap-3">
                                <a href="<?= url('register.php?type=creator') ?>" class="btn btn-secondary btn-sm">
                                    クリエイター登録
                                </a>
                                <a href="<?= url('register.php?type=client') ?>" class="btn btn-primary btn-sm">
                                    依頼者登録
                                </a>
                            </div>
                            <a href="<?= url('post-job.php') ?>" class="w-full btn btn-success btn-md">
                                案件を投稿
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="<?= url('post-job.php') ?>" class="w-full btn btn-success btn-md">
                            案件を投稿
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Quick Search Modal -->
    <div id="quick-search-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 opacity-0 invisible transition-all duration-300">
        <div class="flex items-start justify-center min-h-screen pt-16 px-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl transform scale-95 transition-all duration-300">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">クイック検索</h3>
                        <button onclick="toggleQuickSearch()" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="relative">
                        <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" 
                               placeholder="作品、クリエイター、案件を検索..." 
                               class="w-full pl-12 pr-4 py-4 text-lg border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all duration-300"
                               id="quick-search-input">
                    </div>
                    <div class="mt-4 text-sm text-gray-500">
                        <p>検索のヒント: 「Web制作」「ロゴデザイン」「動画編集」など</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Flash Messages -->
    <?php $flashMessages = getFlash(); ?>
    <?php if (!empty($flashMessages)): ?>
        <div class="fixed top-24 right-4 z-50 space-y-3 max-w-sm">
            <?php foreach ($flashMessages as $type => $message): ?>
                <div class="flash-message flash-<?= h($type) ?> p-4 rounded-2xl shadow-xl backdrop-blur-lg border animate-slide-in-right" 
                     data-type="<?= h($type) ?>">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <?php if ($type === 'success'): ?>
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            <?php elseif ($type === 'error'): ?>
                                <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                    <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </div>
                            <?php elseif ($type === 'warning'): ?>
                                <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                    <svg class="h-5 w-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                </div>
                            <?php else: ?>
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900"><?= h($message) ?></p>
                        </div>
                        
                        <button onclick="this.parentElement.parentElement.remove()" 
                                class="flex-shrink-0 p-1 text-gray-400 hover:text-gray-600 rounded-lg transition-colors duration-200">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Notification Update Script -->
    <?php if (isLoggedIn()): ?>
    <script>
        // 未読メッセージ数を更新する関数
        async function updateNotificationCount() {
            try {
                const response = await fetch('<?= url('api/get-unread-count.php') ?>');
                const result = await response.json();
                
                if (result.success) {
                    const badge = document.getElementById('notification-badge');
                    const button = document.getElementById('notification-button');
                    
                    if (result.unread_count > 0) {
                        badge.textContent = result.unread_count > 99 ? '99+' : result.unread_count;
                        badge.classList.remove('hidden');
                        button.classList.add('text-primary-600');
                        button.classList.remove('text-gray-400');
                    } else {
                        badge.classList.add('hidden');
                        button.classList.remove('text-primary-600');
                        button.classList.add('text-gray-400');
                    }
                }
            } catch (error) {
                console.error('Notification update error:', error);
            }
        }
        
        // ページ読み込み時に実行
        document.addEventListener('DOMContentLoaded', function() {
            updateNotificationCount();
            
            // 30秒ごとに未読メッセージ数を更新
            setInterval(updateNotificationCount, 30000);
        });
        
        // グローバル関数として公開（他のページから呼び出せるように）
        window.updateNotificationCount = updateNotificationCount;
    </script>
    <?php endif; ?>

    <main class="min-h-screen relative"><?php // メインコンテンツはここから開始 ?>

