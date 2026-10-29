[Consulter la version EN 🇬🇧](README.md)

# Bootstrap Darkroom (redux)

Un thème Piwigo qui met vos photos au premier plan : un style sombre et calme inspiré de Lightroom, un diaporama plein écran, et une mise en page qui s'adapte à tous les écrans — téléphone, tablette ou ordinateur.

![Trois styles de couleurs sur ordinateur, et la page d'une photo sur téléphone](screenshot.png)

*Trois styles de couleurs sur ordinateur, et la page d'une photo sur téléphone — image d'aperçu issue du projet d'origine.*

---

## Pour qui ?

- **Les photographes et les galeries** qui veulent leurs images sur un fond neutre et sombre, où les couleurs restent justes et où l'interface se fait discrète.
- **Les galeries surtout consultées sur téléphone et tablette** : gestes de balayage et de toucher, tailles d'image adaptées, rien à pincer ni à agrandir.
- **Les webmasters qui aiment ajuster** sans toucher aux fichiers du thème : un vrai panneau de réglages, un fichier de surcharge CSS, des thèmes enfants.

## Ce que vous y trouvez

### Un style pensé pour les photos

- **Darkroom, le style par défaut** : une palette sombre à faible contraste tirée des couleurs de Lightroom. Pas d'éblouissement, pas de cadre qui rivalise avec vos images.
- **Plus de 30 styles de couleurs** si le sombre n'est pas votre goût : Bootswatch, Bootstrap Material Design et Darkroom. On choisit dans une grille de vignettes, pas dans une liste de noms.
- **Un en-tête facultatif** : une image de fond pleine largeur en fondu, ou un jumbotron, pour une galerie qui s'ouvre sur une déclaration.
- **Des cartes alignées** : les vignettes d'albums et de photos s'alignent rangée par rangée, quelle que soit la longueur de leurs titres.

### Une navigation pensée pour les images

- **Un diaporama plein écran** avec PhotoSwipe : lecture automatique, vidéo HTML5, et vignettes d'album qui peuvent l'ouvrir directement.
- **Un carrousel sur la page d'une photo** pour passer d'une image à la suivante sans retourner à l'album.
- **Entièrement adaptatif** : barres de navigation, carrousel, diaporama et vidéo s'ajustent à l'écran. Le contenu du carrousel et du diaporama se charge à la demande, avec la taille d'image choisie pour l'appareil.
- **Des boutons de plugins qui trouvent leur place** : les boutons ajoutés à la barre par des plugins reçoivent un repli raisonnable au lieu de casser la barre.

### À configurer à votre main

- **Un panneau de réglages à onglets**, avec une légende et une infobulle sur chaque réglage. Les réglages qui dépendent d'un autre n'apparaissent que lorsqu'ils servent.
- **Un éditeur intégré pour votre CSS**, et une section de maintenance pour les cas avancés.
- **Une personnalisation qui survit aux mises à jour** : les surcharges vivent dans `local/bootstrap_darkroom/custom.css`, hors du dossier du thème — ou bâtissez un thème enfant.

### Respectueux par conception

- **Aucune requête vers un tiers.** Toutes les polices web sont servies depuis votre propre serveur, et les avatars des commentaires sont une image locale : ni Google Fonts, ni Gravatar. Rien à déclarer à vos visiteurs, rien qui casse quand un CDN tombe.
- **Des crédits lisibles.** Chaque composant redistribué, avec sa licence, est listé dans le panneau d'administration et sur la page « À propos » publique.
- **Léger pour la base de données** : le thème n'y écrit jamais pendant le chargement d'une page publique.

## Installation

1. Copiez ce thème dans `themes/bootstrap_darkroom` de votre installation Piwigo.
2. Activez-le depuis **Administration → Thèmes**.
3. Désactivez le thème smartpocket (activé par défaut), pour que Bootstrap Darkroom soit aussi le thème par défaut sur mobile.
4. Ouvrez les réglages du thème et choisissez un style de couleurs.

> **⚠️ IMPORTANT : le dossier doit s'appeler `bootstrap_darkroom`**, jamais `redux`. Ce nom est l'identité du thème pour Piwigo.

## Vous venez de Bootstrap Darkroom d'origine ?

Bootstrap Darkroom (redux) est un fork autonome de Bootstrap Darkroom de Thomas Kuther. Il est conçu pour **reprendre la place de l'original sur site : rien n'est perdu** — thème sélectionné, réglages, préférences des utilisateurs et thèmes enfants sont conservés.

### Pourquoi rien n'est perdu

Le thème s'installe sous le **même nom de dossier** que l'original, `bootstrap_darkroom`. Ce nom est l'identité du thème pour Piwigo : tout ce qui s'y rattache reste valide.

| Quoi | Pourquoi c'est conservé |
| --- | --- |
| **Thème sélectionné** | Même identifiant : toujours activé, toujours par défaut. |
| **Réglages** | Même ligne `bootstrap_darkroom` et même format dans la configuration de Piwigo : lus tels quels, **jamais réécrits** par la reprise. |
| **Préférences utilisateur** | Rangées sous l'identifiant du thème, qui ne change pas. |
| **Thèmes enfants** | Ils déclarent `bootstrap_darkroom` comme parent (BC-BG continue de fonctionner). |
| **Vos personnalisations** | `local/bootstrap_darkroom/custom.css` vit hors du dossier du thème. |

### Ce qui se passe quand vous remplacez les fichiers

Piwigo n'enregistre la version d'un thème qu'à son activation et n'a aucune étape de mise à jour pour les thèmes. Après un remplacement, il continuerait donc de servir les gabarits compilés de la version précédente. Le thème s'en charge lui-même :

```
Fichiers remplacés dans themes/bootstrap_darkroom
                 │
                 ▼
Page de réglages du thème ouverte par le webmaster
                 │
                 ▼
┌──────────────────────────────────────────┐
│ Quelle version tournait avant ?          │
│ original · redux ancien · inconnue · même│
└──────────────────────────────────────────┘
                 │
                 ▼
Gabarits compilés purgés ──► version enregistrée ──► message « Terminé »
                 │
                 ▼
Réglages intacts, galerie servie par la nouvelle version
```

- **Webmaster seulement** : la purge et l'enregistrement ne sont jamais déclenchés par un autre profil.
- D'ici là, un webmaster qui parcourt la galerie voit un message avec un lien vers la page de réglages du thème, pour terminer l'installation. Aucune page publique n'écrit quoi que ce soit.
- Sont reconnus : l'original (versions de la forme `16.d`), les anciennes versions redux (`16.d-redux.N`), la numérotation actuelle, et tout le reste (traité comme inconnu, avec le même résultat sûr).
- **Limite connue** : une nouvelle version livrée sous le *même* numéro n'est pas détectée.

### Marche à suivre

1. **Sauvegardez** votre `themes/bootstrap_darkroom` actuel hors de `themes/`.
2. **Remplacez** le dossier par les fichiers de ce thème, sous le même nom `themes/bootstrap_darkroom` (remplacement complet plutôt que fusion, pour ne laisser aucun fichier périmé).
3. Connectez-vous en **webmaster** et ouvrez **Administration → Thèmes → Bootstrap Darkroom (réglages)** : la reprise s'achève et une confirmation s'affiche.
4. Si smartpocket est encore activé, désactivez-le pour que Bootstrap Darkroom soit aussi le thème par défaut sur mobile.

Inutile de désactiver puis réactiver le thème. Pour revenir en arrière, remettez votre sauvegarde : les réglages n'ont jamais été modifiés.

### Suivi de l'original

L'original continue d'évoluer (compatibilité avec les nouvelles versions de Piwigo, traductions). Il n'est plus fusionné en bloc, mais **surveillé et reporté de façon sélective**, changement par changement : ce qui est utile arrive dans ce fork, sans importer ce qui a divergé.

| Sens | Politique |
| --- | --- |
| Original → redux | Examiné, puis reporté de façon sélective. |
| redux → original | Rien n'est renvoyé. |

### Mises à jour et fiche Piwigo

Le thème est distribué sur **sa propre fiche** du dépôt d'extensions Piwigo (`eid=1122`), référencée par `Theme URI` dans `themeconf.inc.php`. Piwigo compare la version installée à cette fiche uniquement : il ne proposera jamais la version de l'original comme « mise à jour » qui écraserait ce fork.

## Problèmes connus

- Sous IE11, les barres de navigation ne s'affichent correctement qu'en mise en page pleine largeur.
- Sous iOS, le plein écran de PhotoSwipe n'est pas pris en charge (iPhone : en paysage seulement ; iPad : pas du tout) — un bogue d'iOS.
- Les vidéos en portrait (prises au téléphone, par exemple) doivent être réencodées réellement en portrait : les balises de rotation sont ignorées.
- Le thème utilise le jQuery fourni par Piwigo, **jQuery 1.11.3 (2015), obsolète** et visé par des avis de sécurité connus ; sa mise à jour relève de Piwigo.

## FAQ

**Vais-je perdre mes réglages en quittant l'original ?**  
Non. La ligne de réglages est partagée avec l'original et n'est pas réécrite par la reprise.

**La galerie semble cassée juste après le remplacement.**  
Les gabarits compilés de la version précédente sont encore servis. Ouvrez la page de réglages du thème en webmaster : ils sont purgés.

**Je suis administrateur, pas webmaster : puis-je terminer l'installation ?**  
Non, cette étape est réservée au webmaster.

**Puis-je revenir à l'original ?**  
Oui : restaurez votre sauvegarde de `themes/bootstrap_darkroom`. Vos réglages sont inchangés.

**Dois-je compiler quoi que ce soit pour personnaliser l'apparence ?**  
Non. Placez vos surcharges dans `local/bootstrap_darkroom/custom.css`, ou dans l'éditeur CSS du panneau de réglages.

**Quelles langues sont maintenues ?**  
L'anglais (`en_UK`) et le français (`fr_FR`).

## Crédits

Thème original de **Thomas Kuther**. Fork maintenu par Gotcha (Julien Moreau) — https://julien-moreau.fr  
Les composants tiers et leurs licences sont listés dans le panneau d'administration du thème.

## Licence

Apache License 2.0 — voir [`LICENSE.txt`](LICENSE.txt). Les mentions de copyright d'origine sont conservées.

---

##### Liens rapides :
| Ressource | Lien |
|---|---|
| Projet | https://github.com/Gotcha26/piwigo-bootstrap-darkroom_redux |
| Thème original | https://github.com/Piwigo/piwigo-bootstrap-darkroom |
| Fiche Piwigo | https://piwigo.org/ext/extension_view.php?eid=1122 |
| Auteur | Gotcha (Julien Moreau) — https://julien-moreau.fr |
