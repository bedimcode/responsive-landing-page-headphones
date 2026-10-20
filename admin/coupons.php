<?php
/**
 * Admin — Coupons
 * Create, switch on/off and delete discount codes.
 */
$adminPageTitle = 'Coupons';
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$errors = [];
$form = ['code' => '', 'description' => '', 'type' => 'percent', 'value' => '', 'min_subtotal' => '', 'max_uses' => '', 'expires_at' => '', 'once_per_customer' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $cid    = (int) ($_POST['coupon_id'] ?? 0);

    if (!validateCsrf()) {
        $errors[] = 'Invalid security token. Please try again.';
    } elseif ($action === 'toggle' && $cid) {
        $db->prepare('UPDATE coupons SET is_active = 1 - is_active WHERE id = ?')->execute([$cid]);
        setFlash('success', 'Coupon updated.');
        redirect($basePath . '/admin/coupons.php');
    } elseif ($action === 'delete' && $cid) {
        $db->prepare('DELETE FROM coupons WHERE id = ?')->execute([$cid]);
        setFlash('success', 'Coupon deleted. Past orders keep their discount.');
        redirect($basePath . '/admin/coupons.php');
    } elseif ($action === 'create') {
        $form = [
            'code'              => strtoupper(preg_replace('/\s+/', '', cleanText($_POST['code'] ?? '', 40))),
            'description'       => cleanText($_POST['description'] ?? '', 255),
            'type'              => ($_POST['type'] ?? '') === 'fixed' ? 'fixed' : 'percent',
            'value'             => (float) ($_POST['value'] ?? 0),
            'min_subtotal'      => max(0, (float) ($_POST['min_subtotal'] ?? 0)),
            'max_uses'          => ($_POST['max_uses'] ?? '') !== '' ? max(1, (int) $_POST['max_uses']) : null,
            'expires_at'        => cleanText($_POST['expires_at'] ?? '', 20),
            'once_per_customer' => isset($_POST['once_per_customer']) ? 1 : 0,
        ];
        if (!preg_match('/^[A-Z0-9_-]{3,40}$/', $form['code'])) $errors[] = 'Code must be 3–40 letters, numbers, dashes or underscores.';
        if ($form['value'] <= 0) $errors[] = 'Discount value must be more than 0.';
        if ($form['type'] === 'percent' && $form['value'] > 100) $errors[] = 'A percentage discount cannot be more than 100%.';
        if ($form['expires_at'] !== '' && !strtotime($form['expires_at'])) $errors[] = 'Expiry date is not valid.';

        if (!$errors) {
            try {
                $db->prepare('INSERT INTO coupons (code, description, type, value, min_subtotal, max_uses, once_per_customer, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                   ->execute([
                       $form['code'], $form['description'] ?: null, $form['type'], $form['value'], $form['min_subtotal'],
                       $form['max_uses'], $form['once_per_customer'],
                       $form['expires_at'] !== '' ? date('Y-m-d H:i:s', strtotime($form['expires_at'])) : null,
                   ]);
                setFlash('success', 'Coupon ' . $form['code'] . ' created.');
                redirect($basePath . '/admin/coupons.php');
            } catch (PDOException $e) {
                $errors[] = $e->getCode() === '23000' ? 'A coupon with that code already exists.' : 'Could not save the coupon.';
            }
        }
    }
}

$coupons = $db->query('SELECT * FROM coupons ORDER BY is_active DESC, created_at DESC')->fetchAll();
$inp = 'w-full px-4 py-2.5 bg-dark-800 border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors';
?>

<?php if ($errors): ?>
<div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm space-y-1">
    <?php foreach ($errors as $err): ?><div class="flex items-center gap-2"><i class="ri-error-warning-line flex-shrink-0"></i><?= e($err) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Create -->
    <form method="POST" class="bg-dark-900/60 border border-white/5 rounded-2xl p-6 space-y-4 h-fit">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="create">
        <h2 class="font-semibold text-white">New Coupon</h2>
        <div>
            <label for="code" class="block text-xs text-gray-500 mb-1.5">Code</label>
            <input id="code" name="code" required maxlength="40" value="<?= e($form['code']) ?>" class="<?= $inp ?> uppercase font-mono" placeholder="SAVE10">
        </div>
        <div>
            <label for="description" class="block text-xs text-gray-500 mb-1.5">Description <span class="text-gray-600">(shown to the customer)</span></label>
            <input id="description" name="description" maxlength="255" value="<?= e($form['description']) ?>" class="<?= $inp ?>" placeholder="10% off everything">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="type" class="block text-xs text-gray-500 mb-1.5">Type</label>
                <select id="type" name="type" class="<?= $inp ?>">
                    <option value="percent" class="bg-dark-800" <?= $form['type'] === 'percent' ? 'selected' : '' ?>>Percent (%)</option>
                    <option value="fixed" class="bg-dark-800" <?= $form['type'] === 'fixed' ? 'selected' : '' ?>>Fixed (<?= e(setting('currency_symbol')) ?>)</option>
                </select>
            </div>
            <div>
                <label for="value" class="block text-xs text-gray-500 mb-1.5">Value</label>
                <input id="value" name="value" type="number" min="0.01" step="0.01" required value="<?= e((string) $form['value']) ?>" class="<?= $inp ?>" placeholder="10">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="min_subtotal" class="block text-xs text-gray-500 mb-1.5">Minimum order</label>
                <input id="min_subtotal" name="min_subtotal" type="number" min="0" step="0.01" value="<?= e((string) $form['min_subtotal']) ?>" class="<?= $inp ?>" placeholder="0">
            </div>
            <div>
                <label for="max_uses" class="block text-xs text-gray-500 mb-1.5">Total uses</label>
                <input id="max_uses" name="max_uses" type="number" min="1" value="<?= e((string) ($form['max_uses'] ?? '')) ?>" class="<?= $inp ?>" placeholder="Unlimited">
            </div>
        </div>
        <div>
            <label for="expires_at" class="block text-xs text-gray-500 mb-1.5">Expires</label>
            <input id="expires_at" name="expires_at" type="datetime-local" value="<?= e($form['expires_at']) ?>" class="<?= $inp ?>">
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-300 cursor-pointer">
            <input type="checkbox" name="once_per_customer" value="1" <?= $form['once_per_customer'] ? 'checked' : '' ?> class="w-4 h-4 rounded border-white/20 bg-dark-800 text-neon-blue focus:ring-neon-blue/50">
            One use per customer
        </label>
        <button type="submit" class="w-full px-5 py-3 bg-gradient-to-r from-neon-blue to-neon-purple rounded-xl text-sm font-bold text-dark-950 hover:opacity-90 transition-opacity">
            <i class="ri-add-line mr-1"></i> Create Coupon
        </button>
    </form>

    <!-- List -->
    <div class="lg:col-span-2 bg-dark-900/60 border border-white/5 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-white/5 text-sm text-gray-500"><?= count($coupons) ?> coupon<?= count($coupons) !== 1 ? 's' : '' ?></div>
        <?php if (!$coupons): ?>
        <div class="px-6 py-16 text-center text-gray-600"><i class="ri-coupon-3-line text-5xl block mb-3"></i> No coupons yet.</div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-white/5 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="text-left px-6 py-3">Code</th>
                        <th class="text-left px-4 py-3">Discount</th>
                        <th class="text-center px-4 py-3">Used</th>
                        <th class="text-left px-4 py-3 hidden md:table-cell">Expires</th>
                        <th class="text-center px-4 py-3">Active</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($coupons as $c):
                        $expired = $c['expires_at'] && strtotime($c['expires_at']) < time(); ?>
                    <tr class="hover:bg-white/[.02] transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-mono font-semibold text-white"><?= e($c['code']) ?></div>
                            <div class="text-gray-600 text-xs"><?= e($c['description'] ?? '') ?></div>
                        </td>
                        <td class="px-4 py-4 text-gray-300">
                            <?= $c['type'] === 'percent' ? rtrim(rtrim($c['value'], '0'), '.') . '%' : formatPrice($c['value']) ?>
                            <?php if ($c['min_subtotal'] > 0): ?><div class="text-gray-600 text-xs">min <?= formatPrice($c['min_subtotal']) ?></div><?php endif; ?>
                            <?php if ($c['once_per_customer']): ?><div class="text-gray-600 text-xs">once per customer</div><?php endif; ?>
                        </td>
                        <td class="px-4 py-4 text-center text-gray-300"><?= (int) $c['used_count'] ?><?= $c['max_uses'] ? ' / ' . (int) $c['max_uses'] : '' ?></td>
                        <td class="px-4 py-4 hidden md:table-cell <?= $expired ? 'text-red-400' : 'text-gray-400' ?>">
                            <?= $c['expires_at'] ? date('M j, Y H:i', strtotime($c['expires_at'])) . ($expired ? ' · expired' : '') : 'Never' ?>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <form method="POST" class="inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="coupon_id" value="<?= (int) $c['id'] ?>">
                                <button type="submit" class="w-10 h-6 rounded-full inline-flex items-center justify-center transition-colors text-xs font-bold <?= $c['is_active'] ? 'bg-green-500/20 text-green-400 border border-green-500/30' : 'bg-white/5 text-gray-500 border border-white/10' ?>" title="Switch on/off">
                                    <?= $c['is_active'] ? 'ON' : 'OFF' ?>
                                </button>
                            </form>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <form method="POST" class="inline" onsubmit="return confirm('Delete coupon <?= e($c['code']) ?>?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="coupon_id" value="<?= (int) $c['id'] ?>">
                                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-500/10 border border-red-500/20 rounded-lg text-xs text-red-400 hover:bg-red-500/20 transition-colors" aria-label="Delete coupon">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
