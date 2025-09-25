<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
requireAdmin();

$db = Database::getInstance();

$roomId = (int)($_GET['room_id'] ?? 0);

// Load rooms
$rooms = $db->select(
    "SELECT cr.id, cr.created_at,
            u1.id AS u1_id, u1.full_name AS u1_name,
            u2.id AS u2_id, u2.full_name AS u2_name,
            (SELECT MAX(created_at) FROM chat_messages WHERE room_id = cr.id) AS last_message_at
       FROM chat_rooms cr
       LEFT JOIN users u1 ON cr.user1_id = u1.id
       LEFT JOIN users u2 ON cr.user2_id = u2.id
       ORDER BY COALESCE(last_message_at, cr.created_at) DESC, cr.id DESC
       LIMIT 200"
);

$messages = [];
if ($roomId > 0) {
    $messages = $db->select(
        "SELECT cm.id, cm.sender_id, u.full_name AS sender_name, cm.message, cm.file_path, cm.created_at
           FROM chat_messages cm
           LEFT JOIN users u ON cm.sender_id = u.id
           WHERE cm.room_id = ?
           ORDER BY cm.id DESC
           LIMIT 50",
        [$roomId]
    );
    $messages = array_reverse($messages);
}

renderAdminHeader('チャット監視', 'chats');
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <div class="bg-white border rounded-xl overflow-hidden">
    <div class="px-4 py-3 border-b bg-gray-50 font-semibold text-gray-900">チャットルーム</div>
    <div class="divide-y max-h-[70vh] overflow-auto">
      <?php foreach ($rooms as $r): ?>
        <a href="<?= h(adminUrl('chats.php?room_id=' . (int)$r['id'])) ?>" class="block px-4 py-3 hover:bg-gray-50 <?= $roomId===(int)$r['id'] ? 'bg-blue-50' : '' ?>">
          <div class="text-sm text-gray-900 font-medium">#<?= (int)$r['id'] ?> <?= h($r['u1_name'] ?? '？') ?> × <?= h($r['u2_name'] ?? '？') ?></div>
          <div class="text-xs text-gray-500">更新: <?= h($r['last_message_at'] ?: $r['created_at']) ?></div>
        </a>
      <?php endforeach; ?>
      <?php if (empty($rooms)): ?>
        <div class="px-4 py-6 text-center text-gray-500">ルームがありません</div>
      <?php endif; ?>
    </div>
  </div>
  <div class="lg:col-span-2 bg-white border rounded-xl overflow-hidden">
    <div class="px-4 py-3 border-b bg-gray-50 font-semibold text-gray-900">メッセージ</div>
    <div class="p-4 space-y-3 max-h-[70vh] overflow-auto">
      <?php if ($roomId === 0): ?>
        <div class="text-gray-500">左のリストからルームを選択してください</div>
      <?php elseif (empty($messages)): ?>
        <div class="text-gray-500">メッセージがありません</div>
      <?php else: ?>
        <?php foreach ($messages as $m): ?>
          <div class="border rounded-lg px-3 py-2">
            <div class="text-xs text-gray-500">#<?= (int)$m['sender_id'] ?> <?= h($m['sender_name'] ?? '-') ?> ・ <?= h($m['created_at']) ?></div>
            <?php if (!empty($m['message'])): ?>
              <div class="text-sm text-gray-900 whitespace-pre-wrap"><?= h($m['message']) ?></div>
            <?php endif; ?>
            <?php if (!empty($m['file_path'])): ?>
              <div class="mt-1"><a class="text-blue-600 underline text-sm" href="<?= h(uploaded_asset($m['file_path'])) ?>" target="_blank" rel="noopener">ファイルを開く</a></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php renderAdminFooter();
