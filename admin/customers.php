<?php
/**
 * Admin — Customers
 */
$adminPageTitle = 'Customers';
require_once __DIR__ . '/includes/header.php';

$db     = getDB();
$search = trim($_GET['q'] ?? '');

$where  = ["u.role = 'customer'"];
$params = [];

if ($search !== '') {
    $where[]  = '(u.name LIKE ? OR u.email LIKE ?)';
    $like      = '%' . $search . '%';
    $params   = [$like, $like];
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$stmt = $db->prepare("
    SELECT u.id, u.name, u.email, u.phone, u.is_active, u.created_at,
           COUNT(DISTINCT o.id) AS order_count,
           COALESCE(SUM(o.total), 0) AS total_spent
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id
    $whereSql
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>

<!-- Search -->
<div class="flex flex-wrap gap-3 mb-6">
    <form method="GET" class="flex gap-3 flex-1">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search by name or email…"
            class="flex-1 min-w-[200px] px-4 py-2.5 bg-dark-900/60 border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors">
        <button type="submit" class="px-4 py-2.5 bg-neon-blue/10 border border-neon-blue/20 rounded-xl text-sm text-neon-blue hover:bg-neon-blue/20 transition-colors">
            <i class="ri-search-line"></i> Search
        </button>
        <?php if ($search): ?>
        <a href="<?= e($basePath) ?>/admin/customers.php" class="px-4 py-2.5 bg-white/5 rounded-xl text-sm text-gray-400 hover:text-white transition-colors">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Customers Table -->
<div class="bg-dark-900/60 border border-white/5 rounded-2xl overflow-hidden">
    <div class="px-6 py-4 border-b border-white/5">
        <span class="text-sm text-gray-500"><?= count($customers) ?> customer<?= count($customers) !== 1 ? 's' : '' ?></span>
    </div>
    <?php if (empty($customers)): ?>
    <div class="px-6 py-16 text-center text-gray-600">
        <i class="ri-user-line text-5xl block mb-3"></i> No customers found.
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/5">
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Phone</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Orders</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Total Spent</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Joined</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($customers as $c): ?>
                <tr class="hover:bg-white/2 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-neon-blue/10 border border-neon-blue/20 flex items-center justify-center text-neon-blue text-xs font-bold flex-shrink-0 uppercase">
                                <?= substr($c['name'], 0, 1) ?>
                            </div>
                            <div>
                                <div class="font-medium text-white text-sm"><?= e($c['name']) ?></div>
                                <div class="text-gray-500 text-xs"><?= e($c['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-gray-400 text-sm hidden md:table-cell"><?= e($c['phone'] ?: '—') ?></td>
                    <td class="px-4 py-4 text-center text-white text-sm hidden sm:table-cell"><?= (int)$c['order_count'] ?></td>
                    <td class="px-4 py-4 text-right font-semibold text-white hidden lg:table-cell">$<?= number_format($c['total_spent'], 2) ?></td>
                    <td class="px-4 py-4 text-center">
                        <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-semibold border <?= $c['is_active'] ? 'bg-green-500/10 text-green-400 border-green-500/20' : 'bg-red-500/10 text-red-400 border-red-500/20' ?>">
                            <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="px-4 py-4 text-gray-500 text-xs hidden lg:table-cell"><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
