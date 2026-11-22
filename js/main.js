/* ============================================================
   MAIN — Boutique Style (Offre 1)
   Header/footer injectés (définis 1 fois hna), panier
   localStorage, catalogue + filtres, fiche produit,
   theme switcher, reveal on scroll.
   ============================================================ */
(function () {
  "use strict";
  const CFG = window.BOUTIQUE_CONFIG || {};
  const SUB = window.location.pathname.includes("/pages/");
  const R = SUB ? "../" : ""; // préfixe relatif (pages/ vs racine)
  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));
  const money = (n) => Number(n).toLocaleString("fr-MA") + " " + (CFG.currency || "DH");
  const waLink = (msg) => "https://wa.me/" + (CFG.whatsapp || "") + "?text=" + encodeURIComponent(msg || "Bonjour " + (CFG.name || "") + " !");
  const esc = (s) => String(s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/"/g, "&quot;");


  /* Images locales : si une photo manque, afficher le placeholder local */
  document.addEventListener("error", (e) => {
    const t = e.target;
    if (t && t.tagName === "IMG" && !t.dataset.phFallback) {
      t.dataset.phFallback = "1";
      t.src = R + "assets/placeholder.svg";
    }
  }, true);
  function fixBrokenImgs() {
    $$("img").forEach((im) => {
      if (im.complete && im.naturalWidth === 0 && !im.dataset.phFallback) {
        im.dataset.phFallback = "1";
        im.src = R + "assets/placeholder.svg";
      }
    });
  }

  /* ---------- Theme ---------- */
  const THEMES = ["terracotta", "rose", "sauge", "noir-or", "denim"];
  function applyTheme(t) {
    if (!THEMES.includes(t)) t = CFG.defaultTheme || "noir";
    document.documentElement.setAttribute("data-theme", t);
    try { localStorage.setItem("bs_theme", t); } catch (e) {}
    $$(".theme-dot").forEach((b) => b.classList.toggle("active", b.dataset.setTheme === t));
  }
  let savedTheme = CFG.defaultTheme || "noir";
  try { savedTheme = localStorage.getItem("bs_theme") || savedTheme; } catch (e) {}

  /* ---------- Panier (localStorage) ---------- */
  function getCart() { try { return JSON.parse(localStorage.getItem("bs_cart") || "{}"); } catch (e) { return {}; } }
  function setCart(c) { try { localStorage.setItem("bs_cart", JSON.stringify(c)); } catch (e) {} updateBadge(); }
  function cartCount() { return Object.values(getCart()).reduce((a, it) => a + (it.qty || 0), 0); }
  function stockOf(p) { return typeof p.stock === "number" ? p.stock : 99; }
  function catName(slug) { return (window.CATEGORIES && window.CATEGORIES[slug]) || slug; }
  function addToCart(id, qty, size, sourceImg) {
    const cart = getCart();
    const p = (window.PRODUCTS || []).find((x) => x.id === id);
    if (!p) return;
    const stock = stockOf(p);
    if (stock <= 0) { toast("Rupture de stock — " + p.name); return; }
    const current = cart[id] ? cart[id].qty : 0;
    const want = Math.min(stock, current + (qty || 1));
    if (want <= current) { toast("Stock maximum atteint pour " + p.name); return; }
    if (cart[id]) cart[id].qty = want;
    else cart[id] = { qty: want, size: size || (p.sizes && p.sizes[0]) || "Unique" };
    if (size) cart[id].size = size;
    setCart(cart);
    flyToCart(sourceImg);
    bumpCart();
    toast(p.name + " ajouté au panier");
  }
  function bumpCart() {
    $$(".cart-btn").forEach((b) => { b.classList.remove("bump"); void b.offsetWidth; b.classList.add("bump"); });
  }
  /* Animation : la photo du produit « vole » jusqu'au panier. */
  function flyToCart(imgEl) {
    try {
      const target = $(".cart-btn");
      if (!imgEl || !target || !imgEl.getBoundingClientRect) return;
      const r1 = imgEl.getBoundingClientRect();
      const r2 = target.getBoundingClientRect();
      if (!r1.width || !r2.width) return;
      const ghost = imgEl.cloneNode();
      ghost.className = "fly-img";
      ghost.style.left = r1.left + "px";
      ghost.style.top = r1.top + "px";
      ghost.style.width = r1.width + "px";
      ghost.style.height = r1.height + "px";
      document.body.appendChild(ghost);
      requestAnimationFrame(() => {
        ghost.style.left = (r2.left + r2.width / 2 - 17) + "px";
        ghost.style.top = (r2.top + r2.height / 2 - 21) + "px";
        ghost.style.width = "34px";
        ghost.style.height = "42px";
        ghost.style.opacity = "0.25";
        ghost.style.borderRadius = "50%";
      });
      setTimeout(() => ghost.remove(), 820);
    } catch (e) {}
  }
  function updateBadge() {
    const n = cartCount();
    $$(".cart-count").forEach((el) => { el.textContent = n; el.style.display = n ? "flex" : "none"; });
  }

  /* ---------- Toast ---------- */
  let toastTimer;
  function toast(msg) {
    let t = $(".toast");
    if (!t) { t = document.createElement("div"); t.className = "toast"; document.body.appendChild(t); }
    t.textContent = msg;
    t.classList.add("show");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove("show"), 2600);
  }

  /* ---------- Header / Footer / WhatsApp flottant ---------- */
  const ICON_BAG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8h13l-1.1 12.1a1.5 1.5 0 0 1-1.5 1.4H8.1a1.5 1.5 0 0 1-1.5-1.4L5.5 8z"/><path d="M8.8 10.5V6.8a3.2 3.2 0 0 1 6.4 0v3.7"/></svg>';
  const ICON_MENU = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>';
  const ICON_CHAT = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3.5c-4.9 0-8.8 3.6-8.8 8 0 1.6.5 3.1 1.4 4.3L3.5 20.5l4.9-1.1c1.1.6 2.3.9 3.6.9 4.9 0 8.8-3.6 8.8-8s-3.9-8.3-8.8-8.3zM8.2 13.6c-.7 0-1.2-.5-1.2-1.2s.5-1.2 1.2-1.2 1.2.5 1.2 1.2-.5 1.2-1.2 1.2zm3.8 0c-.7 0-1.2-.5-1.2-1.2s.5-1.2 1.2-1.2 1.2.5 1.2 1.2-.5 1.2-1.2 1.2zm3.8 0c-.7 0-1.2-.5-1.2-1.2s.5-1.2 1.2-1.2 1.2.5 1.2 1.2-.5 1.2-1.2 1.2z"/></svg>';
  const NAV = [
    ["Accueil", R + "index.php", "index"],
    ["Collection", R + "pages/collection.php", "collection"],
    ["La Boutique", R + "pages/boutique.php", "boutique"],
    ["Contact", R + "pages/contact.php", "contact"],
  ];
  function currentPage() {
    const p = window.location.pathname;
    if (p.includes("collection")) return "collection";
    if (p.includes("produit") || p.includes("panier") || p.includes("facture")) return "collection";
    if (p.includes("boutique")) return "boutique";
    if (p.includes("contact")) return "contact";
    return "index";
  }
  function renderChrome() {
    const cur = currentPage();
    const header = $("#app-header");
    if (header) {
      header.innerHTML =
        '<header class="site-header"><div class="container header-inner">' +
        '<a class="logo" href="' + R + 'index.php">' + esc(CFG.name || "Boutique") + '<span>.</span></a>' +
        '<nav class="nav" id="main-nav">' +
        NAV.map((n) => '<a href="' + n[1] + '"' + (n[2] === cur ? ' class="active"' : "") + ">" + n[0] + "</a>").join("") +
        "</nav>" +
        '<div class="header-actions">' +
        '<a class="icon-btn cart-btn" href="' + R + 'pages/panier.php" aria-label="Panier">' + ICON_BAG + '<span class="cart-count">0</span></a>' +
        '<button class="icon-btn burger" id="burger" aria-label="Menu">' + ICON_MENU + '</button>' +
        "</div></div></header>";
      const burger = $("#burger");
      if (burger) burger.addEventListener("click", () => $("#main-nav").classList.toggle("open"));
    }
    const footer = $("#app-footer");
    if (footer) {
      const hours = (CFG.hours || []).map((h) => "<li>" + esc(h.days) + " — <b>" + esc(h.time) + "</b></li>").join("");
      footer.innerHTML =
        '<footer class="site-footer"><div class="container footer-grid">' +
        '<div><a class="logo" href="' + R + 'index.php">' + esc(CFG.name || "Boutique") + '<span>.</span></a>' +
        '<p style="color:var(--muted);margin-top:.7rem;max-width:320px">' + esc(CFG.tagline || "") + '</p>' +
        '<div class="theme-switcher" style="margin-top:1.1rem"><span style="font-size:.85rem;color:var(--muted)">Thème :</span>' +
        '<button class="theme-dot" data-set-theme="terracotta" style="background:#c05f33" title="Terracotta"></button>' +
        '<button class="theme-dot" data-set-theme="rose" style="background:#8e3b46" title="Rose"></button>' +
        '<button class="theme-dot" data-set-theme="sauge" style="background:#5f6f52" title="Sauge"></button>' +
        '<button class="theme-dot" data-set-theme="noir-or" style="background:#12100d" title="Noir & Or"></button>' +
        '<button class="theme-dot" data-set-theme="denim" style="background:#2e4a6b" title="Denim"></button></div></div>' +
        '<div><h4>Navigation</h4><ul>' +
        NAV.map((n) => '<li><a href="' + n[1] + '">' + n[0] + "</a></li>").join("") +
        '<li><a href="' + R + 'pages/panier.php">Panier</a></li></ul></div>' +
        '<div><h4>Horaires</h4><ul>' + hours + "</ul></div>" +
        '<div><h4>Contact</h4><ul>' +
        "<li><b>Adresse :</b> " + esc(CFG.address || "") + "</li>" +
        '<li><b>Tél :</b> <a href="' + (CFG.phoneHref || "#") + '">' + esc(CFG.phoneDisplay || "") + "</a></li>" +
        '<li><b>Email :</b> <a href="mailto:' + esc(CFG.email || "") + '">' + esc(CFG.email || "") + "</a></li>" +
        '<li><b>Instagram :</b> <a href="https://instagram.com/' + esc(CFG.instagram || "") + '" target="_blank" rel="noopener">@' + esc(CFG.instagram || "") + "</a></li>" +
        "</ul></div></div>" +
        '<div class="container footer-bottom"><span>© <span data-year></span> ' + esc(CFG.name || "") + ' — ' + esc(CFG.city || "") + '. Boutique en ligne — Offre 2.</span>' +
        '<span>Paiement à la livraison · Échange sous 7 jours</span></div></footer>';
      $$("[data-year]").forEach((el) => (el.textContent = new Date().getFullYear()));
    }
    if (!document.querySelector(".wa-float")) {
      const a = document.createElement("a");
      a.className = "wa-float";
      a.href = waLink("Bonjour " + (CFG.name || "") + " ! Je voudrais un renseignement.");
      a.target = "_blank"; a.rel = "noopener";
      a.setAttribute("aria-label", "WhatsApp");
      a.innerHTML = ICON_CHAT;
      document.body.appendChild(a);
    }
    $$("[data-config-name]").forEach((el) => (el.textContent = CFG.name || ""));
    $$("[data-config-address]").forEach((el) => (el.textContent = CFG.address || ""));
    $$("[data-config-phone]").forEach((el) => (el.textContent = CFG.phoneDisplay || ""));
    $$("[data-config-email]").forEach((el) => (el.textContent = CFG.email || ""));
    $$("[data-config-instagram]").forEach((el) => (el.textContent = "@" + (CFG.instagram || "")));
    const map = $("#map-frame");
    if (map && CFG.mapLat && CFG.mapLon) {
      const lat = CFG.mapLat, lon = CFG.mapLon;
      map.src = "https://www.openstreetmap.org/export/embed.html?bbox=" + (lon - 0.05) + "%2C" + (lat - 0.03) + "%2C" + (lon + 0.05) + "%2C" + (lat + 0.03) + "&layer=mapnik&marker=" + lat + "%2C" + lon;
    }
    $$("[data-maps-google]").forEach((el) => (el.href = "https://www.google.com/maps/search/?api=1&query=" + encodeURIComponent(CFG.mapQuery || "")));
    $$("[data-maps-osm]").forEach((el) => (el.href = "https://www.openstreetmap.org/search?query=" + encodeURIComponent(CFG.mapQuery || "")));
    $$("[data-wa-link]").forEach((el) => (el.href = waLink(el.dataset.waLink || "")));
    applyTheme(savedTheme);
    updateBadge();
  }

  /* ---------- Cartes produits ---------- */
  function cardHTML(p) {
    const oos = stockOf(p) <= 0;
    const promo = oos
      ? '<span class="badge badge-oos">Rupture de stock</span>'
      : (p.oldPrice ? '<span class="badge">-' + Math.round((1 - p.price / p.oldPrice) * 100) + "%</span>" : (p.badge ? '<span class="badge">' + esc(p.badge) + "</span>" : ""));
    const actions = oos
      ? '<div class="product-actions"><button class="btn btn-dark" disabled>Rupture de stock</button></div>'
      : '<div class="product-actions"><button class="btn btn-dark" data-add="' + p.id + '">Ajouter</button>' +
        '<a class="btn btn-ghost" href="' + waLink("Bonjour ! Je voudrais commander : " + p.name + " (" + money(p.price) + "). Taille dispo ?") + '" target="_blank" rel="noopener">WhatsApp</a></div>';
    return (
      '<article class="product-card reveal in' + (oos ? " is-oos" : "") + '">' +
      '<div class="product-media"><a href="' + R + "pages/produit.php?id=" + p.id + '">' +
      '<img src="' + R + p.img + '" alt="' + esc(p.name) + '" loading="lazy"></a>' + promo +
      (oos ? "" : '<div class="quick-add"><button class="btn btn-accent btn-block" data-add="' + p.id + '">+ Ajouter au panier</button></div>') + "</div>" +
      '<div class="product-info"><span class="product-cat">' + esc(catName(p.cat)) + "</span>" +
      '<h3 class="product-name"><a href="' + R + "pages/produit.php?id=" + p.id + '">' + esc(p.name) + "</a></h3>" +
      '<div class="product-price"><b>' + money(p.price) + "</b>" + (p.oldPrice ? "<s>" + money(p.oldPrice) + "</s>" : "") + "</div>" +
      actions +
      "</div></article>"
    );
  }
  function bindAdds(scope) {
    $$("[data-add]", scope).forEach((b) => b.addEventListener("click", (e) => {
      e.preventDefault();
      const card = b.closest(".product-card");
      const img = card ? card.querySelector(".product-media img") : null;
      addToCart(b.dataset.add, 1, null, img);
    }));
  }

  /* ---------- Page Accueil : nouveautés ---------- */
  function renderHome() {
    const grid = $("#home-new");
    if (!grid || !window.PRODUCTS) return;
    const news = window.PRODUCTS.filter((p) => p.badge === "Nouveau" || p.badge === "Best-seller").slice(0, 4);
    grid.innerHTML = (news.length ? news : window.PRODUCTS.slice(0, 4)).map(cardHTML).join("");
    bindAdds(grid);
  }

  /* ---------- Page Collection : filtres ---------- */
  const state = { cat: "all", q: "", sort: "featured", max: 800 };
  function renderCollection() {
    const grid = $("#product-grid");
    if (!grid || !window.PRODUCTS) return;
    let list = window.PRODUCTS.slice();
    if (state.cat !== "all") list = list.filter((p) => p.cat === state.cat);
    if (state.q) { const q = state.q.toLowerCase(); list = list.filter((p) => (p.name + " " + p.cat + " " + p.desc).toLowerCase().includes(q)); }
    list = list.filter((p) => p.price <= state.max);
    if (state.sort === "price-asc") list.sort((a, b) => a.price - b.price);
    if (state.sort === "price-desc") list.sort((a, b) => b.price - a.price);
    if (state.sort === "promo") list.sort((a, b) => (b.oldPrice ? 1 : 0) - (a.oldPrice ? 1 : 0));
    grid.innerHTML = list.length ? list.map(cardHTML).join("") : '<p class="result-count">Aucun article trouvé — essayez un autre filtre.</p>';
    const c = $("#result-count");
    if (c) c.textContent = list.length + " article(s) trouvé(s)";
    bindAdds(grid);
  }
  function initCollection() {
    if (!$("#product-grid")) return;
    // Liens entrants : ?cat=femme / ?sort=promo (depuis l'accueil)
    const params = new URLSearchParams(window.location.search);
    const pc = params.get("cat");
    if (pc && (window.CATEGORIES ? (pc in window.CATEGORIES) : ["femme", "homme", "enfant", "accessoires"].includes(pc))) {
      state.cat = pc;
      $$(".chip[data-cat]").forEach((x) => x.classList.toggle("active", x.dataset.cat === pc));
    }
    const ps = params.get("sort");
    if (ps && ["price-asc", "price-desc", "promo", "featured"].includes(ps)) {
      state.sort = ps;
      const s0 = $("#sort-select");
      if (s0) s0.value = ps;
    }
    $$(".chip[data-cat]").forEach((ch) => ch.addEventListener("click", () => {
      $$(".chip[data-cat]").forEach((x) => x.classList.remove("active"));
      ch.classList.add("active");
      state.cat = ch.dataset.cat;
      renderCollection();
    }));
    const q = $("#search-input");
    if (q) q.addEventListener("input", () => { state.q = q.value.trim(); renderCollection(); });
    const s = $("#sort-select");
    if (s) s.addEventListener("change", () => { state.sort = s.value; renderCollection(); });
    const r = $("#price-range");
    const rl = $("#price-label");
    if (r) r.addEventListener("input", () => { state.max = Number(r.value); if (rl) rl.textContent = "Max : " + money(state.max); renderCollection(); });
    renderCollection();
  }

  /* ---------- Page Produit ---------- */
  function initProduct() {
    const box = $("#product-detail");
    if (!box || !window.PRODUCTS) return;
    const id = new URLSearchParams(window.location.search).get("id") || "p01";
    const p = window.PRODUCTS.find((x) => x.id === id) || window.PRODUCTS[0];
    document.title = p.name + " — " + (CFG.name || "Boutique");
    const thumbs = [p.img, p.img.replace("w=900", "w=900&sat=-30"), p.img.replace("w=900", "w=900&flip=h")];
    box.innerHTML =
      '<div><div class="gallery-main"><img id="main-img" src="' + R + p.img + '" alt="' + esc(p.name) + '"></div>' +
      '<div class="gallery-thumbs">' + thumbs.map((t, i) => '<img src="' + R + t + '" alt="vue ' + (i + 1) + '" class="' + (i === 0 ? "active" : "") + '" data-thumb="' + R + t + '">').join("") + "</div></div>" +
      '<div class="detail-info"><span class="product-cat">' + esc(p.cat) + (p.badge ? " · " + esc(p.badge) : "") + "</span>" +
      "<h1>" + esc(p.name) + "</h1>" +
      '<div class="stars">★★★★★ <span style="color:var(--muted);font-size:.9rem">4.8/5 (26 avis)</span></div>' +
      '<div class="detail-price"><b>' + money(p.price) + "</b>" + (p.oldPrice ? "<s>" + money(p.oldPrice) + "</s>" : "") + "</div>" +
      '<p class="detail-desc">' + esc(p.desc) + "</p>" +
      '<p class="opt-label">Couleur</p><div class="opt-row">' +
      p.colors.map((c, i) => '<button class="swatch' + (i === 0 ? " active" : "") + '" style="background:' + c + '" data-color="' + c + '" aria-label="couleur"></button>').join("") + "</div>" +
      '<p class="opt-label">Taille</p><div class="opt-row">' +
      p.sizes.map((s, i) => '<button class="opt-btn size-btn' + (i === 0 ? " active" : "") + '" data-size="' + esc(s) + '">' + esc(s) + "</button>").join("") + "</div>" +
      '<div class="qty-row"><span class="opt-label" style="margin:0">Quantité</span><span class="qty"><button id="qty-minus" aria-label="moins">−</button><span id="qty-val">1</span><button id="qty-plus" aria-label="plus">+</button></span>' +
      (stockOf(p) <= 0 ? '<span class="oos-note">Rupture de stock</span>' : (stockOf(p) <= 5 ? '<span class="oos-note">Plus que ' + stockOf(p) + " en stock</span>" : "")) + "</div>" +
      '<div class="detail-cta"><button class="btn btn-accent btn-block" id="add-cart"' + (stockOf(p) <= 0 ? " disabled" : "") + ">Ajouter au panier — " + money(p.price) + "</button>" +
      '<a class="btn btn-ghost btn-block" target="_blank" rel="noopener" href="' + waLink("Bonjour ! Je voudrais commander : " + p.name + " (" + money(p.price) + "). Taille dispo ?") + '">Commander via WhatsApp</a></div>' +
      '<div class="meta-list"><div><b>Livraison :</b> ' + (CFG.freeDeliveryFrom ? "gratuite dès " + money(CFG.freeDeliveryFrom) + ", sinon " + money(CFG.deliveryFee || 20) : money(CFG.deliveryFee || 20)) + " — Bouznika & partout au Maroc</div>" +
      "<div><b>Échange :</b> sous 7 jours en boutique</div>" +
      "<div><b>Paiement :</b> à la livraison ou en boutique</div></div></div>";
    let qty = 1, size = p.sizes[0];
    const maxQty = Math.max(1, Math.min(9, stockOf(p)));
    $$(".size-btn", box).forEach((b) => b.addEventListener("click", () => { $$(".size-btn", box).forEach((x) => x.classList.remove("active")); b.classList.add("active"); size = b.dataset.size; }));
    $$(".swatch", box).forEach((b) => b.addEventListener("click", () => { $$(".swatch", box).forEach((x) => x.classList.remove("active")); b.classList.add("active"); }));
    $$("[data-thumb]", box).forEach((t) => t.addEventListener("click", () => {
      $$("[data-thumb]", box).forEach((x) => x.classList.remove("active"));
      t.classList.add("active");
      $("#main-img").src = t.dataset.thumb;
    }));
    $("#qty-minus").addEventListener("click", () => { qty = Math.max(1, qty - 1); $("#qty-val").textContent = qty; });
    $("#qty-plus").addEventListener("click", () => { qty = Math.min(maxQty, qty + 1); $("#qty-val").textContent = qty; });
    const addBtn = $("#add-cart");
    if (addBtn && stockOf(p) > 0) addBtn.addEventListener("click", () => addToCart(p.id, qty, size, $("#main-img")));
    const rel = $("#related-grid");
    if (rel) {
      const others = window.PRODUCTS.filter((x) => x.cat === p.cat && x.id !== p.id).concat(window.PRODUCTS.filter((x) => x.cat !== p.cat)).slice(0, 4);
      rel.innerHTML = others.map(cardHTML).join("");
      bindAdds(rel);
    }
  }

  /* ---------- Page Panier + checkout unifié ---------- */
  function cartTotals() {
    const cart = getCart();
    let subtotal = 0;
    const lines = [];
    Object.keys(cart).forEach((id) => {
      const p = (window.PRODUCTS || []).find((x) => x.id === id);
      if (!p) return;
      const it = cart[id];
      const oos = stockOf(p) <= 0;
      if (!oos) subtotal += p.price * it.qty;
      lines.push({ p: p, it: it, oos: oos });
    });
    const fee = subtotal === 0 || subtotal >= (CFG.freeDeliveryFrom || 0) ? 0 : (CFG.deliveryFee || 20);
    return { lines: lines, subtotal: subtotal, fee: fee, total: subtotal + fee };
  }
  function renderCart() {
    const box = $("#cart-lines");
    if (!box || !window.PRODUCTS) return;
    const cart = getCart();
    const ids = Object.keys(cart);
    const empty = $("#cart-empty"), layout = $("#cart-layout");
    const co = $("#checkout-section");
    if (!ids.length) {
      if (empty) empty.style.display = "block";
      if (layout) layout.style.display = "none";
      if (co) co.style.display = "none";
      return;
    }
    if (co) co.style.display = "block";
    if (empty) empty.style.display = "none";
    if (layout) layout.style.display = "grid";
    const t = cartTotals();
    box.innerHTML = t.lines.map(({ p, it, oos }) => {
      return '<div class="cart-line"><img src="' + R + p.img + '" alt="' + esc(p.name) + '">' +
        '<div><h3>' + esc(p.name) + '</h3><p class="muted">Taille : ' + esc(it.size || "") + " · " + money(p.price) + "</p>" +
        (oos
          ? '<p class="oos-note" style="margin-top:.4rem">Rupture de stock — retirez cet article pour continuer.</p>'
          : '<span class="qty" style="margin-top:.5rem"><button data-dec="' + p.id + '">−</button><span>' + it.qty + '</span><button data-inc="' + p.id + '">+</button></span> ') +
        '<button class="remove-btn" data-del="' + p.id + '">Retirer</button></div>' +
        '<div class="line-total">' + (oos ? "—" : money(p.price * it.qty)) + "</div></div>";
    }).join("");
    // Facture intégrée au formulaire de commande.
    const inv = $("#invoice-lines");
    if (inv) {
      inv.innerHTML = "<h3>Votre facture</h3>" + t.lines.filter((l) => !l.oos).map(({ p, it }) =>
        '<div class="invoice-line"><span>' + esc(p.name) + " (Taille " + esc(it.size || "") + ") × " + it.qty + "</span><b>" + money(p.price * it.qty) + "</b></div>"
      ).join("") +
        '<div class="invoice-line"><span>Sous-total</span><b>' + money(t.subtotal) + "</b></div>" +
        '<div class="invoice-line"><span>Livraison</span><b>' + (t.fee === 0 ? "Gratuite" : money(t.fee)) + "</b></div>" +
        '<div class="summary-row total" style="margin-top:.4rem"><span>Total</span><span>' + money(t.total) + "</span></div>";
    }
    $$("[data-inc]", box).forEach((b) => b.addEventListener("click", () => {
      const c = getCart();
      const p = (window.PRODUCTS || []).find((x) => x.id === b.dataset.inc);
      const max = Math.min(9, p ? stockOf(p) : 9);
      if (c[b.dataset.inc].qty >= max) { toast("Stock maximum atteint"); return; }
      c[b.dataset.inc].qty += 1; setCart(c); renderCart();
    }));
    $$("[data-dec]", box).forEach((b) => b.addEventListener("click", () => { const c = getCart(); c[b.dataset.dec].qty -= 1; if (c[b.dataset.dec].qty <= 0) delete c[b.dataset.dec]; setCart(c); renderCart(); }));
    $$("[data-del]", box).forEach((b) => b.addEventListener("click", () => { const c = getCart(); delete c[b.dataset.del]; setCart(c); renderCart(); toast("Article retiré du panier"); }));
    const wa = $("#wa-order");
    if (wa) {
      wa.addEventListener("click", (e) => {
        e.preventDefault();
        const tt = cartTotals();
        const lines = tt.lines.filter((l) => !l.oos).map(({ p, it }) => "• " + p.name + " (Taille " + (it.size || "") + ") x" + it.qty + " — " + money(p.price * it.qty));
        const val = (id) => { const el = $(id); return el ? el.value.trim() : ""; };
        let msg = "Bonjour " + (CFG.name || "") + " ! Je voudrais commander :\n" + lines.join("\n") + "\nTotal : " + money(tt.total) + " (livraison incluse)";
        const nm = val("#of-name"), ph = val("#of-phone"), ad = val("#of-address"), ci = val("#of-city");
        if (nm || ph || ad || ci) msg += "\n\nMes coordonnées :\n" + [nm, ph, ad, ci].filter(Boolean).join(" — ");
        window.open(waLink(msg), "_blank");
      });
    }
  }

  /* ---------- Commande en ligne (paiement à la livraison) ---------- */
  function orderKey() {
    // Clé anti-doublon : la même clé est réutilisée si la page est
    // rechargée / la commande renvoyée (réseau instable) -> le serveur
    // renvoie la commande existante au lieu d'en créer une 2e.
    try {
      let k = sessionStorage.getItem("bs_order_key");
      if (!k) {
        k = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : "key-" + Date.now() + "-" + Math.random().toString(36).slice(2);
        sessionStorage.setItem("bs_order_key", k);
      }
      return k;
    } catch (e) { return "key-" + Date.now(); }
  }
  function initCheckout() {
    const form = $("#checkout-form");
    if (!form) return;
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const btn = $("#checkout-submit");
      const errBox = $("#checkout-error");
      const showErr = (m) => { if (errBox) { errBox.textContent = m; errBox.style.display = "block"; } };
      if (errBox) errBox.style.display = "none";
      const cart = getCart();
      const ids = Object.keys(cart);
      if (!ids.length) { showErr("Votre panier est vide."); return; }
      const payload = {
        items: ids.map((id) => ({ id: id, qty: cart[id].qty || 1, size: cart[id].size || null })),
        customer: {
          name: $("#of-name").value.trim(),
          phone: $("#of-phone").value.trim(),
          address: $("#of-address").value.trim(),
          city: $("#of-city").value.trim(),
          notes: $("#of-notes").value.trim()
        },
        idempotency_key: orderKey()
      };
      if (btn) { btn.disabled = true; btn.textContent = "Envoi en cours…"; }
      try {
        const res = await fetch(R + "api/order.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(payload) });
        const data = await res.json().catch(() => null);
        if (!res.ok || !data || !data.success) {
          showErr((data && data.error) || "Erreur lors de l'envoi. Vérifiez vos informations et réessayez — votre panier est intact.");
          return;
        }
        // Succès : on vide le panier, puis on affiche la page
        // facture (vérifiée par numéro + clé anti-doublon).
        const usedKey = payload.idempotency_key;
        try { sessionStorage.removeItem("bs_order_key"); } catch (e) {}
        setCart({});
        window.location.href = R + "pages/facture.php?n=" + encodeURIComponent(data.order_number) + "&k=" + encodeURIComponent(usedKey);
      } catch (err) {
        // Erreur réseau : on NE vide PAS le panier, on NE renvoie PAS
        // automatiquement. La cliente réessaie quand elle veut, avec
        // la même clé anti-doublon (pas de commande en double).
        showErr("Problème de connexion — la commande n'a pas été envoyée. Vérifiez votre réseau et réessayez, votre panier est intact.");
      } finally {
        if (btn) { btn.disabled = false; btn.textContent = "Commander — paiement à la livraison"; }
      }
    });
  }

  /* ---------- Formulaires ---------- */
  function initForms() {
    const cf = $("#contact-form");
    if (cf) cf.addEventListener("submit", (e) => {
      e.preventDefault();
      const name = $("#cf-name").value.trim(), phone = $("#cf-phone").value.trim(), msg = $("#cf-msg").value.trim();
      window.open(waLink("Bonjour " + (CFG.name || "") + " ! Je suis " + name + " (" + phone + ").\n" + msg), "_blank");
      toast("Ouverture de WhatsApp…");
    });
    const nf = $("#newsletter-form");
    if (nf) nf.addEventListener("submit", (e) => { e.preventDefault(); toast("Merci ! Vous êtes inscrit(e) à la newsletter."); nf.reset(); });
  }

  /* ---------- Reveal ---------- */
  function initReveal() {
    const io = new IntersectionObserver((es) => es.forEach((en) => { if (en.isIntersecting) { en.target.classList.add("in"); io.unobserve(en.target); } }), { threshold: 0.12 });
    $$(".reveal").forEach((el) => io.observe(el));
  }

  /* ---------- Init ---------- */
  document.addEventListener("DOMContentLoaded", () => {
    renderChrome();
    fixBrokenImgs();
    $$("[data-set-theme]").forEach((b) => b.addEventListener("click", () => applyTheme(b.dataset.setTheme)));
    renderHome();
    initCollection();
    initProduct();
    renderCart();
    initCheckout();
    initForms();
    initReveal();
  });
})();
