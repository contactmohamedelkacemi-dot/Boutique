<?php declare(strict_types=1);
define('APP_RUNNING', true);
/* ============================================================
   ADMIN — Produits
   Liste + ajout / modification / suppression.
   La suppression est refusée si le produit figure déjà dans
   des commandes (contrainte FK) : désactivez-le plutôt.
   ============================================================ */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload.php';
require_once __DIR__ . '/layout.php';

check_global_limit($pdo, client_ip(), true);
require_admin();
$admin = current_admin();
$cats = categories_list($pdo);

$message = '';
$error = '';

function slugify(string $s): string
{
    $s = mb_strtolower(trim($s));
    $s = str_replace(['é', 'è', 'ê', 'à', 'â', 'î', 'ï', 'ô', 'ù', 'û', 'ç'], ['e', 'e', 'e', 'a', 'a', 'i', 'i', 'o', 'u', 'u', 'c'], $s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
    return trim($s, '-') ?: 'produit';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_check()) {
        $error = 'Session expirée — rechargez la page et réessayez.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'delete') {
            $id = (string) ($_POST['id'] ?? '');
            try {
                $st = $pdo->prepare('DELETE FROM products WHERE id = ?');
                $st->execute([$id]);
                $message = $st->rowCount() ? 'Produit supprimé.' : 'Produit introuvable.';
            } catch (PDOException $e) {
                $error = 'Impossible de supprimer : ce produit figure dans des commandes — désactivez-le plutôt (case « Actif »).';
            }
        } elseif ($action === 'save') {
            $id = trim((string) ($_POST['id'] ?? ''));
            $name = trim((string) ($_POST['name'] ?? ''));
            $cat = (string) ($_POST['cat'] ?? '');
            $price = (int) ($_POST['price'] ?? 0);
            $oldPrice = trim((string) ($_POST['old_price'] ?? ''));
            $stock = (int) ($_POST['stock'] ?? 0);
            $badge = trim((string) ($_POST['badge'] ?? ''));
            $img = trim((string) ($_POST['current_img'] ?? ''));
            $desc = trim((string) ($_POST['description'] ?? ''));
            $sizes = array_values(array_filter(array_map('trim', explode(',', (string) ($_POST['sizes'] ?? '')))));
            // Couleurs : choisies au color-picker (champ colors[]),
            // jamais tapées en code. On ne garde que des hex valides.
            $colors = [];
            foreach ((array) ($_POST['colors'] ?? []) as $c) {
                $c = trim((string) $c);
                if (preg_match('/^#[0-9a-fA-F]{6}$/', $c)) {
                    $colors[] = strtolower($c);
                }
            }
            $colors = array_values(array_unique($colors));
            $active = isset($_POST['active']) ? 1 : 0;
            // Photo : import d'un fichier (remplace le chemin tapé).
            $uploadError = null;
            $uploaded = handle_image_upload('photo', 'products', $uploadError);
            if ($uploadError !== null) {
                $error = $uploadError;
            } elseif ($uploaded !== null) {
                if ($id !== '' && $img !== $uploaded) {
                    delete_uploaded_image($img); // supprime l'ancien import
                }
                $img = $uploaded;
            }
            if ($error !== '') {
                // Erreur d'upload déjà renseignée : on n'enregistre rien.
            } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
                $error = 'Nom invalide (2 à 120 caractères).';
            } elseif (!isset($cats[$cat])) {
                $error = 'Catégorie invalide.';
            } elseif ($price < 0 || $stock < 0) {
                $error = 'Prix et stock doivent être positifs.';
            } else {
                $oldPriceVal = $oldPrice === '' ? null : max(0, (int) $oldPrice);
                $imgVal = $img === '' ? 'assets/placeholder.svg' : $img;
                if ($id === '') {
                    // Nouvel id : p13, p14… (max existant + 1).
                    $max = (int) ($pdo->query('SELECT COALESCE(MAX(CAST(SUBSTRING(id, 2) AS UNSIGNED)), 0) AS m FROM products')->fetch()['m'] ?? 0);
                    $id = 'p' . str_pad((string) ($max + 1), 2, '0', STR_PAD_LEFT);
                    $slug = slugify($name);
                    $st = $pdo->prepare('SELECT COUNT(*) AS n FROM products WHERE slug = ?');
                    $st->execute([$slug]);
                    if ((int) $st->fetch()['n'] > 0) {
                        $slug .= '-' . substr($id, 1);
                    }
                    $st = $pdo->prepare('INSERT INTO products (id, slug, name, cat, price, old_price, img, badge, description, stock, sizes, colors, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $st->execute([$id, $slug, $name, $cat, $price, $oldPriceVal, $imgVal, $badge === '' ? null : $badge, $desc, $stock, json_encode($sizes), json_encode($colors), $active]);
                    $message = 'Produit « ' . $name . ' » ajouté (' . $id . ').';
                } else {
                    $st = $pdo->prepare('UPDATE products SET name = ?, cat = ?, price = ?, old_price = ?, img = ?, badge = ?, description = ?, stock = ?, sizes = ?, colors = ?, active = ? WHERE id = ?');
                    $st->execute([$name, $cat, $price, $oldPriceVal, $imgVal, $badge === '' ? null : $badge, $desc, $stock, json_encode($sizes), json_encode($colors), $active, $id]);
                    $message = 'Produit « ' . $name . ' » modifié.';
                }
            }
        }
    }
}

$editId = (string) ($_GET['edit'] ?? '');
$edit = null;
if ($editId !== '') {
    $st = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $st->execute([$editId]);
    $edit = $st->fetch() ?: null;
}
$showForm = isset($_GET['new']) || $edit !== null;
$products = $pdo->query('SELECT * FROM products ORDER BY id')->fetchAll();

admin_header('Produits', 'products', $admin);
if ($message !== '') { echo '<div class="alert alert-ok">' . e($message) . '</div>'; }
if ($error !== '') { echo '<div class="alert alert-err">' . e($error) . '</div>'; }
?>
<p><a class="btn btn-accent" href="products.php?new=1">+ Ajouter un produit</a></p>

<?php if ($showForm): ?>
  <div class="form-card admin-form" style="margin:1.2rem 0">
    <h2 style="font-size:1.25rem;margin-bottom:1rem"><?= $edit ? 'Modifier — ' . e($edit['name']) : 'Nouveau produit' ?></h2>
    <form method="post" action="products.php" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
      <input type="hidden" name="current_img" value="<?= e($edit['img'] ?? 'assets/placeholder.svg') ?>">
      <div class="field"><label for="f-name">Nom *</label><input id="f-name" name="name" type="text" required value="<?= e($edit['name'] ?? '') ?>"></div>
      <div class="field"><label for="f-cat">Catégorie *</label>
        <select id="f-cat" name="cat">
          <?php foreach ($cats as $slug => $label): ?>
            <option value="<?= e($slug) ?>" <?= ($edit['cat'] ?? '') === $slug ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label for="f-price">Prix (DH) *</label><input id="f-price" name="price" type="number" min="0" step="1" required value="<?= e((string) ($edit['price'] ?? 0)) ?>"></div>
      <div class="field"><label for="f-old">Ancien prix (DH) — vide = pas de promo</label><input id="f-old" name="old_price" type="number" min="0" step="1" value="<?= e($edit['old_price'] !== null ? (string) $edit['old_price'] : '') ?>"></div>
      <div class="field"><label for="f-stock">Stock *</label><input id="f-stock" name="stock" type="number" min="0" step="1" required value="<?= e((string) ($edit['stock'] ?? 20)) ?>"></div>
      <div class="field"><label for="f-badge">Badge (Nouveau, Promo, Best-seller… — vide = aucun)</label><input id="f-badge" name="badge" type="text" value="<?= e($edit['badge'] ?? '') ?>"></div>
      <div class="field"><label>Photo du produit</label>
        <div class="upload-preview">
          <img id="photo-preview" src="../<?= e($edit['img'] ?? 'assets/placeholder.svg') ?>" alt="Photo actuelle">
          <div style="flex:1">
            <input class="file-input" id="f-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
            <p class="muted" style="font-size:.85rem;margin-top:.4rem">Importez une image (jpg, png, webp — max 5 Mo). Elle se place automatiquement sur le site. Sans nouvel import, la photo actuelle est conservée.</p>
          </div>
        </div>
      </div>
      <div class="field"><label for="f-sizes">Tailles (séparées par des virgules — ex : S, M, L)</label><input id="f-sizes" name="sizes" type="text" value="<?= e(implode(', ', json_decode($edit['sizes'] ?? '[]', true) ?: [])) ?>"></div>
      <div class="field"><label>Couleurs — cliquez sur une couleur pour la changer</label>
        <div class="color-rows" id="color-rows">
          <?php foreach ((json_decode($edit['colors'] ?? '[]', true) ?: ['#2b2b2b']) as $c): ?>
            <div class="color-row">
              <input type="color" name="colors[]" value="<?= e($c) ?>">
              <span class="hex-lab"><?= e($c) ?></span>
              <button type="button" class="rm">Retirer</button>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-ghost btn-sm" id="add-color" style="margin-top:.6rem">+ Ajouter une couleur</button>
      </div>
      <div class="field"><label for="f-desc">Description</label><textarea id="f-desc" name="description" rows="3"><?= e($edit['description'] ?? '') ?></textarea></div>
      <div class="field"><label><input type="checkbox" name="active" value="1" <?= ($edit === null || (int) $edit['active'] === 1) ? 'checked' : '' ?>> Actif (visible sur le site)</label></div>
      <button class="btn btn-accent" type="submit">Enregistrer</button>
      <a class="btn btn-ghost" href="products.php">Annuler</a>
    </form>
  </div>
  <script>
    // Aperçu immédiat de la photo importée.
    const photoInput = document.getElementById('f-photo');
    if (photoInput) photoInput.addEventListener('change', () => {
      const f = photoInput.files && photoInput.files[0];
      if (f) document.getElementById('photo-preview').src = URL.createObjectURL(f);
    });
    // Éditeur de couleurs : ajout / retrait / libellé hex.
    const rowsBox = document.getElementById('color-rows');
    function bindColorRow(row) {
      const input = row.querySelector('input[type="color"]');
      const lab = row.querySelector('.hex-lab');
      input.addEventListener('input', () => { lab.textContent = input.value; });
      row.querySelector('.rm').addEventListener('click', () => row.remove());
    }
    if (rowsBox) {
      rowsBox.querySelectorAll('.color-row').forEach(bindColorRow);
      document.getElementById('add-color').addEventListener('click', () => {
        const row = document.createElement('div');
        row.className = 'color-row';
        row.innerHTML = '<input type="color" name="colors[]" value="#c05f33"><span class="hex-lab">#c05f33</span><button type="button" class="rm">Retirer</button>';
        rowsBox.appendChild(row);
        bindColorRow(row);
      });
    }
  </script>
<?php endif; ?>

<table class="admin-table">
  <thead><tr><th></th><th>Produit</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th>Badge</th><th>Actif</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($products as $p): ?>
      <tr>
        <td><img class="thumb" src="../<?= e($p['img']) ?>" alt=""></td>
        <td><b><?= e($p['name']) ?></b><br><span class="muted"><?= e($p['id']) ?></span></td>
        <td><?= e($cats[$p['cat']] ?? $p['cat']) ?></td>
        <td><?= money((int) $p['price']) ?><?= $p['old_price'] !== null ? '<br><s class="muted">' . money((int) $p['old_price']) . '</s>' : '' ?></td>
        <td><?= (int) $p['stock'] === 0 ? '<b style="color:#b3261e">Rupture</b>' : ((int) $p['stock'] < 5 ? '<b style="color:#b3261e">' . (int) $p['stock'] . '</b>' : (int) $p['stock']) ?></td>
        <td><?= e($p['badge'] ?? '—') ?></td>
        <td><?= (int) $p['active'] === 1 ? 'Actif' : 'Inactif' ?></td>
        <td style="white-space:nowrap">
          <a href="products.php?edit=<?= e($p['id']) ?>">Modifier</a>
          <form method="post" action="products.php" style="display:inline" onsubmit="return confirm('Supprimer « <?= e($p['name']) ?> » ?');">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= e($p['id']) ?>">
            <button type="submit" style="background:none;border:none;color:#b3261e;cursor:pointer;font:inherit;padding:0">Supprimer</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php admin_footer(); ?>
