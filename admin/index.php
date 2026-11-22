<?php declare(strict_types=1);
define('APP_RUNNING', true);
/* ============================================================
   ADMIN — Tableau de bord e-commerce
   Vue d'ensemble : ventes du jour, chiffre d'affaires,
   graphique des 14 derniers jours, commandes par statut
   et par source (site / WhatsApp), top produits, dernières
   commandes, alertes stock.
   ============================================================ */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

check_global_limit($pdo, client_ip(), true);
require_admin();
$admin = current_admin();
ensure_orders_source($pdo);

// ---------- Indicateurs ----------
$todayRow = $pdo->query("SELECT COUNT(*) AS n, COALESCE(SUM(total),0) AS s FROM orders WHERE DATE(created_at) = CURDATE() AND status <> 'cancelled'")->fetch();
$todayCount = (int) $todayRow['n'];
$todaySum = (int) $todayRow['s'];
$revenue = (int) ($pdo->query("SELECT COALESCE(SUM(total),0) AS s FROM orders WHERE status = 'delivered'")->fetch()['s'] ?? 0);
$monthRevenue = (int) ($pdo->query("SELECT COALESCE(SUM(total),0) AS s FROM orders WHERE status <> 'cancelled' AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')")->fetch()['s'] ?? 0);
$nbOrdersNew = (int) $pdo->query("SELECT COUNT(*) AS n FROM orders WHERE status = 'new'")->fetch()['n'];
$nbProducts = (int) $pdo->query('SELECT COUNT(*) AS n FROM products')->fetch()['n'];
$nbCustomers = (int) $pdo->query('SELECT COUNT(*) AS n FROM customers')->fetch()['n'];
$nbRupture = (int) $pdo->query('SELECT COUNT(*) AS n FROM products WHERE stock = 0 AND active = 1')->fetch()['n'];
$nbWhatsapp = (int) $pdo->query("SELECT COUNT(*) AS n FROM orders WHERE source = 'whatsapp'")->fetch()['n'];

// ---------- Commandes par statut ----------
$byStatus = ['new' => 0, 'confirmed' => 0, 'shipped' => 0, 'delivered' => 0, 'cancelled' => 0];
foreach ($pdo->query('SELECT status, COUNT(*) AS n FROM orders GROUP BY status')->fetchAll() as $r) {
    $byStatus[$r['status']] = (int) $r['n'];
}

// ---------- Graphique : CA des 14 derniers jours ----------
$daily = [];
$rows = $pdo->query(
    "SELECT DATE(created_at) AS d, COALESCE(SUM(total),0) AS s
     FROM orders
     WHERE status <> 'cancelled' AND created_at >= CURDATE() - INTERVAL 13 DAY
     GROUP BY DATE(created_at)"
)->fetchAll();
foreach ($rows as $r) {
    $daily[$r['d']] = (int) $r['s'];
}
$days = [];
$maxDay = 1;
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $v = $daily[$d] ?? 0;
    $maxDay = max($maxDay, $v);
    $days[] = ['label' => date('d/m', strtotime($d)), 'value' => $v];
}

// ---------- Top produits vendus ----------
$topProducts = $pdo->query(
    'SELECT oi.product_name, SUM(oi.qty) AS qty, SUM(oi.qty * oi.price) AS amount
     FROM order_items oi JOIN orders o ON o.id = oi.order_id
     WHERE o.status <> \'cancelled\'
     GROUP BY oi.product_id, oi.product_name
     ORDER BY qty DESC LIMIT 5'
)->fetchAll();

// ---------- Dernières commandes ----------
$latest = $pdo->query(
    'SELECT o.id, o.order_number, o.total, o.status, o.source, o.created_at, c.name AS customer_name
     FROM orders o JOIN customers c ON c.id = o.customer_id
     ORDER BY o.created_at DESC, o.id DESC LIMIT 8'
)->fetchAll();

// ---------- Stock faible / ruptures ----------
$lowStock = $pdo->query('SELECT id, name, stock FROM products WHERE stock < 5 AND active = 1 ORDER BY stock ASC LIMIT 10')->fetchAll();

admin_header('Tableau de bord', 'dashboard', $admin);
?>
<div class="stat-grid">
  <div class="stat-card"><b><?= $todayCount ?></b><span>Commande(s) aujourd'hui — <?= money($todaySum) ?></span></div>
  <div class="stat-card"><b><?= money($monthRevenue) ?></b><span>Chiffre d'affaires ce mois-ci</span></div>
  <div class="stat-card"><b><?= money($revenue) ?></b><span>CA total (commandes livrées)</span></div>
  <div class="stat-card"><b><?= $nbOrdersNew ?></b><span>Commande(s) en attente</span></div>
</div>
<div class="stat-grid">
  <div class="stat-card"><b><?= $nbProducts ?></b><span>Produit(s) au catalogue</span></div>
  <div class="stat-card"><b><?= $nbCustomers ?></b><span>Cliente(s) / Client(s)</span></div>
  <div class="stat-card"><b><?= $nbWhatsapp ?></b><span>Commande(s) via WhatsApp</span></div>
  <div class="stat-card"><b><?= $nbRupture ?></b><span>Produit(s) en rupture de stock</span></div>
</div>

<div class="dash-grid">
  <div>
    <div class="dash-card">
      <h2>Chiffre d'affaires — 14 derniers jours</h2>
      <div class="chart">
        <?php foreach ($days as $d): ?>
          <div class="bar-col" title="<?= e($d['label']) ?> : <?= money($d['value']) ?>">
            <span class="bar-val"><?= $d['value'] > 0 ? number_format($d['value'], 0, ',', ' ') : '' ?></span>
            <div class="bar" style="height:<?= max(2, (int) round($d['value'] / $maxDay * 100)) ?>%"></div>
            <span class="bar-lab"><?= e($d['label']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="dash-card">
      <h2>Dernières commandes</h2>
      <?php if ($latest === []): ?>
        <p class="muted">Aucune commande pour le moment.</p>
      <?php else: ?>
        <table class="admin-table">
          <thead><tr><th>N°</th><th>Cliente / Client</th><th>Source</th><th>Total</th><th>Statut</th><th>Date</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($latest as $o): ?>
              <tr>
                <td><b><?= e($o['order_number']) ?></b></td>
                <td><?= e($o['customer_name']) ?></td>
                <td><?= ($o['source'] ?? 'site') === 'whatsapp' ? '<span class="src-tag src-whatsapp">WhatsApp</span>' : '<span class="src-tag src-site">Site</span>' ?></td>
                <td><?= money((int) $o['total']) ?></td>
                <td><span class="badge-status st-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
                <td><?= e($o['created_at']) ?></td>
                <td><a href="order.php?id=<?= (int) $o['id'] ?>">Voir →</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <p style="margin-top:.8rem"><a href="orders.php">Toutes les commandes →</a> · <a href="order-new.php">Créer une commande (WhatsApp) →</a></p>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <div class="dash-card">
      <h2>Commandes par statut</h2>
      <div class="status-pills">
        <?php foreach (['new', 'confirmed', 'shipped', 'delivered', 'cancelled'] as $s): ?>
          <a href="orders.php?status=<?= e($s) ?>"><span class="badge-status st-<?= e($s) ?>"><?= e(status_label($s)) ?></span> <b><?= $byStatus[$s] ?></b></a>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="dash-card">
      <h2>Top produits vendus</h2>
      <?php if ($topProducts === []): ?>
        <p class="muted">Aucune vente pour le moment.</p>
      <?php else: ?>
        <table class="admin-table">
          <thead><tr><th>Produit</th><th>Qté</th><th>Montant</th></tr></thead>
          <tbody>
            <?php foreach ($topProducts as $tp): ?>
              <tr><td><?= e($tp['product_name']) ?></td><td><?= (int) $tp['qty'] ?></td><td><?= money((int) $tp['amount']) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="dash-card">
      <h2>Stock faible (&lt; 5)</h2>
      <?php if ($lowStock === []): ?>
        <p class="muted">Aucun produit en stock faible.</p>
      <?php else: ?>
        <table class="admin-table">
          <thead><tr><th>Produit</th><th>Stock</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($lowStock as $p): ?>
              <tr>
                <td><?= e($p['name']) ?></td>
                <td><?= (int) $p['stock'] === 0 ? '<b style="color:#b3261e">Rupture</b>' : '<b style="color:#b3261e">' . (int) $p['stock'] . '</b>' ?></td>
                <td><a href="products.php?edit=<?= e($p['id']) ?>">Modifier →</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php admin_footer(); ?>
