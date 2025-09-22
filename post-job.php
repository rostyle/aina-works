<?php
require_once 'config/config.php';

$pageTitle = '案件を投稿';
$pageDescription = '優秀なクリエイターに案件を依頼しましょう';

// ログインチェック
if (!isLoggedIn()) {
    // ログインしていない場合は登録を促す
    $showLoginPrompt = true;
} else {
    $user = getCurrentUser();
    // クリエイターの場合は案件投稿を制限（必要に応じて）
    if (!empty($user['is_creator'])) {
        $isCreator = true;
    }
}

// データベース接続
$db = Database::getInstance();

// カテゴリ一覧取得
$categories = $db->select("
    SELECT * FROM categories 
    WHERE is_active = 1 
    ORDER BY sort_order ASC
");


$errors = [];
$formData = [
    'title' => '',
    'description' => '',
    'category_id' => '',
    'budget_min' => '',
    'budget_max' => '',
    'duration_weeks' => '',
    'urgency' => 'medium',
    'deadline' => ''
];

// フォーム送信処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ログインチェック
    if (!isLoggedIn()) {
        $errors[] = 'ログインが必要です。';
    } elseif (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = '不正なリクエストです。';
    } else {
        // フォームデータ取得
        $formData['title'] = trim($_POST['title'] ?? '');
        $formData['description'] = trim($_POST['description'] ?? '');
        $formData['category_id'] = (int)($_POST['category_id'] ?? 0);
        $formData['budget_min'] = (int)($_POST['budget_min'] ?? 0);
        $formData['budget_max'] = (int)($_POST['budget_max'] ?? 0);
        $formData['duration_weeks'] = (int)($_POST['duration_weeks'] ?? 1);
        $formData['urgency'] = $_POST['urgency'] ?? 'medium';
        $formData['deadline'] = $_POST['deadline'] ?? '';

        // バリデーション
        if (empty($formData['title'])) {
            $errors[] = '案件タイトルは必須です。';
        } elseif (mb_strlen($formData['title']) > 200) {
            $errors[] = '案件タイトルは200文字以内で入力してください。';
        }

        if (empty($formData['description'])) {
            $errors[] = '案件の詳細説明は必須です。';
        } elseif (mb_strlen($formData['description']) < 50) {
            $errors[] = '案件の詳細説明は50文字以上で入力してください。';
        }

        if (empty($formData['category_id'])) {
            $errors[] = 'カテゴリを選択してください。';
        }

        // 予算のバリデーション（クリエイターの場合は任意）
        $currentUser = getCurrentUser();
        $isCreatorUser = $currentUser && !empty($currentUser['is_creator']);
        
        if (!$isCreatorUser) {
            // 依頼者の場合は予算必須
            if ($formData['budget_min'] <= 0) {
                $errors[] = '予算の下限を入力してください。';
            }

            if ($formData['budget_max'] <= 0) {
                $errors[] = '予算の上限を入力してください。';
            }
        }

        if ($formData['budget_min'] > 0 && $formData['budget_max'] > 0 && $formData['budget_min'] > $formData['budget_max']) {
            $errors[] = '予算の上限は下限より大きい値を入力してください。';
        }

        if ($formData['duration_weeks'] <= 0) {
            $errors[] = '期間を入力してください。';
        }

        if (!in_array($formData['urgency'], ['low', 'medium', 'high'])) {
            $formData['urgency'] = 'medium';
        }

        if (!empty($formData['deadline']) && strtotime($formData['deadline']) < time()) {
            $errors[] = '締切日は未来の日付を入力してください。';
        }

        // エラーがない場合は保存
        if (empty($errors)) {
            try {
                $db->beginTransaction();

                // 仮のクライアントID（実際はセッションから取得）
                $clientId = getCurrentUser()['id'] ?? 1;

                $jobId = $db->insert("
                    INSERT INTO jobs (
                        client_id, title, description, category_id,
                        budget_min, budget_max, duration_weeks,
                        required_skills, urgency, deadline
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $clientId,
                    $formData['title'],
                    $formData['description'],
                    $formData['category_id'],
                    $formData['budget_min'],
                    $formData['budget_max'],
                    $formData['duration_weeks'],
                    json_encode([]), // 空のJSON配列を既定で保存（CHECK json_valid 対策）
                    $formData['urgency'],
                    $formData['deadline'] ?: null
                ]);

                $db->commit();

                setFlash('success', '案件を投稿しました。');
                redirect(url('job-detail?id=' . $jobId));

            } catch (Exception $e) {
                $db->rollback();
                error_log('[post-job] INSERT失敗: ' . $e->getMessage());
                $errors[] = DEBUG ? ('案件の投稿に失敗しました: ' . $e->getMessage()) : '案件の投稿に失敗しました。再度お試しください。';
            }
        }
    }
}

include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-6">案件を投稿</h1>
        <p class="text-xl text-blue-100">
            優秀なクリエイターに依頼したい案件を投稿しましょう
        </p>
    </div>
</section>

<!-- Form Section -->
<section class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <?php if (isset($showLoginPrompt) && $showLoginPrompt): ?>
            <!-- ログイン促進セクション -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 mb-8 text-center">
                <div class="mb-6">
                    <svg class="mx-auto h-16 w-16 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">案件投稿にはログインが必要です</h2>
                <p class="text-gray-600 mb-8 max-w-2xl mx-auto">
                    AiNA Worksで案件を投稿するには、アカウントが必要です。<br>
                    既にアカウントをお持ちの場合はログインしてください。
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="<?= url('login') ?>" class="inline-flex items-center px-6 py-3 border border透明 text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        ログイン
                    </a>
                    <!-- 登録導線はAiNA側で実施するため非表示 -->
                </div>
            </div>
        <?php else: ?>
        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-8">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-red-800 mb-2">入力エラーがあります</h3>
                        <ul class="text-sm text-red-700 space-y-1">
                            <?php foreach ($errors as $error): ?>
                                <li><?= h($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 space-y-8">
            <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">

            <!-- Basic Information -->
            <div class="space-y-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">基本情報</h2>
                </div>

                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                        案件タイトル <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           value="<?= h($formData['title']) ?>"
                           maxlength="200"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                           placeholder="例：企業サイトのロゴデザイン制作">
                    <p class="text-sm text-gray-500 mt-1">200文字以内で入力してください</p>
                </div>

                <!-- Category -->
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                        カテゴリ <span class="text-red-500">*</span>
                    </label>
                    <select id="category_id" 
                            name="category_id" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="">カテゴリを選択してください</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= h($category['id']) ?>" <?= $formData['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                <?= h($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        案件の詳細説明 <span class="text-red-500">*</span>
                    </label>
                    <textarea id="description" 
                              name="description" 
                              rows="8" 
                              required
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-vertical"
                              placeholder="案件の詳細な内容、要件、期待する成果物などを具体的に記載してください。（最低50文字以上）"><?= h($formData['description']) ?></textarea>
                    <p class="text-sm text-gray-500 mt-1">50文字以上で詳細に記載してください</p>
                    <div class="mt-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <button type="button" id="ai-director-run" class="btn btn-secondary btn-sm btn-shimmer">
                                <svg class="w-4 h-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                AIディレクターで添削・相場提案
                            </button>
                            <button type="button" id="ai-history-btn" class="px-3 py-2 text-sm border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                履歴
                            </button>
                        </div>
                        <div class="flex-1 ml-6">
                            <div class="w-full bg-gray-200 rounded-full overflow-hidden"><div id="ai-director-progress" class="ai-progress" style="width:0%"></div></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Budget and Schedule -->
            <div class="space-y-6 border-t border-gray-200 pt-8">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">予算・スケジュール</h2>
                </div>

                <!-- Budget -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="budget_min" class="block text-sm font-medium text-gray-700 mb-2">
                            予算下限 
                            <?php if (!isset($isCreator) || !$isCreator): ?>
                                <span class="text-red-500">*</span>
                            <?php else: ?>
                                <span class="text-gray-500">(任意)</span>
                            <?php endif; ?>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">¥</span>
                            <input type="number" 
                                   id="budget_min" 
                                   name="budget_min" 
                                   value="<?= h($formData['budget_min']) ?>"
                                   min="1000"
                                   step="1000"
                                   <?php if (!isset($isCreator) || !$isCreator): ?>required<?php endif; ?>
                                   class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                   placeholder="50000">
                        </div>
                    </div>

                    <div>
                        <label for="budget_max" class="block text-sm font-medium text-gray-700 mb-2">
                            予算上限 
                            <?php if (!isset($isCreator) || !$isCreator): ?>
                                <span class="text-red-500">*</span>
                            <?php else: ?>
                                <span class="text-gray-500">(任意)</span>
                            <?php endif; ?>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">¥</span>
                            <input type="number" 
                                   id="budget_max" 
                                   name="budget_max" 
                                   value="<?= h($formData['budget_max']) ?>"
                                   min="1000"
                                   step="1000"
                                   <?php if (!isset($isCreator) || !$isCreator): ?>required<?php endif; ?>
                                   class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                   placeholder="100000">
                        </div>
                    </div>
                </div>

                <!-- Duration -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="duration_weeks" class="block text-sm font-medium text-gray-700 mb-2">
                            期間（週） <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               id="duration_weeks" 
                               name="duration_weeks" 
                               value="<?= h($formData['duration_weeks']) ?>"
                               min="1"
                               max="52"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                               placeholder="4">
                        <p class="text-sm text-gray-500 mt-1">予想される作業期間を週単位で入力</p>
                    </div>

                    <div>
                        <label for="deadline" class="block text-sm font-medium text-gray-700 mb-2">
                            締切日（任意）
                        </label>
                        <input type="date" 
                               id="deadline" 
                               name="deadline" 
                               value="<?= h($formData['deadline']) ?>"
                               min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <p class="text-sm text-gray-500 mt-1">応募締切日を設定する場合</p>
                    </div>
                </div>

                <!-- Urgency -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">緊急度</label>
                    <div class="flex space-x-6">
                        <label class="flex items-center">
                            <input type="radio" 
                                   name="urgency" 
                                   value="low" 
                                   <?= $formData['urgency'] === 'low' ? 'checked' : '' ?>
                                   class="mr-2 text-purple-600 focus:ring-purple-500">
                            <span class="text-sm text-gray-700">低（急がない）</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" 
                                   name="urgency" 
                                   value="medium" 
                                   <?= $formData['urgency'] === 'medium' ? 'checked' : '' ?>
                                   class="mr-2 text-purple-600 focus:ring-purple-500">
                            <span class="text-sm text-gray-700">中（通常）</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" 
                                   name="urgency" 
                                   value="high" 
                                   <?= $formData['urgency'] === 'high' ? 'checked' : '' ?>
                                   class="mr-2 text-purple-600 focus:ring-purple-500">
                            <span class="text-sm text-gray-700">高（急ぎ）</span>
                        </label>
                    </div>
                </div>
            </div>


            <!-- Submit Button -->
            <div class="border-t border-gray-200 pt-8">
                <div class="flex justify-end space-x-4">
                    <a href="<?= url('jobs') ?>" 
                       class="px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        キャンセル
                    </a>
                    <button type="submit" 
                            class="px-8 py-3 bg-purple-600 text-white font-semibold rounded-lg hover:bg-purple-700 transition-colors shadow-lg">
                        案件を投稿する
                    </button>
                </div>
            </div>
        </form>
        <div id="ai-director-spark" class="ai-spark opacity-0 scale-50 px-3 py-1 bg-gradient-to-r from-primary-500 to-secondary-600 text-white rounded-full shadow-lg text-sm">+50 XP</div>
        
        <!-- AI Director Dock Panel -->
        <div id="ai-director-dock" class="ai-dock">
            <div class="ai-dock-header flex items-center justify-between px-4 py-3 border-b bg-gradient-to-r from-blue-50 to-purple-50">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    <span class="font-semibold text-gray-800">AIディレクター提案</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="ai-dock-minimize" class="px-2 py-1 text-xs rounded-md bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 transition-colors" title="最小化">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                        </svg>
                    </button>
                    <button type="button" id="ai-dock-close" class="px-2 py-1 text-xs rounded-md bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 transition-colors" title="閉じる">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div id="ai-director-dock-body" class="ai-dock-body overflow-y-auto">
                <div class="p-4 text-center text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    <p>AIディレクターの提案がここに表示されます</p>
                </div>
            </div>
        </div>

        <!-- AI History Modal -->
        <div id="ai-history-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
                    <div class="flex items-center justify-between p-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">AI提案履歴</h3>
                        <button type="button" id="ai-history-close" class="text-gray-500 hover:text-gray-700 text-xl">✕</button>
                    </div>
                    <div id="ai-history-body" class="p-4 overflow-y-auto max-h-[60vh]">
                        <div class="text-center text-gray-500 py-8">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p>まだ履歴がありません</p>
                            <p class="text-sm">AIディレクターを使用すると、ここに履歴が表示されます</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Info Section -->
<section class="bg-white py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">案件投稿の流れ</h2>
            <p class="text-lg text-gray-600">簡単3ステップで優秀なクリエイターとマッチング</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-2xl font-bold">1</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">案件情報を入力</h3>
                <p class="text-gray-600">依頼したい案件の詳細、予算、期間などを入力してください。</p>
            </div>

            <div class="text-center">
                <div class="w-16 h-16 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-2xl font-bold">2</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">クリエイターから応募</h3>
                <p class="text-gray-600">投稿した案件に興味を持ったクリエイターから応募が届きます。</p>
            </div>

            <div class="text-center">
                <div class="w-16 h-16 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-2xl font-bold">3</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">最適な人材を選択</h3>
                <p class="text-gray-600">応募者の中から最適なクリエイターを選んでプロジェクトを開始。</p>
            </div>
        </div>
    </div>
</section>

<script>
// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const budgetMin = document.getElementById('budget_min');
    const budgetMax = document.getElementById('budget_max');

    function validateBudget() {
        const min = parseInt(budgetMin.value) || 0;
        const max = parseInt(budgetMax.value) || 0;
        
        if (min > 0 && max > 0 && min > max) {
            budgetMax.setCustomValidity('予算の上限は下限より大きい値を入力してください');
        } else {
            budgetMax.setCustomValidity('');
        }
    }

    budgetMin.addEventListener('input', validateBudget);
    budgetMax.addEventListener('input', validateBudget);

    // Character count for title
    const titleInput = document.getElementById('title');
    const titleCounter = document.createElement('div');
    titleCounter.className = 'text-sm text-gray-500 mt-1';
    titleInput.parentNode.appendChild(titleCounter);

    function updateTitleCounter() {
        const length = titleInput.value.length;
        titleCounter.textContent = `${length}/200文字`;
        if (length > 200) {
            titleCounter.className = 'text-sm text-red-500 mt-1';
        } else {
            titleCounter.className = 'text-sm text-gray-500 mt-1';
        }
    }

    titleInput.addEventListener('input', updateTitleCounter);
    updateTitleCounter();

    // Character count for description
    const descInput = document.getElementById('description');
    const descCounter = document.createElement('div');
    descCounter.className = 'text-sm text-gray-500 mt-1';
    descInput.parentNode.appendChild(descCounter);

    function updateDescCounter() {
        const length = descInput.value.length;
        descCounter.textContent = `${length}文字（最低50文字）`;
        if (length < 50) {
            descCounter.className = 'text-sm text-red-500 mt-1';
        } else {
            descCounter.className = 'text-sm text-green-600 mt-1';
        }
    }

    descInput.addEventListener('input', updateDescCounter);
    updateDescCounter();
});
</script>

<script src="<?= asset('js/ai-director.js') ?>"></script>

<?php include 'includes/footer.php'; ?>
