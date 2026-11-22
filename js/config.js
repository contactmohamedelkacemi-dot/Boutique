/* ============================================================
   CONFIG — Boutique Style (Offre 1)
   HADA HOWA L-FICHIER LI KHASSK T-BEDDEL L KOL CLIENT.
   Beddel smiya, numero, adresse, horaires hna fa9at —
   site kamel ghadi y-tbeddel bou7do (header, footer,
   WhatsApp, contact, map).
   ============================================================ */
window.BOUTIQUE_CONFIG = {
  // --- Identité (PLACEHOLDERS — beddelhom) ---
  name: "Boutique Style",
  tagline: "Mode & élégance à Bouznika",
  city: "Bouznika",
  address: "12, Rue des Fleurs, Bouznika, Maroc", // PLACEHOLDER
  phoneDisplay: "06 00 00 00 00", // PLACEHOLDER
  phoneHref: "tel:+212600000000",
  whatsapp: "212600000000", // PLACEHOLDER — numero WhatsApp b format international, bla + w bla espaces
  email: "contact@boutiquestyle.ma", // PLACEHOLDER
  instagram: "boutique.style", // PLACEHOLDER (bla @)

  // --- Horaires ---
  hours: [
    { days: "Lundi – Samedi", time: "10:00 – 20:00" },
    { days: "Dimanche", time: "11:00 – 19:00" },
  ],
  hoursShort: "Lun – Sam : 10h – 20h",

  // --- Map (Google Maps, bla API key) ---
  mapQuery: "Bouznika, Maroc", // beddel b adresse 7a9i9iya dyal l-mahal
  mapLat: 33.7926, // Bouznika — beddel b lat/lon dyal l-mahal (Google Maps : clic droit 3la l-mahal → coordonnées)
  mapLon: -7.1667,

  // --- Offre ---
  currency: "DH",
  freeDeliveryFrom: 300, // Livraison gratuite à partir de 300 DH (0 = désactivé)
  deliveryFee: 20,

  // --- Theme par défaut (terracotta | rose | sauge | noir-or | denim) ---
  defaultTheme: "terracotta",
};
