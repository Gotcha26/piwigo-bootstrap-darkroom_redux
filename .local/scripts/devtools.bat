@echo off
:: ===========================================================================
:: Bootstrap Darkroom (redux) - devtools.bat - Tools box project manager
:: ===========================================================================
:: Usage : Double-cliquer sur ce fichier
:: ===========================================================================

REM Se placer à la racine du projet (deux niveaux au-dessus de .local\scripts\)
cd /d "%~dp0..\.."

REM Lancer le script partagé avec le .env.project de ce projet
REM (le loader charge automatiquement .env.project + .env.secrets côte à côte)
python "D:\Gotcha\Documents\DIY\GitHub\Piwigo\.local\scripts\devtools.py" "%~dp0.env.project"
