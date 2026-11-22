<?php declare(strict_types=1);
define('APP_RUNNING', true);
/* ============================================================
   INSTALL — Installation en 1 clic (à lancer UNE SEULE fois)
   1. Crée la base si elle n'existe pas.
   2. Importe database.sql (tables + catégories + 12 produits
      + paramètres boutique).
   3. Crée le compte admin : admin / admin123 (bcrypt).
    SUPPRIMEZ CE FICHIER après l'installation !
   S'il est relancé alors qu'un admin existe déjà, il refuse.
   ============================================================ */

require_once __DIR__ . '/config/config.php';

$errors = [];
$done = [];

function install_page(string $title, string $body): void
{
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . ' — Installation</title>';
    echo '<style>body{font-family:system-ui,sans-serif;max-width:680px;margin:3rem auto;padding:0 1rem;line-height:1.6;color:#222}';
    echo '.ok{color:#1a7a3a}.err{color:#b3261e}.warn{background:#fff4d6;border:1px solid #e6c34a;padding:1rem;border-radius:8px}';
    echo 'code{background:#f2f2f2;padding:.1rem .35rem;border-radius:4px}</style></head><body>';
    echo $body;
    echo '</body></html>';
}

/** Découpe un fichier SQL en requêtes (respecte les chaînes
 *  '...' "..." `...`, les commentaires -- # et /* *​/. */
function split_sql(string $sql): array
{
    $stmts = [];
    $buf = '';
    $len = strlen($sql);
    $inS = false; $inD = false; $inB = false; // ' " `
    $inLine = false; $inBlock = false;
    for ($i = 0; $i < $len; $i++) {
        $c = $sql[$i];
        $n = $i + 1 < $len ? $sql[$i + 1] : '';
        if ($inLine) {
            $buf .= $c;
            if ($c === "\n") { $inLine = false; }
            continue;
        }
        if ($inBlock) {
            $buf .= $c;
            if ($c === '*' && $n === '/') { $buf .= $n; $i++; $inBlock = false; }
            continue;
        }
        if (!$inS && !$inD && !$inB) {
            if ($c === '-' && $n === '-' && ($i + 2 >= $len || $sql[$i + 2] === ' ' || $sql[$i + 2] === "\t" || $sql[$i + 2] === "\n")) { $inLine = true; $buf .= $c; continue; }
            if ($c === '#') { $inLine = true; $buf .= $c; continue; }
            if ($c === '/' && $n === '*') { $inBlock = true; $buf .= $c; continue; }
            if ($c === "'") { $inS = true; $buf .= $c; continue; }
            if ($c === '"') { $inD = true; $buf .= $c; continue; }
            if ($c === '`') { $inB = true; $buf .= $c; continue; }
            if ($c === ';') {
                $t = trim($buf);
                if ($t !== '') { $stmts[] = $t; }
                $buf = '';
                continue;
            }
            $buf .= $c;
            continue;
        }
        $buf .= $c;
        if ($inS && $c === "'" && $sql[$i - 1] !== '\\') { $inS = false; }
        if ($inD && $c === '"' && $sql[$i - 1] !== '\\') { $inD = false; }
        if ($inB && $c === '`') { $inB = false; }
    }
    $t = trim($buf);
    if ($t !== '') { $stmts[] = $t; }
    return $stmts;
}

try {
    // 1. Connexion au serveur MySQL (sans base) + création de la base.
    $pdoRoot = new PDO(
        'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET,
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdoRoot->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', DB_NAME) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $done[] = 'Base de données « ' . DB_NAME . ' » prête.';

    // 2. Connexion à la base + refus si déjà installé.
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $alreadyInstalled = false;
    try {
        $n = (int) $pdo->query('SELECT COUNT(*) AS n FROM admins')->fetch()['n'];
        $alreadyInstalled = $n > 0;
    } catch (PDOException $e) {
        $alreadyInstalled = false; // table admins absente : 1ère installation
    }
    if ($alreadyInstalled) {
        install_page('Déjà installé', '<h1>Déjà installé</h1>'
            . '<p class="err">Un compte admin existe déjà — l\'installation a déjà été faite.</p>'
            . '<p>Si vous voulez réinstaller, supprimez la base <code>' . htmlspecialchars(DB_NAME, ENT_QUOTES, 'UTF-8') . '</code> puis relancez ce fichier.</p>'
            . '<p class="warn"><b>Sécurité :</b> supprimez <code>install.php</code> du serveur, il ne doit pas rester en ligne.</p>'
            . '<p><a href="index.php">← Retour à la boutique</a></p>');
        exit;
    }

    // 3. Import de database.sql.
    $sqlFile = __DIR__ . '/database.sql';
    if (!is_readable($sqlFile)) {
        throw new RuntimeException('Fichier database.sql introuvable.');
    }
    $stmts = split_sql((string) file_get_contents($sqlFile));
    $count = 0;
    foreach ($stmts as $stmt) {
        $pdo->exec($stmt);
        $count++;
    }
    $done[] = $count . ' requêtes SQL exécutées (tables + 12 produits + paramètres).';

    // 4. Création de l'admin.
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $st = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
    $st->execute(['admin', $hash]);
    $done[] = 'Compte admin créé : <code>admin</code> / <code>admin123</code>.';

    $body = '<h1 class="ok">Installation terminée</h1><ul>';
    foreach ($done as $d) {
        $body .= '<li>' . $d . '</li>';
    }
    $body .= '</ul>'
        . '<p><a href="index.php">Voir la boutique →</a> &nbsp;·&nbsp; <a href="admin/">Ouvrir l\'admin →</a></p>'
        . '<p class="warn"><b>IMPORTANT — à faire maintenant :</b><br>'
        . '1. <b>Supprimez <code>install.php</code></b> du serveur (sinon n\'importe qui peut réinitialiser la boutique).<br>'
        . '2. Connectez-vous à l\'admin (<code>admin</code> / <code>admin123</code>) et <b>changez le mot de passe</b> depuis le menu « Mot de passe » de l\'admin.</p>';
    install_page('Installation terminée', $body);
} catch (Throwable $e) {
    install_page('Erreur d\'installation', '<h1 class="err">Erreur d\'installation</h1>'
        . '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p>Vérifiez les identifiants dans <code>config/config.php</code> et que MySQL/MariaDB est démarré (XAMPP : démarrez MySQL).</p>');
}
