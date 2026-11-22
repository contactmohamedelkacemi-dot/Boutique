<?php declare(strict_types=1);
defined('APP_RUNNING') or exit('Accès interdit.');
/* ============================================================
   AUTH — Session admin
   Mot de passe vérifié avec password_verify() (bcrypt).
   L'admin est créé par install.php (admin / admin123).
   ============================================================ */

function admin_session_start(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/** Admin connecté ou null. */
function current_admin(): ?array
{
    admin_session_start();
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    $st = db()->prepare('SELECT id, username, created_at FROM admins WHERE id = ?');
    $st->execute([(int) $_SESSION['admin_id']]);
    $row = $st->fetch();
    return $row ?: null;
}

/** Protège une page admin : redirige vers le login si déconnecté. */
function require_admin(): void
{
    if (current_admin() === null) {
        redirect(BASE_URL . '/admin/login.php');
    }
}

/** Tente une connexion. Retourne true si identifiants valides. */
function admin_login(PDO $pdo, string $username, string $password): bool
{
    $st = $pdo->prepare('SELECT id, password_hash FROM admins WHERE username = ?');
    $st->execute([$username]);
    $row = $st->fetch();
    if (!$row || !password_verify($password, $row['password_hash'])) {
        return false;
    }
    admin_session_start();
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $row['id'];
    return true;
}

/** Déconnexion : détruit la session. */
function admin_logout(): void
{
    admin_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
