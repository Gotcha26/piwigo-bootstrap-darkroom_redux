![Bootstrap Darkroom (redux)](assets/logos/bootstrapDarkroom-banner.en@2x.webp)

# Bootstrap Darkroom (redux)

Bootstrap Darkroom (redux) is a mobile-ready Piwigo theme based on Bootstrap 4, with a dark, Lightroom-inspired look by default.  
It's for galleries that want their photos shown on a calm dark background, on any screen.

---

## 🎯 What it does, and why

A gallery is mostly viewed on phones and tablets, and a photo looks best on a neutral dark
background. ***Bootstrap Darkroom (redux)*** is a responsive theme built for that, with a
full-screen slideshow, a carousel on the photo page and a choice of colour styles.

It is an independent fork of Bootstrap Darkroom by Thomas Kuther, with its own fixes and changes.
It installs under the same folder name as the original, so it **replaces it in place**: your
selected theme, settings and child themes carry over.

## ✨ What you get

- **Colour styles**: Bootswatch, Bootstrap Material Design, and Darkroom — a dark, low-contrast scheme (the default).
- **A fullscreen slideshow** with PhotoSwipe, including auto play and HTML5 video.
- **A carousel** to browse the album from the photo page, plus an optional hero image or jumbotron header.
- **Fully responsive**: navbars, carousel, slideshow and video adapt to the screen, with swipe and tap gestures.
- **Easy customization** with CSS overrides in `local/bootstrap_darkroom/custom.css`, or a child theme.

## 🛠️ Installation

1. Keep a copy of your current `themes/bootstrap_darkroom` outside of `themes/`, then copy this theme into `themes/bootstrap_darkroom` on your Piwigo installation.
2. Enable it from **Administration → Themes**.
3. Disable the smartpocket theme (enabled by default), so Bootstrap Darkroom is also the default on mobile.

This theme is not listed in the Piwigo extension repository: Piwigo will never offer to update it, nor overwrite it with the official release.

## ⚠️ Known issues

- In IE11, the navbars only display correctly with the fluid-width layout.
- On iOS, PhotoSwipe fullscreen is not supported (iPhone: landscape only; iPad: not at all) — an iOS bug.
- Portrait videos (e.g. from phones) must be re-encoded in actual portrait orientation: rotation tags are ignored.
- The theme uses the jQuery bundled with Piwigo, **jQuery 1.11.3 (2015), obsolete** and subject to known security advisories; updating it is up to Piwigo.

## 📜 Origin and license

Original theme by Thomas Kuther, released under the Apache License 2.0 — see [`LICENSE.txt`](LICENSE.txt). Fork maintained by Gotcha.

---

##### Quick links:
- Project: https://github.com/Gotcha26/piwigo-bootstrap-darkroom_redux
- Author: Gotcha (Julien Moreau) — https://julien-moreau.fr
