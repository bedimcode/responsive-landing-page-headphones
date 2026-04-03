<?php
/**
 * Admin — Newsletter Subscribers
 */
$adminPageTitle = 'Newsletter';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Handle toggle active / delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf()) {
    $action = $_POST['action'] ?? '';
    $sid    = (int)($_POST['sub_id'] ?? 0);

    if ($action === 'toggle' && $sid) {
        $db->prepare('UPDATE newsletter SET is_active = 1 - is_active WHERE id = ?')->execute([$sid]);
    } elseif ($action === 'delete' && $sid) {
        $db->prepare('DELETE FROM newsletter WHERE id = ?')->execute([$sid]);
        setFlash('success', 'Subscriber removed.');
    }
    header('Location: ' . $basePath . '/admin/newsletter.php'); exit;
}

// Stats
$total   = (int) $db->query("SELECT COUNT(*) FROM newsletter")->fetchColumn();
$active  = (int) $db->query("SELECT COUNT(*) FROM newsletter WHERE is_active = 1")->fetchColumn();
$todayNew = (int) $db->query("SELECT COUNT(*) FROM newsletter WHERE DATE(subscribed_at)=CURDATE()")->fetchColumn();

// Filter
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['q'] ?? '');

$where  = [];
$params = [];

if ($filter === 'active')   { $where[] = 'is_active = 1'; }
if ($filter === 'inactive') { $where[] = 'is_active = 0'; }
if ($search !== '') {
    $where[]  = 'email LIKE ?';
    $params[] = '%' . $search . '%';
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$subscribers = $db->prepare("SELECT * FROM newsletter $whereSql ORDER BY subscribed_at DESC");
$subscribers->execute($params);
$subscribers = $subscribers->fetchAll();
?>

<!-- Stats row -->
<div class="grid grid-cols-3 gap-4 mb-8">
    <?php foreach ([
        ['Total Subscribers', $total, 'ri-mail-line', 'text-neon-blue', 'bg-neon-blue/10'],
        ['Active',            $active, 'ri-checkbox-circle-line', 'text-green-400', 'bg-green-500/10'],
        ["New Today",         $todayNew, 'ri-user-add-line', 'text-neon-purple', 'bg-neon-purple/10'],
    ] as [$label, $val, $icon, $color, $bg]): ?>
    <div class="bg-dark-900/60 border border-white/5 rounded-2xl p-5">
        <div class="w-9 h-9 <?= $bg ?> rounded-xl flex items-center justify-center mb-3">
            <i class="<?= $icon ?> <?= $color ?> text-lg"></i>
        </div>
        <div class="font-display text-2xl font-bold text-white"><?= $val ?></div>
        <div class="text-xs text-gray-500 mt-1"><?= e($label) ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filters + Search -->
<div class="flex flex-wrap items-center gap-3 mb-6">
    <div class="flex gap-2">
        <?php foreach (['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'] as $key => $label): ?>
        <a href="?filter=<?= $key ?><?= $search ? '&q=' . rawurlencode($search) : '' ?>"
           class="px-4 py-2 rounded-xl text-sm font-medium transition-colors <?= $filter === $key ? 'bg-neon-blue text-dark-950' : 'bg-dark-900/60 border border-white/10 text-gray-400 hover:text-white' ?>">
            <?= e($label) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <form method="GET" class="flex gap-3 ml-auto">
        <input type="hidden" name="filter" value="<?= e($filter) ?>">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search email…"
            class="px-4 py-2 bg-dark-900/60 border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors w-52">
        <button type="submit" class="px-4 py-2 bg-neon-blue/10 border border-neon-blue/20 rounded-xl text-sm text-neon-blue hover:bg-neon-blue/20 transition-colors">
            <i class="ri-search-line"></i>
        </button>
        <?php if ($search): ?>
        <a href="?filter=<?= e($filter) ?>" class="px-4 py-2 bg-white/5 rounded-xl text-sm text-gray-400 hover:text-white transition-colors">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Table -->
<div class="bg-dark-900/60 border border-white/5 rounded-2xl overflow-hidden">
    <div class="px-6 py-4 border-b border-white/5">
        <span class="text-sm text-gray-500"><?= count($subscribers) ?> subscriber<?= count($subscribers) !== 1 ? 's' : '' ?></span>
    </div>
    <?php if (empty($subscribers)): ?>
    <div class="px-6 py-16 text-center text-gray-600">
        <i class="ri-mail-line text-5xl block mb-3"></i> No subscribers found.
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/5">
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Subscribed</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($subscribers as $sub): ?>
                <tr class="hover:bg-white/2 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-neon-purple/10 flex items-center justify-center text-neon-purple text-xs flex-shrink-0">
                                <i class="ri-mail-line"></i>
                            </div>
                            <span class="text-white font-medium"><?= e($sub['email']) ?></span>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-center">
                        <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-semibold border <?= $sub['is_active'] ? 'bg-green-500/10 text-green-400 border-green-500/20' : 'bg-gray-500/10 text-gray-400 border-gray-500/20' ?>">
                            <?= $sub['is_active'] ? 'Active' : 'Unsubscribed' ?>
                        </span>
                    </td>
                    <td class="px-4 py-4 text-gray-500 text-xs hidden md:table-cell"><?= date('M j, Y H:i', strtotime($sub['subscribed_at'])) ?></td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <form method="POST" class="inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="sub_id" value="<?= (int)$sub['id'] ?>">
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/5 border border-white/10 rounded-lg text-xs text-gray-400 hover:text-white hover:bg-white/10 transition-colors"
                                    title="<?= $sub['is_active'] ? 'Unsubscribe' : 'Re-subscribe' ?>">
                                    <i class="<?= $sub['is_active'] ? 'ri-user-unfollow-line' : 'ri-user-follow-line' ?>"></i>
                                    <?= $sub['is_active'] ? 'Unsub' : 'Resub' ?>
                                </button>
                            </form>
                            <form method="POST" class="inline" onsubmit="return confirm('Remove this subscriber?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="sub_id" value="<?= (int)$sub['id'] ?>">
                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-500/10 border border-red-500/20 rounded-lg text-xs text-red-400 hover:bg-red-500/20 transition-colors">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
