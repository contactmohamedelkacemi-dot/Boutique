-- ============================================================
--  DATABASE — Boutique Style (Offre 2)
--  Import : via install.php (recommandé) ou phpMyAdmin.
--  Charset utf8mb4 (accents, emoji). Moteur InnoDB.
--  L'admin n'est PAS créé ici : c'est install.php qui le crée
--  (admin / admin123) avec password_hash().
-- ============================================================

CREATE TABLE IF NOT EXISTS settings (
    setting_key   VARCHAR(64)  NOT NULL PRIMARY KEY,
    setting_value TEXT         NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id          VARCHAR(16)  NOT NULL PRIMARY KEY,
    slug        VARCHAR(80)  NOT NULL UNIQUE,
    name        VARCHAR(120) NOT NULL,
    cat         VARCHAR(32)  NOT NULL,
    price       INT          NOT NULL,
    old_price   INT          NULL,
    img         VARCHAR(255) NOT NULL DEFAULT 'assets/placeholder.svg',
    badge       VARCHAR(40)  NULL,
    description TEXT         NULL,
    stock       INT          NOT NULL DEFAULT 20,
    sizes       JSON         NULL,
    colors      JSON         NULL,
    active      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customers (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    phone      VARCHAR(30)  NOT NULL,
    address    VARCHAR(255) NULL,
    city       VARCHAR(80)  NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    order_number    VARCHAR(20)  NOT NULL UNIQUE,
    customer_id     INT          NOT NULL,
    status          ENUM('new','confirmed','shipped','delivered','cancelled') NOT NULL DEFAULT 'new',
    source          VARCHAR(16)  NOT NULL DEFAULT 'site',
    payment_method  VARCHAR(16)  NOT NULL DEFAULT 'cod',
    paid            TINYINT(1)   NOT NULL DEFAULT 0,
    subtotal        INT          NOT NULL,
    delivery_fee    INT          NOT NULL DEFAULT 0,
    total           INT          NOT NULL,
    notes           TEXT         NULL,
    idempotency_key VARCHAR(64)  NOT NULL UNIQUE,
    ip_address      VARCHAR(45)  NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    order_id     INT          NOT NULL,
    product_id   VARCHAR(16)  NOT NULL,
    product_name VARCHAR(120) NOT NULL,
    price        INT          NOT NULL,
    qty          INT          NOT NULL,
    size         VARCHAR(30)  NULL,
    CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admins (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(60)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    ip_address  VARCHAR(45) NOT NULL,
    username    VARCHAR(60) NULL,
    success     TINYINT(1)  NOT NULL DEFAULT 0,
    attempted_at DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Limite globale (anti-flood) : 1 ligne par IP = la fenêtre
-- de la minute en cours + le nombre de requêtes dedans.
CREATE TABLE IF NOT EXISTS rate_limits (
    ip_address   VARCHAR(45) NOT NULL PRIMARY KEY,
    window_start DATETIME    NOT NULL,
    hits         INT         NOT NULL DEFAULT 1,
    INDEX idx_window (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  SEED — Catégories
-- ============================================================
INSERT INTO categories (slug, name) VALUES
    ('femme', 'Femme'),
    ('homme', 'Homme'),
    ('enfant', 'Enfant'),
    ('accessoires', 'Accessoires')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ============================================================
--  SEED — Paramètres boutique (mêmes valeurs que Offre 1)
-- ============================================================
INSERT INTO settings (setting_key, setting_value) VALUES
    ('shop_name', 'Boutique Style'),
    ('tagline', 'Mode & élégance à Bouznika'),
    ('city', 'Bouznika'),
    ('address', '12, Rue des Fleurs, Bouznika, Maroc'),
    ('phone_display', '06 00 00 00 00'),
    ('phone_href', 'tel:+212600000000'),
    ('whatsapp', '212600000000'),
    ('email', 'contact@boutiquestyle.ma'),
    ('instagram', 'boutique.style'),
    ('hours', '[{"days":"Lundi – Samedi","time":"10:00 – 20:00"},{"days":"Dimanche","time":"11:00 – 19:00"}]'),
    ('hours_short', 'Lun – Sam : 10h – 20h'),
    ('map_query', 'Bouznika, Maroc'),
    ('map_lat', '33.7926'),
    ('map_lon', '-7.1667'),
    ('currency', 'DH'),
    ('free_delivery_from', '300'),
    ('delivery_fee', '20'),
    ('default_theme', 'terracotta'),
    ('site_font', 'italiana'),
    ('img_hero', 'assets/hero.jpg'),
    ('img_promo', 'assets/promo.jpg'),
    ('img_cat_femme', 'assets/cat-femme.jpg'),
    ('img_cat_homme', 'assets/cat-homme.jpg'),
    ('img_cat_enfant', 'assets/cat-enfant.jpg'),
    ('img_cat_accessoires', 'assets/cat-accessoires.jpg'),
    ('img_boutique_1', 'assets/boutique-1.jpg'),
    ('img_boutique_2', 'assets/boutique-2.jpg')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- ============================================================
--  SEED — 12 produits exemples (mêmes que Offre 1, stock 20)
-- ============================================================
INSERT INTO products (id, slug, name, cat, price, old_price, img, badge, description, stock, sizes, colors, active) VALUES
('p01', 'robe-florale-ete', 'Robe Florale d\'Été', 'femme', 349, 449, 'assets/p01.jpg', 'Nouveau', 'Robe légère en coton, coupe fluide et imprimé floral. Parfaite pour l\'été à Bouznika.', 20, '["XS","S","M","L"]', '["#e8b4a0","#2b2b2b","#f4efe8"]', 1),
('p02', 'veste-en-jean-classique', 'Veste en Jean Classique', 'femme', 429, NULL, 'assets/p02.jpg', NULL, 'Veste en jean intemporelle, col classique et poches plaquées. Un basique qui va avec tout.', 20, '["S","M","L","XL"]', '["#5b7a99","#2b2b2b"]', 1),
('p03', 't-shirt-blanc-premium', 'T-Shirt Blanc Premium', 'homme', 129, 179, 'assets/p03.jpg', 'Promo', 'T-shirt 100% coton bio, col rond renforcé. Coupe droite, confort toute la journée.', 20, '["S","M","L","XL","XXL"]', '["#ffffff","#2b2b2b","#8a9478"]', 1),
('p04', 'chemise-lin-homme', 'Chemise Lin Homme', 'homme', 279, NULL, 'assets/p04.jpg', NULL, 'Chemise en lin respirant, manches longues retroussables. L\'élégance décontractée.', 20, '["M","L","XL"]', '["#f4efe8","#7ea0ff","#5c6650"]', 1),
('p05', 'robe-de-soiree-noire', 'Robe de Soirée Noire', 'femme', 549, 699, 'assets/p05.jpg', 'Promo', 'Robe de soirée élégante, dos nu et fente latérale. Pour les grandes occasions.', 20, '["XS","S","M"]', '["#171204","#b76e79"]', 1),
('p06', 'ensemble-enfant-colore', 'Ensemble Enfant Coloré', 'enfant', 199, NULL, 'assets/p06.jpg', 'Nouveau', 'Ensemble confortable pour enfants, tissu doux et couleurs joyeuses. Facile à laver.', 20, '["4 ans","6 ans","8 ans","10 ans"]', '["#e8b4a0","#7ea0ff","#f4d35e"]', 1),
('p07', 'sac-a-main-cuir', 'Sac à Main Cuir', 'accessoires', 399, NULL, 'assets/p07.jpg', NULL, 'Sac à main en cuir véritable, bandoulière ajustable et poche intérieure zippée.', 20, '["Unique"]', '["#9a6b2f","#171204","#b76e79"]', 1),
('p08', 'pull-oversize-beige', 'Pull Oversize Beige', 'femme', 299, 379, 'assets/p08.jpg', 'Promo', 'Pull oversize en maille douce, col rond. Le confort chic de l\'hiver.', 20, '["S","M","L"]', '["#e8dcc4","#b3a894","#2b2b2b"]', 1),
('p09', 'pantalon-chino-homme', 'Pantalon Chino Homme', 'homme', 249, NULL, 'assets/p09.jpg', NULL, 'Pantalon chino coupe slim, coton stretch. Du bureau au week-end sans effort.', 20, '["30","32","34","36"]', '["#5c6650","#2b2b2b","#c9a24b"]', 1),
('p10', 'jupe-plissee-midi', 'Jupe Plissée Midi', 'femme', 259, NULL, 'assets/p10.jpg', 'Nouveau', 'Jupe midi plissée, taille haute élastiquée. Mouvement fluide à chaque pas.', 20, '["XS","S","M","L"]', '["#171204","#b76e79","#e8dcc4"]', 1),
('p11', 'sneakers-blanches', 'Sneakers Blanches', 'accessoires', 459, 599, 'assets/p11.jpg', 'Promo', 'Sneakers blanches en cuir, semelle confort. Le passe-partout de toutes les tenues.', 20, '["38","39","40","41","42","43"]', '["#ffffff","#2b2b2b"]', 1),
('p12', 'manteau-long-camel', 'Manteau Long Camel', 'femme', 699, NULL, 'assets/p12.jpg', 'Best-seller', 'Manteau long camel, coupe droite et boutons dorés. La pièce forte de l\'hiver.', 20, '["S","M","L"]', '["#c49a5b","#171204"]', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name), cat = VALUES(cat), price = VALUES(price),
    old_price = VALUES(old_price), img = VALUES(img), badge = VALUES(badge),
    description = VALUES(description), sizes = VALUES(sizes), colors = VALUES(colors);
