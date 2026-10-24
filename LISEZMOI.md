![Bootstrap Darkroom (redux)](assets/logos/bootstrapDarkroom-banner.fr@2x.webp)

# Bootstrap Darkroom (redux)

Bootstrap Darkroom (redux) est un thème Piwigo adapté aux mobiles, fondé sur Bootstrap 4, avec par défaut un style sombre inspiré de Lightroom.  
C'est pour les galeries qui veulent montrer leurs photos sur un fond sombre et calme, sur tous les écrans.

---

## 🎯 À quoi ça sert, et pourquoi

Une galerie se consulte surtout sur téléphone et tablette, et une photo est mise en valeur par un
fond sombre neutre. ***Bootstrap Darkroom (redux)*** est un thème adaptatif pensé pour cela, avec
un diaporama plein écran, un carrousel sur la page d'une photo et un choix de styles de couleurs.

C'est un fork autonome de Bootstrap Darkroom de Thomas Kuther, avec ses propres correctifs et
évolutions. Il s'installe sous le même nom de dossier que l'original et le **remplace donc à
l'identique** : thème sélectionné, réglages et thèmes enfants sont conservés.

## ✨ Ce que vous y trouvez

- **Des styles de couleurs** : Bootswatch, Bootstrap Material Design, et Darkroom — une palette sombre à faible contraste (par défaut).
- **Un diaporama plein écran** avec PhotoSwipe, lecture automatique et vidéo HTML5 comprises.
- **Un carrousel** pour parcourir l'album depuis la page d'une photo, et un en-tête facultatif en image pleine largeur ou jumbotron.
- **Entièrement adaptatif** : barres de navigation, carrousel, diaporama et vidéo s'ajustent à l'écran, avec gestes de balayage et de toucher.
- **Une personnalisation facile** par surcharges CSS dans `local/bootstrap_darkroom/custom.css`, ou par thème enfant.

## 🛠️ Installation

1. Conservez une copie de votre `themes/bootstrap_darkroom` actuel hors de `themes/`, puis copiez ce thème dans `themes/bootstrap_darkroom` de votre installation Piwigo.
2. Activez-le depuis **Administration → Thèmes**.
3. Désactivez le thème smartpocket (activé par défaut), pour que Bootstrap Darkroom soit aussi le thème par défaut sur mobile.

Ce thème n'est pas référencé dans le dépôt d'extensions Piwigo : Piwigo ne vous proposera jamais de le mettre à jour, ni ne l'écrasera par la version officielle.

## ⚠️ Problèmes connus

- Sous IE11, les barres de navigation ne s'affichent correctement qu'en mise en page pleine largeur.
- Sous iOS, le plein écran de PhotoSwipe n'est pas pris en charge (iPhone : en paysage seulement ; iPad : pas du tout) — un bogue d'iOS.
- Les vidéos en portrait (prises au téléphone, par exemple) doivent être réencodées réellement en portrait : les balises de rotation sont ignorées.
- Le thème utilise le jQuery fourni par Piwigo, **jQuery 1.11.3 (2015), obsolète** et visé par des avis de sécurité connus ; sa mise à jour relève de Piwigo.

## 📜 Origine et licence

Thème original de Thomas Kuther, publié sous licence Apache 2.0 — voir [`LICENSE.txt`](LICENSE.txt). Fork maintenu par Gotcha.

---

##### Liens rapides :
- Projet : https://github.com/Gotcha26/piwigo-bootstrap-darkroom_redux
- Auteur : Gotcha (Julien Moreau) — https://julien-moreau.fr
