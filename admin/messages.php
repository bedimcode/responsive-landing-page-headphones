<?php
/**
 * Admin — Contact Messages
 */
$adminPageTitle = 'Messages';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Handle mark read / delete via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf()) {
    $action = $_POST['action'] ?? '';
    $mid    = (int)($_POST['message_id'] ?? 0);

    if ($action === 'mark_read' && $mid) {
        $db->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')->execute([$mid]);
    } elseif ($action === 'mark_unread' && $mid) {
        $db->prepare('UPDATE contact_messages SET is_read = 0 WHERE id = ?')->execute([$mid]);
    } elseif ($action === 'delete' && $mid) {
        $db->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$mid]);
        setFlash('success', 'Message deleted.');
    } elseif ($action === 'mark_all_read') {
        $db->query('UPDATE contact_messages SET is_read = 1 WHERE is_read = 0');
        setFlash('success', 'All messages marked as read.');
    }
    header('Location: ' . $basePath . '/admin/messages.php'); exit;
}

// Filter
$filter = $_GET['filter'] ?? 'all'; // all | unread | read
$where  = [];
if ($filter === 'unread') $where[] = 'is_read = 0';
if ($filter === 'read')   $where[] = 'is_read = 1';
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$messages = $db->query("SELECT * FROM contact_messages $whereSql ORDER BY created_at DESC")->fetchAll();
$unread   = (int) $db->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();

// Expanded message (view inline)
$viewId   = (int)($_GET['view'] ?? 0);
$viewMsg  = null;
if ($viewId) {
    $stmt = $db->prepare('SELECT * FROM contact_messages WHERE id = ?');
    $stmt->execute([$viewId]);
    $viewMsg = $stmt->fetch();
    // Mark as read automatically
    if ($viewMsg && !$viewMsg['is_read']) {
        $db->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')->execute([$viewId]);
        $viewMsg['is_read'] = 1;
    }
}
?>

<!-- Filters + action bar -->
<div class="flex flex-wrap items-center gap-3 mb-6">
    <div class="flex gap-2">
        <?php foreach (['all' => 'All', 'unread' => "Unread ($unread)", 'read' => 'Read'] as $key => $label): ?>
        <a href="?filter=<?= $key ?>"
           class="px-4 py-2 rounded-xl text-sm font-medium transition-colors <?= $filter === $key ? 'bg-neon-blue text-dark-950' : 'bg-dark-900/60 border border-white/10 text-gray-400 hover:text-white' ?>">
            <?= e($label) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php if ($unread > 0): ?>
    <form method="POST" class="ml-auto">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="mark_all_read">
        <button type="submit" class="px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-sm text-gray-400 hover:text-white transition-colors">
            <i class="ri-checkbox-circle-line mr-1"></i> Mark all read
        </button>
    </form>
    <?php endif; ?>
</div>

<!-- View message panel -->
<?php if ($viewMsg): ?>
<div class="bg-dark-900/60 border border-neon-blue/20 rounded-2xl p-6 mb-6">
    <div class="flex items-start justify-between gap-4 mb-4">
        <div>
            <h2 class="font-semibold text-white text-lg mb-1"><?= e($viewMsg['subject']) ?></h2>
            <div class="flex items-center gap-3 text-sm text-gray-500">
                <span><i class="ri-user-line mr-1"></i><?= e($viewMsg['name']) ?></span>
                <span><i class="ri-mail-line mr-1"></i><a href="mailto:<?= e($viewMsg['email']) ?>" class="text-neon-blue hover:underline"><?= e($viewMsg['email']) ?></a></span>
                <span><i class="ri-time-line mr-1"></i><?= date('M j, Y H:i', strtotime($viewMsg['created_at'])) ?></span>
            </div>
        </div>
        <a href="<?= e($basePath) ?>/admin/messages.php?filter=<?= e($filter) ?>" class="text-gray-500 hover:text-white transition-colors flex-shrink-0">
            <i class="ri-close-line text-xl"></i>
        </a>
    </div>
    <div class="bg-dark-800 rounded-xl p-4 text-gray-300 text-sm leading-relaxed whitespace-pre-wrap"><?= e($viewMsg['message']) ?></div>
    <div class="mt-4">
        <a href="mailto:<?= e($viewMsg['email']) ?>?subject=Re: <?= rawurlencode($viewMsg['subject']) ?>"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-neon-blue/10 border border-neon-blue/20 text-neon-blue rounded-xl text-sm font-medium hover:bg-neon-blue/20 transition-colors">
            <i class="ri-reply-line"></i> Reply via Email
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Messages Table -->
<div class="bg-dark-900/60 border border-white/5 rounded-2xl overflow-hidden">
    <div class="px-6 py-4 border-b border-white/5">
        <span class="text-sm text-gray-500"><?= count($messages) ?> message<?= count($messages) !== 1 ? 's' : '' ?></span>
    </div>
    <?php if (empty($messages)): ?>
    <div class="px-6 py-16 text-center text-gray-600">
        <i class="ri-message-3-line text-5xl block mb-3"></i> No messages found.
    </div>
    <?php else: ?>
    <div class="divide-y divide-white/5">
        <?php foreach ($messages as $msg): ?>
        <?php $isView = $viewId === (int)$msg['id']; ?>
        <div class="flex items-start gap-4 px-6 py-4 hover:bg-white/2 transition-colors <?= !$msg['is_read'] ? 'border-l-2 border-neon-blue' : '' ?>">
            <!-- Unread dot -->
            <div class="flex-shrink-0 mt-1">
                <?php if (!$msg['is_read']): ?>
                <div class="w-2 h-2 rounded-full bg-neon-blue"></div>
                <?php else: ?>
                <div class="w-2 h-2 rounded-full bg-transparent"></div>
                <?php endif; ?>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-medium text-white text-sm <?= !$msg['is_read'] ? 'font-bold' : '' ?>"><?= e($msg['name']) ?></span>
                    <span class="text-gray-600 text-xs"><?= e($msg['email']) ?></span>
                    <span class="ml-auto text-gray-600 text-xs"><?= date('M j, Y H:i', strtotime($msg['created_at'])) ?></span>
                </div>
                <div class="text-sm <?= !$msg['is_read'] ? 'text-white' : 'text-gray-400' ?> mt-0.5"><?= e($msg['subject']) ?></div>
                <div class="text-xs text-gray-600 mt-0.5 truncate"><?= e(substr($msg['message'], 0, 120)) ?></div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                <a href="?view=<?= (int)$msg['id'] ?>&filter=<?= e($filter) ?>"
                   class="w-8 h-8 flex items-center justify-center rounded-lg bg-white/5 text-gray-400 hover:text-neon-blue hover:bg-neon-blue/10 transition-colors text-sm" title="View">
                    <i class="ri-eye-line"></i>
                </a>
                <form method="POST" class="inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="<?= $msg['is_read'] ? 'mark_unread' : 'mark_read' ?>">
                    <input type="hidden" name="message_id" value="<?= (int)$msg['id'] ?>">
                    <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white/5 text-gray-400 hover:text-white hover:bg-white/10 transition-colors text-sm"
                        title="<?= $msg['is_read'] ? 'Mark unread' : 'Mark read' ?>">
                        <i class="<?= $msg['is_read'] ? 'ri-mail-unread-line' : 'ri-mail-check-line' ?>"></i>
                    </button>
                </form>
                <form method="POST" class="inline" onsubmit="return confirm('Delete this message?')">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="message_id" value="<?= (int)$msg['id'] ?>">
                    <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white/5 text-red-400/60 hover:text-red-400 hover:bg-red-500/10 transition-colors text-sm" title="Delete">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
