<?php declare(strict_types=1);
define('APP_RUNNING', true);
/* ============================================================
   FACTURE — Page affichée après une commande réussie.
   Accès protégé : il faut le numéro de commande + la clé
   anti-doublon générée à la commande (impossible à deviner),
   ou être connecté en admin (lien depuis la fiche commande).
   Bouton « Télécharger » = impression / enregistrement PDF
   du navigateur (mise en page d'impression dédiée).
   ============================================================ */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
check_global_limit($pdo, client_ip());
ensure_orders_source($pdo);
$cfg = shop_config($pdo);

$number = trim((string) ($_GET['n'] ?? ''));
$key = (string) ($_GET['k'] ?? '');

$order = null;
if ($number !== '') {
    $st = $pdo->prepare(
        'SELECT o.*, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address, c.city AS customer_city
         FROM orders o JOIN customers c ON c.id = o.customer_id
         WHERE o.order_number = ?'
    );
    $st->execute([$number]);
    $order = $st->fetch() ?: null;
}

$allowed = false;
if ($order !== null) {
    if ($key !== '' && hash_equals((string) $order['idempotency_key'], $key)) {
        $allowed = true;
    } elseif (current_admin() !== null) {
        $allowed = true;
    }
}

$items = [];
if ($order !== null && $allowed) {
    $st = $pdo->prepare('SELECT product_name, price, qty, size FROM order_items WHERE order_id = ? ORDER BY id');
    $st->execute([(int) $order['id']]);
    $items = $st->fetchAll();
}

$statusLabels = [
    'new' => 'Nouvelle', 'confirmed' => 'Confirmée', 'shipped' => 'Expédiée',
    'delivered' => 'Livrée', 'cancelled' => 'Annulée',
];
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?= e($cfg['defaultTheme']) ?>" data-font="<?= e($cfg['font']) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Facture <?= $order !== null ? e($order['order_number']) : '' ?> — <?= e($cfg['name']) ?></title>
  <link rel="stylesheet" href="../css/fonts.css">
  <link rel="stylesheet" href="../css/themes.css">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <div id="app-header"></div>

  <main class="section">
    <div class="container">
      <?php if ($order === null || !$allowed): ?>
        <div class="empty-state">
          <h1 class="section-title" style="font-size:1.7rem">Facture introuvable</h1>
          <p>Ce lien de facture est invalide ou incomplet. Si vous venez de commander, utilisez le lien affiché juste après votre commande.</p>
          <div style="margin-top:1.4rem"><a class="btn btn-accent" href="../index.php">Retour à la boutique</a></div>
        </div>
      <?php else: ?>
        <div class="no-print" style="text-align:center;margin-bottom:1.6rem">
          <h1 class="section-title">Commande confirmée, merci !</h1>
          <p class="section-sub" style="margin:0 auto">On vous appelle très vite pour confirmer la disponibilité et la livraison. <b>Paiement à la livraison</b>, en espèces.</p>
        </div>

        <div class="invoice-doc">
          <div class="invoice-meta" style="margin-top:0">
            <div>
              <h1><?= e($cfg['name']) ?></h1>
              <p><?= e($cfg['address']) ?><br>Tél : <?= e($cfg['phoneDisplay']) ?> — <?= e($cfg['email']) ?></p>
            </div>
            <div style="text-align:right">
              <h1>Facture</h1>
              <p>Commande <b><?= e($order['order_number']) ?></b><br>
              Date : <?= e(date('d/m/Y H:i', strtotime((string) $order['created_at']))) ?><br>
              Statut : <b><?= e($statusLabels[$order['status']] ?? $order['status']) ?></b><?= ($order['source'] ?? 'site') === 'whatsapp' ? ' · via WhatsApp' : '' ?></p>
            </div>
          </div>

          <div class="invoice-meta">
            <div>
              <p><b>Cliente / Client</b><br>
              <?= e($order['customer_name']) ?><br>
              <?= e($order['customer_phone']) ?><br>
              <?= e($order['customer_address']) ?> — <?= e($order['customer_city']) ?></p>
            </div>
            <div>
              <p><b>Paiement</b><br>À la livraison (espèces)</p>
            </div>
          </div>

          <table class="invoice-table">
            <thead><tr><th>Article</th><th>Taille</th><th class="num">Qté</th><th class="num">Prix</th><th class="num">Total</th></tr></thead>
            <tbody>
              <?php foreach ($items as $it): ?>
                <tr>
                  <td><?= e($it['product_name']) ?></td>
                  <td><?= e($it['size'] ?? '—') ?></td>
                  <td class="num"><?= (int) $it['qty'] ?></td>
                  <td class="num"><?= money((int) $it['price']) ?></td>
                  <td class="num"><?= money((int) $it['price'] * (int) $it['qty']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <div class="invoice-totals">
            <div class="summary-row"><span>Sous-total</span><span><?= money((int) $order['subtotal']) ?></span></div>
            <div class="summary-row"><span>Livraison</span><span><?= (int) $order['delivery_fee'] === 0 ? 'Gratuite' : money((int) $order['delivery_fee']) ?></span></div>
            <div class="summary-row total"><span>Total</span><span><?= money((int) $order['total']) ?></span></div>
          </div>

          <?php if (!empty($order['notes'])): ?>
            <p style="margin-top:1.2rem;color:#6b5d4d"><b>Notes :</b> <?= e($order['notes']) ?></p>
          <?php endif; ?>
          <p style="margin-top:1.6rem;color:#6b5d4d;font-size:.9rem">Merci pour votre confiance — <?= e($cfg['name']) ?>, <?= e($cfg['city']) ?>. Échange sous 7 jours en boutique.</p>
        </div>

        <div class="no-print" style="display:flex;gap:.7rem;justify-content:center;flex-wrap:wrap;margin-top:1.8rem">
          <button class="btn btn-accent" onclick="window.print()">Télécharger la facture</button>
          <a class="btn btn-ghost" href="collection.php">Continuer mes achats</a>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <div id="app-footer"></div>

  <script src="../js/config.js"></script>
  <script>window.BOUTIQUE_CONFIG = <?= json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  window.PRODUCTS = [];</script>
  <script src="../js/main.js"></script>
</body>
</html>
