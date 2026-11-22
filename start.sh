#!/bin/bash
# ============================================================
#  LANCEMENT LOCAL — Boutique Style (Offre 2)
#  Démarre le serveur PHP intégré sur http://localhost:8000
#  Pré-requis : PHP 8+ et MySQL/MariaDB démarré + install.php
#  déjà exécuté une fois (voir README.md).
# ============================================================
cd "$(dirname "$0")" || exit 1

if ! command -v php >/dev/null 2>&1; then
  echo "PHP est introuvable. Installez PHP 8+ (ex : XAMPP : https://www.apachefriends.org)."
  exit 1
fi

echo "Boutique Style — Offre 2"
echo "Site  : http://localhost:8000"
echo "Admin : http://localhost:8000/admin/  (admin / admin123 par défaut)"
echo "Arrêt : Ctrl+C"
echo ""
php -S localhost:8000
