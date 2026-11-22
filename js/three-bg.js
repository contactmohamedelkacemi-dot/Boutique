/* ============================================================
   FOND 3D — hero (Three.js)
   Particules + forme 3D li kat-dour, kat-t7erek m3a scroll
   w m3a souris. Ila Three.js ma-t-chargeach, hero kay-b9a
   b image + dégradé (bla erreur). Respecte
   prefers-reduced-motion.
   ============================================================ */
(function () {
  "use strict";
  function init() {
    const canvas = document.getElementById("bg3d");
    if (!canvas) return;
    if (typeof THREE === "undefined") return; // fallback : image hero
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;

    const renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(60, 1, 0.1, 100);
    camera.position.set(0, 0, 9);

    // --- Particules (vague) ---
    const COUNT = 1100;
    const pos = new Float32Array(COUNT * 3);
    const base = new Float32Array(COUNT * 2);
    for (let i = 0; i < COUNT; i++) {
      const x = (Math.random() - 0.5) * 26;
      const y = (Math.random() - 0.5) * 14;
      pos[i * 3] = x; pos[i * 3 + 1] = y; pos[i * 3 + 2] = (Math.random() - 0.5) * 4;
      base[i * 2] = x; base[i * 2 + 1] = y;
    }
    const geo = new THREE.BufferGeometry();
    geo.setAttribute("position", new THREE.BufferAttribute(pos, 3));
    const accent = getComputedStyle(document.documentElement).getPropertyValue("--accent").trim() || "#c9a24b";
    const mat = new THREE.PointsMaterial({ color: new THREE.Color(accent), size: 0.055, transparent: true, opacity: 0.85, sizeAttenuation: true });
    const points = new THREE.Points(geo, mat);
    scene.add(points);

    // --- Forme 3D (anneau torsadé, filaire) ---
    const knot = new THREE.Mesh(
      new THREE.TorusKnotGeometry(2.1, 0.55, 120, 12),
      new THREE.MeshBasicMaterial({ color: new THREE.Color(accent), wireframe: true, transparent: true, opacity: 0.28 })
    );
    knot.position.set(4.6, 0.4, -1);
    scene.add(knot);

    const knot2 = new THREE.Mesh(
      new THREE.TorusGeometry(1.4, 0.06, 12, 80),
      new THREE.MeshBasicMaterial({ color: new THREE.Color(accent), wireframe: true, transparent: true, opacity: 0.4 })
    );
    knot2.position.set(-5.4, -1.2, -0.5);
    scene.add(knot2);

    // --- Scroll + souris ---
    let targetRotY = 0, mouseX = 0, mouseY = 0;
    function onScroll() {
      const max = Math.max(document.body.scrollHeight - window.innerHeight, 1);
      targetRotY = (window.scrollY / max) * Math.PI * 1.2;
    }
    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("mousemove", (e) => {
      mouseX = (e.clientX / window.innerWidth - 0.5) * 2;
      mouseY = (e.clientY / window.innerHeight - 0.5) * 2;
    }, { passive: true });
    onScroll();

    function resize() {
      const hero = canvas.parentElement;
      const w = hero ? hero.clientWidth : window.innerWidth;
      const h = hero ? hero.clientHeight : window.innerHeight;
      renderer.setSize(w, h, false);
      camera.aspect = w / h;
      camera.updateProjectionMatrix();
    }
    window.addEventListener("resize", resize);
    resize();

    const clock = new THREE.Clock();
    let running = true;
    document.addEventListener("visibilitychange", () => { running = !document.hidden; });
    (function animate() {
      requestAnimationFrame(animate);
      if (!running) return;
      const t = clock.getElapsedTime();
      const p = geo.attributes.position;
      for (let i = 0; i < COUNT; i++) {
        const bx = base[i * 2], by = base[i * 2 + 1];
        p.array[i * 3 + 1] = by + Math.sin(t * 0.7 + bx * 0.45) * 0.55;
        p.array[i * 3] = bx + Math.cos(t * 0.4 + by * 0.4) * 0.25;
      }
      p.needsUpdate = true;
      points.rotation.y += (targetRotY - points.rotation.y) * 0.04;
      points.rotation.x = mouseY * 0.12;
      knot.rotation.x = t * 0.12;
      knot.rotation.y = t * 0.16 + targetRotY * 0.6;
      knot2.rotation.x = t * 0.2;
      knot2.rotation.z = t * 0.1;
      camera.position.x += (mouseX * 0.6 - camera.position.x) * 0.03;
      camera.position.y += (-mouseY * 0.4 - camera.position.y) * 0.03;
      camera.lookAt(0, 0, 0);
      renderer.render(scene, camera);
    })();
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init);
  else init();
})();
