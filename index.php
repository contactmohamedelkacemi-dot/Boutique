<?php declare(strict_types=1);
define('APP_RUNNING', true);
require_once __DIR__ . '/includes/bootstrap.php';
check_global_limit($pdo, client_ip());
$cfg = shop_config($pdo);
$jsConfig = $cfg;
$jsProducts = products_for_js($pdo);
$jsCats = categories_list($pdo);
$allSettings = load_settings($pdo);
$img = $cfg['images'];
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?= e($cfg['defaultTheme']) ?>" data-font="<?= e($cfg['font']) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($cfg['name']) ?> — Mode & élégance à Bouznika</title>
  <meta name="description" content="<?= e($cfg['name']) ?> — vêtements femme, homme et enfant à Bouznika. Nouvelle collection, livraison partout au Maroc, commande via WhatsApp.">
  <link rel="stylesheet" href="css/fonts.css">
  <link rel="stylesheet" href="css/themes.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <div id="app-header"></div>

  <!-- HERO + fond 3D (Three.js, kayt7erek m3a scroll) -->
  <section class="hero">
    <canvas id="bg3d" aria-hidden="true"></canvas>
    <div class="container hero-grid">
      <div class="hero-inner">
        <span class="eyebrow">✦ Nouvelle collection — Été 2026</span>
        <h1>L'élégance commence <em>ici.</em></h1>
        <p>Des pièces choisies avec soin, des prix honnêtes, et un accueil
          chaleureux en boutique à Bouznika. Découvrez la nouvelle collection
          femme, homme et enfant.</p>
        <div class="hero-cta">
          <a class="btn btn-accent" href="pages/collection.php">Découvrir la collection →</a>
          <a class="btn btn-ghost" href="pages/boutique.php">Notre histoire</a>
        </div>
        <div class="hero-stats">
          <div><b>500+</b><span>Articles en boutique</span></div>
          <div><b>4.8★</b><span>76 avis clients</span></div>
          <div><b>24h</b><span>Livraison à Bouznika</span></div>
        </div>
      </div>
      <figure class="hero-img-card">
        <img src="<?= e($img['hero']) ?>" alt="Intérieur de la boutique">
        <figcaption><span>Nouvelle collection — Été 2026</span><b>À partir de 129 DH</b></figcaption>
      </figure>
    </div>
  </section>

  <!-- Marquee -->
  <div class="marquee" aria-hidden="true">
    <div class="marquee-track">
      <span>Livraison gratuite dès <b>300 DH</b></span><span>✦</span>
      <span>Échange sous <b>7 jours</b></span><span>✦</span>
      <span>Paiement à la <b>livraison</b></span><span>✦</span>
      <span>Nouveautés <b>chaque semaine</b></span><span>✦</span>
      <span>Livraison gratuite dès <b>300 DH</b></span><span>✦</span>
      <span>Échange sous <b>7 jours</b></span><span>✦</span>
      <span>Paiement à la <b>livraison</b></span><span>✦</span>
      <span>Nouveautés <b>chaque semaine</b></span><span>✦</span>
    </div>
  </div>

  <!-- Catégories -->
  <section class="section">
    <div class="container">
      <span class="eyebrow reveal">Nos univers</span>
      <h2 class="section-title reveal">Choisissez votre style</h2>
      <div class="cat-grid">
        <?php foreach ($jsCats as $slug => $name): ?>
          <a class="cat-card reveal" href="pages/collection.php?cat=<?= e($slug) ?>">
            <img src="<?= e($img['cat_' . $slug] ?? 'assets/placeholder.svg') ?>" alt="<?= e($name) ?>" loading="lazy">
            <div><h3><?= e($name) ?></h3><p><?= e(category_subtitle($allSettings, $slug)) ?></p></div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Nouveautés -->
  <section class="section" style="padding-top:0">
    <div class="container">
      <span class="eyebrow reveal">Fraîchement arrivé</span>
      <h2 class="section-title reveal">Nos nouveautés</h2>
      <p class="section-sub reveal">Les pièces que nos clientes adorent en ce moment — quantités limitées.</p>
      <div class="product-grid" id="home-new"></div>
      <div class="center" style="margin-top:2.4rem">
        <a class="btn btn-ghost" href="pages/collection.php">Voir toute la collection →</a>
      </div>
    </div>
  </section>

  <!-- Promo -->
  <section class="section" style="padding-top:0">
    <div class="container">
      <div class="promo reveal">
        <img src="<?= e($img['promo']) ?>" alt="Collection été" loading="lazy">
        <div class="promo-content">
          <span class="eyebrow">Offre limitée</span>
          <h2>Jusqu'à -30% sur une sélection</h2>
          <p>Robes, pulls et sneakers en promo — jusqu'à épuisement du stock en boutique.</p>
          <a class="btn btn-accent" href="pages/collection.php?sort=promo">Voir les promos →</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Avis -->
  <section class="section" style="padding-top:0">
    <div class="container">
      <span class="eyebrow reveal">Elles en parlent</span>
      <h2 class="section-title reveal">Ce que disent nos clientes</h2>
      <div class="review-grid">
        <article class="review-card reveal"><div class="stars">★★★★★</div><p>"Qualité au top et prix très corrects. La robe d'été est devenue ma préférée — je reviens chaque semaine !"</p><b>Salma B.</b><span>Cliente à Bouznika</span></article>
        <article class="review-card reveal"><div class="stars">★★★★★</div><p>"Accueil adorable, on m'a aidée à choisir ma taille. Livraison le lendemain à Casablanca, nickel."</p><b>Karima M.</b><span>Commande WhatsApp</span></article>
        <article class="review-card reveal"><div class="stars">★★★★★</div><p>"Enfin une boutique avec du choix pour enfants sans exploser le budget. Le site est super simple à utiliser."</p><b>Yasmine E.</b><span>Maman de deux enfants</span></article>
      </div>
    </div>
  </section>

  <!-- Newsletter -->
  <section class="section" style="padding-top:0">
    <div class="container">
      <div class="newsletter reveal">
        <span class="eyebrow">Restez inspirée</span>
        <h2 class="section-title">Recevez nos nouveautés en avant-première</h2>
        <p class="section-sub" style="margin:0 auto">Nouveautés, promos et looks — une fois par semaine, sans spam.</p>
        <form id="newsletter-form">
          <input type="email" placeholder="Votre adresse email" required aria-label="Email">
          <button class="btn btn-accent" type="submit">S'inscrire</button>
        </form>
      </div>
    </div>
  </section>

  <div id="app-footer"></div>

  <script src="js/config.js"></script>
  <script>window.BOUTIQUE_CONFIG = <?= json_encode($jsConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  window.PRODUCTS = <?= json_encode($jsProducts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  window.CATEGORIES = <?= json_encode($jsCats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
  <script src="js/main.js"></script>
  <script src="js/vendor/three.min.js"></script>
  <script src="js/three-bg.js"></script>
</body>
</html>
