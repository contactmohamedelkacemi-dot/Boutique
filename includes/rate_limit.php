<?php declare(strict_types=1);
defined('APP_RUNNING') or exit('Accès interdit.');
/* ============================================================
   RATE LIMIT — Anti-bruteforce (login) + anti-spam (commandes)
   + anti-flood global (visiteurs)
   - Global : 120 requêtes/minute par IP (site + API),
     300/minute sur l'admin. Au-delà → HTTP 429 + Retry-After.
   - Login : 5 échecs depuis la même IP en 15 min → bloqué.
   - Commandes : max 10 commandes par IP et par heure.
   La clé anti-doublon (idempotency_key) de api/order.php
   protège en plus contre les envois doubles (réseau qui
   renvoie la même commande 2 fois).
   ============================================================ */

/** Enregistre une tentative de login (réussie ou non). */
function record_login_attempt(PDO $pdo, string $ip, string $username, bool $ok): void
{
    $st = $pdo->prepare('INSERT INTO login_attempts (ip_address, username, success) VALUES (?, ?, ?)');
    $st->execute([$ip, $username, $ok ? 1 : 0]);
    // Ménage : on supprime les tentatives de plus d'1 jour.
    $pdo->exec("DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL 1 DAY");
}

/** L'IP est-elle bloquée pour le login ? */
function is_login_blocked(PDO $pdo, string $ip): bool
{
    $st = $pdo->prepare(
        "SELECT COUNT(*) AS n FROM login_attempts
         WHERE ip_address = ? AND success = 0
         AND attempted_at >= NOW() - INTERVAL " . LOGIN_BLOCK_MINUTES . " MINUTE"
    );
    $st->execute([$ip]);
    $row = $st->fetch();
    return ((int) ($row['n'] ?? 0)) >= LOGIN_MAX_ATTEMPTS;
}

/** L'IP a-t-elle dépassé le quota de commandes (10 / heure) ? */
function check_order_limit(PDO $pdo, string $ip): bool
{
    $st = $pdo->prepare(
        "SELECT COUNT(*) AS n FROM orders
         WHERE ip_address = ? AND created_at >= NOW() - INTERVAL 1 HOUR"
    );
    $st->execute([$ip]);
    $row = $st->fetch();
    return ((int) ($row['n'] ?? 0)) >= ORDER_MAX_PER_HOUR;
}

/**
 * Limite globale anti-flood (table rate_limits).
 * - $isAdmin = true  → 300 requêtes/minute (admin).
 * - $isAdmin = false → 120 requêtes/minute (site public + API).
 * Au-delà : HTTP 429 + en-tête Retry-After, puis arrêt.
 * $json = true → réponse JSON (API), sinon page HTML simple.
 * Si la table est absente (avant install.php), on laisse passer.
 */
function check_global_limit(PDO $pdo, string $ip, bool $isAdmin = false, bool $json = false): void
{
    $limit = $isAdmin ? 300 : 120;
    $window = date('Y-m-d H:i:00'); // fenêtre = la minute en cours
    try {
        $st = $pdo->prepare(
            'INSERT INTO rate_limits (ip_address, window_start, hits) VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE
                hits = IF(window_start = VALUES(window_start), hits + 1, 1),
                window_start = VALUES(window_start)'
        );
        $st->execute([$ip, $window]);
        $st = $pdo->prepare('SELECT hits FROM rate_limits WHERE ip_address = ?');
        $st->execute([$ip]);
        $hits = (int) ($st->fetch()['hits'] ?? 1);
    } catch (PDOException $e) {
        return; // table absente : pas de limitation (site non installé)
    }

    if ($hits > $limit) {
        $retry = max(1, 60 - (int) date('s')); // secondes avant la prochaine minute
        header('Retry-After: ' . $retry);
        if ($json) {
            json_response(['success' => false, 'error' => 'Trop de requêtes, réessayez dans quelques secondes.'], 429);
        }
        http_response_code(429);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Trop de requêtes</title></head>'
            . '<body style="font-family:sans-serif;max-width:560px;margin:4rem auto;padding:0 1rem">'
            . '<h1>Trop de requêtes</h1>'
            . '<p>Vous avez envoyé trop de requêtes en peu de temps. Réessayez dans quelques secondes.</p>'
            . '</body></html>';
        exit;
    }

    // Ménage : quand une nouvelle fenêtre commence pour cette IP,
    // on supprime les lignes vieilles de plus d'1 jour.
    if ($hits === 1) {
        try {
            $pdo->exec("DELETE FROM rate_limits WHERE window_start < NOW() - INTERVAL 1 DAY");
        } catch (PDOException $e) {
            // Ménage non critique : on ignore l'erreur.
        }
    }
}
