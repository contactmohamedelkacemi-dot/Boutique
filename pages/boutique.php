<?php declare(strict_types=1);
define('APP_RUNNING', true);
require_once __DIR__ . '/../includes/bootstrap.php';
check_global_limit($pdo, client_ip());
$cfg = shop_config($pdo);
$jsConfig = $cfg;
$jsProducts = products_for_js($pdo);
$jsCats = categories_list($pdo);
$img = $cfg['images'];
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?= e($cfg['defaultTheme']) ?>" data-font="<?= e($cfg['font']) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>La Boutique — <?= e($cfg['name']) ?></title>
  <meta name="description" content="L'histoire de <?= e($cfg['name']) ?> à Bouznika : une sélection soignée, des prix honnêtes et un accueil chaleureux.">
  <link rel="stylesheet" href="../css/fonts.css">
  <link rel="stylesheet" href="../css/themes.css">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <div id="app-header"></div>

  <main class="section">
    <div class="container">
      <div class="about-grid">
        <div>
          <span class="eyebrow reveal">Notre histoire</span>
          <h1 class="section-title reveal">Une boutique de quartier, une sélection de passionnée</h1>
          <p class="reveal" style="color:var(--muted);margin-top:.6rem"><?= e($cfg['name']) ?> est née d'une idée simple : proposer à Bouznika
            des vêtements modernes et de qualité, sans devoir aller jusqu'à Casablanca.
            Chaque pièce est choisie en main propre, essayée, et ne rentre en boutique
            que si elle vaut vraiment son prix.</p>
          <p class="reveal" style="color:var(--muted);margin-top:.8rem">Aujourd'hui, la boutique habille toute la famille — femme, homme
            et enfant — avec des nouveautés chaque semaine et des conseils honnêtes,
            jamais forcés.</p>
          <div class="hero-stats reveal" style="margin-top:1.8rem">
            <div><b>2021</b><span>Année d'ouverture</span></div>
            <div><b>500+</b><span>Articles en boutique</span></div>
            <div><b>4.8★</b><span>76 avis clients</span></div>
          </div>
        </div>
        <div class="about-img reveal">
          <img src="../<?= e($img['boutique_1']) ?>" alt="Rayon de la boutique" loading="lazy">
        </div>
      </div>

      <div class="values-grid">
        <article class="value-card reveal"><div class="ico">✦</div><h3>Qualité d'abord</h3><p>Chaque tissu est vérifié avant d'entrer en boutique. Si ça ne tient pas au lavage, ça ne rentre pas.</p></article>
        <article class="value-card reveal"><div class="ico">◆</div><h3>Prix honnêtes</h3><p>Des prix affichés clairement, des promos régulières — sans gonfler les prix avant de les baisser.</p></article>
        <article class="value-card reveal"><div class="ico">●</div><h3>Accueil chaleureux</h3><p>On vous conseille, on vous aide à choisir votre taille — et on vous dit quand un article ne vous va pas.</p></article>
      </div>

      <div class="about-grid" style="margin-top:4rem">
        <div class="form-card reveal">
          <span class="eyebrow">Horaires d'ouverture</span>
          <h2 class="section-title" style="font-size:1.7rem">Passez nous voir</h2>
          <table class="hours-table">
            <tr><td>Lundi – Samedi</td><td>10:00 – 20:00</td></tr>
            <tr><td>Dimanche</td><td>11:00 – 19:00</td></tr>
          </table>
          <p style="color:var(--muted);margin-top:1rem"><b>Adresse :</b> <span data-config-address>12, Rue des Fleurs, Bouznika, Maroc</span><br>
          <b>Tél :</b> <span data-config-phone>06 00 00 00 00</span></p>
          <div style="margin-top:1.2rem;display:flex;gap:.6rem;flex-wrap:wrap">
            <a class="btn btn-accent" href="contact.php">Nous trouver →</a>
            <a class="btn btn-ghost" data-wa-link="Bonjour ! Je voudrais un renseignement." href="#" target="_blank" rel="noopener">WhatsApp</a>
          </div>
        </div>
        <div class="about-img reveal">
          <img src="../<?= e($img['boutique_2']) ?>" alt="Sélection de vêtements" loading="lazy">
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
