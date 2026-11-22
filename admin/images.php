<?php declare(strict_types=1);
define('APP_RUNNING', true);
/* ============================================================
   ADMIN — Images du site
   L'admin contrôle directement toutes les images affichées
   sur le site (accueil, catégories, page boutique) : import
   d'une nouvelle image par emplacement, ou retour à l'image
   par défaut. Les chemins vivent dans la table settings
   (img_<slot>) — aucun code à toucher.
   ============================================================ */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload.php';
require_once __DIR__ . '/layout.php';

check_global_limit($pdo, client_ip(), true);
require_admin();
$admin = current_admin();

$slots = [
    'hero' => ['Image principale (hero)', 'En haut de la page d\'accueil, à droite du grand titre.'],
    'promo' => ['Bannière promo', 'Section « Jusqu\'à -30% » de la page d\'accueil.'],
    'cat_femme' => ['Catégorie Femme', 'Carte « Femme » de l\'accueil (Choisissez votre style).'],
    'cat_homme' => ['Catégorie Homme', 'Carte « Homme » de l\'accueil.'],
    'cat_enfant' => ['Catégorie Enfant', 'Carte « Enfant » de l\'accueil.'],
    'cat_accessoires' => ['Catégorie Accessoires', 'Carte « Accessoires » de l\'accueil.'],
    'boutique_1' => ['Boutique — photo 1', 'Page « La Boutique », à côté de « Notre histoire ».'],
    'boutique_2' => ['Boutique — photo 2', 'Page « La Boutique », à côté des horaires.'],
];
$defaults = DEFAULT_CONFIG['images'];

$message = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_check()) {
        $error = 'Session expirée — rechargez la page et réessayez.';
    } else {
        $slot = (string) ($_POST['slot'] ?? '');
        $action = (string) ($_POST['action'] ?? '');
        if (!isset($slots[$slot])) {
            $error = 'Emplacement d\'image inconnu.';
        } elseif ($action === 'reset') {
            $settings = load_settings($pdo);
            $old = $settings['img_' . $slot] ?? '';
            $st = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
            $st->execute(['img_' . $slot, $defaults[$slot]]);
            delete_uploaded_image($old);
            $message = 'Image « ' . $slots[$slot][0] . ' » remise par défaut.';
        } elseif ($action === 'upload') {
            $uploadError = null;
            $uploaded = handle_image_upload('photo', 'site', $uploadError);
            if ($uploadError !== null) {
                $error = $uploadError;
            } elseif ($uploaded === null) {
                $error = 'Choisissez une image à importer.';
            } else {
                $settings = load_settings($pdo);
                $old = $settings['img_' . $slot] ?? '';
                $st = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
                $st->execute(['img_' . $slot, $uploaded]);
                delete_uploaded_image($old);
                $message = 'Image « ' . $slots[$slot][0] . ' » mise à jour — elle est déjà visible sur le site.';
            }
        }
    }
}

$cfg = shop_config($pdo);
$images = $cfg['images'];

admin_header('Images du site', 'images', $admin);
if ($message !== '') { echo '<div class="alert alert-ok">' . e($message) . '</div>'; }
if ($error !== '') { echo '<div class="alert alert-err">' . e($error) . '</div>'; }
?>
<p class="muted">Toutes les images affichées sur le site se changent ici — importez une nouvelle photo, elle apparaît sur le site immédiatement. Formats acceptés : jpg, png, webp, gif (max 5 Mo). Les images des <b>produits</b> se gèrent dans la fiche de chaque produit.</p>

<div class="img-grid">
  <?php foreach ($slots as $slot => [$title, $where]): ?>
    <div class="img-slot">
      <img src="../<?= e($images[$slot] ?? $defaults[$slot]) ?>" alt="<?= e($title) ?>">
      <h3><?= e($title) ?></h3>
      <p class="where"><?= e($where) ?></p>
      <form method="post" action="images.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="upload">
        <input type="hidden" name="slot" value="<?= e($slot) ?>">
        <input class="file-input" type="file" name="photo" accept="image/jpeg,image/png,image/webp,image/gif" required>
        <div style="display:flex;gap:.5rem;margin-top:.7rem;flex-wrap:wrap">
          <button class="btn btn-accent btn-sm" type="submit">Remplacer l'image</button>
        </div>
      </form>
      <?php if (($images[$slot] ?? '') !== $defaults[$slot]): ?>
        <form method="post" action="images.php" style="margin-top:.5rem" onsubmit="return confirm('Remettre l\'image par défaut ?');">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="reset">
          <input type="hidden" name="slot" value="<?= e($slot) ?>">
          <button class="btn btn-ghost btn-sm" type="submit">Remettre l'image par défaut</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php admin_footer(); ?>
