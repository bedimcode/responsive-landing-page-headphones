<?php
/**
 * Admin — Products List
 */
$adminPageTitle = 'Products';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Handle toggle active via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf()) {
    $action = $_POST['action'] ?? '';
    $pid    = (int)($_POST['product_id'] ?? 0);

    if ($action === 'toggle' && $pid) {
        $db->prepare('UPDATE products SET is_active = 1 - is_active WHERE id = ?')->execute([$pid]);
        setFlash('success', 'Product status updated.');
    } elseif ($action === 'delete' && $pid) {
        $db->prepare('DELETE FROM products WHERE id = ?')->execute([$pid]);
        setFlash('success', 'Product deleted.');
    }
    header('Location: ' . $basePath . '/admin/products.php'); exit;
}

// Fetch categories for filter dropdown
$categories = $db->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

$catFilter = (int)($_GET['cat'] ?? 0);
$search    = trim($_GET['q'] ?? '');

$where  = [];
$params = [];

if ($catFilter) { $where[] = 'p.category_id = ?'; $params[] = $catFilter; }
if ($search)    { $where[] = '(p.name LIKE ? OR p.sku LIKE ?)'; $like = '%'.$search.'%'; $params = array_merge($params, [$like, $like]); }

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $db->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    $whereSql
    ORDER BY p.featured DESC, p.created_at DESC
");
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<!-- Top bar -->
<div class="flex flex-wrap gap-3 mb-6 items-center">
    <form method="GET" class="flex flex-wrap gap-3 flex-1">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search by name or SKU…"
            class="flex-1 min-w-[180px] px-4 py-2.5 bg-dark-900/60 border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors">
        <select name="cat" onchange="this.form.submit()"
            class="px-4 py-2.5 bg-dark-900/60 border border-white/10 rounded-xl text-sm text-white focus:border-neon-blue/50 focus:outline-none">
            <option value="0" class="bg-dark-900">All Categories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $catFilter == $cat['id'] ? 'selected' : '' ?> class="bg-dark-900"><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="px-4 py-2.5 bg-neon-blue/10 border border-neon-blue/20 rounded-xl text-sm text-neon-blue hover:bg-neon-blue/20 transition-colors">
            <i class="ri-search-line"></i> Filter
        </button>
        <?php if ($search || $catFilter): ?>
        <a href="<?= e($basePath) ?>/admin/products.php" class="px-4 py-2.5 bg-white/5 rounded-xl text-sm text-gray-400 hover:text-white transition-colors">Clear</a>
        <?php endif; ?>
    </form>
    <a href="<?= e($basePath) ?>/admin/product-edit.php"
       class="flex-shrink-0 inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-neon-blue to-neon-purple rounded-xl text-sm font-bold text-dark-950 hover:opacity-90 transition-opacity">
        <i class="ri-add-line"></i> Add Product
    </a>
</div>

<!-- Products Table -->
<div class="bg-dark-900/60 border border-white/5 rounded-2xl overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-white/5">
        <span class="text-sm text-gray-500"><?= count($products) ?> product<?= count($products) !== 1 ? 's' : '' ?></span>
    </div>
    <?php if (empty($products)): ?>
    <div class="px-6 py-16 text-center text-gray-600">
        <i class="ri-headphone-line text-5xl block mb-3"></i> No products found.
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/5">
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Product</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Category</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Price</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Stock</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Featured</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Active</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($products as $p): ?>
                <tr class="hover:bg-white/2 transition-colors group">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <img src="<?= e($basePath) ?>/<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>" class="w-10 h-10 object-contain rounded-lg bg-dark-800 flex-shrink-0">
                            <div>
                                <div class="font-medium text-white text-sm"><?= e($p['name']) ?></div>
                                <div class="text-gray-600 text-xs"><?= e($p['sku'] ?? '—') ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-gray-400 text-sm hidden md:table-cell"><?= e($p['category_name'] ?? '—') ?></td>
                    <td class="px-4 py-4 text-right">
                        <?php if ($p['sale_price']): ?>
                        <div class="text-white font-semibold">$<?= number_format($p['sale_price'], 2) ?></div>
                        <div class="text-gray-600 text-xs line-through">$<?= number_format($p['price'], 2) ?></div>
                        <?php else: ?>
                        <div class="text-white font-semibold">$<?= number_format($p['price'], 2) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-4 text-center">
                        <span class="text-sm font-medium <?= $p['stock'] > 10 ? 'text-green-400' : ($p['stock'] > 0 ? 'text-yellow-400' : 'text-red-400') ?>">
                            <?= (int)$p['stock'] ?>
                        </span>
                    </td>
                    <td class="px-4 py-4 text-center hidden sm:table-cell">
                        <?= $p['featured'] ? '<i class="ri-star-fill text-yellow-400"></i>' : '<i class="ri-star-line text-gray-700"></i>' ?>
                    </td>
                    <td class="px-4 py-4 text-center">
                        <form method="POST" class="inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="w-10 h-6 rounded-full inline-flex items-center justify-center transition-colors text-xs font-bold <?= $p['is_active'] ? 'bg-green-500/20 text-green-400 border border-green-500/30' : 'bg-white/5 text-gray-500 border border-white/10' ?>" title="Toggle">
                                <?= $p['is_active'] ? 'ON' : 'OFF' ?>
                            </button>
                        </form>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="<?= e($basePath) ?>/admin/product-edit.php?id=<?= (int)$p['id'] ?>"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/5 border border-white/10 rounded-lg text-xs text-gray-300 hover:text-white hover:bg-white/10 transition-colors">
                                <i class="ri-edit-line"></i> Edit
                            </a>
                            <form method="POST" class="inline" onsubmit="return confirm('Delete this product? This cannot be undone.')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
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
