<?php declare(strict_types=1);
define('APP_RUNNING', true);
/* ============================================================
   ADMIN — Changer le mot de passe admin
   Vérifie le mot de passe actuel, puis enregistre le nouveau
   avec password_hash (bcrypt). Session régénérée après
   le changement.
   ============================================================ */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

check_global_limit($pdo, client_ip(), true);
require_admin();
$admin = current_admin();

$message = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_check()) {
        $error = 'Session expirée — rechargez la page et réessayez.';
    } else {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['new_password_confirm'] ?? '');

        $st = $pdo->prepare('SELECT password_hash FROM admins WHERE id = ?');
        $st->execute([(int) $admin['id']]);
        $row = $st->fetch();

        if (!$row || !password_verify($current, (string) $row['password_hash'])) {
            $error = 'Mot de passe actuel incorrect.';
        } elseif (strlen($new) < 8) {
            $error = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
        } elseif ($new !== $confirm) {
            $error = 'La confirmation ne correspond pas au nouveau mot de passe.';
        } elseif ($new === $current) {
            $error = 'Le nouveau mot de passe doit être différent de l’actuel.';
        } else {
            $up = $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?');
            $up->execute([password_hash($new, PASSWORD_DEFAULT), (int) $admin['id']]);
            session_regenerate_id(true);
            $message = 'Mot de passe changé. Utilisez-le à votre prochaine connexion.';
        }
    }
}

admin_header('Mot de passe', 'password', $admin);
if ($message !== '') { echo '<div class="alert alert-ok">' . e($message) . '</div>'; }
if ($error !== '') { echo '<div class="alert alert-err">' . e($error) . '</div>'; }
?>
<p style="color:var(--muted);margin-bottom:1rem">Changez le mot de passe du compte <strong><?= e($admin['username']) ?></strong>. Minimum 8 caractères — choisissez un mot de passe unique, différent de celui des autres comptes.</p>
<form method="post" class="admin-form" style="max-width:480px">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <label>Mot de passe actuel
    <input type="password" name="current_password" required autocomplete="current-password">
  </label>
  <label>Nouveau mot de passe (min. 8 caractères)
    <input type="password" name="new_password" required minlength="8" autocomplete="new-password">
  </label>
  <label>Confirmer le nouveau mot de passe
    <input type="password" name="new_password_confirm" required minlength="8" autocomplete="new-password">
  </label>
  <button class="btn btn-primary" type="submit">Changer le mot de passe</button>
</form>
<?php admin_footer(); ?>
