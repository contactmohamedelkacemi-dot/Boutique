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
  <title>Produit — <?= e($cfg['name']) ?></title>
  <meta name="description" content="Fiche produit <?= e($cfg['name']) ?> — choisissez taille et couleur, ajoutez au panier ou commandez via WhatsApp.">
  <link rel="stylesheet" href="../css/fonts.css">
  <link rel="stylesheet" href="../css/themes.css">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <div id="app-header"></div>

  <main class="section">
    <div class="container">
      <p style="margin-bottom:1.6rem"><a href="collection.php" style="color:var(--accent);font-weight:700">← Retour à la collection</a></p>
      <div class="product-detail" id="product-detail"><!-- rempli par js/main.js --></div>

      <section style="margin-top:4rem">
        <span class="eyebrow">Vous aimerez aussi</span>
        <h2 class="section-title" style="font-size:1.8rem">Produits similaires</h2>
        <div class="product-grid" id="related-grid"></div>
      </section>
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
