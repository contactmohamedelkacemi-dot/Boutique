<?php declare(strict_types=1);
define('APP_RUNNING', true);
require_once __DIR__ . '/../includes/bootstrap.php';
check_global_limit($pdo, client_ip());
$cfg = shop_config($pdo);
$jsConfig = $cfg;
$jsProducts = products_for_js($pdo);
$jsCats = categories_list($pdo);
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?= e($cfg['defaultTheme']) ?>" data-font="<?= e($cfg['font']) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact — <?= e($cfg['name']) ?></title>
  <meta name="description" content="Contactez <?= e($cfg['name']) ?> à Bouznika : adresse, horaires, téléphone et WhatsApp.">
  <link rel="stylesheet" href="../css/fonts.css">
  <link rel="stylesheet" href="../css/themes.css">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <div id="app-header"></div>

  <main class="section">
    <div class="container">
      <span class="eyebrow"><?= e($cfg['name']) ?></span>
      <h1 class="section-title">Contact & accès</h1>
      <p class="section-sub">Une question sur un article, une taille, une livraison ? Écrivez-nous — on répond vite, en boutique comme sur WhatsApp.</p>

      <div class="contact-grid" style="margin-top:2.2rem">
        <div class="form-card">
          <h2 style="font-size:1.4rem;margin-bottom:1.2rem">Envoyer un message</h2>
          <form id="contact-form">
            <div class="field"><label for="cf-name">Votre nom</label><input id="cf-name" type="text" placeholder="Ex : Salma" required></div>
            <div class="field"><label for="cf-phone">Téléphone / WhatsApp</label><input id="cf-phone" type="tel" placeholder="Ex : 06 12 34 56 78" required></div>
            <div class="field"><label for="cf-msg">Votre message</label><textarea id="cf-msg" rows="5" placeholder="Bonjour, je voudrais savoir si la robe florale est dispo en taille M…" required></textarea></div>
            <button class="btn btn-accent btn-block" type="submit">Envoyer via WhatsApp →</button>
            <p style="color:var(--muted);font-size:.88rem;margin-top:.8rem">Le formulaire ouvre WhatsApp avec votre message prêt à envoyer. Aucune donnée n'est stockée sur le site.</p>
          </form>
        </div>

        <div>
          <div class="map-wrap">
            <iframe id="map-frame" title="Carte — <?= e($cfg['name']) ?>, Bouznika" src="https://www.openstreetmap.org/export/embed.html?bbox=-7.2167%2C33.7626%2C-7.1167%2C33.8226&layer=mapnik&marker=33.7926%2C-7.1667" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
          </div>
          <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:.8rem">
            <a class="btn btn-ghost btn-sm" data-maps-google href="#" target="_blank" rel="noopener">Itinéraire — Google Maps →</a>
            <a class="btn btn-ghost btn-sm" data-maps-osm href="#" target="_blank" rel="noopener">Ouvrir dans OpenStreetMap →</a>
          </div>
          <div class="info-list">
            <div><div><b>Adresse</b><span data-config-address>12, Rue des Fleurs, Bouznika, Maroc</span></div></div>
            <div><div><b>Téléphone</b><span data-config-phone>06 00 00 00 00</span></div></div>
            <div><div><b>Email</b><span data-config-email>contact@boutiquestyle.ma</span></div></div>
            <div><div><b>Horaires</b><span>Lun – Sam : 10h – 20h · Dim : 11h – 19h</span></div></div>
            <div><div><b>Instagram</b><span data-config-instagram>@boutique.style</span></div></div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <div id="app-footer"></div>

  <script src="../js/config.js"></script>
  <script>window.BOUTIQUE_CONFIG = <?= json_encode($jsConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  window.PRODUCTS = <?= json_encode($jsProducts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  window.CATEGORIES = <?= json_encode($jsCats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
  <script src="../js/main.js"></script>
</body>
</html>
