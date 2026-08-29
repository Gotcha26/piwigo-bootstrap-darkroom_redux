# Bootstrap Darkroom (redux)

Fork maintenu du thème Piwigo [Bootstrap Darkroom](https://github.com/Piwigo/piwigo-bootstrap-darkroom)
de Thomas Kuther.

La documentation des fonctionnalités du thème reste celle de l'amont : voir
[README.md](README.md). Ce fichier ne décrit que ce qui distingue ce fork.

## Pourquoi ce fork

Corriger des défauts sans attendre leur passage en amont. Les correctifs d'intérêt général y
sont proposés en pull request ; ce dépôt sert d'antichambre, pas de divergence.

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

## Modèle de branches

- `master` — branche d'intégration. Elle diverge de l'amont en permanence : elle porte l'identité
  du fork. Les synchronisations se font par `git merge upstream/master`.
- Un correctif = une branche partant de `upstream/master`, jamais de `master`. C'est ce qui le
  rend proposable tel quel en pull request amont.

Un correctif accepté en amont doit être retiré d'ici. Piwigo rebase les PR à la fusion, donc le
commit revient sous un SHA différent et `git branch --merged` ne le voit pas. L'outil qui tranche
est `git cherry master <branche>` : un préfixe `-` signifie « patch déjà présent ».

## Style du code

Le code propre au fork suit le style 2 espaces + K&R (voir `.git-blame-ignore-revs` pour le
commit de reformatage). Les pull requests vers l'amont, elles, doivent respecter le style de
l'amont — 4 espaces, accolades Allman.

## Licence et attribution

Thème original de Thomas Kuther, sous licence Apache 2.0 — voir [LICENSE.txt](LICENSE.txt).
Fork maintenu par [Gotcha](https://github.com/Gotcha26).
