<?php
/**
 * Admin — Testimonials
 * Customer quotes shown on the homepage (Settings → Marketing switches the section on/off).
 */
$adminPageTitle = 'Testimonials';
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$errors = [];
$editId = (int) ($_GET['edit'] ?? 0);
$form = ['id' => 0, 'name' => '', 'role' => '', 'quote' => '', 'rating' => 5, 'sort_order' => 0];

if ($editId) {
    $stmt = $db->prepare('SELECT * FROM testimonials WHERE id = ?');
    $stmt->execute([$editId]);
    $form = $stmt->fetch() ?: $form;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $tid    = (int) ($_POST['testimonial_id'] ?? 0);

    if (!validateCsrf()) {
        $errors[] = 'Invalid security token. Please try again.';
    } elseif ($action === 'toggle' && $tid) {
        $db->prepare('UPDATE testimonials SET is_active = 1 - is_active WHERE id = ?')->execute([$tid]);
        setFlash('success', 'Testimonial updated.');
        redirect($basePath . '/admin/testimonials.php');
    } elseif ($action === 'delete' && $tid) {
        $db->prepare('DELETE FROM testimonials WHERE id = ?')->execute([$tid]);
        setFlash('success', 'Testimonial deleted.');
        redirect($basePath . '/admin/testimonials.php');
    } elseif ($action === 'save') {
        $form = [
            'id'         => $tid,
            'name'       => cleanText($_POST['name'] ?? '', 100),
            'role'       => cleanText($_POST['role'] ?? '', 100),
            'quote'      => cleanText($_POST['quote'] ?? '', 600),
            'rating'     => max(1, min(5, (int) ($_POST['rating'] ?? 5))),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];
        if (mb_strlen($form['name']) < 2)   $errors[] = 'Name is required.';
        if (mb_strlen($form['quote']) < 10) $errors[] = 'The quote must be at least 10 characters.';

        if (!$errors) {
            if ($tid) {
                $db->prepare('UPDATE testimonials SET name = ?, role = ?, quote = ?, rating = ?, sort_order = ? WHERE id = ?')
                   ->execute([$form['name'], $form['role'] ?: null, $form['quote'], $form['rating'], $form['sort_order'], $tid]);
            } else {
                $db->prepare('INSERT INTO testimonials (name, role, quote, rating, sort_order) VALUES (?, ?, ?, ?, ?)')
                   ->execute([$form['name'], $form['role'] ?: null, $form['quote'], $form['rating'], $form['sort_order']]);
            }
            setFlash('success', 'Testimonial saved.');
            redirect($basePath . '/admin/testimonials.php');
        }
    }
}

$testimonials = $db->query('SELECT * FROM testimonials ORDER BY sort_order, id')->fetchAll();
$inp = 'w-full px-4 py-2.5 bg-dark-800 border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors';
?>

<?php if ($errors): ?>
<div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm space-y-1">
    <?php foreach ($errors as $err): ?><div class="flex items-center gap-2"><i class="ri-error-warning-line flex-shrink-0"></i><?= e($err) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="mb-6 p-4 rounded-xl bg-yellow-500/5 border border-yellow-500/20 text-[12px] text-yellow-200/80">
    <i class="ri-scales-3-line text-yellow-400"></i>
    Publish only real customer feedback, with their permission. Made-up reviews break consumer-protection law in most countries.
    <?php if (!settingOn('show_testimonials')): ?>
    The homepage section is currently <strong>hidden</strong> — switch it on in <a href="<?= e($basePath) ?>/admin/settings.php?tab=marketing" class="underline">Settings → Marketing</a>.
    <?php endif; ?>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Add / edit -->
    <form method="POST" action="<?= e($basePath) ?>/admin/testimonials.php<?= $form['id'] ? '?edit=' . (int) $form['id'] : '' ?>" class="bg-dark-900/60 border border-white/5 rounded-2xl p-6 space-y-4 h-fit">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="testimonial_id" value="<?= (int) $form['id'] ?>">
        <h2 class="font-semibold text-white"><?= $form['id'] ? 'Edit Testimonial' : 'New Testimonial' ?></h2>
        <div>
            <label for="name" class="block text-xs text-gray-500 mb-1.5">Customer name</label>
            <input id="name" name="name" required maxlength="100" value="<?= e($form['name']) ?>" class="<?= $inp ?>" placeholder="Wanjiku M.">
        </div>
        <div>
            <label for="role" class="block text-xs text-gray-500 mb-1.5">Detail <span class="text-gray-600">(optional)</span></label>
            <input id="role" name="role" maxlength="100" value="<?= e($form['role'] ?? '') ?>" class="<?= $inp ?>" placeholder="Nairobi · bought in March">
        </div>
        <div>
            <label for="quote" class="block text-xs text-gray-500 mb-1.5">Quote</label>
            <textarea id="quote" name="quote" required rows="4" maxlength="600" class="<?= $inp ?> resize-none"><?= e($form['quote']) ?></textarea>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="rating" class="block text-xs text-gray-500 mb-1.5">Stars</label>
                <select id="rating" name="rating" class="<?= $inp ?>">
                    <?php for ($r = 5; $r >= 1; $r--): ?><option value="<?= $r ?>" class="bg-dark-800" <?= (int) $form['rating'] === $r ? 'selected' : '' ?>><?= $r ?> ★</option><?php endfor; ?>
                </select>
            </div>
            <div>
                <label for="sort_order" class="block text-xs text-gray-500 mb-1.5">Order</label>
                <input id="sort_order" name="sort_order" type="number" value="<?= (int) $form['sort_order'] ?>" class="<?= $inp ?>">
            </div>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 px-5 py-3 bg-gradient-to-r from-neon-blue to-neon-purple rounded-xl text-sm font-bold text-dark-950 hover:opacity-90 transition-opacity">
                <i class="ri-save-line mr-1"></i> <?= $form['id'] ? 'Save Changes' : 'Add Testimonial' ?>
            </button>
            <?php if ($form['id']): ?>
            <a href="<?= e($basePath) ?>/admin/testimonials.php" class="px-4 py-3 bg-white/5 rounded-xl text-sm text-gray-400 hover:text-white">Cancel</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- List -->
    <div class="lg:col-span-2 space-y-3">
        <?php if (!$testimonials): ?>
        <div class="bg-dark-900/60 border border-white/5 rounded-2xl px-6 py-16 text-center text-gray-600"><i class="ri-chat-quote-line text-5xl block mb-3"></i> No testimonials yet.</div>
        <?php endif; ?>
        <?php foreach ($testimonials as $t): ?>
        <div class="bg-dark-900/60 border border-white/5 rounded-2xl p-5 flex gap-4 <?= $t['is_active'] ? '' : 'opacity-50' ?>">
            <div class="flex-1 min-w-0">
                <div class="text-yellow-400 text-xs mb-1"><?= str_repeat('★', (int) $t['rating']) ?><span class="text-gray-700"><?= str_repeat('★', 5 - (int) $t['rating']) ?></span></div>
                <p class="text-sm text-gray-300">&ldquo;<?= e($t['quote']) ?>&rdquo;</p>
                <p class="text-xs text-gray-500 mt-2"><?= e($t['name']) ?><?= $t['role'] ? ' · ' . e($t['role']) : '' ?></p>
            </div>
            <div class="flex flex-col items-end gap-2 flex-shrink-0">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="testimonial_id" value="<?= (int) $t['id'] ?>">
                    <button type="submit" class="w-10 h-6 rounded-full inline-flex items-center justify-center text-xs font-bold <?= $t['is_active'] ? 'bg-green-500/20 text-green-400 border border-green-500/30' : 'bg-white/5 text-gray-500 border border-white/10' ?>" title="Show/hide">
                        <?= $t['is_active'] ? 'ON' : 'OFF' ?>
                    </button>
                </form>
                <div class="flex gap-2">
                    <a href="?edit=<?= (int) $t['id'] ?>" class="px-3 py-1.5 bg-white/5 border border-white/10 rounded-lg text-xs text-gray-300 hover:text-white" aria-label="Edit"><i class="ri-edit-line"></i></a>
                    <form method="POST" onsubmit="return confirm('Delete this testimonial?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="testimonial_id" value="<?= (int) $t['id'] ?>">
                        <button type="submit" class="px-3 py-1.5 bg-red-500/10 border border-red-500/20 rounded-lg text-xs text-red-400 hover:bg-red-500/20" aria-label="Delete"><i class="ri-delete-bin-line"></i></button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
