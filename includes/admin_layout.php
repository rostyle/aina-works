<?php

function renderAdminHeader(string $title = 'Admin', string $active = ''): void {
    $nav = [
        ['file' => 'index.php',        'key' => 'dashboard',   'label' => 'ダッシュボード', 'icon' => '<path d="M10 3.22l6 3.33v6.9l-6 3.33-6-3.33v-6.9l6-3.33z"/>' ],
        ['file' => 'users.php',         'key' => 'users',       'label' => 'ユーザー',       'icon' => '<path d="M10 10a4 4 0 100-8 4 4 0 000 8zM2 16a6 6 0 1116 0v1H2v-1z"/>' ],
        ['file' => 'works.php',         'key' => 'works',       'label' => '作品',           'icon' => '<path d="M4 3h12a1 1 0 011 1v12l-4-2-4 2-4-2-4 2V4a1 1 0 011-1z"/>' ],
        ['file' => 'jobs.php',          'key' => 'jobs',        'label' => '案件',           'icon' => '<path d="M6 2a2 2 0 00-2 2v1H3a1 1 0 000 2h14a1 1 0 100-2h-1V4a2 2 0 00-2-2H6zm10 7H4v7a2 2 0 002 2h8a2 2 0 002-2V9z"/>' ],
        ['file' => 'applications.php',  'key' => 'applications','label' => '応募',           'icon' => '<path d="M4 3a2 2 0 00-2 2v10l4-2 4 2 4-2 4 2V5a2 2 0 00-2-2H4z"/>' ],
        ['file' => 'reviews.php',       'key' => 'reviews',     'label' => 'レビュー',       'icon' => '<path d="M2 5a2 2 0 012-2h12a2 2 0 012 2v8l-4-3-4 3-4-3-4 3V5z"/>' ],
        ['file' => 'categories.php',    'key' => 'categories',  'label' => 'カテゴリー',     'icon' => '<path d="M4 3h12v4H4V3zm0 6h12v8H4V9z"/>' ],
        ['file' => 'chats.php',         'key' => 'chats',       'label' => 'チャット',       'icon' => '<path d="M18 13a3 3 0 01-3 3H7l-4 3V6a3 3 0 013-3h9a3 3 0 013 3v7z"/>' ],
    ];

    echo '<!DOCTYPE html><html lang="ja"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . h($title) . ' - Admin</title>';
    echo '<link rel="icon" type="image/x-icon" href="' . h(asset('images/favicon.ico')) . '">';
    echo '<script src="https://cdn.tailwindcss.com"></script>';
    echo '<meta name="csrf-token" content="' . h(generateCsrfToken()) . '">';
    echo '</head><body class="bg-gray-50">';
    echo '<header class="bg-white/90 backdrop-blur border-b border-gray-200 sticky top-0 z-30">';
    echo '<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">';
    // Brand
    echo '<a class="flex items-center gap-2 font-semibold text-gray-900" href="' . h(adminUrl('index.php')) . '">';
    echo '<span class="inline-flex w-8 h-8 items-center justify-center rounded-lg bg-gradient-to-br from-blue-600 to-indigo-500 text-white">A</span>';
    echo '<span>AiNA Works 管理</span>';
    echo '</a>';
    // Desktop nav
    echo '<nav class="hidden md:flex items-center gap-1">';
    foreach ($nav as $item) {
        $isActive = ($active === $item['key']);
        $cls = $isActive ? 'bg-blue-600 text-white' : 'text-gray-700 hover:text-gray-900 hover:bg-gray-100';
        $aria = $isActive ? ' aria-current="page"' : '';
        echo '<a class="px-3 py-2 rounded-md text-sm inline-flex items-center gap-2 ' . $cls . '" href="' . h(adminUrl($item['file'])) . '"' . $aria . '>';
        echo '<svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">' . $item['icon'] . '</svg>';
        echo h($item['label']) . '</a>';
    }
    echo '</nav>';
    // Right side
    echo '<div class="hidden md:flex items-center gap-3">';
    echo '<a class="text-sm text-gray-600 hover:text-gray-900" href="' . h(url('', true)) . '">サイトを見る</a>';
    echo '<a class="text-sm text-gray-600 hover:text-gray-900" href="' . h(url('logout', true)) . '">ログアウト</a>';
    echo '</div>';
    // Mobile toggle
    echo '<button type="button" class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-md hover:bg-gray-100" id="admin-mobile-toggle" aria-label="メニュー">';
    echo '<svg class="w-6 h-6 text-gray-700" viewBox="0 0 20 20" fill="currentColor"><path d="M3 6h14M3 10h14M3 14h14"/></svg>';
    echo '</button>';
    echo '</div>';
    // Mobile menu
    echo '<div id="admin-mobile-menu" class="md:hidden hidden border-t border-gray-200">';
    echo '<div class="px-4 py-3 space-y-1 bg-white">';
    foreach ($nav as $item) {
        $isActive = ($active === $item['key']);
        $cls = $isActive ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50';
        echo '<a class="block px-3 py-2 rounded-md text-sm ' . $cls . '" href="' . h(adminUrl($item['file'])) . '">' . h($item['label']) . '</a>';
    }
    echo '<div class="pt-2 mt-2 border-t">';
    echo '<a class="block px-3 py-2 rounded-md text-sm text-gray-700 hover:bg-gray-50" href="' . h(url('', true)) . '">サイトを見る</a>';
    echo '<a class="block px-3 py-2 rounded-md text-sm text-gray-700 hover:bg-gray-50" href="' . h(url('logout', true)) . '">ログアウト</a>';
    echo '</div>';
    echo '</div></div>';
    echo '</header>';
    echo '<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">';
}

function renderAdminFooter(): void {
    echo '</main>';
    // Mobile toggle script
    echo '<script>(function(){var b=document.getElementById("admin-mobile-toggle"),m=document.getElementById("admin-mobile-menu");if(!b||!m)return;b.addEventListener("click",function(){m.classList.toggle("hidden");});})();</script>';
    echo '</body></html>';
}

