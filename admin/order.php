<?php declare(strict_types=1);
define('APP_RUNNING', true);
/* ============================================================
   ADMIN — Détail d'une commande
   - Changement de statut : new → confirmed → shipped →
     delivered, ou cancelled à tout moment avant livraison.
   - Annuler une commande remet les articles en stock.
   - « Marqué payé » : paiement à la livraison, à cocher
     quand l'argent est reçu (en général à la livraison).
   ============================================================ */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

check_global_limit($pdo, client_ip(), true);
require_admin();
$admin = current_admin();
ensure_orders_source($pdo);

$id = (int) ($_GET['id'] ?? 0);
$st = $pdo->prepare(
    'SELECT o.*, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address, c.city AS customer_city
     FROM orders o JOIN customers c ON c.id = o.customer_id WHERE o.id = ?'
);
$st->execute([$id]);
$order = $st->fetch();
if (!$order) {
    admin_header('Commande introuvable', 'orders', $admin);
    echo '<div class="alert alert-err">Commande introuvable.</div><p><a href="orders.php">← Retour aux commandes</a></p>';
    admin_footer();
    exit;
}

$allowed = [
    'new' => ['confirmed', 'cancelled'],
    'confirmed' => ['shipped', 'cancelled'],
    'shipped' => ['delivered'],
    'delivered' => [],
    'cancelled' => [],
];

$message = '';
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_check()) {
        $error = 'Session expirée — rechargez la page et réessayez.';
    } else {
        $newStatus = (string) ($_POST['status'] ?? '');
        $paid = isset($_POST['paid']) ? 1 : 0;
        $current = $order['status'];
        if ($newStatus === $current) {
            // Seul le statut de paiement change.
            $st = $pdo->prepare('UPDATE orders SET paid = ? WHERE id = ?');
            $st->execute([$paid, $id]);
            $order['paid'] = $paid;
            $message = 'Paiement mis à jour.';
        } elseif (!in_array($newStatus, $allowed[$current] ?? [], true)) {
            $error = 'Changement de statut impossible : « ' . status_label($current) . ' » → « ' . status_label($newStatus) . ' ».';
        } else {
            try {
                $pdo->beginTransaction();
                if ($newStatus === 'cancelled') {
                    // On remet les articles en stock.
                    $items = $pdo->prepare('SELECT product_id, qty FROM order_items WHERE order_id = ?');
                    $items->execute([$id]);
                    $stStock = $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
                    foreach ($items->fetchAll() as $it) {
                        $stStock->execute([(int) $it['qty'], $it['product_id']]);
                    }
                }
                $st = $pdo->prepare('UPDATE orders SET status = ?, paid = ?, updated_at = NOW() WHERE id = ?');
                $st->execute([$newStatus, $paid, $id]);
                $pdo->commit();
                $order['status'] = $newStatus;
                $order['paid'] = $paid;
                $message = 'Commande passée au statut « ' . status_label($newStatus) . ' ».'
                    . ($newStatus === 'cancelled' ? ' Stock restauré.' : '');
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Erreur lors de la mise à jour.';
            }
        }
    }
}

$st = $pdo->prepare('SELECT product_id, product_name, price, qty, size FROM order_items WHERE order_id = ? ORDER BY id');
$st->execute([$id]);
$items = $st->fetchAll();

admin_header('Commande ' . $order['order_number'], 'orders', $admin);
if ($message !== '') { echo '<div class="alert alert-ok">' . e($message) . '</div>'; }
if ($error !== '') { echo '<div class="alert alert-err">' . e($error) . '</div>'; }
?>
<p><a href="orders.php">← Toutes les commandes</a> · <a href="../pages/facture.php?n=<?= e(urlencode($order['order_number'])) ?>" target="_blank" rel="noopener">Voir la facture</a></p>

<div class="stat-grid">
  <div class="stat-card"><b><?= e($order['order_number']) ?></b><span>Commande du <?= e($order['created_at']) ?></span></div>
  <div class="stat-card"><b><span class="badge-status st-<?= e($order['status']) ?>"><?= e(status_label($order['status'])) ?></span></b><span>Statut actuel</span></div>
  <div class="stat-card"><b><?= money((int) $order['total']) ?></b><span>Total (livraison incluse)</span></div>
  <div class="stat-card"><b><?= (int) $order['paid'] === 1 ? 'Payé' : 'Non payé' ?></b><span>Paiement à la livraison</span></div>
</div>

<h2 style="font-size:1.25rem;margin:1.4rem 0 .8rem">Cliente / Client</h2>
<table class="admin-table"><tbody>
  <tr><td style="width:180px"><b>Nom</b></td><td><?= e($order['customer_name']) ?></td></tr>
  <tr><td><b>Téléphone</b></td><td><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $order['customer_phone'])) ?>"><?= e($order['customer_phone']) ?></a></td></tr>
  <tr><td><b>Adresse</b></td><td><?= e($order['customer_address'] ?? '') ?>, <?= e($order['customer_city'] ?? '') ?></td></tr>
  <?php if (!empty($order['notes'])): ?><tr><td><b>Notes</b></td><td><?= e($order['notes']) ?></td></tr><?php endif; ?>
</tbody></table>

<h2 style="font-size:1.25rem;margin:1.6rem 0 .8rem">Articles</h2>
<table class="admin-table">
  <thead><tr><th>Article</th><th>Taille</th><th>Prix</th><th>Qté</th><th>Total</th></tr></thead>
  <tbody>
    <?php foreach ($items as $it): ?>
      <tr>
        <td><?= e($it['product_name']) ?></td>
        <td><?= e($it['size'] ?? '—') ?></td>
        <td><?= money((int) $it['price']) ?></td>
        <td><?= (int) $it['qty'] ?></td>
        <td><?= money((int) $it['price'] * (int) $it['qty']) ?></td>
      </tr>
    <?php endforeach; ?>
    <tr><td colspan="4"><b>Sous-total</b></td><td><b><?= money((int) $order['subtotal']) ?></b></td></tr>
    <tr><td colspan="4">Livraison</td><td><?= (int) $order['delivery_fee'] === 0 ? 'Gratuite' : money((int) $order['delivery_fee']) ?></td></tr>
    <tr><td colspan="4"><b>Total</b></td><td><b><?= money((int) $order['total']) ?></b></td></tr>
  </tbody>
</table>

<h2 style="font-size:1.25rem;margin:1.6rem 0 .8rem">Suivi</h2>
<div class="form-card admin-form">
  <form method="post" action="order.php?id=<?= (int) $id ?>">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div class="field">
      <label for="f-status">Statut</label>
      <select id="f-status" name="status">
        <?php foreach (['new', 'confirmed', 'shipped', 'delivered', 'cancelled'] as $s): ?>
          <option value="<?= e($s) ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
        <?php endforeach; ?>
      </select>
      <p class="muted" style="font-size:.85rem;margin-top:.4rem">Parcours normal : Nouvelle → Confirmée → Expédiée → Livrée. « Annulée » remet les articles en stock.</p>
    </div>
    <div class="field">
      <label><input type="checkbox" name="paid" value="1" <?= (int) $order['paid'] === 1 ? 'checked' : '' ?>> Marqué payé (argent reçu — paiement à la livraison)</label>
    </div>
    <button class="btn btn-accent" type="submit">Mettre à jour</button>
  </form>
</div>

<h2 style="font-size:1.25rem;margin:1.6rem 0 .8rem">Détails techniques</h2>
<table class="admin-table"><tbody>
  <tr><td style="width:180px"><b>Source</b></td><td><?= ($order['source'] ?? 'site') === 'whatsapp' ? 'Commande WhatsApp (créée par l\'admin)' : 'Site web' ?></td></tr>
  <tr><td style="width:180px"><b>Clé anti-doublon</b></td><td><code><?= e($order['idempotency_key']) ?></code></td></tr>
  <tr><td><b>IP cliente</b></td><td><?= e($order['ip_address'] ?? '—') ?></td></tr>
  <tr><td><b>Moyen de paiement</b></td><td>Paiement à la livraison (COD)</td></tr>
  <tr><td><b>Dernière mise à jour</b></td><td><?= e($order['updated_at']) ?></td></tr>
</tbody></table>
<?php admin_footer(); ?>
