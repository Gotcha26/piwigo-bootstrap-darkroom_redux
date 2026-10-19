Piwigo Bootstrap Darkroom
-------------------
A mobile-ready [Piwigo](http://piwigo.org) theme based on [Bootstrap 4](https://getbootstrap.com)

### Features

* Various color styles
  * [Bootswatch](https://bootswatch.com)
  * [Material Design](https://mdbootstrap.com/)
  * Darkroom: a dark, low contrast color scheme based on Lightroom® colors (the default)
* Optional page header with fancy fading full width background image, or a jumbotron banner
* Different layout option for the picture details page
* Video support using native HTML 5 video widget
* Fullscreen slideshow view using [PhotoSwipe](http://photoswipe.com)
  * Supports auto play
  * Supports HTML5 video
  * Album thumbnails can be linked to PhotoSwipe directly (like smartpocket)
* Configurable carousel album navigation on the picture page using [slick slider](http://kenwheeler.github.io/slick/)
* 100% mobile ready
  * fully responsive Navbars, Carousel, PhotoSwipe slideshow, video content
  * async/ondemand loading of carousel & PhotoSwipe content, adaptive image size selection, swipe & tap events
* Various configuration options
* Easy customization using CSS overrides or SASS custom build for advanced needs.
 

### Usage

1. Installation:
 * Use the Piwigo built-in extension manager (preferred)
 * or git clone and move to piwigo/themes/bootstrap_darkroom
 * or download from http://piwigo.org/ext/extension_view.php?eid=1122
2. Enable Bootstrap Darkroom
3. Disable the smartpocket theme (it's enabled by default). This is required in order to use Bootstrap Darkroom by default on mobile devices, too.

### Demo

A demo is available at https://demo2.piwigo.com/ and another one at https://demo4.piwigo.com/

### Documentation, Support and Issue tracker
* [Github Wiki](https://github.com/tkuther/piwigo-bootstrap-darkroom/wiki)
* [Forum thread](http://piwigo.org/forum/viewtopic.php?id=26624)
* [Issue tracker](https://github.com/tkuther/piwigo-bootstrap-darkroom/issues)

### Recommended Plugins for best mobile user experience
* [GThumb+](http://piwigo.org/ext/extension_view.php?eid=591) or [gdThumb](http://piwigo.org/ext/extension_view.php?eid=771): this will give you masonry-style thumbnail pages that make most use of valuable space.
* [RV Thumbnail Scroller](http://piwigo.org/ext/extension_view.php?eid=493): this one will load items on the thumbnails page as they are requested using ajax, ideal for swiping through albums while keeping the initial page load size small. Avoids pagination.

### Known issues

* In IE11, the navbars only display correctly if using fluid-width layout.
* On iOS the PhotoSwipe fullscreen mode isn't supported. On iPhone it does work in landscape orientation only, on iPad it doesn't work at all. That's an iOS bug. Works just fine on Android
* Plugins that add buttons to the Navbar might not (yet) be supported, see [Plugin Support Matrix](https://github.com/tkuther/piwigo-bootstrap-darkroom/wiki/Plugin-Support-Matrix)
* Portrait mode videos (e.g. from mobile phones) need to be recoded in actual portrait orientation, rotation tags won't work.
* The theme no longer ships jQuery: it uses the copy bundled with Piwigo, which is **jQuery 1.11.3 (2015) and obsolete**, with known security advisories. Updating it is up to Piwigo, not to this theme.

### Preview

![Preview](https://raw.githubusercontent.com/tkuther/piwigo-bootstrap-darkroom/master/screenshot.png)

### Components

The full list of redistributed components, with their versions, origins and licences, lives in one place: [`tools/vendor.json`](tools/vendor.json). [`include/credits.php`](include/credits.php) is generated from it (`python tools/vendor.py credits --apply`) and feeds both the credits window of the admin panel and the public About page, so this README does not repeat it.

### Development & Customizing
* **The stylesheets under `css/` are compiled and frozen** at Bootstrap 4.3.1. The node-sass/Yarn build chain was retired (it no longer installs on current Node and Python); `scss/` is kept as a reference only. Do not expect `npm run build` to exist.
* To adapt the look, write override rules in `local/bootstrap_darkroom/custom.css` (the theme loads it last) or in a child theme.
* Third-party scripts and styles live under `node_modules/` (runtime files only, see `.gitignore`) and are described by [`tools/vendor.json`](tools/vendor.json): name, version, origin, licence and kept files. No npm and no Yarn are involved, only Python 3:

```
python tools/vendor.py check                     # manifest vs versioned files
python tools/vendor.py outdated                  # latest same-major version + known advisories (read-only)
python tools/vendor.py update popper.js 1.16.1   # dry run: shows what would change
python tools/vendor.py update popper.js 1.16.1 --apply
python -m unittest discover -s tools/tests       # tests of the tool
```

`update` writes nothing without `--apply`, never keeps source maps, and rolls back if a step fails. Review the result with `git diff`, then open these pages on a gallery: home, a photo (PhotoSwipe), the thumbnail carousel (Slick), search (Selectize), touch gestures, rating, tag cloud.

jQuery and jQuery Cookie are not shipped: the theme uses the copies bundled with Piwigo, and jQuery there (1.11.3) is old and obsolete. Updating it is Piwigo's job.

### Thanks
* Phil Bayfield for his work on the Bootstrap Default theme, which this theme was initiallly based on as a child theme. It's grown up now.
* The Piwigo Team for a great gallery software
* The Piwigo translation team

### License

```
Copyright 2017 Thomas Kuther

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
```
