<?php declare(strict_types=1);
defined('APP_RUNNING') or exit('Accès interdit.');
/* ============================================================
   BOOTSTRAP — Chargé par toutes les pages .php (site + admin).
   Charge la config, la connexion PDO et les helpers.
   Si la base est absente (avant install.php), affiche un
   message clair avec le lien vers /install.php.
   ============================================================ */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/shop.php';
require_once __DIR__ . '/rate_limit.php';

try {
    $pdo = db();
} catch (PDOException $e) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Installation requise</title></head><body style="font-family:sans-serif;max-width:640px;margin:4rem auto;padding:0 1rem">';
    echo '<h1>Installation requise</h1>';
    echo '<p>La base de données n\'est pas encore configurée.</p>';
    echo '<p>1. Vérifiez les identifiants dans <code>config/config.php</code>.<br>';
    echo '2. Ouvrez <a href="' . e(BASE_URL) . '/install.php">/install.php</a> pour créer la base et le compte admin.</p>';
    echo '</body></html>';
    exit;
}
