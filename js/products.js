/* ============================================================
   CATALOGUE — 12 produits exemples (PLACEHOLDERS)
   Beddel smiyat, atmina, tsawer hna. Kol produit:
   id, nom, categorie, prix (DH), ancienPrix (0 = bla promo),
   img (local, dossier assets/), couleurs, tailles, badge, description.
   Categories: femme | homme | enfant | accessoires
   ============================================================ */
window.PRODUCTS = [
  { id: "p01", name: "Robe Florale d'Été", cat: "femme", price: 349, oldPrice: 449, img: "assets/p01.jpg", colors: ["#e8b4a0", "#2b2b2b", "#f4efe8"], sizes: ["XS", "S", "M", "L"], badge: "Nouveau", desc: "Robe légère en coton, coupe fluide et imprimé floral. Parfaite pour l'été à Bouznika." },
  { id: "p02", name: "Veste en Jean Classique", cat: "femme", price: 429, oldPrice: 0, img: "assets/p02.jpg", colors: ["#5b7a99", "#2b2b2b"], sizes: ["S", "M", "L", "XL"], badge: "", desc: "Veste en jean intemporelle, col classique et poches plaquées. Un basique qui va avec tout." },
  { id: "p03", name: "T-Shirt Blanc Premium", cat: "homme", price: 129, oldPrice: 179, img: "assets/p03.jpg", colors: ["#ffffff", "#2b2b2b", "#8a9478"], sizes: ["S", "M", "L", "XL", "XXL"], badge: "Promo", desc: "T-shirt 100% coton bio, col rond renforcé. Coupe droite, confort toute la journée." },
  { id: "p04", name: "Chemise Lin Homme", cat: "homme", price: 279, oldPrice: 0, img: "assets/p04.jpg", colors: ["#f4efe8", "#7ea0ff", "#5c6650"], sizes: ["M", "L", "XL"], badge: "", desc: "Chemise en lin respirant, manches longues retroussables. L'élégance décontractée." },
  { id: "p05", name: "Robe de Soirée Noire", cat: "femme", price: 549, oldPrice: 699, img: "assets/p05.jpg", colors: ["#171204", "#b76e79"], sizes: ["XS", "S", "M"], badge: "Promo", desc: "Robe de soirée élégante, dos nu et fente latérale. Pour les grandes occasions." },
  { id: "p06", name: "Ensemble Enfant Coloré", cat: "enfant", price: 199, oldPrice: 0, img: "assets/p06.jpg", colors: ["#e8b4a0", "#7ea0ff", "#f4d35e"], sizes: ["4 ans", "6 ans", "8 ans", "10 ans"], badge: "Nouveau", desc: "Ensemble confortable pour enfants, tissu doux et couleurs joyeuses. Facile à laver." },
  { id: "p07", name: "Sac à Main Cuir", cat: "accessoires", price: 399, oldPrice: 0, img: "assets/p07.jpg", colors: ["#9a6b2f", "#171204", "#b76e79"], sizes: ["Unique"], badge: "", desc: "Sac à main en cuir véritable, bandoulière ajustable et poche intérieure zippée." },
  { id: "p08", name: "Pull Oversize Beige", cat: "femme", price: 299, oldPrice: 379, img: "assets/p08.jpg", colors: ["#e8dcc4", "#b3a894", "#2b2b2b"], sizes: ["S", "M", "L"], badge: "Promo", desc: "Pull oversize en maille douce, col rond. Le confort chic de l'hiver." },
  { id: "p09", name: "Pantalon Chino Homme", cat: "homme", price: 249, oldPrice: 0, img: "assets/p09.jpg", colors: ["#5c6650", "#2b2b2b", "#c9a24b"], sizes: ["30", "32", "34", "36"], badge: "", desc: "Pantalon chino coupe slim, coton stretch. Du bureau au week-end sans effort." },
  { id: "p10", name: "Jupe Plissée Midi", cat: "femme", price: 259, oldPrice: 0, img: "assets/p10.jpg", colors: ["#171204", "#b76e79", "#e8dcc4"], sizes: ["XS", "S", "M", "L"], badge: "Nouveau", desc: "Jupe midi plissée, taille haute élastiquée. Mouvement fluide à chaque pas." },
  { id: "p11", name: "Sneakers Blanches", cat: "accessoires", price: 459, oldPrice: 599, img: "assets/p11.jpg", colors: ["#ffffff", "#2b2b2b"], sizes: ["38", "39", "40", "41", "42", "43"], badge: "Promo", desc: "Sneakers blanches en cuir, semelle confort. Le passe-partout de toutes les tenues." },
  { id: "p12", name: "Manteau Long Camel", cat: "femme", price: 699, oldPrice: 0, img: "assets/p12.jpg", colors: ["#c49a5b", "#171204"], sizes: ["S", "M", "L"], badge: "Best-seller", desc: "Manteau long camel, coupe droite et boutons dorés. La pièce forte de l'hiver." },
];
