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
  <title>Panier — <?= e($cfg['name']) ?></title>
  <meta name="description" content="Votre panier <?= e($cfg['name']) ?> — vérifiez vos articles, remplissez le formulaire et commandez sur le site ou via WhatsApp.">
  <link rel="stylesheet" href="../css/fonts.css">
  <link rel="stylesheet" href="../css/themes.css">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <div id="app-header"></div>

  <main class="section">
    <div class="container">
      <span class="eyebrow"><?= e($cfg['name']) ?></span>
      <h1 class="section-title">Votre panier</h1>
      <p class="section-sub">Vérifiez vos articles, remplissez vos coordonnées une seule fois, puis commandez sur le site ou via WhatsApp — paiement à la livraison.</p>

      <div class="empty-state" id="cart-empty" style="display:none">
        <h2 class="section-title" style="font-size:1.6rem">Votre panier est vide</h2>
        <p>Parcourez la collection et ajoutez vos coups de cœur.</p>
        <div style="margin-top:1.4rem"><a class="btn btn-accent" href="collection.php">Découvrir la collection →</a></div>
      </div>

      <div class="cart-layout" id="cart-layout" style="margin-top:2rem">
        <div id="cart-lines"><!-- rempli par js/main.js --></div>

        <!-- ===== Checkout unifié : facture + formulaire + WhatsApp ===== -->
        <aside class="summary" id="checkout-section" style="display:none">
          <h2 style="font-size:1.4rem;margin-bottom:1rem">Finaliser ma commande</h2>

          <!-- La facture, intégrée au formulaire -->
          <div class="invoice-lines" id="invoice-lines"><!-- rempli par js/main.js --></div>

          <div id="checkout-error" style="display:none;background:#fee2e2;color:#991b1b;padding:.8rem 1rem;border-radius:10px;margin-bottom:1rem"></div>
          <form id="checkout-form">
            <div class="field"><label for="of-name">Nom complet *</label><input id="of-name" type="text" placeholder="Ex : Salma Benali" required minlength="2" maxlength="100"></div>
            <div class="field"><label for="of-phone">Téléphone / WhatsApp *</label><input id="of-phone" type="tel" placeholder="Ex : 06 12 34 56 78" required></div>
            <div class="field"><label for="of-address">Adresse de livraison *</label><input id="of-address" type="text" placeholder="Ex : 12, Rue des Fleurs, Bouznika" required minlength="5" maxlength="200"></div>
            <div class="field"><label for="of-city">Ville *</label><input id="of-city" type="text" placeholder="Ex : Bouznika" required maxlength="60" value="Bouznika"></div>
            <div class="field"><label for="of-notes">Notes (optionnel)</label><textarea id="of-notes" rows="2" maxlength="500" placeholder="Ex : appeler avant de livrer, 2e étage…"></textarea></div>
            <button class="btn btn-accent btn-block" type="submit" id="checkout-submit">Commander — paiement à la livraison</button>
          </form>

          <p style="color:var(--muted);font-size:.88rem;margin-top:.9rem;text-align:center">— ou commandez via —</p>
          <button class="btn btn-dark btn-block" id="wa-order" type="button" style="margin-top:.4rem">Commander via WhatsApp</button>

          <p style="color:var(--muted);font-size:.88rem;margin-top:1rem">Paiement à la livraison ou en boutique. Échange sous 7 jours. Livraison gratuite dès <?= (int) $cfg['freeDeliveryFrom'] ?> DH.</p>
          <div style="margin-top:1rem"><a class="btn btn-ghost btn-block" href="collection.php">← Continuer mes achats</a></div>
        </aside>
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
