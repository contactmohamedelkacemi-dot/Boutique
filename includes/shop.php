<?php declare(strict_types=1);
defined('APP_RUNNING') or exit('Accès interdit.');
/* ============================================================
   SHOP — Paramètres boutique + catalogue (depuis la base)
   La configuration JS (window.BOUTIQUE_CONFIG) et le catalogue
   (window.PRODUCTS) sont injectés dans les pages .php depuis
   la base de données — plus de dépendance à js/products.js.
   js/config.js reste chargé en fallback (valeurs par défaut).
   ============================================================ */

/** Toutes les settings (clé => valeur) depuis la table settings. */
function load_settings(PDO $pdo): array
{
    $out = [];
    try {
        $rows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        foreach ($rows as $r) {
            $out[$r['setting_key']] = $r['setting_value'];
        }
    } catch (PDOException $e) {
        // Table absente (avant install.php) : on utilise les défauts.
    }
    return $out;
}

/** Config boutique au format attendu par js/main.js.
 *  Les settings en base écrasent les valeurs par défaut. */
function shop_config(PDO $pdo): array
{
    $cfg = DEFAULT_CONFIG;
    $s = load_settings($pdo);
    if ($s === []) {
        return $cfg;
    }
    $map = [
        'shop_name' => 'name', 'tagline' => 'tagline', 'city' => 'city',
        'address' => 'address', 'phone_display' => 'phoneDisplay',
        'phone_href' => 'phoneHref', 'whatsapp' => 'whatsapp',
        'email' => 'email', 'instagram' => 'instagram',
        'hours_short' => 'hoursShort', 'map_query' => 'mapQuery',
        'currency' => 'currency', 'default_theme' => 'defaultTheme',
    ];
    foreach ($map as $dbKey => $jsKey) {
        if (isset($s[$dbKey]) && $s[$dbKey] !== '') {
            $cfg[$jsKey] = $s[$dbKey];
        }
    }
    if (isset($s['map_lat']) && is_numeric($s['map_lat'])) {
        $cfg['mapLat'] = (float) $s['map_lat'];
    }
    if (isset($s['map_lon']) && is_numeric($s['map_lon'])) {
        $cfg['mapLon'] = (float) $s['map_lon'];
    }
    if (isset($s['free_delivery_from']) && is_numeric($s['free_delivery_from'])) {
        $cfg['freeDeliveryFrom'] = (int) $s['free_delivery_from'];
    }
    if (isset($s['delivery_fee']) && is_numeric($s['delivery_fee'])) {
        $cfg['deliveryFee'] = (int) $s['delivery_fee'];
    }
    if (isset($s['hours'])) {
        $hours = json_decode($s['hours'], true);
        if (is_array($hours) && $hours !== []) {
            $cfg['hours'] = $hours;
        }
    }
    // Police du site (admin → Paramètres).
    if (isset($s['site_font']) && in_array($s['site_font'], ['italiana', 'playfair', 'moderne'], true)) {
        $cfg['font'] = $s['site_font'];
    }
    // Images du site (admin → Images du site / Catégories) : la clé
    // en base est img_<slot>, ex : img_hero, img_cat_femme… — on
    // prend aussi les catégories ajoutées par l'admin (img_cat_*).
    foreach ($s as $k => $v) {
        if (str_starts_with($k, 'img_') && $v !== '') {
            $cfg['images'][substr($k, 4)] = $v;
        }
    }
    return $cfg;
}

/** Catalogue au format attendu par js/main.js :
 *  {id, name, cat, price, oldPrice, img, colors[], sizes[], badge, desc}.
 *  oldPrice = 0 quand pas de promo, badge = '' quand aucun. */
function products_for_js(PDO $pdo): array
{
    $out = [];
    try {
        $rows = $pdo->query(
            'SELECT id, name, cat, price, old_price, img, badge, description, sizes, colors, stock
             FROM products WHERE active = 1 ORDER BY id'
        )->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
    foreach ($rows as $r) {
        $out[] = [
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
            'stock' => (int) $r['stock'],
        ];
    }
    return $out;
}

/** Liste des catégories (slug => nom). */
function categories_list(PDO $pdo): array
{
    try {
        $rows = $pdo->query('SELECT slug, name FROM categories ORDER BY id')->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
    $out = [];
    foreach ($rows as $r) {
        $out[$r['slug']] = $r['name'];
    }
    return $out;
}

/** Sous-titre d'une catégorie (affiché sur la carte d'accueil).
 *  Stocké dans settings sous cat_<slug>_sub, défauts ci-dessous. */
function category_subtitle(array $settings, string $slug): string
{
    $defaults = [
        'femme' => 'Robes, vestes, pulls & plus',
        'homme' => 'Chemises, t-shirts, pantalons',
        'enfant' => 'Confort & couleurs joyeuses',
        'accessoires' => 'Sacs, sneakers & plus',
    ];
    $v = $settings['cat_' . $slug . '_sub'] ?? '';
    return $v !== '' ? $v : ($defaults[$slug] ?? '');
}

/**
 * Migration douce : ajoute la colonne orders.source (site |
 * whatsapp) si elle n'existe pas encore — les installations
 * créées avant cette version se mettent à jour toutes seules
 * à la première utilisation, sans réinstaller.
 */
function ensure_orders_source(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        $col = $pdo->query("SHOW COLUMNS FROM orders LIKE 'source'")->fetch();
        if (!$col) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN source VARCHAR(16) NOT NULL DEFAULT 'site' AFTER status");
        }
    } catch (PDOException $e) {
        // Base non installée : on ignore, les pages afficheront
        // le message d'installation habituel.
    }
}
