<?php declare(strict_types=1);
define('APP_RUNNING', true);
/* ============================================================
   ADMIN — Créer une commande manuellement
   Pour les commandes reçues par WhatsApp (ou en boutique) :
   l'admin saisit la cliente et les articles, la commande
   entre dans le même système que celles du site — stock
   décrémenté, facture disponible, suivi de statut — avec
   la source « whatsapp » pour la distinguer.
   ============================================================ */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

check_global_limit($pdo, client_ip(), true);
require_admin();
$admin = current_admin();
ensure_orders_source($pdo);
$cfg = shop_config($pdo);

$message = '';
$error = '';

$products = $pdo->query('SELECT id, name, price, stock, sizes FROM products WHERE active = 1 ORDER BY name')->fetchAll();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_check()) {
        $error = 'Session expirée — rechargez la page et réessayez.';
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $status = (string) ($_POST['status'] ?? 'new');
        if (!in_array($status, ['new', 'confirmed'], true)) {
            $status = 'new';
        }
        $pids = $_POST['pid'] ?? [];
        $qtys = $_POST['qty'] ?? [];
        $sizes = $_POST['size'] ?? [];
        $lines = [];
        if (is_array($pids)) {
            foreach ($pids as $i => $pid) {
                $pid = (string) $pid;
                $qty = (int) ($qtys[$i] ?? 0);
                $size = trim((string) ($sizes[$i] ?? ''));
                if ($pid !== '' && $qty >= 1) {
                    $lines[] = ['id' => $pid, 'qty' => min(99, $qty), 'size' => $size === '' ? null : $size];
                }
            }
        }
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $error = 'Nom de la cliente invalide (2 à 100 caractères).';
        } elseif (!preg_match('/^\+?[0-9][0-9\s\-]{7,18}$/', $phone)) {
            $error = 'Téléphone invalide.';
        } elseif ($lines === []) {
            $error = 'Ajoutez au moins un article.';
        } else {
            try {
                $pdo->beginTransaction();
                $ids = array_column($lines, 'id');
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $st = $pdo->prepare("SELECT id, name, price, stock, active FROM products WHERE id IN ($placeholders) FOR UPDATE");
                $st->execute($ids);
                $dbProducts = [];
                foreach ($st->fetchAll() as $row) {
                    $dbProducts[$row['id']] = $row;
                }
                $qtyById = [];
                foreach ($lines as $ln) {
                    $qtyById[$ln['id']] = ($qtyById[$ln['id']] ?? 0) + $ln['qty'];
                }
                $subtotal = 0;
                $clean = [];
                foreach ($lines as $ln) {
                    $p = $dbProducts[$ln['id']] ?? null;
                    if (!$p || (int) $p['active'] !== 1) {
                        throw new RuntimeException('Article introuvable ou inactif.');
                    }
                    if ((int) $p['stock'] < $qtyById[$ln['id']]) {
                        throw new RuntimeException('Stock insuffisant pour : ' . $p['name'] . ' (' . (int) $p['stock'] . ' restant).');
                    }
                    $subtotal += (int) $p['price'] * $ln['qty'];
                    $clean[] = ['id' => $p['id'], 'name' => $p['name'], 'price' => (int) $p['price'], 'qty' => $ln['qty'], 'size' => $ln['size']];
                }
                $freeFrom = (int) ($cfg['freeDeliveryFrom'] ?? 0);
                $feeCfg = (int) ($cfg['deliveryFee'] ?? 20);
                $deliveryFee = ($freeFrom > 0 && $subtotal >= $freeFrom) ? 0 : $feeCfg;
                $total = $subtotal + $deliveryFee;

                $st = $pdo->prepare(
                    'INSERT INTO customers (name, phone, address, city) VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE name = VALUES(name), address = VALUES(address), city = VALUES(city), id = LAST_INSERT_ID(id)'
                );
                $st->execute([$name, $phone, $address, $city]);
                $customerId = (int) $pdo->lastInsertId();

                $key = 'admin-' . bin2hex(random_bytes(12));
                $st = $pdo->prepare(
                    'INSERT INTO orders (order_number, customer_id, status, source, payment_method, paid, subtotal, delivery_fee, total, notes, idempotency_key, ip_address)
                     VALUES (?, ?, ?, \'whatsapp\', \'cod\', 0, ?, ?, ?, ?, ?, ?)'
                );
                $st->execute(['TMP-' . substr(md5($key), 0, 16), $customerId, $status, $subtotal, $deliveryFee, $total, $notes === '' ? null : $notes, $key, client_ip()]);
                $orderId = (int) $pdo->lastInsertId();
                $orderNumber = 'CMD-' . date('Y') . '-' . str_pad((string) $orderId, 4, '0', STR_PAD_LEFT);
                $st = $pdo->prepare('UPDATE orders SET order_number = ? WHERE id = ?');
                $st->execute([$orderNumber, $orderId]);

                $stItem = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, price, qty, size) VALUES (?, ?, ?, ?, ?, ?)');
                $stStock = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
                foreach ($clean as $ln) {
                    $stItem->execute([$orderId, $ln['id'], $ln['name'], $ln['price'], $ln['qty'], $ln['size']]);
                    $stStock->execute([$ln['qty'], $ln['id']]);
                }
                $pdo->commit();
                redirect('order.php?id=' . $orderId);
            } catch (RuntimeException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = $e->getMessage();
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Erreur lors de la création de la commande.';
            }
        }
    }
}

$jsProducts = array_map(static fn(array $p): array => [
    'id' => $p['id'],
    'name' => $p['name'],
    'price' => (int) $p['price'],
    'stock' => (int) $p['stock'],
    'sizes' => json_decode($p['sizes'] ?? '[]', true) ?: [],
], $products);

admin_header('Créer une commande (WhatsApp)', 'order-new', $admin);
if ($message !== '') { echo '<div class="alert alert-ok">' . e($message) . '</div>'; }
if ($error !== '') { echo '<div class="alert alert-err">' . e($error) . '</div>'; }
?>
<p class="muted" style="margin-bottom:1rem">Pour une commande reçue par WhatsApp : remplissez les coordonnées de la cliente et ses articles. La commande apparaît ensuite dans « Commandes » avec la source <b>WhatsApp</b>, le stock est décrémenté, et la facture est disponible sur sa fiche.</p>

<div class="form-card admin-form" style="max-width:760px">
  <form method="post" action="order-new.php" id="new-order-form">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <h2 style="font-size:1.15rem;margin-bottom:.8rem">Cliente / Client</h2>
    <div class="field"><label for="f-name">Nom complet *</label><input id="f-name" name="name" type="text" required minlength="2" maxlength="100"></div>
    <div class="field"><label for="f-phone">Téléphone / WhatsApp *</label><input id="f-phone" name="phone" type="tel" required placeholder="Ex : 06 12 34 56 78"></div>
    <div class="field"><label for="f-address">Adresse de livraison</label><input id="f-address" name="address" type="text" maxlength="200"></div>
    <div class="field"><label for="f-city">Ville</label><input id="f-city" name="city" type="text" maxlength="60" value="Bouznika"></div>

    <h2 style="font-size:1.15rem;margin:1.2rem 0 .8rem">Articles</h2>
    <div id="lines"></div>
    <button type="button" class="btn btn-ghost btn-sm" id="add-line">+ Ajouter un article</button>

    <div class="invoice-lines" style="margin-top:1.2rem">
      <div class="invoice-line"><span>Sous-total</span><b id="t-subtotal">0 DH</b></div>
      <div class="invoice-line"><span>Livraison</span><b id="t-delivery">—</b></div>
      <div class="summary-row total"><span>Total</span><span id="t-total">0 DH</span></div>
    </div>

    <div class="field"><label for="f-status">Statut initial</label>
      <select id="f-status" name="status">
        <option value="new">Nouvelle</option>
        <option value="confirmed">Confirmée</option>
      </select>
    </div>
    <div class="field"><label for="f-notes">Notes (optionnel)</label><textarea id="f-notes" name="notes" rows="2" maxlength="500"></textarea></div>
    <button class="btn btn-accent" type="submit">Créer la commande</button>
    <a class="btn btn-ghost" href="orders.php">Annuler</a>
  </form>
</div>

<script>
const PRODUCTS = <?= json_encode($jsProducts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const FREE_FROM = <?= (int) ($cfg['freeDeliveryFrom'] ?? 0) ?>;
const FEE = <?= (int) ($cfg['deliveryFee'] ?? 20) ?>;
const linesBox = document.getElementById('lines');
const money = (n) => n.toLocaleString('fr-MA') + ' DH';

function sizeOptions(p, selected) {
  const sizes = (p && p.sizes && p.sizes.length) ? p.sizes : ['Unique'];
  return sizes.map((s) => '<option value="' + s.replace(/"/g, '&quot;') + '"' + (s === selected ? ' selected' : '') + '>' + s + '</option>').join('');
}
function addLine() {
  const row = document.createElement('div');
  row.className = 'order-line-row';
  row.innerHTML =
    '<select name="pid[]" class="lp">' + PRODUCTS.map((p) => '<option value="' + p.id + '">' + p.name.replace(/</g, '&lt;') + ' — ' + p.price + ' DH (stock ' + p.stock + ')</option>').join('') + '</select>' +
    '<select name="size[]" class="ls"></select>' +
    '<input name="qty[]" class="lq" type="number" min="1" max="99" value="1">' +
    '<button type="button" class="rm" title="Retirer">×</button>';
  linesBox.appendChild(row);
  const sel = row.querySelector('.lp');
  const sync = () => {
    const p = PRODUCTS.find((x) => x.id === sel.value);
    row.querySelector('.ls').innerHTML = sizeOptions(p);
    totals();
  };
  sel.addEventListener('change', sync);
  row.querySelector('.lq').addEventListener('input', totals);
  row.querySelector('.rm').addEventListener('click', () => { row.remove(); totals(); });
  sync();
}
function totals() {
  let sub = 0;
  linesBox.querySelectorAll('.order-line-row').forEach((row) => {
    const p = PRODUCTS.find((x) => x.id === row.querySelector('.lp').value);
    const q = Math.max(0, parseInt(row.querySelector('.lq').value || '0', 10));
    if (p) sub += p.price * q;
  });
  const fee = sub === 0 || (FREE_FROM > 0 && sub >= FREE_FROM) ? 0 : FEE;
  document.getElementById('t-subtotal').textContent = money(sub);
  document.getElementById('t-delivery').textContent = sub === 0 ? '—' : (fee === 0 ? 'Gratuite' : money(fee));
  document.getElementById('t-total').textContent = money(sub + fee);
}
document.getElementById('add-line').addEventListener('click', addLine);
addLine();
</script>
<?php admin_footer(); ?>
