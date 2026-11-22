<?php declare(strict_types=1);
define('APP_RUNNING', true);
/* ============================================================
   ADMIN — Connexion
   - 5 échecs depuis la même IP en 15 min → bloqué.
   - Message générique en cas d'échec (on ne dit jamais si
     c'est le nom d'utilisateur ou le mot de passe qui est faux).
   ============================================================ */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/rate_limit.php';

check_global_limit($pdo, client_ip(), true);
header('X-Robots-Tag: noindex, nofollow'); // admin caché : ne pas indexer
admin_session_start();
if (current_admin() !== null) {
    redirect(BASE_URL . '/admin/index.php');
}

$error = '';
$blocked = is_login_blocked($pdo, client_ip());

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !$blocked) {
    if (!csrf_check()) {
        $error = 'Session expirée — rechargez la page et réessayez.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $ok = $username !== '' && $password !== '' && admin_login($pdo, $username, $password);
        record_login_attempt($pdo, client_ip(), $username, $ok);
        if ($ok) {
            redirect(BASE_URL . '/admin/index.php');
        }
        $error = 'Identifiants incorrects.';
        $blocked = is_login_blocked($pdo, client_ip());
    }
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="terracotta">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion — Admin Boutique</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="../css/fonts.css">
  <link rel="stylesheet" href="../css/themes.css">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="admin.css">
</head>
<body>
  <main class="admin-wrap">
    <div class="form-card login-card">
      <a class="logo" href="../index.php" style="font-size:1.4rem">Boutique<span>.</span></a>
      <h1 style="font-size:1.5rem;margin:1rem 0 .4rem">Connexion admin</h1>
      <p class="muted" style="margin-bottom:1.4rem">Espace réservé à la gérante / au gérant de la boutique.</p>
      <?php if ($blocked): ?>
        <div class="alert alert-err">Trop de tentatives, réessayez dans 15 minutes.</div>
      <?php elseif ($error !== ''): ?>
        <div class="alert alert-err"><?= e($error) ?></div>
      <?php endif; ?>
      <form method="post" action="login.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="field">
          <label for="username">Nom d'utilisateur</label>
          <input id="username" name="username" type="text" autocomplete="username" required autofocus>
        </div>
        <div class="field">
          <label for="password">Mot de passe</label>
          <input id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-accent btn-block" type="submit" <?= $blocked ? 'disabled' : '' ?>>Se connecter →</button>
      </form>
      <p class="muted" style="font-size:.85rem;margin-top:1rem">Par défaut : <b>admin</b> / <b>admin123</b> — changez-le après la première connexion.</p>
      <p><a href="../index.php">← Retour à la boutique</a></p>
    </div>
  </main>
</body>
</html>
