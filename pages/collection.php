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
  <title>Collection — <?= e($cfg['name']) ?></title>
  <meta name="description" content="Toute la collection <?= e($cfg['name']) ?> : femme, homme, enfant et accessoires. Filtrez, triez et commandez via WhatsApp.">
  <link rel="stylesheet" href="../css/fonts.css">
  <link rel="stylesheet" href="../css/themes.css">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <div id="app-header"></div>

  <main class="section">
    <div class="container">
      <span class="eyebrow"><?= e($cfg['name']) ?></span>
      <h1 class="section-title">La collection</h1>
      <p class="section-sub">Femme, homme, enfant et accessoires — des pièces choisies en
        boutique, aux prix affichés en dirhams. Commandez en un clic via WhatsApp.</p>

      <div class="filters" role="group" aria-label="Catégories">
        <button class="chip active" data-cat="all">Tout</button>
        <?php foreach ($jsCats as $slug => $name): ?>
          <button class="chip" data-cat="<?= e($slug) ?>"><?= e($name) ?></button>
        <?php endforeach; ?>
      </div>

      <div class="toolbar">
        <input type="search" id="search-input" placeholder="Rechercher un article… (ex : robe, jean)" aria-label="Rechercher">
        <select id="sort-select" aria-label="Trier">
          <option value="featured">Trier : Nos favoris</option>
          <option value="price-asc">Prix croissant</option>
          <option value="price-desc">Prix décroissant</option>
          <option value="promo">Promos d'abord</option>
        </select>
        <div style="display:flex;align-items:center;gap:.6rem;flex:1;min-width:200px">
          <input type="range" id="price-range" min="100" max="800" step="10" value="800" style="flex:1" aria-label="Prix maximum">
          <span id="price-label" style="white-space:nowrap;font-weight:700">Max : 800 DH</span>
        </div>
      </div>

      <p class="result-count" id="result-count"></p>
      <div class="product-grid" id="product-grid"></div>

      <div class="newsletter reveal" style="margin-top:3.5rem">
        <h2 class="section-title" style="font-size:1.7rem">Vous ne trouvez pas votre taille ?</h2>
        <p class="section-sub" style="margin:0 auto">Écrivez-nous sur WhatsApp — on vérifie le stock en boutique en quelques minutes.</p>
        <div style="margin-top:1.4rem"><a class="btn btn-accent" data-wa-link="Bonjour ! Je cherche un article / une taille précise." href="#" target="_blank" rel="noopener">Demander sur WhatsApp →</a></div>
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
