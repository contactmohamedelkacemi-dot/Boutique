<?php declare(strict_types=1);
defined('APP_RUNNING') or exit('Accès interdit.');
/* ============================================================
   DB — Connexion PDO (singleton)
   Erreurs en exceptions, fetch en tableau associatif, utf8mb4.
   Les requêtes préparées sont obligatoires partout (pas
   d'interpolation de variables dans le SQL).
   ============================================================ */

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}
