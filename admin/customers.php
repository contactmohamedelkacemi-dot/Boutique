<?php declare(strict_types=1);
define('APP_RUNNING', true);
/* ============================================================
   ADMIN — Clients (avec nombre de commandes et total dépensé)
   ============================================================ */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

check_global_limit($pdo, client_ip(), true);
require_admin();
$admin = current_admin();

$customers = $pdo->query(
    'SELECT c.id, c.name, c.phone, c.address, c.city, c.created_at,
            COUNT(o.id) AS nb_orders, COALESCE(SUM(o.total), 0) AS total_spent
     FROM customers c
     LEFT JOIN orders o ON o.customer_id = c.id
     GROUP BY c.id
     ORDER BY c.created_at DESC, c.id DESC'
)->fetchAll();

admin_header('Clients', 'customers', $admin);
?>
<?php if ($customers === []): ?>
  <p class="muted">Aucun client pour le moment — les clients sont créés automatiquement à leur première commande.</p>
<?php else: ?>
  <table class="admin-table">
    <thead><tr><th>Nom</th><th>Téléphone</th><th>Adresse</th><th>Commandes</th><th>Total dépensé</th><th>Depuis</th></tr></thead>
    <tbody>
      <?php foreach ($customers as $c): ?>
        <tr>
          <td><b><?= e($c['name']) ?></b></td>
          <td><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $c['phone'])) ?>"><?= e($c['phone']) ?></a></td>
          <td><?= e($c['address'] ?? '') ?><?= $c['city'] ? ', ' . e($c['city']) : '' ?></td>
          <td><?= (int) $c['nb_orders'] ?></td>
          <td><?= money((int) $c['total_spent']) ?></td>
          <td><?= e($c['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
<?php admin_footer(); ?>
