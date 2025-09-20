<?php
require_once 'config/config.php';

// ログインチェック
if (!isLoggedIn()) {
    redirect(url('login'));
}

$user = getCurrentUser();
if (!$user) {
    setFlash('error', 'ユーザー情報が見つかりません。再度ログインしてください。');
    redirect(url('login'));
}
$db = Database::getInstance();
$errors = [];
$success = false;

// 既存の銀行口座情報を取得
$bankAccount = null;
try {
    $bankAccount = $db->selectOne(
        "SELECT bank_name, branch_name, account_type, account_number, account_holder_name, account_holder_kana, note 
         FROM user_bank_accounts WHERE user_id = ? AND is_primary = 1",
        [$user['id']]
    );
} catch (Exception $e) {
    error_log('Bank account fetch error: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRFトークン検証
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = '不正なリクエストです。';
    } else {
        // フォームデータ取得
        $fullName = trim($_POST['full_name'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $location = trim($_POST['location'] ?? '');
        
        // 空文字列の場合はNULLに変換
        $bio = empty($bio) ? null : $bio;
        $location = empty($location) ? null : $location;
        $website = trim($_POST['website'] ?? '');
        $twitter = trim($_POST['twitter_url'] ?? '');
        $instagram = trim($_POST['instagram_url'] ?? '');
        $facebook = trim($_POST['facebook_url'] ?? '');
        $linkedin = trim($_POST['linkedin_url'] ?? '');
        $youtube = trim($_POST['youtube_url'] ?? '');
        $tiktok = trim($_POST['tiktok_url'] ?? '');
        
        // 空文字列のURLフィールドはNULLに変換
        $website = empty($website) ? null : $website;
        $twitter = empty($twitter) ? null : $twitter;
        $instagram = empty($instagram) ? null : $instagram;
        $facebook = empty($facebook) ? null : $facebook;
        $linkedin = empty($linkedin) ? null : $linkedin;
        $youtube = empty($youtube) ? null : $youtube;
        $tiktok = empty($tiktok) ? null : $tiktok;
        $birthdate = trim($_POST['birthdate'] ?? '');
        // 空文字列の場合はNULLに変換
        if (empty($birthdate)) {
            $birthdate = null;
        }
        $responseTime = isset($_POST['response_time']) && $_POST['response_time'] !== '' ? (int)$_POST['response_time'] : (int)($user['response_time'] ?? 24);
        $isCreator = isset($_POST['is_creator']) ? 1 : 0;
        $isClient = isset($_POST['is_client']) ? 1 : 0;

        // バリデーション
        if (empty($fullName)) {
            $errors[] = '氏名は必須です。';
        }
        
        // 生年月日の必須チェックと年齢確認
        if (empty($birthdate)) {
            $errors[] = '生年月日は必須です。';
        } else {
            // 日付形式の確認
            $birthdateObj = DateTime::createFromFormat('Y-m-d', $birthdate);
            if (!$birthdateObj || $birthdateObj->format('Y-m-d') !== $birthdate) {
                $errors[] = '生年月日の形式が正しくありません。';
            } else {
                // 年齢計算
                $today = new DateTime();
                $age = $today->diff($birthdateObj)->y;
                
                // 13歳未満チェック
                if ($age < 13) {
                    $errors[] = '13歳未満の方はご利用いただけません。';
                }
                
                // 未来の日付チェック
                if ($birthdateObj > $today) {
                    $errors[] = '生年月日に未来の日付は設定できません。';
                }
                
                // 120歳以上チェック（現実的でない年齢）
                if ($age > 120) {
                    $errors[] = '生年月日を正しく入力してください。';
                }
            }
        }
        
        // URL形式のバリデーション
        $urlFields = [
            'website' => $website,
            'twitter_url' => $twitter,
            'instagram_url' => $instagram,
            'facebook_url' => $facebook,
            'linkedin_url' => $linkedin,
            'youtube_url' => $youtube,
            'tiktok_url' => $tiktok
        ];
        
        foreach ($urlFields as $fieldName => $url) {
            if (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
                $errors[] = ucfirst(str_replace('_', ' ', $fieldName)) . 'の形式が正しくありません。';
            }
        }
        
        // レスポンス時間のバリデーション
        if ($responseTime < 1 || $responseTime > 168) { // 1時間から1週間まで
            $errors[] = 'レスポンス時間は1時間から168時間（1週間）の間で設定してください。';
        }
        

        // 少なくとも一つのロールが選択されている必要がある
        if (!$isCreator && !$isClient) {
            $errors[] = 'クリエイターまたは依頼者のいずれかは選択してください。';
        }

        // 画像アップロード処理
        $profileImagePath = $user['profile_image'] ?? null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            try {
                $profileImagePath = uploadFile($_FILES['profile_image']);
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (empty($errors)) {
            try {
                $db->beginTransaction();
                $userId = (int)($_SESSION['user_id'] ?? 0);
                if ($userId <= 0) {
                    throw new Exception('ユーザーIDが不正です');
                }
                
                // デバッグ用ログ（本番環境では削除）
                if (defined('DEBUG') && DEBUG) {
                    error_log("Profile update values - response_time: " . $responseTime);
                    error_log("Profile update values - fullName: " . $fullName . ", bio length: " . strlen($bio));
                    error_log("Profile update values - birthdate: " . $birthdate . ", isCreator: " . $isCreator . ", isClient: " . $isClient);
                }
                
                // ユーザー情報を更新
                $db->update(
                    "UPDATE users SET full_name = ?, bio = ?, location = ?, website = ?, twitter_url = ?, instagram_url = ?, facebook_url = ?, linkedin_url = ?, youtube_url = ?, tiktok_url = ?, birthdate = ?, response_time = ?, profile_image = ?, is_creator = ?, is_client = ? WHERE id = ?",
                    [$fullName, $bio, $location, $website, $twitter, $instagram, $facebook, $linkedin, $youtube, $tiktok, $birthdate, $responseTime, $profileImagePath, $isCreator, $isClient, $userId]
                );
                
                // user_rolesテーブルを更新
                // 既存のロールを削除
                $db->delete("DELETE FROM user_roles WHERE user_id = ?", [$userId]);
                
                // 新しいロールを追加
                if ($isCreator) {
                    $db->insert("INSERT INTO user_roles (user_id, role, is_enabled) VALUES (?, 'creator', 1)", [$userId]);
                }
                if ($isClient) {
                    $db->insert("INSERT INTO user_roles (user_id, role, is_enabled) VALUES (?, 'client', 1)", [$userId]);
                }
                
                // active_role カラム廃止のため、セッションのみ更新
                $currentRole = getCurrentRole();
                $availableRoles = [];
                if ($isCreator) $availableRoles[] = 'creator';
                if ($isClient) $availableRoles[] = 'client';

                if (!in_array($currentRole, $availableRoles) && !empty($availableRoles)) {
                    $_SESSION['active_role'] = $availableRoles[0];
                }
                
                $db->commit();
                $success = true;
                setFlash('success', 'プロフィールを更新しました。');
                // 更新後のキャッシュを破棄して再取得
                $user = getCurrentUser(true);
            } catch (Exception $e) {
                $db->rollback();
                
                // より具体的なエラーメッセージを提供
                $errorMessage = 'プロフィールの更新に失敗しました。';
                
                // データベースエラーの場合、より詳細な情報を提供
                if (strpos($e->getMessage(), 'SQLSTATE') !== false) {
                    if (strpos($e->getMessage(), 'Data too long') !== false) {
                        $errorMessage = '入力されたデータが長すぎます。文字数を確認してください。';
                    } elseif (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $errorMessage = '重複するデータが検出されました。入力内容を確認してください。';
                    } elseif (strpos($e->getMessage(), 'Invalid date') !== false) {
                        $errorMessage = '日付の形式が正しくありません。';
                    } elseif (strpos($e->getMessage(), 'Column count') !== false) {
                        $errorMessage = 'データベースの構造に問題があります。管理者にお問い合わせください。';
                    } elseif (strpos($e->getMessage(), 'Unknown column') !== false) {
                        $errorMessage = 'データベースの構造に問題があります。管理者にお問い合わせください。';
                    } else {
                        $errorMessage = 'データベースエラーが発生しました。エラー詳細: ' . $e->getMessage();
                    }
                } else {
                    $errorMessage .= ' エラー詳細: ' . $e->getMessage();
                }
                
                $errors[] = $errorMessage;
                
                // デバッグ用（本番環境では削除）
                error_log("Profile update error: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                error_log("Profile update values - fullName: " . $fullName . ", bio: " . substr($bio, 0, 100) . "...");
                error_log("Profile update values - birthdate: " . $birthdate . ", userId: " . $userId);
            }
        }
    }
}

$pageTitle = 'プロフィール編集';
include 'includes/header.php';
?>

<section class="py-4 sm:py-8 bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-6 sm:mb-8 break-words">プロフィール編集</h1>

        <?php if ($success): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p class="font-bold">成功</p>
                <p>プロフィールを更新しました。</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)):
            foreach ($errors as $error) {
                echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6' role='alert'><p>" . h($error) . "</p></div>";
            }
        endif; ?>

        <form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 sm:p-6 lg:p-8 space-y-4 sm:space-y-6">
            <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">

            <div class="flex flex-col sm:flex-row sm:items-center space-y-4 sm:space-y-0 sm:space-x-6">
                <img src="<?= uploaded_asset($user['profile_image'] ?? 'assets/images/default-avatar.png') ?>" alt="プロフィール画像" class="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-cover mx-auto sm:mx-0">
                <div class="text-center sm:text-left">
                    <label for="profile_image" class="block text-sm font-medium text-gray-700 mb-2">プロフィール画像を変更</label>
                    <input type="file" name="profile_image" id="profile_image" class="text-sm text-gray-600 w-full sm:w-auto">
                </div>
            </div>

            <div>
                <label for="full_name" class="block text-sm font-medium text-gray-700">氏名</label>
                <input type="text" name="full_name" id="full_name" value="<?= h($user['full_name'] ?? '') ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>

            <div>
                <label for="birthdate" class="block text-sm font-medium text-gray-700">生年月日 <span class="text-red-500">*</span></label>
                <input type="date" name="birthdate" id="birthdate" value="<?= h($user['birthdate'] ?? '') ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <p class="mt-1 text-xs text-gray-500">13歳未満の方はご利用いただけません。</p>
            </div>

            <div>
                <label for="bio" class="block text-sm font-medium text-gray-700">自己紹介</label>
                <textarea name="bio" id="bio" rows="4" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"><?= h($user['bio'] ?? '') ?></textarea>
            </div>

            <div>
                <label for="location" class="block text-sm font-medium text-gray-700">所在地</label>
                <input type="text" name="location" id="location" value="<?= h($user['location'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>

            <div>
                <label for="website" class="block text-sm font-medium text-gray-700">Webサイト</label>
                <input type="url" name="website" id="website" value="<?= h($user['website'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="https://example.com">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="twitter_url" class="block text-sm font-medium text-gray-700">X(Twitter)</label>
                    <input type="url" name="twitter_url" id="twitter_url" value="<?= h($user['twitter_url'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="https://x.com/">
                </div>
                <div>
                    <label for="instagram_url" class="block text-sm font-medium text-gray-700">Instagram</label>
                    <input type="url" name="instagram_url" id="instagram_url" value="<?= h($user['instagram_url'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="https://instagram.com/">
                </div>
                <div>
                    <label for="facebook_url" class="block text-sm font-medium text-gray-700">Facebook</label>
                    <input type="url" name="facebook_url" id="facebook_url" value="<?= h($user['facebook_url'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="https://facebook.com/">
                </div>
                <div>
                    <label for="linkedin_url" class="block text-sm font-medium text-gray-700">LinkedIn</label>
                    <input type="url" name="linkedin_url" id="linkedin_url" value="<?= h($user['linkedin_url'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="https://linkedin.com/in/">
                </div>
                <div>
                    <label for="youtube_url" class="block text-sm font-medium text-gray-700">YouTube</label>
                    <input type="url" name="youtube_url" id="youtube_url" value="<?= h($user['youtube_url'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="https://youtube.com/@">
                </div>
                <div>
                    <label for="tiktok_url" class="block text-sm font-medium text-gray-700">TikTok</label>
                    <input type="url" name="tiktok_url" id="tiktok_url" value="<?= h($user['tiktok_url'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="https://tiktok.com/@">
                </div>
            </div>

            <div>
                <label for="response_time" class="block text-sm font-medium text-gray-700">平均レスポンス時間（時間）</label>
                <input type="number" min="1" max="168" name="response_time" id="response_time" value="<?= (int)($user['response_time'] ?? 24) ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>

            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">アカウントタイプ設定</h3>
                <p class="text-sm text-gray-600 mb-4">クリエイターと依頼者の両方の機能を使用できます。どちらを使用するか選択してください。</p>
                
                <div class="space-y-3">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_creator" id="is_creator" value="1" <?= ($user['is_creator'] ?? 1) ? 'checked' : '' ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_creator" class="ml-2 block text-sm text-gray-900">
                            <span class="font-medium">クリエイター</span>
                            <span class="text-gray-500">- 作品を投稿し、案件に応募できます</span>
                        </label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="is_client" id="is_client" value="1" <?= ($user['is_client'] ?? 1) ? 'checked' : '' ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_client" class="ml-2 block text-sm text-gray-900">
                            <span class="font-medium">依頼者</span>
                            <span class="text-gray-500">- 案件を投稿し、クリエイターを探せます</span>
                        </label>
                    </div>
                </div>
                
                <div class="mt-4 p-3 bg-blue-50 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                両方のタイプを選択することで、クリエイターとして作品を投稿しながら、依頼者として案件を投稿することも可能です。
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4">
                <button type="submit" class="w-full sm:w-auto px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">保存</button>
            </div>
        </form>

        <!-- 振込口座設定 -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 sm:p-6 lg:p-8 mt-6">
            <div class="border-b pb-4 mb-6">
                <h3 class="text-lg font-medium text-gray-900">振込口座設定</h3>
                <p class="text-sm text-gray-600 mt-1">案件の報酬を受け取るための銀行口座を設定してください。</p>
            </div>

            <form id="bank-account-form" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="bank_name" class="block text-sm font-medium text-gray-700">銀行名 <span class="text-red-500">*</span></label>
                        <input type="text" name="bank_name" id="bank_name" value="<?= h($bankAccount['bank_name'] ?? '') ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="例：三菱UFJ銀行">
                    </div>
                    <div>
                        <label for="branch_name" class="block text-sm font-medium text-gray-700">支店名</label>
                        <input type="text" name="branch_name" id="branch_name" value="<?= h($bankAccount['branch_name'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="例：新宿支店">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="account_type" class="block text-sm font-medium text-gray-700">口座種別</label>
                        <select name="account_type" id="account_type" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="普通" <?= ($bankAccount['account_type'] ?? '普通') === '普通' ? 'selected' : '' ?>>普通</option>
                            <option value="当座" <?= ($bankAccount['account_type'] ?? '') === '当座' ? 'selected' : '' ?>>当座</option>
                            <option value="貯蓄" <?= ($bankAccount['account_type'] ?? '') === '貯蓄' ? 'selected' : '' ?>>貯蓄</option>
                            <option value="その他" <?= ($bankAccount['account_type'] ?? '') === 'その他' ? 'selected' : '' ?>>その他</option>
                        </select>
                    </div>
                    <div>
                        <label for="account_number" class="block text-sm font-medium text-gray-700">口座番号 <span class="text-red-500">*</span></label>
                        <input type="text" name="account_number" id="account_number" value="<?= h($bankAccount['account_number'] ?? '') ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="例：1234567">
                    </div>
                </div>

                <div>
                    <label for="account_holder_name" class="block text-sm font-medium text-gray-700">口座名義 <span class="text-red-500">*</span></label>
                    <input type="text" name="account_holder_name" id="account_holder_name" value="<?= h($bankAccount['account_holder_name'] ?? '') ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="例：田中太郎">
                </div>

                <div>
                    <label for="account_holder_kana" class="block text-sm font-medium text-gray-700">口座名義カナ</label>
                    <input type="text" name="account_holder_kana" id="account_holder_kana" value="<?= h($bankAccount['account_holder_kana'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="例：タナカタロウ">
                </div>

                <div>
                    <label for="note" class="block text-sm font-medium text-gray-700">備考</label>
                    <textarea name="note" id="note" rows="2" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="その他の情報があれば記入してください"><?= h($bankAccount['note'] ?? '') ?></textarea>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit" id="save-bank-account" class="w-full sm:w-auto px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                        振込口座を保存
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
// プロフィールフォームのバリデーション
document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.querySelector('form[method="POST"]');
    
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            let hasErrors = false;
            const errors = [];
            
            // 氏名の必須チェック
            const fullName = document.getElementById('full_name');
            if (!fullName.value.trim()) {
                errors.push('氏名は必須です。');
                hasErrors = true;
            }
            
            // 生年月日の必須チェック
            const birthdate = document.getElementById('birthdate');
            if (!birthdate.value) {
                errors.push('生年月日は必須です。');
                hasErrors = true;
            } else {
                // 年齢チェック
                const birthDate = new Date(birthdate.value);
                const today = new Date();
                const age = today.getFullYear() - birthDate.getFullYear();
                
                if (age < 13) {
                    errors.push('13歳未満の方はご利用いただけません。');
                    hasErrors = true;
                } else if (age > 120) {
                    errors.push('生年月日を正しく入力してください。');
                    hasErrors = true;
                }
            }
            
            // URL形式のチェック
            const urlFields = ['website', 'twitter_url', 'instagram_url', 'facebook_url', 'linkedin_url', 'youtube_url', 'tiktok_url'];
            urlFields.forEach(fieldName => {
                const field = document.getElementById(fieldName);
                if (field && field.value && !isValidUrl(field.value)) {
                    errors.push(fieldName.replace('_', ' ') + 'の形式が正しくありません。');
                    hasErrors = true;
                }
            });
            
            // レスポンス時間のチェック
            const responseTime = document.getElementById('response_time');
            if (responseTime && (responseTime.value < 1 || responseTime.value > 168)) {
                errors.push('レスポンス時間は1時間から168時間（1週間）の間で設定してください。');
                hasErrors = true;
            }
            
            
            // ロールのチェック
            const isCreator = document.getElementById('is_creator');
            const isClient = document.getElementById('is_client');
            if (!isCreator.checked && !isClient.checked) {
                errors.push('クリエイターまたは依頼者のいずれかは選択してください。');
                hasErrors = true;
            }
            
            if (hasErrors) {
                e.preventDefault();
                alert('以下のエラーがあります：\n\n' + errors.join('\n'));
                return false;
            }
        });
    }
    
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
});

// 振込口座フォームの処理
document.addEventListener('DOMContentLoaded', function() {
    const bankForm = document.getElementById('bank-account-form');
    const saveButton = document.getElementById('save-bank-account');

    if (bankForm) {
        bankForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // ボタンを無効化
            if (saveButton) {
                saveButton.disabled = true;
                saveButton.textContent = '保存中...';
            }

            try {
                const formData = new FormData(bankForm);
                
                const response = await fetch('api/save-bank-account.php', {
                    method: 'POST',
                    body: formData
                });

                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const responseText = await response.text();
                console.log('Raw response:', responseText);

                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (jsonError) {
                    console.error('JSON parse error:', jsonError);
                    console.log('Response was not valid JSON:', responseText.substring(0, 200));
                    throw new Error('サーバーから無効なレスポンスが返されました');
                }

                console.log('Parsed result:', result);

                if (result.success) {
                    // 成功メッセージを表示
                    if (typeof showNotification === 'function') {
                        showNotification(result.message || '振込口座を保存しました', 'success');
                    } else {
                        alert(result.message || '振込口座を保存しました');
                    }
                } else {
                    // エラーメッセージを表示
                    const errorMsg = result.error || result.errors?.join(', ') || '保存に失敗しました';
                    if (typeof showNotification === 'function') {
                        showNotification(errorMsg, 'error');
                    } else {
                        alert('エラー: ' + errorMsg);
                    }
                }
            } catch (error) {
                console.error('save bank account error', error);
                const errorMsg = '通信エラーが発生しました: ' + error.message;
                if (typeof showNotification === 'function') {
                    showNotification(errorMsg, 'error');
                } else {
                    alert(errorMsg);
                }
            } finally {
                // ボタンを有効化
                if (saveButton) {
                    saveButton.disabled = false;
                    saveButton.textContent = '振込口座を保存';
                }
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>