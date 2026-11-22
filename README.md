# Boutique Style — Offre 2 (Boutique en ligne complète)

Site de vente en ligne complet pour une boutique de vêtements : vitrine +
commandes en ligne avec **paiement à la livraison** + **tableau de bord admin**.

> **Offre 2 — 4 500 DH** : base de données + partie client + partie admin +
> galerie + stock + commandes + panier. (Offre 1 = site vitrine simple.)

100 % hors-ligne côté assets : polices auto-hébergées, Three.js local,
photos locales, carte OpenStreetMap — **aucun CDN**.

---

## Ce que fait le site

**Côté cliente (le site)**
- Accueil : catégories (gérées depuis l'admin), nouveautés, promos, avis.
- Collection : filtres par catégorie (celles de l'admin), recherche,
  tri par prix, prix max.
- Fiche produit : couleurs, tailles, quantité, produits similaires.
- **Rupture de stock automatique** : dès que le stock d'un produit tombe
  à 0, il s'affiche « Rupture de stock » partout et ne peut plus être
  commandé — aucune intervention nécessaire.
- Panier (localStorage) + **un seul formulaire de commande** : la facture
  détaillée est affichée dans le formulaire, le client remplit ses
  coordonnées une fois, puis choisit : **commander sur le site**
  (paiement à la livraison) ou **commander via WhatsApp** (bouton juste
  en dessous, message pré-rempli avec ses coordonnées).
- Après une commande site : **page facture** avec le détail complet et
  un bouton **« Télécharger la facture »** (PDF via l'impression).
- Animation « la photo vole dans le panier » à chaque ajout, panier bien
  visible dans l'en-tête.
- 5 thèmes boutique (terracotta par défaut, rose, sauge, noir-or, denim)
  + 3 polices — **thème et police se choisissent dans l'admin**.

**Côté admin (`/admin/`)**
- **Tableau de bord e-commerce** : commandes et CA du jour, CA du mois,
  graphique du CA des 14 derniers jours, commandes par statut et par
  source (Site / WhatsApp), top 5 produits vendus, dernières commandes,
  alertes stock faible et ruptures.
- Produits : ajouter / modifier / supprimer, **photo par import de
  fichier** (aperçu immédiat, plus de chemin à taper), **couleurs au
  color-picker visuel** (plus de codes hex à taper), stock, badge,
  actif/inactif.
- Commandes : liste + filtre par statut, **source Site / WhatsApp**,
  détail complet, lien vers la facture, changement de statut
  (Nouvelle → Confirmée → Expédiée → Livrée, ou Annulée),
  « marqué payé », **annuler remet les articles en stock**.
- **Créer une commande (WhatsApp)** : pour les commandes reçues par
  WhatsApp, l'admin saisit la cliente et les articles — la commande
  entre dans le même système (stock décrémenté, facture, suivi) avec
  la source « WhatsApp ».
- **Images du site** : toutes les images affichées (hero, promo,
  catégories, page boutique) se remplacent par import, sans code.
- **Catégories** : nom, sous-titre et image de chaque catégorie
  modifiables ; ajout et suppression de catégories.
- Clients : liste avec nombre de commandes et total dépensé.
- Paramètres : nom, WhatsApp, adresse, ville, email, livraison,
  **thème de couleurs et police du site**.

## Protections (sécurité)

Détail complet dans **[SECURITY.md](SECURITY.md)** (à lire avant la mise
en ligne).

- **Rate limit global (anti-flood)** : 120 requêtes / minute / IP sur
  le site + API, 300 / minute sur l'admin — au-delà : HTTP 429 +
  en-tête `Retry-After` (table `rate_limits`, fonction
  `check_global_limit()` appelée sur chaque page PHP).
- **Login admin** : mot de passe bcrypt (`password_hash`), sessions,
  CSRF sur tous les formulaires, `session_regenerate_id` à la connexion.
- **Rate limit login** : 5 échecs depuis la même IP en 15 min → bloqué
  15 minutes (« Trop de tentatives, réessayez dans 15 minutes »).
- **Anti-doublon commande** : chaque commande a une clé unique
  (`idempotency_key`). Si le réseau renvoie la même commande 2 fois,
  la 2e est ignorée et la commande existante est renvoyée
  (`duplicate: true`) — **jamais de commande en double**.
- **Rate limit commandes** : max 10 commandes / heure par IP (HTTP 429).
- **Prix jamais trustés** : les prix viennent de la base, pas du navigateur.
- **Stock en transaction** : vérifié + décrémenté en une seule transaction
  (lignes verrouillées `FOR UPDATE`) — pas de vente d'un stock épuisé.
- **PDO + requêtes préparées partout** (anti injection SQL),
  `htmlspecialchars` partout en affichage (anti XSS).
- **Fichiers protégés** : `.htaccess` à la racine interdit l'accès web
  à `config/`, `includes/`, `*.sql`, `*.md` et `install.php` (Apache /
  hébergement mutualisé — le serveur `php -S` local l'ignore, voir
  SECURITY.md). Chaque fichier de `config/` et `includes/` a en plus
  une garde `APP_RUNNING` : ouvert directement, il répond
  « Accès interdit ».

> ⚠️ Après l'installation : **supprimez `install.php`** et **changez le
> mot de passe admin** (`admin / admin123`). Activez le **HTTPS**
> (Let's Encrypt gratuit dans cPanel) en production.

## Prérequis

- **PHP 8+** (avec `pdo_mysql`) et **MySQL / MariaDB**.
- En local sur Windows : **XAMPP** (https://www.apachefriends.org) —
  démarrez Apache + MySQL, copiez ce dossier dans `C:\xampp\htdocs\`.
- En local sur Linux/Mac : `php -S localhost:8000` (voir `start.sh`).

## Installation (5 minutes)

1. **Copiez** ce dossier (ex : dans `htdocs/boutique/` sous XAMPP).
2. **Vérifiez** `config/config.php` : par défaut `localhost`,
   base `boutique_offre2`, utilisateur `root`, mot de passe vide
   (réglages XAMPP). Sur un hébergement, mettez vos identifiants.
3. **Ouvrez** `http://localhost:8000/install.php` (ou
   `http://localhost/boutique/install.php` sous XAMPP) :
   il crée la base, importe `database.sql` (tables + 12 produits +
   paramètres) et crée le compte admin.
4. **⚠️ SUPPRIMEZ `install.php`** juste après (sinon n'importe qui
   peut réinitialiser la boutique).
5. **Lancez** : `start.sh` (Linux/Mac) ou `start.bat` (Windows),
   ou `php -S localhost:8000` depuis ce dossier.
6. **Connectez-vous** : `http://localhost:8000/admin/`

### Compte admin par défaut

| | |
|---|---|
| Utilisateur | `admin` |
| Mot de passe | `admin123` |

> **⚠️ CHANGEZ CE MOT DE PASSE dès la première connexion** (menu
> « Mot de passe » dans l'admin). Ne laissez jamais
> `admin / admin123` sur un site en ligne.

## Le parcours d'une commande

1. La cliente remplit le panier puis le formulaire (nom, téléphone,
   adresse, ville).
2. La commande arrive en base avec le statut **Nouvelle**,
   numéro `CMD-2026-0001`, et le stock est décrémenté.
3. L'admin appelle la cliente pour confirmer → statut **Confirmée**.
4. Envoi → **Expédiée**, réception → **Livrée** (+ « marqué payé »
   quand l'argent est reçu).
5. Si la cliente annule → statut **Annulée**, le stock est restauré.

Paiement : **à la livraison, en espèces**. Aucune passerelle de
paiement en ligne dans cette version (nécessite un compte commerçant).

## Structure du projet

```
index.php                  Accueil (produits injectés depuis la base)
pages/                     Collection, produit, panier (+ commande),
                           facture, boutique, contact
config/config.php          Identifiants base + réglages
includes/                  db.php (PDO), helpers.php, auth.php,
                           rate_limit.php, shop.php, upload.php,
                           bootstrap.php
api/products.php           API catalogue (JSON, filtres)
api/order.php              API commande (POST JSON, anti-doublon)
admin/                     login, tableau de bord, produits,
                           commandes, détail commande, création de
                           commande (WhatsApp), clients, images du
                           site, catégories, paramètres, mot de passe
                           paramètres (+ admin.css, layout.php)
database.sql               Tables + 12 produits + paramètres (+ rate_limits)
install.php                Installation en 1 clic (À SUPPRIMER après)
SECURITY.md                Toutes les protections, expliquées (à lire)
.htaccess                  Protection fichiers sensibles (Apache uniquement)
start.sh / start.bat       Lancement localhost:8000
css/ fonts/ assets/ js/    Design Offre 1 (thèmes, 3D, photos locales)
js/config.js               Config de secours (la base fait foi)
js/products.js             Référence seulement (les pages ne l'utilisent plus)
```

## Déployer sur un hébergement mutualisé pas cher (cPanel)

1. Dans cPanel : créez une base MySQL + un utilisateur, notez les
   identifiants.
2. Dans phpMyAdmin : importez `database.sql`.
3. Créez l'admin : dans la table `admins`, insérez
   `admin` + le hash de `admin123` — ou lancez `install.php` une fois
   sur le serveur **puis supprimez-le**.
4. Modifiez `config/config.php` (DB_NAME, DB_USER, DB_PASS).
5. Uploadez tous les fichiers dans `public_html/` (ou un sous-dossier).
6. Changez le mot de passe admin immédiatement, depuis le menu « Mot de passe » de l'admin (admin / Mot de passe).

Coût typique : domaine ~112–150 DH/an + hébergement mutualisé
~600–800 DH/an (voir le devis Offre 2).

## Personnaliser pour un client

- **Nom, WhatsApp, adresse, ville, livraison** : depuis
  l'admin → Paramètres (aucun code à toucher).
- **Produits** : depuis l'admin → Produits (ou import SQL).
- **Photos** : remplacez les fichiers dans `assets/` (mêmes noms) —
  voir `IMAGES-LIST.md` pour la liste des photos à demander au client.
- **Thème par défaut** : `config/config.php` → `defaultTheme`
  (terracotta | rose | sauge | noir-or | denim), ou la table `settings`.

## Photos

Photos d'exemple locales (licences libres, voir `IMAGES-LIST.md`) —
à remplacer par les vraies photos du client quand il les envoie.
