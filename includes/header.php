<?php
require_once __DIR__ . '/functions.php';

// 現在のページパスを取得
$currentPath = $_SERVER['REQUEST_URI'];

// ユーザー情報を取得
$user = isLoggedIn() ? getCurrentUser() : null;

// ナビゲーションアイテムを定義
$navItems = [
    ['url' => 'dashboard', 'label' => 'ダッシュボード', 'icon' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 01-2-2z'],
    ['url' => 'works', 'label' => '作品を探す', 'icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
    ['url' => 'creators', 'label' => 'クリエイター', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z'],
    ['url' => 'jobs', 'label' => '案件一覧', 'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2V6z'],
    ['url' => 'chats', 'label' => 'チャット', 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-8 8a8.955 8.955 0 01-4.126-.98L3 20l1.98-5.874A8.955 8.955 0 013 12c0-4.418 3.582-8 8-8s8 3.582 8 8z'],
    ['url' => 'job-applications', 'label' => '応募管理', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z']
];
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AiNA Works</title>
    <link rel="icon" type="image/x-icon" href="<?= asset('images/favicon.ico') ?>">
    <link rel="apple-touch-icon" href="<?= asset('images/logo.png') ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
  tailwind.config = {
    darkMode: false, // ダークモードを完全に無効化
    theme: {
      extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a'
                        },
                        secondary: {
                            50: '#faf5ff',
                            100: '#f3e8ff',
                            200: '#e9d5ff',
                            300: '#d8b4fe',
                            400: '#c084fc',
                            500: '#a855f7',
                            600: '#9333ea',
                            700: '#7c3aed',
                            800: '#6b21a8',
                            900: '#581c87'
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?= asset('css/custom.css') ?>">
    <meta name="theme-color" content="#ffffff">
    <meta name="csrf-token" content="<?= generateCsrfToken() ?>">
    <style>
        /* モバイルメニューの確実な最前面表示 */
        #mobile-menu {
            z-index: 99999 !important;
        }
        
        #mobile-menu * {
            z-index: inherit;
        }
        
        /* オーバーレイとパネルの確実な表示 */
        #mobile-menu .fixed {
            z-index: 99999 !important;
        }
        
        /* 他の要素がメニューより上に来ることを防ぐ */
        body.menu-open {
            position: relative;
        }
        
        body.menu-open > *:not(#mobile-menu):not(header) {
            z-index: 1 !important;
        }
        
        /* メニューオープン時はヘッダーごと最前面に */
        body.menu-open header {
            z-index: 99998 !important;
        }
        
        /* シンプルなスクロールロック */
        body.menu-open,
        html.menu-open {
            overflow: hidden !important;
        }
        
        /* メニューの安定した位置固定 */
        #mobile-menu {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            overflow: hidden !important; /* パネルのスライド時のはみ出し防止 */
        }
        
        #mobile-menu .absolute {
            position: absolute !important;
        }
        
        #mobile-menu-panel {
            position: absolute !important;
            right: 0 !important;
            top: 0 !important;
            height: 100% !important;
            overscroll-behavior: contain; /* スクロール連鎖防止 */
            -webkit-overflow-scrolling: touch; /* iOS慣性スクロール */
        }

        /* スクロール連鎖の全体制御 */
        body.menu-open,
        html.menu-open {
            overscroll-behavior: none;
        }

        /* モバイルメニューのtransformを無効化（custom.cssの競合対策） */
        #mobile-menu {
            transform: none !important;
        }

        #mobile-menu.hidden {
            display: none !important;
            transform: none !important;
        }
        
        /* ダークモード環境での自動反転抑止（全体はライト基調を維持） */
        :root { 
            color-scheme: light !important;
        }
        
        /* ダークモードでも完全にライトモードと同じ表示に強制 */
        @media (prefers-color-scheme: dark) {
            /* すべての要素をライトモードに強制 */
            *,
            *::before,
            *::after {
                color-scheme: light !important;
            }
            
            /* 基本要素をライトモードに強制 */
            html, 
            body { 
                background-color: #f9fafb !important; 
                color: #111827 !important; 
            }
            
            /* すべての背景色をライトモードに強制 */
            header,
            main,
            section,
            article,
            aside,
            nav,
            footer,
            div,
            span,
            p,
            h1, h2, h3, h4, h5, h6,
            a,
            button,
            .bg-white,
            .bg-gray-50,
            .bg-gray-100,
            .bg-gray-200,
            .bg-gray-300,
            .card,
            [class*="bg-"]:not([class*="bg-primary"]):not([class*="bg-secondary"]):not([class*="bg-blue"]):not([class*="bg-green"]):not([class*="bg-yellow"]):not([class*="bg-red"]):not([class*="bg-purple"]):not([class*="bg-indigo"]):not([class*="bg-orange"]):not([class*="bg-pink"]):not([class*="bg-cyan"]):not([class*="bg-emerald"]):not([class*="bg-rose"]) {
                background-color: inherit !important;
            }
            
            /* 白背景要素を強制 */
            header,
            .bg-white,
            .card {
                background-color: #ffffff !important;
            }
            
            .bg-gray-50,
            section.bg-gray-50 {
                background-color: #f9fafb !important;
            }
            
            .bg-gray-100 {
                background-color: #f3f4f6 !important;
            }
            
            .bg-gray-200 {
                background-color: #e5e7eb !important;
            }
            
            .bg-gray-300 {
                background-color: #d1d5db !important;
            }
            
            /* すべてのテキスト色をライトモードに強制 */
            *,
            *::before,
            *::after {
                color: inherit !important;
            }
            
            html,
            body,
            p,
            span,
            div,
            h1, h2, h3, h4, h5, h6,
            a:not([class*="text-white"]):not([class*="bg-primary"]):not([class*="bg-secondary"]):not([class*="bg-blue"]):not([class*="bg-green"]):not([class*="bg-red"]),
            .text-gray-900, 
            .text-gray-800, 
            .text-gray-700,
            .text-gray-600,
            .text-gray-500,
            .text-gray-400,
            .text-gray-300 {
                color: #111827 !important;
            }
            
            .text-gray-800 {
                color: #1f2937 !important;
            }
            
            .text-gray-700 {
                color: #374151 !important;
            }
            
            .text-gray-600 {
                color: #4b5563 !important;
            }
            
            .text-gray-500 {
                color: #6b7280 !important;
            }
            
            .text-gray-400 {
                color: #9ca3af !important;
            }
            
            .text-gray-300 {
                color: #d1d5db !important;
            }
            
            /* 白背景の要素には白文字を適用しない（ただし、青や紫の背景の要素は除く） */
            .bg-white .text-white:not(.bg-primary-600):not(.bg-primary-700):not(.bg-primary-800):not(.bg-primary-900):not(.bg-secondary-600):not(.bg-secondary-700):not(.bg-secondary-800):not(.bg-secondary-900):not(.bg-blue-600):not(.bg-blue-700):not(.bg-blue-800):not(.bg-blue-900):not(.bg-gray-600):not(.bg-gray-700):not(.bg-gray-800):not(.bg-gray-900),
            .bg-gray-50 .text-white:not(.bg-primary-600):not(.bg-primary-700):not(.bg-primary-800):not(.bg-primary-900):not(.bg-secondary-600):not(.bg-secondary-700):not(.bg-secondary-800):not(.bg-secondary-900):not(.bg-blue-600):not(.bg-blue-700):not(.bg-blue-800):not(.bg-blue-900):not(.bg-gray-600):not(.bg-gray-700):not(.bg-gray-800):not(.bg-gray-900),
            .bg-gray-100 .text-white:not(.bg-primary-600):not(.bg-primary-700):not(.bg-primary-800):not(.bg-primary-900):not(.bg-secondary-600):not(.bg-secondary-700):not(.bg-secondary-800):not(.bg-secondary-900):not(.bg-blue-600):not(.bg-blue-700):not(.bg-blue-800):not(.bg-blue-900):not(.bg-gray-600):not(.bg-gray-700):not(.bg-gray-800):not(.bg-gray-900),
            header .text-white:not(.bg-primary-600):not(.bg-primary-700):not(.bg-primary-800):not(.bg-primary-900):not(.bg-secondary-600):not(.bg-secondary-700):not(.bg-secondary-800):not(.bg-secondary-900):not(.bg-blue-600):not(.bg-blue-700):not(.bg-blue-800):not(.bg-blue-900):not(.bg-gray-600):not(.bg-gray-700):not(.bg-gray-800):not(.bg-gray-900),
            .card .text-white:not(.bg-primary-600):not(.bg-primary-700):not(.bg-primary-800):not(.bg-primary-900):not(.bg-secondary-600):not(.bg-secondary-700):not(.bg-secondary-800):not(.bg-secondary-900):not(.bg-blue-600):not(.bg-blue-700):not(.bg-blue-800):not(.bg-blue-900):not(.bg-gray-600):not(.bg-gray-700):not(.bg-gray-800):not(.bg-gray-900) {
                color: #111827 !important;
            }
            
            /* 入力要素をライトモードに強制 */
            input, 
            select, 
            textarea,
            [type="text"],
            [type="email"],
            [type="password"],
            [type="number"],
            [type="search"],
            [type="tel"],
            [type="url"] { 
                background-color: #ffffff !important; 
                color: #111827 !important; 
                border-color: #e5e7eb !important;
            }
            
            input::placeholder,
            textarea::placeholder {
                color: #9ca3af !important;
            }
            
            /* Card等のコンポーネントをライト基調に固定 */
            .card { 
                background-color: #ffffff !important; 
                border-color: #e5e7eb !important; 
            }
            
            .badge-primary { 
                background-color: #dbeafe !important; 
                color: #1e40af !important; 
            }
            
            .badge-secondary { 
                background-color: #f3e8ff !important; 
                color: #6b21a8 !important; 
            }
            
            /* ボーダー色をライトモードに強制 */
            .border-gray-200,
            .border-gray-300 {
                border-color: #e5e7eb !important;
            }
            
            /* ドロップダウンメニューをライトモードに強制 */
            #user-menu-dropdown {
                background-color: #ffffff !important;
                border-color: #e5e7eb !important;
            }
            
            #user-menu-dropdown a {
                color: #374151 !important;
            }
            
            #user-menu-dropdown a:hover {
                background-color: #f3f4f6 !important;
                color: #111827 !important;
            }
            
            /* モバイルメニューをライトモードに強制 */
            #mobile-menu-panel {
                background-color: #ffffff !important;
            }
            
            #mobile-menu-panel a {
                color: #374151 !important;
            }
            
            #mobile-menu-panel .text-gray-700 {
                color: #374151 !important;
            }
            
            #mobile-menu-panel .text-gray-500 {
                color: #6b7280 !important;
            }
            
            #mobile-menu-panel .border-gray-200 {
                border-color: #e5e7eb !important;
            }
            
            /* リンクの色をライトモードに強制 */
            a:not([class*="text-white"]):not([class*="bg-primary"]):not([class*="bg-secondary"]):not([class*="bg-blue"]):not([class*="bg-green"]):not([class*="bg-red"]) {
                color: #2563eb !important;
            }
            
            a:hover:not([class*="text-white"]):not([class*="bg-primary"]):not([class*="bg-secondary"]):not([class*="bg-blue"]):not([class*="bg-green"]):not([class*="bg-red"]) {
                color: #1d4ed8 !important;
            }
            
            .text-blue-600,
            a.text-blue-600 {
                color: #2563eb !important;
            }
            
            .text-blue-600:hover,
            a.text-blue-600:hover {
                color: #1d4ed8 !important;
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="./" class="flex items-center space-x-3">
                        <img src="<?= asset('images/logo.png') ?>" alt="AiNA Works" class="h-8 w-auto">
                        <span class="text-xl font-bold text-gray-900">AiNA Works</span>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <nav class="hidden lg:flex items-center space-x-8">
                    <?php foreach ($navItems as $item): ?>
                    <?php
                        $isActive = strpos($currentPath, $item['url']) !== false;
                        $activeClass = $isActive ? 'text-primary-600 bg-primary-50' : 'text-gray-600 hover:text-primary-600';
                        ?>
                        <a href="<?= url($item['url']) ?>"
                           class="px-3 py-2 rounded-md text-sm font-medium transition-colors <?= $activeClass ?>">
                            <?= $item['label'] ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <!-- Desktop User Menu -->
                <div class="hidden lg:flex items-center space-x-4">
                    <?php if (isLoggedIn()): ?>
                        <a href="<?= url('post-job') ?>" 
                           class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
                            案件を投稿
                        </a>
                        
                        <!-- User Dropdown -->
                        <div class="relative" id="user-menu">
                            <button type="button" 
                                    class="flex items-center space-x-2 text-gray-700 hover:text-primary-600 p-2 rounded-lg hover:bg-gray-50"
                                    id="user-menu-button"
                                    onclick="toggleUserMenu()">
                                <?php if (!empty($user['profile_image'])): ?>
                                    <img src="<?= uploaded_asset($user['profile_image']) ?>" alt="プロフィール画像" class="w-8 h-8 rounded-full object-cover">
                                <?php else: ?>
                                    <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                                        <span class="text-primary-600 font-semibold text-sm">
                                            <?= strtoupper(substr($user['nickname'] ?? $user['full_name'] ?? $user['username'] ?? 'U', 0, 1)) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <span><?= h($user['nickname'] ?? $user['full_name'] ?? $user['username'] ?? 'ユーザー') ?></span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            
                            <!-- Dropdown Menu -->
                            <div id="user-menu-dropdown" 
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50 hidden">
                                <a href="<?= url('profile') ?>" 
                                   class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    プロフィール
                                </a>
                                <a href="<?= url('favorites') ?>" 
                                   class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    お気に入り
                                </a>
                                <div class="border-t border-gray-100 my-1"></div>
                                <a href="<?= url('logout') ?>" 
                                   class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <svg class="w-4 h-4 mr-3 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    ログアウト
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?= url('login') ?>" 
                           class="text-gray-600 hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium">
                            ログイン
                        </a>
                        <!-- 登録リンクはAPI経由のみのため非表示 -->
                    <?php endif; ?>
                </div>

                <!-- Mobile menu button -->
                <div class="lg:hidden">
                    <button type="button" id="mobile-menu-button" onclick="openMobileMenu()"
                            class="p-2 text-gray-600 hover:text-primary-600 hover:bg-gray-100 rounded-md">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation Menu -->
        <div id="mobile-menu" class="lg:hidden fixed top-0 left-0 w-full h-full z-[9999] hidden">
            <!-- Overlay -->
            <div class="absolute top-0 left-0 w-full h-full bg-black/50" onclick="closeMobileMenu()"></div>
            
            <!-- Menu Panel -->
            <div id="mobile-menu-panel" class="absolute right-0 top-0 h-full w-80 max-w-[90vw] bg-white shadow-xl transform translate-x-full transition-transform duration-300 ease-out flex flex-col overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-primary-600 to-secondary-600 p-4 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <?php if (isLoggedIn()): ?>
                                <?php if (!empty($user['profile_image'])): ?>
                                    <img src="<?= uploaded_asset($user['profile_image']) ?>" alt="プロフィール画像" class="w-10 h-10 rounded-full object-cover border-2 border-white/20">
                                <?php else: ?>
                                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                                        <span class="text-white font-bold">
                                            <?= strtoupper(substr($user['nickname'] ?? $user['full_name'] ?? $user['username'] ?? 'U', 0, 1)) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <div class="min-w-0 flex-1">
                                    <div class="font-semibold truncate">
                                        <?= h($user['nickname'] ?? $user['full_name'] ?? $user['username'] ?? 'ユーザー') ?>
                                    </div>
                                    <div class="text-sm text-white/80">AiNA Works</div>
                                </div>
                            <?php else: ?>
                                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                                    <span class="text-white font-bold">AW</span>
                    </div>
                    <div class="min-w-0 flex-1">
                                    <div class="font-semibold truncate">AiNA Works</div>
                                    <div class="text-sm text-white/80 truncate">クリエイティブプラットフォーム</div>
                    </div>
                            <?php endif; ?>
                </div>
                        <button type="button" onclick="closeMobileMenu()" 
                                class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center hover:bg-white/30 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                    </div>
            </div>

                <!-- Menu Content -->
                <div class="flex-1 overflow-y-auto pb-20">
                    <div class="p-4 space-y-2">
                        <!-- Main Navigation -->
                        <?php foreach ($navItems as $item): ?>
                                <?php 
                                    $isActive = strpos($currentPath, $item['url']) !== false; 
                            $activeClass = $isActive ? 'bg-primary-50 text-primary-600 border-primary-200' : 'text-gray-700 hover:bg-gray-50 border-transparent';
                                ?>
                                    <a href="<?= url($item['url']) ?>" 
                               class="flex items-center space-x-3 p-3 rounded-lg border transition-colors <?= $activeClass ?>">
                                <svg class="w-5 h-5 <?= $isActive ? 'text-primary-600' : 'text-gray-400' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $item['icon'] ?>" />
                                            </svg>
                                <span class="font-medium"><?= $item['label'] ?></span>
                                <?php if (isLoggedIn() && $item['url'] === 'chats'): ?>
                                    <span id="mobile-chat-badge" class="ml-auto w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center hidden">0</span>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>

                        <?php if (isLoggedIn()): ?>
                            <!-- Account Section -->
                            <div class="pt-4 mt-4 border-t border-gray-200">
                                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">アカウント</h3>
                                
                                <a href="<?= url('profile') ?>" 
                                   class="flex items-center space-x-3 p-3 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors <?= strpos($currentPath, 'profile') !== false ? 'bg-primary-50 text-primary-600' : '' ?>">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                    <span class="font-medium">プロフィール</span>
                                </a>

                                <a href="<?= url('favorites') ?>" 
                                   class="flex items-center space-x-3 p-3 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                    <span class="font-medium">お気に入り</span>
                                </a>

                                <a href="<?= url('success-stories') ?>" 
                                   class="flex items-center space-x-3 p-3 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                            </svg>
                                    <span class="font-medium">成功事例</span>
                                </a>
                </div>

                            <!-- Actions Section -->
                            <div class="pt-4 mt-4 border-t border-gray-200">
                    <a href="<?= url('post-job') ?>" 
                                   class="flex items-center justify-center space-x-2 p-3 bg-gradient-to-r from-primary-500 to-primary-600 text-white rounded-lg hover:from-primary-600 hover:to-primary-700 transition-colors">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                                    <span class="font-semibold">案件を投稿</span>
                                </a>

                                <a href="<?= url('logout') ?>" 
                                   class="flex items-center space-x-3 p-3 rounded-lg text-red-600 hover:bg-red-50 transition-colors mt-2">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    <span class="font-medium">ログアウト</span>
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Guest Actions -->
                            <div class="pt-4 mt-4 border-t border-gray-200 space-y-2">
                                <a href="<?= url('login') ?>" 
                                   class="flex items-center justify-center space-x-2 p-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                    </svg>
                                    <span class="font-semibold">ログイン</span>
                                </a>

                                <!-- 登録はAiNA側で行うため、ローカル登録導線は非表示
                                <a href="<?= url('register?type=creator') ?>" 
                                   class="flex items-center justify-center space-x-2 p-3 border-2 border-primary-600 text-primary-600 rounded-lg hover:bg-primary-50 transition-colors">
                                    <span class="font-semibold">クリエイター登録</span>
                                </a>

                                <a href="<?= url('register?type=client') ?>" 
                                   class="flex items-center justify-center space-x-2 p-3 border-2 border-secondary-600 text-secondary-600 rounded-lg hover:bg-secondary-50 transition-colors">
                                    <span class="font-semibold">クライアント登録</span>
                                </a>
                                -->
                            </div>
                        <?php endif; ?>

                        <!-- Footer Links -->
                        <div class="pt-4 mt-4 border-t border-gray-200 space-y-2">
                            <a href="<?= url('terms') ?>" 
                               class="flex items-center space-x-3 p-2 text-sm text-gray-500 hover:text-gray-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                                <span>利用規約</span>
                            </a>

                            <a href="<?= url('privacy') ?>" 
                               class="flex items-center space-x-3 p-2 text-sm text-gray-500 hover:text-gray-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                                <span>プライバシーポリシー</span>
                            </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </header>

    <main class="min-h-screen">
        <!-- ページコンテンツはここに入ります -->

    <script>
        // モバイルメニュー制御（シンプル版）
        let isMenuOpen = false;

        function openMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            const panel = document.getElementById('mobile-menu-panel');
            const body = document.body;
            const html = document.documentElement;
            
            if (isMenuOpen) return;
            
            // シンプルなスクロールロック
            body.classList.add('menu-open');
            html.classList.add('menu-open');
            
            menu.classList.remove('hidden');
            isMenuOpen = true;
            
            // アニメーション開始
            requestAnimationFrame(() => {
                panel.classList.remove('translate-x-full');
            });
        }

        function closeMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            const panel = document.getElementById('mobile-menu-panel');
            const body = document.body;
            const html = document.documentElement;
            
            if (!isMenuOpen) return;
            
            panel.classList.add('translate-x-full');
            isMenuOpen = false;
            
            setTimeout(() => {
                // スクロールロック解除
                body.classList.remove('menu-open');
                html.classList.remove('menu-open');
                
                // メニューを非表示
                menu.classList.add('hidden');
            }, 300);
        }

        // イベントリスナー
        document.addEventListener('DOMContentLoaded', function() {
            // モバイルメニューをbody直下へ移動（スタッキング問題回避）
            // 既にbody直下にある場合もあるため、安全にappend（移動）
            const existingMobileMenu = document.getElementById('mobile-menu');
            if (existingMobileMenu && existingMobileMenu.parentElement !== document.body) {
                document.body.appendChild(existingMobileMenu);
            }

            // 開閉時のフォーカス管理・アニメ付与
            const panel = document.getElementById('mobile-menu-panel');
            const overlay = existingMobileMenu?.querySelector('.absolute.top-0.left-0');
            const menuButton = document.getElementById('mobile-menu-button');
            if (menuButton) {
                // 念のためonclickとaddEventListener両方を設定
                menuButton.addEventListener('click', openMobileMenu);
            }

            // Escapeキーでメニューを閉じる
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && isMenuOpen) {
                closeMobileMenu();
            }
        });

        // 画面サイズ変更時にメニューを閉じる
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024 && isMenuOpen) {
                closeMobileMenu();
            }
        });
    });

    // ユーザーメニュードロップダウン機能
    function toggleUserMenu() {
        const dropdown = document.getElementById('user-menu-dropdown');
        const isHidden = dropdown.classList.contains('hidden');
        
        if (isHidden) {
            dropdown.classList.remove('hidden');
        } else {
            dropdown.classList.add('hidden');
        }
    }

    // ドロップダウン外をクリックした時に閉じる
    document.addEventListener('click', function(event) {
        const userMenu = document.getElementById('user-menu');
        const dropdown = document.getElementById('user-menu-dropdown');
        
        if (userMenu && dropdown && !userMenu.contains(event.target)) {
            dropdown.classList.add('hidden');
        }
    });
    </script>

    <!-- オンボーディングツアー -->
    <script src="<?= asset('js/onboarding.js') ?>"></script>

