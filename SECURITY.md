# Sécurité — Boutique Style (Offre 2)

Ce document liste **toutes** les protections du projet, pour que la
gérante / le gérant (et le client) sache exactement ce qui est protégé
et ce qu'il reste à faire côté humain.

---

## 1. Rate limits (anti-flood / anti-abus)

| Quoi | Limite | Quand c'est dépassé |
|---|---|---|
| **Global — site public + API** | 120 requêtes / minute / IP | HTTP 429 + en-tête `Retry-After` + page « Trop de requêtes, réessayez dans quelques secondes » |
| **Global — admin** | 300 requêtes / minute / IP | Idem (429 + `Retry-After`) |
| **Login admin** | 5 échecs / 15 min / IP | « Trop de tentatives, réessayez dans 15 minutes » (compte bloqué 15 min) |
| **Commandes** | 10 commandes / heure / IP | HTTP 429 (JSON) |

- La limite globale est comptée dans la table `rate_limits`
  (1 ligne par IP : fenêtre de la minute en cours + compteur).
  Fonction : `check_global_limit()` dans `includes/rate_limit.php`,
  appelée au début de **chaque** point d'entrée PHP
  (`index.php`, `pages/*.php`, `api/*.php`, `admin/*.php`).
- Les fichiers statiques (css / js / polices / images) ne passent pas
  par PHP : ils ne sont pas comptés, et ne sont pas limités.
- Le flood par IP partagée (ex : plusieurs clientes derrière la même
  box 4G) peut déclencher la limite à 120/min — c'est un compromis
  voulu : un humain ne fait jamais 120 pages en 1 minute.

## 2. Authentification admin

- Mot de passe haché en **bcrypt** (`password_hash()` / `password_verify()`).
  Aucun mot de passe en clair nulle part dans le code ou la base.
- **Session** PHP + `session_regenerate_id(true)` à chaque connexion
  (protection contre le vol de session).
- **CSRF** : un jeton unique par session (`csrf_token()`) est exigé sur
  **tous** les formulaires (login, produits, commandes, paramètres…).
  Sans jeton valide → refus.
- Message d'erreur de login **générique** (« Identifiants incorrects ») :
  on ne révèle jamais si c'est le nom ou le mot de passe qui est faux.
- Chaque tentative (réussie ou non) est journalisée dans
  `login_attempts` (IP, nom, date) — utile pour repérer une attaque.
- **Accès caché** : **aucun lien vers `/admin/`** nulle part sur le site
  public (ni en-tête, ni pied de page, ni menu, ni contact). L'admin y
  accède en tapant l'URL directement. Les pages admin envoient
  `X-Robots-Tag: noindex, nofollow` et la page de login porte
  `<meta name="robots" content="noindex, nofollow">` pour ne pas être
  indexées par les moteurs de recherche.
- **Client = invité uniquement** : aucun compte, aucune inscription,
  aucune connexion côté client. La cliente commande en invité (nom,
  téléphone, adresse, ville) et ses coordonnées sont enregistrées
  comme profil dans `customers` (créé ou réutilisé par téléphone).

## 3. Base de données

- **PDO + requêtes préparées partout** : aucune variable n'est jamais
  interpolée dans le SQL → pas d'injection SQL.
- **Validation stricte** côté serveur dans `api/order.php` :
  nom (2–100 car.), téléphone (format), adresse (5–200 car.),
  quantités (1–9), tailles, clé anti-doublon (format).
- **Les prix viennent de la base, jamais du navigateur** : un client
  qui modifie le prix dans son navigateur ne change rien — le total
  est recalculé depuis les prix en base.
- **Stock en transaction** : vérification + décrément dans une seule
  transaction, lignes verrouillées (`SELECT … FOR UPDATE`) → impossible
  de vendre 2 fois le dernier article en stock.
- **Affichage échappé** : `htmlspecialchars()` partout
  (`e()`) → pas de XSS via les noms de produits / clients.

## 4. Anti-doublon de commande (idempotency)

- Chaque commande porte une **clé unique** (`idempotency_key`,
  générée par le navigateur, conservée en `sessionStorage`).
- Si le réseau renvoie la même commande 2 fois (page rechargée,
  double clic, connexion instable), le serveur **renvoie la commande
  existante** (`duplicate: true`, HTTP 200) au lieu d'en créer une 2e.
- En cas d'erreur réseau, le JavaScript **ne vide pas le panier**
  et **ne renvoie pas tout seul** : la cliente réessaie quand elle veut,
  avec la même clé.

## 5. Fichiers protégés

- **`.htaccess`** (racine, Apache / hébergement mutualisé) :
  - `Options -Indexes` (pas de liste de fichiers).
  - Accès interdit à `config/` et `includes/`.
  - Accès interdit aux `*.sql` et `*.md`.
  - Accès interdit à `install.php` (après installation).
  - ⚠️ Le serveur intégré de PHP (`php -S`) **ignore** `.htaccess`.
- **Garde `APP_RUNNING`** : chaque fichier de `config/` et `includes/`
  (et `admin/layout.php`) commence par
  `defined('APP_RUNNING') or exit('Accès interdit.');`
  Chaque point d'entrée définit `define('APP_RUNNING', true);`
  avant de charger quoi que ce soit. Ouvrir `config/config.php` ou
  `includes/db.php` directement dans le navigateur → « Accès interdit ».
- Les identifiants de la base sont dans **un seul fichier** :
  `config/config.php` (jamais dans le code des pages).

## 6. Paiement

- **Paiement à la livraison (COD) uniquement.** Aucune passerelle de
  paiement en ligne, aucun numéro de carte n'est jamais demandé,
  stocké ou transmis. Le statut « payé » est coché par l'admin
  dans le détail de la commande quand l'argent est reçu.

## 7. À FAIRE par l'humain (checklist — ne pas sauter)

- [ ] **Supprimer `install.php`** juste après l'installation.
      Sinon n'importe qui peut recréer la base et le compte admin.
- [ ] **Changer le mot de passe admin** (`admin / admin123` par défaut)
      dès la première connexion. Ne jamais le laisser en production.
- [ ] **HTTPS en production** : sur l'hébergement, activez le certificat
      SSL gratuit (Let's Encrypt dans cPanel) et forcez le HTTPS —
      sinon logins et sessions circulent en clair.
- [ ] **Sauvegardes** : exportez la base régulièrement (phpMyAdmin →
      Exporter) + gardez une copie du dossier.
- [ ] **Mises à jour** : gardez PHP et MySQL à jour sur l'hébergement.
- [ ] **Ne jamais** donner l'accès cPanel / FTP à un inconnu, et ne
      jamais coller un mot de passe dans un message WhatsApp.

## 8. Ce qui n'est PAS (encore) dans cette version

- Pas de 2FA (double authentification) pour l'admin.
- Pas de journal d'activité admin détaillé (qui a modifié quoi).
- Pas de blocage par pays / géolocalisation.
- Pas de WAF (pare-feu applicatif) — celui de l'hébergeur suffit
  pour une petite boutique.
- Le rate limit se base sur `REMOTE_ADDR` uniquement (pas
  `X-Forwarded-For`, falsifiable). Derrière un CDN / proxy, il faut
  adapter `client_ip()` dans `includes/helpers.php`.
