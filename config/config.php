<?php declare(strict_types=1);
defined('APP_RUNNING') or exit('Accès interdit.');
/* ============================================================
   CONFIG — Boutique Style (Offre 2)
   Fichier à adapter pour chaque client / hébergement :
   - en local (XAMPP) : laissez tel quel (root sans mot de passe)
   - en production (cPanel) : changez DB_NAME, DB_USER, DB_PASS
     avec les identifiants créés dans l'hébergement.
   ============================================================ */

// --- Base de données ---
const DB_HOST = 'localhost';
const DB_NAME = 'boutique_offre2';
const DB_USER = 'root';
const DB_PASS = ''; // vide en local (XAMPP) — mettez le vrai mot de passe sur l'hébergement
const DB_CHARSET = 'utf8mb4';

// --- URLs ---
// '' = site à la racine du domaine. Ex : '/boutique' si le site
// est installé dans un sous-dossier.
const BASE_URL = '';

// --- Sécurité ---
const LOGIN_MAX_ATTEMPTS = 5;   // tentatives échouées avant blocage
const LOGIN_BLOCK_MINUTES = 15; // durée du blocage login (minutes)
const ORDER_MAX_PER_HOUR = 10;  // commandes max par IP et par heure

// --- Valeurs par défaut (utilisées si la table settings est vide) ---
// Mêmes valeurs que js/config.js (Offre 1) — la table settings
// en base les remplace dès que l'admin les modifie.
const DEFAULT_CONFIG = [
    'name' => 'Boutique Style',
    'tagline' => 'Mode & élégance à Bouznika',
    'city' => 'Bouznika',
    'address' => '12, Rue des Fleurs, Bouznika, Maroc',
    'phoneDisplay' => '06 00 00 00 00',
    'phoneHref' => 'tel:+212600000000',
    'whatsapp' => '212600000000',
    'email' => 'contact@boutiquestyle.ma',
    'instagram' => 'boutique.style',
    'hours' => [
        ['days' => 'Lundi – Samedi', 'time' => '10:00 – 20:00'],
        ['days' => 'Dimanche', 'time' => '11:00 – 19:00'],
    ],
    'hoursShort' => 'Lun – Sam : 10h – 20h',
    'mapQuery' => 'Bouznika, Maroc',
    'mapLat' => 33.7926,
    'mapLon' => -7.1667,
    'currency' => 'DH',
    'freeDeliveryFrom' => 300,
    'deliveryFee' => 20,
    'defaultTheme' => 'terracotta',
    // Police du site (choisie par l'admin dans Paramètres) :
    // italiana | playfair | moderne
    'font' => 'italiana',
    // Images du site (gérées par l'admin dans « Images du site »).
    'images' => [
        'hero' => 'assets/hero.jpg',
        'promo' => 'assets/promo.jpg',
        'cat_femme' => 'assets/cat-femme.jpg',
        'cat_homme' => 'assets/cat-homme.jpg',
        'cat_enfant' => 'assets/cat-enfant.jpg',
        'cat_accessoires' => 'assets/cat-accessoires.jpg',
        'boutique_1' => 'assets/boutique-1.jpg',
        'boutique_2' => 'assets/boutique-2.jpg',
    ],
];
