<?php declare(strict_types=1);
define('APP_RUNNING', true);
/* ============================================================
   ADMIN — Paramètres de la boutique
   Modifie la table settings : le site (nom, WhatsApp, adresse…)
   se met à jour tout seul, sans toucher au code.
   ============================================================ */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

check_global_limit($pdo, client_ip(), true);
require_admin();
$admin = current_admin();

$message = '';
$error = '';
$editable = [
    'shop_name' => 'Nom de la boutique',
    'whatsapp' => 'WhatsApp (format international, sans + — ex : 212600000000)',
    'address' => 'Adresse',
    'city' => 'Ville',
    'email' => 'Email',
    'delivery_fee' => 'Frais de livraison (DH)',
    'free_delivery_from' => 'Livraison gratuite à partir de (DH — 0 = toujours payante)',
];
$themes = ['terracotta', 'rose', 'sauge', 'noir-or', 'denim'];
$fonts = [
    'italiana' => 'Italiana — serif élégant (défaut)',
    'playfair' => 'Playfair Display — serif classique chic',
    'moderne' => 'Moderne — sans-serif gras partout',
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_check()) {
        $error = 'Session expirée — rechargez la page et réessayez.';
    } else {
        $theme = (string) ($_POST['default_theme'] ?? '');
        $font = (string) ($_POST['site_font'] ?? '');
        if (!in_array($theme, $themes, true)) {
            $error = 'Thème inconnu.';
        } elseif (!isset($fonts[$font])) {
            $error = 'Police inconnue.';
        } else {
            $st = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
            $st->execute(['default_theme', $theme]);
            $st->execute(['site_font', $font]);
        }
        $st = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        foreach ($error === '' ? $editable : [] as $key => $label) {
            $val = trim((string) ($_POST[$key] ?? ''));
            if ($key === 'delivery_fee' || $key === 'free_delivery_from') {
                if (!is_numeric($val) || (int) $val < 0) {
                    $error = '« ' . $label . ' » doit être un nombre positif.';
                    break;
                }
                $val = (string) (int) $val;
            }
            if ($key === 'whatsapp' && $val !== '' && !preg_match('/^[0-9]{9,15}$/', $val)) {
                $error = 'WhatsApp invalide : chiffres uniquement, format international (ex : 212600000000).';
                break;
            }
            $st->execute([$key, $val]);
        }
        if ($error === '') {
            $message = 'Paramètres enregistrés.';
        }
    }
}

$settings = load_settings($pdo);
$cfg = shop_config($pdo);

admin_header('Paramètres', 'settings', $admin);
if ($message !== '') { echo '<div class="alert alert-ok">' . e($message) . '</div>'; }
if ($error !== '') { echo '<div class="alert alert-err">' . e($error) . '</div>'; }
?>
<div class="form-card admin-form">
  <form method="post" action="settings.php">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <?php
    $fallbacks = [
        'shop_name' => 'name', 'whatsapp' => 'whatsapp', 'address' => 'address',
        'city' => 'city', 'email' => 'email',
        'delivery_fee' => 'deliveryFee', 'free_delivery_from' => 'freeDeliveryFrom',
    ];
    foreach ($editable as $key => $label):
      $value = $settings[$key] ?? (string) ($cfg[$fallbacks[$key]] ?? '');
    ?>
      <div class="field">
        <label for="f-<?= e($key) ?>"><?= e($label) ?></label>
        <input id="f-<?= e($key) ?>" name="<?= e($key) ?>" type="text" value="<?= e($value) ?>">
      </div>
    <?php endforeach; ?>
    <div class="field">
      <label for="f-theme">Thème de couleurs du site</label>
      <select id="f-theme" name="default_theme">
        <?php foreach ($themes as $t): ?>
          <option value="<?= e($t) ?>" <?= ($settings['default_theme'] ?? $cfg['defaultTheme'] ?? 'terracotta') === $t ? 'selected' : '' ?>><?= e(ucfirst($t)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="f-font">Police du site (titres)</label>
      <select id="f-font" name="site_font">
        <?php foreach ($fonts as $fkey => $flabel): ?>
          <option value="<?= e($fkey) ?>" <?= ($settings['site_font'] ?? $cfg['font'] ?? 'italiana') === $fkey ? 'selected' : '' ?>><?= e($flabel) ?></option>
        <?php endforeach; ?>
      </select>
      <p class="muted" style="font-size:.85rem;margin-top:.4rem">Thème et police s'appliquent au site et à cet admin. Les images affichées sur le site se changent dans « Images du site » et « Catégories ».</p>
    </div>
    <button class="btn btn-accent" type="submit">Enregistrer</button>
  </form>
  <p class="muted" style="font-size:.88rem;margin-top:1rem">Le nom, WhatsApp, l'adresse et la ville s'affichent partout sur le site (en-tête, pied de page, contact). Les frais de livraison s'appliquent aux nouvelles commandes.</p>
</div>
<?php admin_footer(); ?>
