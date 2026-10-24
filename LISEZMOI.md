# Bootstrap Darkroom (redux)

Un thème [Piwigo](https://piwigo.org) adapté aux mobiles, fondé sur [Bootstrap 4](https://getbootstrap.com).

Un fork autonome de [Bootstrap Darkroom](https://github.com/Piwigo/piwigo-bootstrap-darkroom) de Thomas Kuther, avec ses propres correctifs et évolutions. Gratuit, et vendu nulle part. Il évolue à son propre rythme : les apports utiles du projet d'origine y sont repris au cas par cas.

## Fonctionnalités

* Plusieurs styles de couleurs
  * [Bootswatch](https://bootswatch.com)
  * [Bootstrap Material Design](https://github.com/FezVrasta/bootstrap-material-design)
  * Darkroom : une palette sombre à faible contraste, inspirée des couleurs de Lightroom® (par défaut)
* En-tête de page facultatif : image pleine largeur, ou bandeau jumbotron
* Plusieurs dispositions possibles pour la page de détail d'une photo
* Lecture vidéo avec le lecteur HTML 5 natif
* Diaporama plein écran avec [PhotoSwipe](https://photoswipe.com)
  * lecture automatique
  * vidéo HTML5
  * les miniatures d'album peuvent ouvrir PhotoSwipe directement (comme smartpocket)
* Carrousel de navigation dans l'album, configurable, sur la page d'une photo, avec [slick slider](https://kenwheeler.github.io/slick/)
* 100 % adapté aux mobiles
  * barres de navigation, carrousel, diaporama PhotoSwipe et vidéos entièrement adaptatifs
  * chargement asynchrone / à la demande du carrousel et de PhotoSwipe, choix adaptatif de la taille d'image, gestes de balayage et de toucher
* De nombreuses options de configuration
* Personnalisation facile par surcharges CSS ou thème enfant (voir *Personnalisation*)

## Installation — à lire en premier

Ce thème est un **remplacement à l'identique**. Il s'installe dans `themes/bootstrap_darkroom`, le même dossier que l'original, et conserve délibérément le même identifiant de thème.

C'est ce qui rend la bascule transparente : votre galerie garde son thème sélectionné, ses réglages et ses thèmes enfants, sans rien à reconfigurer. Cela implique aussi que les deux ne peuvent pas cohabiter — ce fork prend la place du thème officiel. Conservez une copie de votre `themes/bootstrap_darkroom` actuel **hors** de `themes/` avant de basculer, pour pouvoir revenir en arrière.

1. Copiez le thème dans le dossier `themes/bootstrap_darkroom` de votre installation Piwigo.
2. Activez Bootstrap Darkroom dans **Administration → Thèmes**.
3. Désactivez le thème smartpocket (activé par défaut). C'est nécessaire pour que Bootstrap Darkroom soit aussi le thème par défaut sur mobile.

Prérequis : les mêmes que le thème d'origine. Rien d'autre n'est nécessaire.

## Mises à jour

Ce thème n'est pas référencé dans le dépôt d'extensions Piwigo : Piwigo ne vous proposera donc jamais de le mettre à jour — et, surtout, ne l'écrasera jamais par la version officielle. Les mises à jour se récupèrent depuis le dépôt indiqué dans *Projet et licence* ci-dessous.

## Boutons des plugins

Les boutons que les plugins ajoutent aux barres de navigation sont gérés de façon générique, quel que soit le plugin :

* les icônes standard de Piwigo (`pwg-icon-*`) sont affichées sous leur équivalent Font Awesome ;
* les icônes qu'un plugin dessine déjà avec Font Awesome sont conservées ;
* quand l'icône n'est pas reconnue, le bouton affiche son libellé en texte, ou une icône neutre s'il n'en a pas ;
* les images utilisées comme icônes sont calées sur la hauteur des autres icônes ;
* les menus `switchBox` deviennent de vrais menus déroulants.

## Personnalisation

Les feuilles de style de `css/` sont compilées et figées (Bootstrap 4.3.1). Pour adapter l'apparence, écrivez des surcharges dans `local/bootstrap_darkroom/custom.css` (chargé en dernier par le thème) ou dans un thème enfant.

## Problèmes connus

* Sous IE11, les barres de navigation ne s'affichent correctement qu'avec la mise en page en largeur fluide.
* Sous iOS, le plein écran de PhotoSwipe n'est pas pris en charge : sur iPhone il ne fonctionne qu'en paysage, sur iPad pas du tout. C'est un défaut d'iOS ; Android fonctionne normalement.
* Les vidéos en mode portrait (téléphones, par exemple) doivent être ré-encodées en orientation portrait réelle : les balises de rotation ne sont pas prises en compte.
* Le thème n'embarque plus jQuery : il utilise celui du cœur de Piwigo, soit **jQuery 1.11.3 (2015), obsolète** et visé par des avis de sécurité connus. Sa mise à jour relève de Piwigo, pas du thème.

## Aperçu

![Aperçu](screenshot.png)

## Composants

Les composants tiers redistribués, avec leurs versions, origines et licences, sont listés dans la fenêtre de crédits du panneau d'administration du thème et sur sa page publique « À propos » ; ce fichier ne les répète pas.

## Remerciements

* Phil Bayfield, pour son travail sur le thème Bootstrap Default, sur lequel ce thème s'est d'abord appuyé en tant que thème enfant. Il a grandi depuis.
* L'équipe Piwigo, pour un excellent logiciel de galerie.
* L'équipe de traduction de Piwigo.

## Projet et licence

https://github.com/Gotcha26/piwigo-bootstrap-darkroom_redux

Thème original de Thomas Kuther — https://github.com/Piwigo/piwigo-bootstrap-darkroom. Fork maintenu par Gotcha — https://github.com/Gotcha26.

Publié sous licence Apache 2.0 — voir [LICENSE.txt](LICENSE.txt). Copyright 2017 Thomas Kuther.
