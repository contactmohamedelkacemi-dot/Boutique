<?php declare(strict_types=1);
defined('APP_RUNNING') or exit('Accès interdit.');
/* ============================================================
   UPLOAD — Import d'images depuis l'admin (produits + images
   du site). Validation stricte :
   - taille max 5 Mo
   - vraie image vérifiée par getimagesize() (jamais le nom
     ni le type MIME déclaré par le navigateur)
   - extension déduite du type réel (jpg / png / webp / gif)
   - nom de fichier aléatoire, dossier assets/uploads/…
   Le dossier uploads/ contient un .htaccess qui interdit
   l'exécution de scripts (Apache) — et le serveur intégré
   n'exécute de toute façon que les .php demandés, jamais
   un .jpg.
   ============================================================ */

/**
 * Traite un champ fichier ($_FILES[$field]).
 * Retourne le chemin relatif ("assets/uploads/…") en cas de
 * succès, ou null si aucun fichier n'a été envoyé.
 * En cas d'erreur de validation, $error reçoit le message.
 */
function handle_image_upload(string $field, string $subdir, ?string &$error): ?string
{
    $error = null;
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return null;
    }
    $f = $_FILES[$field];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null; // Pas de fichier choisi : pas une erreur.
    }
    if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $error = 'Échec de l\'envoi de l\'image (code ' . (int) $f['error'] . ').';
        return null;
    }
    if (($f['size'] ?? 0) <= 0 || $f['size'] > 5 * 1024 * 1024) {
        $error = 'Image trop lourde (max 5 Mo).';
        return null;
    }
    $info = @getimagesize((string) $f['tmp_name']);
    if ($info === false) {
        $error = 'Le fichier envoyé n\'est pas une image valide.';
        return null;
    }
    $extByMime = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $ext = $extByMime[$info['mime'] ?? ''] ?? null;
    if ($ext === null) {
        $error = 'Format d\'image non accepté (jpg, png, webp ou gif).';
        return null;
    }
    $subdir = trim(preg_replace('/[^a-z0-9\-_]/i', '', $subdir) ?? '', '-');
    $relDir = 'assets/uploads' . ($subdir !== '' ? '/' . $subdir : '');
    $absDir = __DIR__ . '/../' . $relDir;
    if (!is_dir($absDir) && !mkdir($absDir, 0755, true) && !is_dir($absDir)) {
        $error = 'Dossier d\'upload introuvable.';
        return null;
    }
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = $absDir . '/' . $name;
    if (!move_uploaded_file((string) $f['tmp_name'], $dest)) {
        $error = 'Impossible d\'enregistrer l\'image sur le serveur.';
        return null;
    }
    @chmod($dest, 0644);
    return $relDir . '/' . $name;
}

/** Supprime un ancien fichier uploadé (uniquement dans assets/uploads/). */
function delete_uploaded_image(?string $relPath): void
{
    if ($relPath === null || !str_starts_with($relPath, 'assets/uploads/')) {
        return; // On ne touche jamais aux images d'origine (assets/*.jpg).
    }
    $abs = __DIR__ . '/../' . $relPath;
    if (is_file($abs)) {
        @unlink($abs);
    }
}
