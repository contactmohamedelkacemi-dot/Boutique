<?php declare(strict_types=1);
define('APP_RUNNING', true);
/* ============================================================
   ADMIN — Catégories
   L'admin contrôle les catégories de A à Z : nom affiché,
   sous-titre et image de la carte d'accueil, ajout d'une
   nouvelle catégorie, suppression (si aucun produit ne
   l'utilise). Le slug (lien) est créé à l'ajout et ne change
   plus, pour ne pas casser les produits existants.
   ============================================================ */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload.php';
require_once __DIR__ . '/layout.php';

check_global_limit($pdo, client_ip(), true);
require_admin();
$admin = current_admin();

$message = '';
$error = '';

function slugify_cat(string $s): string
{
    $s = mb_strtolower(trim($s));
    $s = str_replace(['é', 'è', 'ê', 'à', 'â', 'î', 'ï', 'ô', 'ù', 'û', 'ç'], ['e', 'e', 'e', 'a', 'a', 'i', 'i', 'o', 'u', 'u', 'c'], $s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
    return trim($s, '-') ?: 'categorie';
}

function set_setting(PDO $pdo, string $key, string $value): void
{
    $st = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $st->execute([$key, $value]);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_check()) {
        $error = 'Session expirée — rechargez la page et réessayez.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $cats = categories_list($pdo);
        if ($action === 'save') {
            $slug = (string) ($_POST['slug'] ?? '');
            $name = trim((string) ($_POST['name'] ?? ''));
            $sub = trim((string) ($_POST['subtitle'] ?? ''));
            if (!isset($cats[$slug])) {
                $error = 'Catégorie inconnue.';
            } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 60) {
                $error = 'Nom de catégorie invalide (2 à 60 caractères).';
            } else {
                $st = $pdo->prepare('UPDATE categories SET name = ? WHERE slug = ?');
                $st->execute([$name, $slug]);
                set_setting($pdo, 'cat_' . $slug . '_sub', $sub);
                $uploadError = null;
                $uploaded = handle_image_upload('photo', 'site', $uploadError);
                if ($uploadError !== null) {
                    $error = $uploadError;
                } elseif ($uploaded !== null) {
                    $settings = load_settings($pdo);
                    $old = $settings['img_cat_' . $slug] ?? '';
                    set_setting($pdo, 'img_cat_' . $slug, $uploaded);
                    delete_uploaded_image($old);
                }
                if ($error === '') {
                    $message = 'Catégorie « ' . $name . ' » mise à jour.';
                }
            }
        } elseif ($action === 'add') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $sub = trim((string) ($_POST['subtitle'] ?? ''));
            if (mb_strlen($name) < 2 || mb_strlen($name) > 60) {
                $error = 'Nom de catégorie invalide (2 à 60 caractères).';
            } else {
                $slug = slugify_cat($name);
                if (isset($cats[$slug])) {
                    $slug .= '-' . random_int(10, 99);
                }
                $st = $pdo->prepare('INSERT INTO categories (slug, name) VALUES (?, ?)');
                $st->execute([$slug, $name]);
                set_setting($pdo, 'cat_' . $slug . '_sub', $sub);
                $uploadError = null;
                $uploaded = handle_image_upload('photo', 'site', $uploadError);
                if ($uploadError !== null) {
                    $error = $uploadError;
                } elseif ($uploaded !== null) {
                    set_setting($pdo, 'img_cat_' . $slug, $uploaded);
                }
                if ($error === '') {
                    $message = 'Catégorie « ' . $name . ' » ajoutée — elle apparaît sur l\'accueil et dans la collection.';
                }
            }
        } elseif ($action === 'delete') {
            $slug = (string) ($_POST['slug'] ?? '');
            if (!isset($cats[$slug])) {
                $error = 'Catégorie inconnue.';
            } else {
                $st = $pdo->prepare('SELECT COUNT(*) AS n FROM products WHERE cat = ?');
                $st->execute([$slug]);
                if ((int) $st->fetch()['n'] > 0) {
                    $error = 'Impossible de supprimer : des produits utilisent cette catégorie. Déplacez-les d\'abord.';
                } else {
                    $settings = load_settings($pdo);
                    delete_uploaded_image($settings['img_cat_' . $slug] ?? null);
                    $st = $pdo->prepare('DELETE FROM categories WHERE slug = ?');
                    $st->execute([$slug]);
                    $st = $pdo->prepare('DELETE FROM settings WHERE setting_key IN (?, ?)');
                    $st->execute(['cat_' . $slug . '_sub', 'img_cat_' . $slug]);
                    $message = 'Catégorie supprimée.';
                }
            }
        }
    }
}

$cats = categories_list($pdo);
$settings = load_settings($pdo);
$cfg = shop_config($pdo);
$counts = [];
foreach ($pdo->query('SELECT cat, COUNT(*) AS n FROM products GROUP BY cat')->fetchAll() as $r) {
    $counts[$r['cat']] = (int) $r['n'];
}

admin_header('Catégories', 'categories', $admin);
if ($message !== '') { echo '<div class="alert alert-ok">' . e($message) . '</div>'; }
if ($error !== '') { echo '<div class="alert alert-err">' . e($error) . '</div>'; }
?>
<p class="muted">Nom, sous-titre et image de chaque catégorie — tels qu'ils apparaissent sur la carte d'accueil et dans les filtres de la collection. Vous pouvez aussi ajouter une nouvelle catégorie ou en supprimer une (si aucun produit ne l'utilise).</p>

<div class="img-grid">
  <?php foreach ($cats as $slug => $name): ?>
    <div class="img-slot">
      <img src="../<?= e($cfg['images']['cat_' . $slug] ?? 'assets/placeholder.svg') ?>" alt="<?= e($name) ?>">
      <form method="post" action="categories.php" enctype="multipart/form-data" style="margin-top:.8rem">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="slug" value="<?= e($slug) ?>">
        <div class="field"><label>Nom affiché</label><input type="text" name="name" value="<?= e($name) ?>" required></div>
        <div class="field"><label>Sous-titre (carte d'accueil)</label><input type="text" name="subtitle" value="<?= e(category_subtitle($settings, $slug)) ?>"></div>
        <div class="field"><label>Image de la carte</label><input class="file-input" type="file" name="photo" accept="image/jpeg,image/png,image/webp,image/gif"></div>
        <p class="muted" style="font-size:.85rem"><?= (int) ($counts[$slug] ?? 0) ?> produit(s) dans cette catégorie · lien : collection.php?cat=<?= e($slug) ?></p>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.6rem">
          <button class="btn btn-accent btn-sm" type="submit">Enregistrer</button>
        </div>
      </form>
      <form method="post" action="categories.php" style="margin-top:.5rem" onsubmit="return confirm('Supprimer la catégorie « <?= e($name) ?> » ?');">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="slug" value="<?= e($slug) ?>">
        <button class="btn btn-ghost btn-sm" type="submit" style="color:#b3261e">Supprimer la catégorie</button>
      </form>
    </div>
  <?php endforeach; ?>
</div>

<h2 style="font-size:1.25rem;margin:2rem 0 .8rem">Ajouter une catégorie</h2>
<div class="form-card admin-form">
  <form method="post" action="categories.php" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="add">
    <div class="field"><label for="n-name">Nom *</label><input id="n-name" type="text" name="name" required placeholder="Ex : Sport"></div>
    <div class="field"><label for="n-sub">Sous-titre (carte d'accueil)</label><input id="n-sub" type="text" name="subtitle" placeholder="Ex : Tenues & chaussures de sport"></div>
    <div class="field"><label>Image de la carte</label><input class="file-input" type="file" name="photo" accept="image/jpeg,image/png,image/webp,image/gif"></div>
    <button class="btn btn-accent" type="submit">Ajouter la catégorie</button>
  </form>
</div>
<?php admin_footer(); ?>
