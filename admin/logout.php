<?php declare(strict_types=1);
define('APP_RUNNING', true);
/* Admin — Déconnexion. */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

check_global_limit($pdo, client_ip(), true);
header('X-Robots-Tag: noindex, nofollow'); // admin caché : ne pas indexer

admin_logout();
redirect(BASE_URL . '/admin/login.php');
