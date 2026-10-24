# Bootstrap Darkroom (redux)

A mobile-ready [Piwigo](https://piwigo.org) theme based on [Bootstrap 4](https://getbootstrap.com).

An independent fork of [Bootstrap Darkroom](https://github.com/Piwigo/piwigo-bootstrap-darkroom) by Thomas Kuther, with its own fixes and changes. Free, and not sold anywhere. It evolves at its own pace: useful changes from the original project are brought in case by case.

## Features

* Various color styles
  * [Bootswatch](https://bootswatch.com)
  * [Bootstrap Material Design](https://github.com/FezVrasta/bootstrap-material-design)
  * Darkroom: a dark, low contrast color scheme based on Lightroom® colors (the default)
* Optional page header: a full width hero image, or a jumbotron banner
* Different layout option for the picture details page
* Video support using the native HTML 5 video widget
* Fullscreen slideshow view using [PhotoSwipe](https://photoswipe.com)
  * Supports auto play
  * Supports HTML5 video
  * Album thumbnails can be linked to PhotoSwipe directly (like smartpocket)
* Configurable carousel album navigation on the picture page using [slick slider](https://kenwheeler.github.io/slick/)
* 100% mobile ready
  * fully responsive navbars, carousel, PhotoSwipe slideshow, video content
  * async/on-demand loading of carousel and PhotoSwipe content, adaptive image size selection, swipe and tap events
* Various configuration options
* Easy customization using CSS overrides or a child theme (see *Customizing*)

## Installation — read this first

This theme is a **drop-in replacement**. It installs into `themes/bootstrap_darkroom`, the same directory as the original, and deliberately keeps the same theme id.

That is what makes the switch transparent: your gallery keeps its theme selection, its settings and its child themes, with nothing to reconfigure. It also means the two cannot coexist — this fork takes the place of the official theme. Keep a copy of your current `themes/bootstrap_darkroom` outside of `themes/` before switching, so you can go back.

1. Copy the theme into the `themes/bootstrap_darkroom` directory of your Piwigo installation.
2. Enable Bootstrap Darkroom in **Administration → Themes**.
3. Disable the smartpocket theme (it is enabled by default). This is required in order to use Bootstrap Darkroom by default on mobile devices, too.

Requirements: the same as the original theme. Nothing else is needed.

## Updates

This theme is not listed in the Piwigo extension repository, so Piwigo will never offer to update it — and, importantly, will never overwrite it with the official release. Updates are fetched from the repository linked under *Project* below.

## Plugin buttons

Buttons that plugins add to the navbars are handled generically, whatever the plugin:

* the standard Piwigo icons (`pwg-icon-*`) are shown as their Font Awesome equivalent;
* icons a plugin already draws with Font Awesome are kept;
* when the icon is not recognised, the button shows its label as text instead, or a neutral icon if it has no label;
* images used as icons are sized to the height of the other icons;
* `switchBox` menus become regular dropdown menus.

## Customizing

The stylesheets under `css/` are compiled and frozen (Bootstrap 4.3.1). To adapt the look, write override rules in `local/bootstrap_darkroom/custom.css` (the theme loads it last) or in a child theme.

## Known issues

* In IE11, the navbars only display correctly if using fluid-width layout.
* On iOS the PhotoSwipe fullscreen mode isn't supported. On iPhone it does work in landscape orientation only, on iPad it doesn't work at all. That's an iOS bug. Works just fine on Android.
* Portrait mode videos (e.g. from mobile phones) need to be recoded in actual portrait orientation, rotation tags won't work.
* The theme no longer ships jQuery: it uses the copy bundled with Piwigo, which is **jQuery 1.11.3 (2015) and obsolete**, with known security advisories. Updating it is up to Piwigo, not to this theme.

## Preview

![Preview](screenshot.png)

## Components

The redistributed third-party components, with their versions, origins and licenses, are listed in the credits window of the theme's admin panel and on its public About page; this README does not repeat them.

## Thanks

* Phil Bayfield for his work on the Bootstrap Default theme, which this theme was initially based on as a child theme. It has grown up now.
* The Piwigo Team for a great gallery software.
* The Piwigo translation team.

## Project and license

https://github.com/Gotcha26/piwigo-bootstrap-darkroom_redux

Original theme by Thomas Kuther — https://github.com/Piwigo/piwigo-bootstrap-darkroom. Fork maintained by Gotcha — https://github.com/Gotcha26.

Released under the Apache License, Version 2.0 — see [LICENSE.txt](LICENSE.txt). Copyright 2017 Thomas Kuther.
