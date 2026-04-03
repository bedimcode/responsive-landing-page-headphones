<?php
/**
 * Admin — Add / Edit Product
 */
$db = getDB() ?: null; // pre-load to avoid header include re-init
$editId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$isEdit = $editId > 0;

$adminPageTitle = $isEdit ? 'Edit Product' : 'Add Product';
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$categories = $db->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

$product = [
    'id' => 0, 'category_id' => '', 'name' => '', 'slug' => '', 'description' => '',
    'short_description' => '', 'price' => '', 'sale_price' => '', 'image' => '',
    'color' => '', 'color_hex' => '', 'stock' => 0, 'sku' => '',
    'featured' => 0, 'is_active' => 1,
];
$errors = [];

if ($isEdit) {
    $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$editId]);
    $found = $stmt->fetch();
    if ($found) {
        $product = $found;
    } else {
        setFlash('error', 'Product not found.');
        header('Location: ' . $basePath . '/admin/products.php'); exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        $errors[] = 'Invalid security token.';
    } else {
        $pid   = (int)($_POST['id'] ?? 0);
        $name  = trim($_POST['name'] ?? '');
        $slug  = trim($_POST['slug'] ?? '');
        $catId = (int)($_POST['category_id'] ?? 0) ?: null;
        $desc  = trim($_POST['description'] ?? '');
        $short = trim($_POST['short_description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $salePr= trim($_POST['sale_price'] ?? '') !== '' ? (float)$_POST['sale_price'] : null;
        $image = trim($_POST['image'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $hex   = trim($_POST['color_hex'] ?? '');
        $stock = max(0, (int)($_POST['stock'] ?? 0));
        $sku   = trim($_POST['sku'] ?? '') ?: null;
        $feat  = isset($_POST['featured']) ? 1 : 0;
        $active= isset($_POST['is_active']) ? 1 : 0;

        if (strlen($name) < 2)  $errors[] = 'Name is required (min 2 chars).';
        if (strlen($slug) < 2)  $errors[] = 'Slug is required.';
        if ($price <= 0)        $errors[] = 'Price must be greater than 0.';
        if (strlen($image) < 1) $errors[] = 'Image path is required.';

        // Auto-generate slug if empty
        if (empty($slug) && $name) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        }

        if (empty($errors)) {
            try {
                if ($pid) {
                    // Update
                    $db->prepare('
                        UPDATE products SET
                            category_id=?, name=?, slug=?, description=?, short_description=?,
                            price=?, sale_price=?, image=?, color=?, color_hex=?,
                            stock=?, sku=?, featured=?, is_active=?
                        WHERE id=?
                    ')->execute([$catId,$name,$slug,$desc,$short,$price,$salePr,$image,$color,$hex,$stock,$sku,$feat,$active,$pid]);
                    setFlash('success', 'Product updated successfully.');
                } else {
                    // Insert
                    $db->prepare('
                        INSERT INTO products (category_id,name,slug,description,short_description,price,sale_price,image,color,color_hex,stock,sku,featured,is_active)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                    ')->execute([$catId,$name,$slug,$desc,$short,$price,$salePr,$image,$color,$hex,$stock,$sku,$feat,$active]);
                    setFlash('success', 'Product created successfully.');
                }
                header('Location: ' . $basePath . '/admin/products.php'); exit;
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $errors[] = 'Slug or SKU already exists. Please use a unique value.';
                } else {
                    $errors[] = 'Database error: ' . ($e->getMessage());
                }
            }
        }

        // Re-populate form with submitted data
        $product = array_merge($product, [
            'id' => $pid, 'category_id' => $catId, 'name' => $name, 'slug' => $slug,
            'description' => $desc, 'short_description' => $short, 'price' => $price,
            'sale_price' => $salePr ?? '', 'image' => $image, 'color' => $color,
            'color_hex' => $hex, 'stock' => $stock, 'sku' => $sku ?? '',
            'featured' => $feat, 'is_active' => $active,
        ]);
    }
}

function field(string $label, string $html): string {
    return "<div><label class=\"block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5\">$label</label>$html</div>";
}
$inp = 'w-full px-4 py-3 bg-dark-800 border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors';
?>

<!-- Back -->
<div class="mb-6">
    <a href="<?= e($basePath) ?>/admin/products.php" class="inline-flex items-center gap-2 text-gray-500 hover:text-neon-blue text-sm transition-colors">
        <i class="ri-arrow-left-line"></i> Back to Products
    </a>
</div>

<?php if ($errors): ?>
<div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm space-y-1">
    <?php foreach ($errors as $err): ?><div class="flex items-center gap-2"><i class="ri-error-warning-line flex-shrink-0"></i><?= e($err) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" class="grid lg:grid-cols-3 gap-6">
    <?= csrfField() ?>
    <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">

    <!-- Main fields (2/3) -->
    <div class="lg:col-span-2 space-y-5">
        <div class="bg-dark-900/60 border border-white/5 rounded-2xl p-6 space-y-5">
            <h2 class="font-semibold text-white">Product Information</h2>

            <?= field('Product Name', '<input type="text" name="name" value="' . e($product['name']) . '" required class="' . $inp . '" placeholder="e.g. Beats 3 — Black" oninput="autoSlug(this)">') ?>

            <div class="grid sm:grid-cols-2 gap-4">
                <?= field('Slug (URL)', '<input type="text" name="slug" id="slug-field" value="' . e($product['slug']) . '" required class="' . $inp . '" placeholder="beats3-black">') ?>
                <?= field('SKU', '<input type="text" name="sku" value="' . e($product['sku']) . '" class="' . $inp . '" placeholder="BEATS3-BLK">') ?>
            </div>

            <?= field('Short Description', '<input type="text" name="short_description" value="' . e($product['short_description']) . '" class="' . $inp . '" placeholder="Brief description shown in cards…" maxlength="500">') ?>
            <?= field('Full Description', '<textarea name="description" rows="5" class="' . $inp . ' resize-none" placeholder="Detailed product description…">' . e($product['description']) . '</textarea>') ?>
        </div>

        <!-- Pricing + Stock -->
        <div class="bg-dark-900/60 border border-white/5 rounded-2xl p-6 space-y-5">
            <h2 class="font-semibold text-white">Pricing &amp; Stock</h2>
            <div class="grid sm:grid-cols-3 gap-4">
                <?= field('Price ($)', '<input type="number" name="price" value="' . e((string)$product['price']) . '" required min="0.01" step="0.01" class="' . $inp . '" placeholder="249.00">') ?>
                <?= field('Sale Price ($)', '<input type="number" name="sale_price" value="' . e((string)$product['sale_price']) . '" min="0.01" step="0.01" class="' . $inp . '" placeholder="Optional">') ?>
                <?= field('Stock Qty', '<input type="number" name="stock" value="' . (int)$product['stock'] . '" min="0" class="' . $inp . '" placeholder="0">') ?>
            </div>
        </div>

        <!-- Colors -->
        <div class="bg-dark-900/60 border border-white/5 rounded-2xl p-6 space-y-5">
            <h2 class="font-semibold text-white">Color &amp; Appearance</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <?= field('Color Name', '<input type="text" name="color" value="' . e($product['color']) . '" class="' . $inp . '" placeholder="e.g. Black">') ?>
                <?= field('Color Hex / CSS', '<input type="text" name="color_hex" value="' . e($product['color_hex']) . '" class="' . $inp . '" placeholder="#1a1a1a or linear-gradient(…)">') ?>
            </div>
        </div>
    </div>

    <!-- Sidebar fields (1/3) -->
    <div class="space-y-5">
        <!-- Image -->
        <div class="bg-dark-900/60 border border-white/5 rounded-2xl p-6 space-y-4">
            <h2 class="font-semibold text-white">Image</h2>
            <?php if ($product['image']): ?>
            <img src="<?= e($basePath) ?>/<?= e($product['image']) ?>" alt="Preview" class="w-full h-32 object-contain rounded-xl bg-dark-800 mb-3" id="img-preview">
            <?php else: ?>
            <div class="w-full h-32 rounded-xl bg-dark-800 flex items-center justify-center mb-3" id="img-preview-placeholder">
                <i class="ri-image-line text-4xl text-gray-700"></i>
            </div>
            <?php endif; ?>
            <?= field('Image Path', '<input type="text" name="image" id="img-path" value="' . e($product['image']) . '" class="' . $inp . '" placeholder="assets/img/product1.png" oninput="previewImg(this)">') ?>
            <p class="text-xs text-gray-600">Relative to site root. Place files in <code class="text-gray-500">assets/img/</code></p>
        </div>

        <!-- Category + Flags -->
        <div class="bg-dark-900/60 border border-white/5 rounded-2xl p-6 space-y-5">
            <h2 class="font-semibold text-white">Settings</h2>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Category</label>
                <select name="category_id" class="<?= $inp ?>">
                    <option value="" class="bg-dark-800">— No Category —</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?> class="bg-dark-800"><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="featured" value="1" <?= $product['featured'] ? 'checked' : '' ?> class="w-4 h-4 rounded border-white/20 bg-dark-800 text-neon-blue focus:ring-neon-blue/50">
                <span class="text-sm text-gray-300">Featured product</span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" <?= $product['is_active'] ? 'checked' : '' ?> class="w-4 h-4 rounded border-white/20 bg-dark-800 text-neon-blue focus:ring-neon-blue/50">
                <span class="text-sm text-gray-300">Active (visible in store)</span>
            </label>
        </div>

        <!-- Submit -->
        <button type="submit" class="w-full px-6 py-4 bg-gradient-to-r from-neon-blue to-neon-purple rounded-2xl font-bold text-dark-950 text-base hover:opacity-90 transition-opacity">
            <i class="ri-save-line mr-2"></i><?= $isEdit ? 'Save Changes' : 'Create Product' ?>
        </button>
    </div>
</form>

<script>
function autoSlug(input) {
    const slugField = document.getElementById('slug-field');
    if (!slugField.dataset.manual) {
        slugField.value = input.value.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .trim().replace(/\s+/g, '-');
    }
}
document.getElementById('slug-field')?.addEventListener('input', function() {
    this.dataset.manual = '1';
});
function previewImg(input) {
    const preview = document.getElementById('img-preview');
    const placeholder = document.getElementById('img-preview-placeholder');
    if (preview) preview.src = '<?= e($basePath) ?>/' + input.value;
    if (placeholder && input.value) placeholder.style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
