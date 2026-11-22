<?php declare(strict_types=1);
define('APP_RUNNING', true);
/* ============================================================
   API — Commande (POST JSON)
   Corps : {items:[{id, qty, size}], customer:{name, phone,
            address, city, notes}, idempotency_key}
   - Paiement à la livraison (COD) uniquement, pas de
     passerelle de paiement.
   - Anti-doublon : si idempotency_key existe déjà, on
     renvoie la commande EXISTANTE (duplicate:true, HTTP 200)
     sans créer de 2e commande (réseau qui renvoie 2 fois).
   - Rate limit : max 10 commandes / heure par IP (HTTP 429).
   - Les prix viennent de la base (jamais du client).
   - Stock vérifié + décrémenté dans une transaction.
   - Numéro : CMD-AAAA-NNNN (ex : CMD-2026-0001).
   ============================================================ */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/shop.php';
require_once __DIR__ . '/../includes/rate_limit.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(['success' => false, 'error' => 'Méthode non autorisée.'], 405);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw !== false ? $raw : '', true);
if (!is_array($data)) {
    json_response(['success' => false, 'error' => 'Corps JSON invalide.'], 400);
}

try {
    $pdo = db();
} catch (PDOException $e) {
    json_response(['success' => false, 'error' => 'Base de données indisponible.'], 500);
}

check_global_limit($pdo, client_ip(), false, true);

// ---------- Validation ----------
$key = $data['idempotency_key'] ?? '';
if (!is_string($key) || !preg_match('/^[A-Za-z0-9\-_]{8,64}$/', $key)) {
    json_response(['success' => false, 'error' => 'Clé de commande manquante ou invalide.'], 400);
}

$customer = $data['customer'] ?? [];
$name = trim((string) ($customer['name'] ?? ''));
$phone = trim((string) ($customer['phone'] ?? ''));
$address = trim((string) ($customer['address'] ?? ''));
$city = trim((string) ($customer['city'] ?? ''));
$notes = trim((string) ($customer['notes'] ?? ''));
if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    json_response(['success' => false, 'error' => 'Nom invalide (2 à 100 caractères).'], 400);
}
if (!preg_match('/^\+?[0-9][0-9\s\-]{7,18}$/', $phone)) {
    json_response(['success' => false, 'error' => 'Téléphone invalide.'], 400);
}
if (mb_strlen($address) < 5 || mb_strlen($address) > 200) {
    json_response(['success' => false, 'error' => 'Adresse invalide (5 à 200 caractères).'], 400);
}
if (mb_strlen($city) > 60) {
    json_response(['success' => false, 'error' => 'Ville invalide.'], 400);
}
if (mb_strlen($notes) > 500) {
    json_response(['success' => false, 'error' => 'Notes trop longues (max 500 caractères).'], 400);
}

$items = $data['items'] ?? [];
if (!is_array($items) || $items === [] || count($items) > 20) {
    json_response(['success' => false, 'error' => 'Panier vide ou trop grand.'], 400);
}
$cleanItems = [];
foreach ($items as $it) {
    if (!is_array($it) || !is_string($it['id'] ?? null) || $it['id'] === '' || mb_strlen($it['id']) > 16) {
        json_response(['success' => false, 'error' => 'Article invalide.'], 400);
    }
    $qty = $it['qty'] ?? 0;
    if ((!is_int($qty) && !(is_float($qty) && (int) $qty === $qty)) || (int) $qty < 1 || (int) $qty > 9) {
        json_response(['success' => false, 'error' => 'Quantité invalide (1 à 9).'], 400);
    }
    $size = isset($it['size']) ? trim((string) $it['size']) : '';
    if (mb_strlen($size) > 30) {
        json_response(['success' => false, 'error' => 'Taille invalide.'], 400);
    }
    $cleanItems[] = ['id' => $it['id'], 'qty' => (int) $qty, 'size' => $size === '' ? null : $size];
}

$ip = client_ip();

try {
    // ---------- Anti-doublon : clé déjà vue ? ----------
    $st = $pdo->prepare('SELECT order_number, total, status FROM orders WHERE idempotency_key = ?');
    $st->execute([$key]);
    $existing = $st->fetch();
    if ($existing) {
        json_response([
            'success' => true,
            'duplicate' => true,
            'order_number' => $existing['order_number'],
            'total' => (int) $existing['total'],
            'status' => $existing['status'],
            'message' => 'Cette commande existe déjà (envoi double ignoré).',
        ], 200);
    }

    // ---------- Rate limit : 10 commandes / heure / IP ----------
    if (check_order_limit($pdo, $ip)) {
        json_response(['success' => false, 'error' => 'Trop de commandes depuis cette adresse. Réessayez dans une heure.'], 429);
    }

    // ---------- Transaction : stock + commande ----------
    $pdo->beginTransaction();

    // Verrouille les lignes produits (évite 2 commandes qui
    // vident le même stock en même temps).
    $ids = array_column($cleanItems, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("SELECT id, name, price, stock, active FROM products WHERE id IN ($placeholders) FOR UPDATE");
    $st->execute($ids);
    $dbProducts = [];
    foreach ($st->fetchAll() as $row) {
        $dbProducts[$row['id']] = $row;
    }

    $subtotal = 0;
    $lines = [];
    // Quantité totale par produit (2 lignes du même produit, même
    // tailles différentes, partagent le même stock) — c'est ce total
    // qui est comparé au stock, pas chaque ligne séparément.
    $qtyById = [];
    foreach ($cleanItems as $it) {
        $qtyById[$it['id']] = ($qtyById[$it['id']] ?? 0) + $it['qty'];
    }
    foreach ($cleanItems as $it) {
        $p = $dbProducts[$it['id']] ?? null;
        if (!$p || (int) $p['active'] !== 1) {
            throw new RuntimeException('Article introuvable ou indisponible : ' . $it['id']);
        }
        if ((int) $p['stock'] < $qtyById[$it['id']]) {
            throw new RuntimeException('Stock insuffisant pour : ' . $p['name']);
        }
        $lineTotal = (int) $p['price'] * $it['qty'];
        $subtotal += $lineTotal;
        $lines[] = [
            'id' => $p['id'], 'name' => $p['name'],
            'price' => (int) $p['price'], 'qty' => $it['qty'], 'size' => $it['size'],
        ];
    }

    $cfg = shop_config($pdo);
    $freeFrom = (int) ($cfg['freeDeliveryFrom'] ?? 0);
    $feeCfg = (int) ($cfg['deliveryFee'] ?? 20);
    $deliveryFee = ($freeFrom > 0 && $subtotal >= $freeFrom) ? 0 : $feeCfg;
    $total = $subtotal + $deliveryFee;

    // Client : réutilisé par téléphone, sinon créé.
    $st = $pdo->prepare(
        'INSERT INTO customers (name, phone, address, city) VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE name = VALUES(name), address = VALUES(address), city = VALUES(city), id = LAST_INSERT_ID(id)'
    );
    $st->execute([$name, $phone, $address, $city]);
    $customerId = (int) $pdo->lastInsertId();

    // Commande (numéro provisoire unique, remplacé juste après).
    // Le provisoire est un hash de la clé (20 car. max) : 2 clés
    // différentes ne peuvent pas donner le même provisoire.
    $st = $pdo->prepare(
        'INSERT INTO orders (order_number, customer_id, status, payment_method, paid, subtotal, delivery_fee, total, notes, idempotency_key, ip_address)
         VALUES (?, ?, \'new\', \'cod\', 0, ?, ?, ?, ?, ?, ?)'
    );
    $st->execute(['TMP-' . substr(md5($key), 0, 16), $customerId, $subtotal, $deliveryFee, $total, $notes === '' ? null : $notes, $key, $ip]);
    $orderId = (int) $pdo->lastInsertId();
    $orderNumber = 'CMD-' . date('Y') . '-' . str_pad((string) $orderId, 4, '0', STR_PAD_LEFT);
    $st = $pdo->prepare('UPDATE orders SET order_number = ? WHERE id = ?');
    $st->execute([$orderNumber, $orderId]);

    // Lignes + décrément du stock.
    $stItem = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, price, qty, size) VALUES (?, ?, ?, ?, ?, ?)');
    $stStock = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
    foreach ($lines as $ln) {
        $stItem->execute([$orderId, $ln['id'], $ln['name'], $ln['price'], $ln['qty'], $ln['size']]);
        $stStock->execute([$ln['qty'], $ln['id']]);
    }

    $pdo->commit();

    json_response([
        'success' => true,
        'duplicate' => false,
        'order_number' => $orderNumber,
        'total' => $total,
        'status' => 'new',
        'message' => 'Commande enregistrée. Paiement à la livraison.',
    ], 201);
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['success' => false, 'error' => $e->getMessage()], 400);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Doublon de clé en concurrence : on renvoie la commande existante.
    if (($e->errorInfo[1] ?? 0) === 1062) {
        $st = $pdo->prepare('SELECT order_number, total, status FROM orders WHERE idempotency_key = ?');
        $st->execute([$key]);
        $existing = $st->fetch();
        if ($existing) {
            json_response([
                'success' => true, 'duplicate' => true,
                'order_number' => $existing['order_number'],
                'total' => (int) $existing['total'],
                'status' => $existing['status'],
                'message' => 'Cette commande existe déjà (envoi double ignoré).',
            ], 200);
        }
    }
    json_response(['success' => false, 'error' => 'Erreur lors de l\'enregistrement de la commande.'], 500);
}
