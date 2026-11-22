<?php declare(strict_types=1);
define('APP_RUNNING', true);
/* ============================================================
   API — Produits (GET JSON)
   ?id=p01              → un seul produit
   ?cat=femme           → filtre catégorie
   ?q=robe              → recherche (nom + description)
   ?sort=price_asc | price_desc | newest | featured
   ?max=500             → prix max
   Seuls les produits actifs (active = 1) sont renvoyés.
   ============================================================ */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/shop.php';
require_once __DIR__ . '/../includes/rate_limit.php';

try {
    $pdo = db();
} catch (PDOException $e) {
    json_response(['success' => false, 'error' => 'Base de données indisponible.'], 500);
}

check_global_limit($pdo, client_ip(), false, true);

function row_to_js(array $r): array
{
    return [
        'id' => $r['id'],
        'name' => $r['name'],
        'cat' => $r['cat'],
        'price' => (int) $r['price'],
        'oldPrice' => $r['old_price'] !== null ? (int) $r['old_price'] : 0,
        'img' => $r['img'],
        'colors' => json_decode($r['colors'] ?? '[]', true) ?: [],
        'sizes' => json_decode($r['sizes'] ?? '[]', true) ?: [],
        'badge' => $r['badge'] ?? '',
        'desc' => $r['description'] ?? '',
        'stock' => (int) ($r['stock'] ?? 0),
    ];
}

$id = $_GET['id'] ?? '';
if (is_string($id) && $id !== '') {
    $st = $pdo->prepare('SELECT * FROM products WHERE id = ? AND active = 1');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) {
        json_response(['success' => false, 'error' => 'Produit introuvable.'], 404);
    }
    json_response(['success' => true, 'product' => row_to_js($row)]);
}

$sql = 'SELECT * FROM products WHERE active = 1';
$params = [];

$cat = $_GET['cat'] ?? '';
if (is_string($cat) && in_array($cat, ['femme', 'homme', 'enfant', 'accessoires'], true)) {
    $sql .= ' AND cat = ?';
    $params[] = $cat;
}
$q = trim((string) ($_GET['q'] ?? ''));
if ($q !== '') {
    $sql .= ' AND (name LIKE ? OR description LIKE ?)';
    $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
    $params[] = $like;
    $params[] = $like;
}
$max = $_GET['max'] ?? '';
if (is_numeric($max) && (int) $max > 0) {
    $sql .= ' AND price <= ?';
    $params[] = (int) $max;
}
$sort = $_GET['sort'] ?? 'featured';
$sql .= match ($sort) {
    'price_asc' => ' ORDER BY price ASC',
    'price_desc' => ' ORDER BY price DESC',
    'newest' => ' ORDER BY created_at DESC, id DESC',
    default => ' ORDER BY id',
};

try {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();
    json_response(['success' => true, 'count' => count($rows), 'products' => array_map(row_to_js(...), $rows)]);
} catch (PDOException $e) {
    json_response(['success' => false, 'error' => 'Erreur de lecture du catalogue.'], 500);
}
