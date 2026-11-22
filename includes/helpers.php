<?php declare(strict_types=1);
defined('APP_RUNNING') or exit('Accès interdit.');
/* ============================================================
   HELPERS — Fonctions utilitaires partagées
   ============================================================ */

/** Échappement HTML (contre XSS). */
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/** Format prix : 349 → "349 DH" (séparateur de milliers fr). */
function money(int|float $n): string
{
    return number_format((float) $n, 0, ',', ' ') . ' ' . (DEFAULT_CONFIG['currency'] ?? 'DH');
}

/** Adresse IP du client (REMOTE_ADDR uniquement — pas de
 *  X-Forwarded-For, qui peut être falsifié par le client). */
function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/** Jeton CSRF (un par session, régénéré après usage sensible). */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Vérifie le jeton CSRF d'un POST. Retourne false si invalide. */
function csrf_check(): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $sent = $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';
    return is_string($sent) && is_string($stored) && $stored !== '' && hash_equals($stored, $sent);
}

/** Réponse JSON + code HTTP, puis arrêt du script. */
function json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Redirection (chemin absolu ou relatif à la racine). */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}
