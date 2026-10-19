# Bootstrap Darkroom (redux)

Fork maintenu du thème Piwigo [Bootstrap Darkroom](https://github.com/Piwigo/piwigo-bootstrap-darkroom)
de Thomas Kuther.

La documentation des fonctionnalités du thème reste celle de l'amont : voir
[README.md](README.md). Ce fichier ne décrit que ce qui distingue ce fork.

## Pourquoi ce fork

Faire évoluer le thème à son propre rythme. Ce fork est **autonome** : il corrige, modifie et
refond le thème sans attendre l'amont, ni chercher à rester fusionnable avec lui. Le projet
d'origine reste une source dont on reprend, au cas par cas, ce qui est utile.

Il est **gratuit et vendu nulle part**. Sa distribution est ce dépôt public.

## Remplacement à l'identique

Ce thème s'installe dans `themes/bootstrap_darkroom` — le dossier du thème d'origine, dont il
conserve l'identifiant.

C'est délibéré. Piwigo identifie un thème par son nom de dossier et le stocke tel quel en base,
dans `$conf['default_theme']` et dans les préférences de chaque utilisateur. Garder ce nom rend
la bascule transparente : thème sélectionné, réglages et thèmes enfants sont préservés, sans
rien à reconfigurer.

Corollaire : les deux ne peuvent pas cohabiter. Conservez une copie de votre
`themes/bootstrap_darkroom` actuel **hors** de `themes/` avant de basculer.

## Mises à jour

L'en-tête `Theme URI` ne pointe pas vers le dépôt d'extensions Piwigo. Ce thème n'est donc jamais
proposé à la mise à jour dans l'administration — et, surtout, jamais écrasé par la version
officielle. Les mises à jour se récupèrent ici.

## jQuery

Le thème n'embarque plus jQuery : il utilise celui du cœur de Piwigo, soit **jQuery 1.11.3 (2015),
obsolète** et visé par des avis de sécurité connus. Sa mise à jour relève de Piwigo, pas du thème.

## Modèle de branches

- `main` — branche principale. Tout changement part de `main` et y revient.
- Les évolutions de l'amont ne sont plus fusionnées en bloc : on les examine et on reporte,
  à la main ou par `git cherry-pick`, celles qui servent ce thème.

## Style du code

Tout le code suit le style 2 espaces + K&R (voir `.git-blame-ignore-revs` pour le commit de
reformatage).

## Source maps

Le dépôt ne contient pas de source maps (`*.map`) : inutiles à l'exécution, elles pesaient
à elles seules les deux tiers du dépôt. `tools/vendor.py` n'en conserve jamais et retire les
commentaires `sourceMappingURL` des fichiers qu'il met à jour.

## CSS figé et composants tiers

Les feuilles de style de `css/` sont **figées** (Bootstrap 4.3.1) : la chaîne node-sass/Yarn est
retirée, elle ne s'installe plus sur les Node et Python actuels. `scss/` reste en référence. Pour
adapter l'apparence, écrire des surcharges dans `local/bootstrap_darkroom/custom.css` (chargé en
dernier par le thème) ou dans un thème enfant.

Les composants tiers (`node_modules/`, fichiers d'exécution seulement) sont décrits dans
`tools/vendor.json`. Python 3 suffit, ni npm ni Yarn :

```
python tools/vendor.py check                      # le manifeste correspond-il aux fichiers versionnés ?
python tools/vendor.py outdated                   # versions plus récentes (même majeure) et avis connus
python tools/vendor.py update popper.js 1.16.1    # simulation : montre ce qui changerait
python tools/vendor.py update popper.js 1.16.1 --apply
python -m unittest discover -s tools/tests        # tests de l'outil
```

`update` n'écrit rien sans `--apply`. Après une mise à jour : `git diff`, puis ouvrir sur une
galerie l'accueil, une photo (PhotoSwipe), le carrousel de miniatures (Slick), la recherche
(Selectize), les gestes tactiles, la notation et le nuage de tags.

## Licence et attribution

Thème original de Thomas Kuther, sous licence Apache 2.0 — voir [LICENSE.txt](LICENSE.txt).
Fork maintenu par [Gotcha](https://github.com/Gotcha26).
