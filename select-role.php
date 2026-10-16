<?php
/**
 * 役割の選択（クリエイターとして受注する／依頼者として発注する）
 * ログイン直後や「応募する」「作品を投稿」から呼ばれる短い画面。プロフィールの全項目を埋めなくても始められる。
 */
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect(url('login?redirect=' . rawurlencode('select-role')));
}

$db = Database::getInstance();
$user = getCurrentUser();
if (!$user) {
    redirect(url('logout'));
}

$redirectRaw = is_string($_REQUEST['redirect'] ?? null) ? $_REQUEST['redirect'] : '';
$want = is_string($_GET['role'] ?? null) ? $_GET['role'] : '';
$needBirthdate = empty($user['birthdate']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = '画面の有効期限が切れました。もう一度お試しください。';
    } else {
        $isCreator = !empty($_POST['is_creator']) ? 1 : 0;
        $isClient = !empty($_POST['is_client']) ? 1 : 0;
        $birthdate = $needBirthdate ? trim((string)($_POST['birthdate'] ?? '')) : (string)$user['birthdate'];

        if (!$isCreator && !$isClient) {
            $errors[] = 'どちらか1つ以上を選んでください。';
        }
        if ($needBirthdate && ($err = validateBirthdateForUse($birthdate))) {
            $errors[] = $err;
        }

        if (empty($errors)) {
            try {
                // 既に持っている役割は外さない（ここでは追加だけ）
                $db->update(
                    "UPDATE users SET is_creator = GREATEST(is_creator, ?), is_client = GREATEST(is_client, ?), birthdate = COALESCE(birthdate, ?) WHERE id = ?",
                    [$isCreator, $isClient, $needBirthdate ? $birthdate : null, $user['id']]
                );
                setFlash('success', '設定しました。' . ($isCreator ? '案件に応募したり作品を載せたりできます。' : '案件を投稿できます。'));
                redirect(safeRedirectUrl($redirectRaw, 'dashboard'));
            } catch (Exception $e) {
                error_log('select-role: ' . $e->getMessage());
                $errors[] = '保存できませんでした。時間をおいて再度お試しください。';
            }
        }
    }
}

$pageTitle = 'はじめに：使い方を選ぶ';
$pageDescription = 'AiNA Worksでの使い方（受注・発注）を選びます';
$noindex = true;
include 'includes/header.php';
?>
<section class="min-h-[70vh] bg-gray-50 py-10 px-4">
    <div class="max-w-lg mx-auto bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">AiNA Worksをどう使いますか？</h1>
        <p class="text-sm text-gray-600 mb-6">あとからプロフィールでいつでも変更できます。両方選んでも大丈夫です。</p>

        <?php if (!empty($errors)): ?>
            <div class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700" role="alert">
                <?php foreach ($errors as $e): ?><p><?= h($e) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= h(url('select-role')) ?>" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
            <input type="hidden" name="redirect" value="<?= h($redirectRaw) ?>">

            <label class="flex items-start gap-3 p-4 border rounded-xl cursor-pointer hover:border-blue-400 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50">
                <input type="checkbox" name="is_creator" value="1" class="mt-1 h-5 w-5 text-blue-600 rounded" <?= ($want === 'creator' || !empty($user['is_creator']) || ($want === '' && empty($user['is_client']))) ? 'checked' : '' ?>>
                <span>
                    <span class="block font-semibold text-gray-900">仕事を受けたい（クリエイター）</span>
                    <span class="block text-sm text-gray-600">案件に応募する・作品を載せる</span>
                </span>
            </label>

            <label class="flex items-start gap-3 p-4 border rounded-xl cursor-pointer hover:border-blue-400 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50">
                <input type="checkbox" name="is_client" value="1" class="mt-1 h-5 w-5 text-blue-600 rounded" <?= ($want === 'client' || !empty($user['is_client'])) ? 'checked' : '' ?>>
                <span>
                    <span class="block font-semibold text-gray-900">仕事を頼みたい（依頼者）</span>
                    <span class="block text-sm text-gray-600">案件を投稿してクリエイターに頼む</span>
                </span>
            </label>

            <?php if ($needBirthdate): ?>
                <div>
                    <label for="birthdate" class="block text-sm font-medium text-gray-700 mb-1">生年月日 <span class="text-red-500">*</span></label>
                    <input type="date" id="birthdate" name="birthdate" required max="<?= date('Y-m-d') ?>"
                           value="<?= h($_POST['birthdate'] ?? '') ?>"
                           class="w-full px-3 py-3 border border-gray-300 rounded-lg text-base">
                    <p class="text-xs text-gray-500 mt-1">13歳以上の方がご利用いただけます。公開はされません。</p>
                </div>
            <?php endif; ?>

            <button type="submit" class="w-full py-3 rounded-xl bg-blue-600 text-white font-bold text-base hover:bg-blue-700">はじめる</button>
            <p class="text-center text-sm">
                <a href="<?= h(url('dashboard')) ?>" class="text-gray-500 underline">あとで設定する</a>
            </p>
        </form>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
