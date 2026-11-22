<?php declare(strict_types=1);
define('APP_RUNNING', true);
/* ============================================================
   ADMIN — Commandes (liste + filtre par statut)
   ============================================================ */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

check_global_limit($pdo, client_ip(), true);
require_admin();
$admin = current_admin();
ensure_orders_source($pdo);

$statuses = ['all' => 'Toutes', 'new' => 'Nouvelles', 'confirmed' => 'Confirmées', 'shipped' => 'Expédiées', 'delivered' => 'Livrées', 'cancelled' => 'Annulées'];
$filter = (string) ($_GET['status'] ?? 'all');
if (!isset($statuses[$filter])) {
    $filter = 'all';
}

$sql = 'SELECT o.id, o.order_number, o.total, o.status, o.source, o.paid, o.created_at, c.name AS customer_name, c.phone AS customer_phone
        FROM orders o JOIN customers c ON c.id = o.customer_id';
$params = [];
if ($filter !== 'all') {
    $sql .= ' WHERE o.status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY o.created_at DESC, o.id DESC LIMIT 200';
$st = $pdo->prepare($sql);
$st->execute($params);
$orders = $st->fetchAll();

admin_header('Commandes', 'orders', $admin);
?>
<p><a class="btn btn-accent" href="order-new.php">+ Créer une commande (WhatsApp)</a></p>
<div class="filters-row">
  <?php foreach ($statuses as $key => $label): ?>
    <a href="orders.php?status=<?= e($key) ?>" class="<?= $filter === $key ? 'active' : '' ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</div>

<?php if ($orders === []): ?>
  <p class="muted">Aucune commande<?= $filter !== 'all' ? ' avec ce statut' : '' ?>.</p>
<?php else: ?>
  <table class="admin-table">
    <thead><tr><th>N°</th><th>Cliente / Client</th><th>Source</th><th>Total</th><th>Payé</th><th>Statut</th><th>Date</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td><b><?= e($o['order_number']) ?></b></td>
          <td><?= e($o['customer_name']) ?><br><span class="muted"><?= e($o['customer_phone']) ?></span></td>
          <td><?= ($o['source'] ?? 'site') === 'whatsapp' ? '<span class="src-tag src-whatsapp">WhatsApp</span>' : '<span class="src-tag src-site">Site</span>' ?></td>
          <td><?= money((int) $o['total']) ?></td>
          <td><?= (int) $o['paid'] === 1 ? 'Oui' : 'Non' ?></td>
          <td><span class="badge-status st-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
          <td><?= e($o['created_at']) ?></td>
          <td><a href="order.php?id=<?= (int) $o['id'] ?>">Voir →</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
<?php admin_footer(); ?>
