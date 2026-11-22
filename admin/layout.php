<?php declare(strict_types=1);
defined('APP_RUNNING') or exit('Accès interdit.');
/* ============================================================
   LAYOUT ADMIN — En-tête + pied de page partagés.
   Toutes les pages admin appellent require_admin() AVANT
   d'inclure ce fichier (sauf login.php).
   ============================================================ */

function status_label(string $status): string
{
    return match ($status) {
        'new' => 'Nouvelle',
        'confirmed' => 'Confirmée',
        'shipped' => 'Expédiée',
        'delivered' => 'Livrée',
        'cancelled' => 'Annulée',
        default => $status,
    };
}

function admin_header(string $title, string $active, ?array $admin): void
{
    // Ne pas indexer les pages admin (accès caché, par URL directe).
    header('X-Robots-Tag: noindex, nofollow');
    $nav = [
        ['Tableau de bord', 'index.php', 'dashboard'],
        ['Produits', 'products.php', 'products'],
        ['Commandes', 'orders.php', 'orders'],
        ['Créer commande', 'order-new.php', 'order-new'],
        ['Clients', 'customers.php', 'customers'],
        ['Images du site', 'images.php', 'images'],
        ['Catégories', 'categories.php', 'categories'],
        ['Paramètres', 'settings.php', 'settings'],
        ['Mot de passe', 'password.php', 'password'],
    ];
    // Thème + police choisis par l'admin (Paramètres) — l'admin
    // utilise les mêmes que le site.
    $theme = 'terracotta';
    $font = 'italiana';
    try {
        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            $cfgA = shop_config($GLOBALS['pdo']);
            $theme = (string) ($cfgA['defaultTheme'] ?? $theme);
            $font = (string) ($cfgA['font'] ?? $font);
        }
    } catch (Throwable $e) {
    }
    echo '<!DOCTYPE html><html lang="fr" data-theme="' . e($theme) . '" data-font="' . e($font) . '"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . e($title) . ' — Admin Boutique</title>';
    echo '<link rel="stylesheet" href="../css/fonts.css">';
    echo '<link rel="stylesheet" href="../css/themes.css">';
    echo '<link rel="stylesheet" href="../css/style.css">';
    echo '<link rel="stylesheet" href="admin.css">';
    echo '</head><body>';
    echo '<div class="admin-topbar"><div class="admin-topbar-inner">';
    echo '<a class="logo" href="index.php">Boutique<span>.</span> <span style="font-size:.8rem;color:var(--muted)">Admin</span></a>';
    echo '<nav class="admin-nav">';
    foreach ($nav as [$label, $href, $key]) {
        echo '<a href="' . $href . '"' . ($key === $active ? ' class="active"' : '') . '>' . e($label) . '</a>';
    }
    echo '</nav>';
    echo '<div class="who">';
    if ($admin) {
        echo '<span>' . e($admin['username']) . '</span>';
        echo '<a href="logout.php">Déconnexion</a>';
    }
    echo '<a href="../index.php" target="_blank" rel="noopener">Voir le site ↗</a>';
    echo '</div></div></div>';
    echo '<main class="admin-wrap">';
    echo '<h1 style="font-size:1.7rem;margin-bottom:.3rem">' . e($title) . '</h1>';
}

function admin_footer(): void
{
    echo '</main></body></html>';
}
