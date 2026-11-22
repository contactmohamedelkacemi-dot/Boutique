@echo off
REM ============================================================
REM  LANCEMENT LOCAL - Boutique Style - Offre 2
REM  Demarre le serveur PHP integre sur http://localhost:8000
REM  Pre-requis : PHP 8+ et MySQL/MariaDB demarre, plus
REM  install.php deja execute une fois - voir README.md
REM ============================================================
cd /d %~dp0
set PHP=
where php >nul 2>nul
if not errorlevel 1 set PHP=php
if defined PHP goto found
if exist C:\xampp\php\php.exe set PHP=C:\xampp\php\php.exe
if defined PHP goto found
echo PHP est introuvable.
echo Installez XAMPP depuis apachefriends.org, ou ajoutez PHP au PATH.
pause
exit /b 1
:found
echo Boutique Style - Offre 2
echo Site  : http://localhost:8000
echo Admin : http://localhost:8000/admin/
echo Identifiants par defaut : admin / admin123
echo Arret : Ctrl+C
echo.
"%PHP%" -S localhost:8000
pause
