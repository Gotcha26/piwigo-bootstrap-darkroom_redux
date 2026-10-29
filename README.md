[Read the FR version 🇫🇷](LISEZMOI.md)

# Bootstrap Darkroom (redux)

A Piwigo theme that puts your photos first: a calm, dark, Lightroom-inspired look, a fullscreen slideshow, and a layout that adapts to every screen — phone, tablet or desktop.

![Three colour styles on desktop, and the photo page on a phone](screenshot.png)

*Three colour styles on desktop, and the photo page on a phone — preview image from the original project.*

---

## Who is it for?

- **Photographers and galleries** who want their pictures on a neutral dark background, where colours read true and the interface stays out of the way.
- **Galleries mostly browsed on phones and tablets**: swipe and tap gestures, adaptive image sizes, nothing to pinch or zoom.
- **Webmasters who like to tweak** without touching the theme's files: a real settings panel, a CSS override file, child themes.

## What you get

### A look made for photos

- **Darkroom, the default style**: a dark, low-contrast scheme drawn from Lightroom's colours. No glare, no frame fighting your pictures.
- **Over 30 colour styles** if dark is not your taste: Bootswatch, Bootstrap Material Design and Darkroom. Pick one from a thumbnail grid, not from a drop-down of names.
- **An optional hero header**: a full-width fading background image, or a jumbotron banner, for a gallery that opens with a statement.
- **Even cards**: album and photo thumbnails line up row by row, whatever the length of their titles.

### A way of browsing made for pictures

- **A fullscreen slideshow** with PhotoSwipe: auto play, HTML5 video, and album thumbnails that can open it directly.
- **A carousel on the photo page** to hop from one picture to the next without going back to the album.
- **Fully responsive**: navbars, carousel, slideshow and video adapt to the screen. Carousel and slideshow content loads on demand, with the image size picked to fit the device.
- **Plugin buttons that find their place**: buttons added to the navbar by plugins get a sensible fallback instead of breaking the bar.

### Yours to configure

- **A settings panel in tabs**, with a caption and a tooltip on every setting. Settings that depend on another one appear only when relevant.
- **A built-in editor for your own CSS**, plus a maintenance section for the advanced cases.
- **Customization that survives updates**: overrides live in `local/bootstrap_darkroom/custom.css`, outside the theme folder — or build a child theme.

### Respectful by design

- **No request to a third party.** Every web font is served from your own server, and comment avatars are a local image: no Google Fonts, no Gravatar. Nothing to disclose to your visitors, nothing to break when a CDN does.
- **Credits you can read.** Every redistributed component, with its licence, is listed in the admin panel and on the public About page.
- **Light on the database**: the theme never writes to it while a public page loads.

## Installation

1. Copy this theme into `themes/bootstrap_darkroom` on your Piwigo installation.
2. Enable it from **Administration → Themes**.
3. Disable the smartpocket theme (enabled by default), so Bootstrap Darkroom is also the default on mobile.
4. Open the theme's settings and pick a colour style.

> **⚠️ IMPORTANT : the folder must be named `bootstrap_darkroom`**, never `redux`. That name is the theme's identity for Piwigo.

## Coming from the original Bootstrap Darkroom?

Bootstrap Darkroom (redux) is an independent fork of Bootstrap Darkroom by Thomas Kuther. It is built to **take over from the original in place: nothing is lost** — theme selection, settings, user preferences and child themes carry over.

### Why nothing is lost

The theme installs under the **same folder name** as the original, `bootstrap_darkroom`. That name is the theme's identity for Piwigo, so everything keyed on it stays valid:

| What | Why it carries over |
| --- | --- |
| **Theme selection** | Same theme identifier, still enabled and still the default. |
| **Settings** | Same `bootstrap_darkroom` row and same format in the Piwigo configuration: read as they are, **never rewritten** by the takeover. |
| **User preferences** | Stored under the theme identifier, which does not change. |
| **Child themes** | They declare `bootstrap_darkroom` as their parent (BC-BG keeps working). |
| **Your customizations** | `local/bootstrap_darkroom/custom.css` lives outside the theme folder. |

### What happens when you swap the files

Piwigo records a theme's version only when it is activated, and has no update step for themes. After a swap, it would keep serving the compiled templates of the previous build. The theme handles this itself:

```
Files replaced in themes/bootstrap_darkroom
                 │
                 ▼
Theme settings page opened by the webmaster
                 │
                 ▼
┌──────────────────────────────────────────┐
│ Which build was running before?          │
│ original · older redux · unknown · same  │
└──────────────────────────────────────────┘
                 │
                 ▼
Compiled templates purged  ──►  version stamped  ──►  "Done" notice
                 │
                 ▼
Settings untouched, gallery served by the new build
```

- **Webmaster only**: the purge and the stamp are never triggered by anyone else.
- Until then, a webmaster browsing the gallery sees a notice linking to the theme settings page, to finish the setup. It never writes anything on a public page.
- The builds recognised are the original (`16.d`-style versions), earlier redux builds (`16.d-redux.N`), the current numbering, and anything else (treated as unknown, with the same safe outcome).
- **Known limit**: a rebuild shipped under the *same* version number is not detected.

### Procedure

1. **Back up** your current `themes/bootstrap_darkroom` outside of `themes/`.
2. **Replace** the folder with this theme's files, under the same name `themes/bootstrap_darkroom` (replace it whole rather than merging, so no stale file remains).
3. Log in as **webmaster** and open **Administration → Themes → Bootstrap Darkroom (settings)**: the takeover completes and a confirmation is displayed.
4. If smartpocket is still enabled, disable it so Bootstrap Darkroom is also the default on mobile.

You do not need to deactivate or reactivate the theme. To go back, put your backup back: the settings were never changed.

### Following the original

The original keeps evolving (compatibility with new Piwigo releases, translations). It is no longer merged wholesale, but **watched and ported selectively**, change by change, so what is useful reaches this fork without importing what has diverged.

| Direction | Policy |
| --- | --- |
| Original → redux | Reviewed, then ported selectively. |
| redux → original | Nothing is sent back. |

### Updates and the Piwigo extension page

The theme is distributed on **its own page** of the Piwigo extension repository (`eid=1122`), referenced by `Theme URI` in `themeconf.inc.php`. Piwigo compares the installed version with that page only: it will never offer the original's release as an "update" that would overwrite this fork.

## Known issues

- In IE11, the navbars only display correctly with the fluid-width layout.
- On iOS, PhotoSwipe fullscreen is not supported (iPhone: landscape only; iPad: not at all) — an iOS bug.
- Portrait videos (e.g. from phones) must be re-encoded in actual portrait orientation: rotation tags are ignored.
- The theme uses the jQuery bundled with Piwigo, **jQuery 1.11.3 (2015), obsolete** and subject to known security advisories; updating it is up to Piwigo.

## FAQ

**Will I lose my settings when moving from the original?**  
No. The settings row is shared with the original and is not rewritten by the takeover.

**The gallery looks broken right after the swap.**  
The compiled templates of the previous build are still served. Open the theme settings page as webmaster: they are purged.

**I am an administrator, not the webmaster: can I finish the setup?**  
No, this step is reserved to the webmaster.

**Can I go back to the original?**  
Yes: restore your backup of `themes/bootstrap_darkroom`. Your settings are unchanged.

**Do I need to build anything to customize the look?**  
No. Put your overrides in `local/bootstrap_darkroom/custom.css`, or in the settings panel's CSS editor.

**Which languages are maintained?**  
English (`en_UK`) and French (`fr_FR`).

## Credits

Original theme by **Thomas Kuther**. Fork maintained by Gotcha (Julien Moreau) — https://julien-moreau.fr  
Third-party components and their licences are listed in the theme's admin panel.

## License

Apache License 2.0 — see [`LICENSE.txt`](LICENSE.txt). The original copyright notices are kept.

---

##### Quick links:
| Resource | Link |
|---|---|
| Project | https://github.com/Gotcha26/piwigo-bootstrap-darkroom_redux |
| Original theme | https://github.com/Piwigo/piwigo-bootstrap-darkroom |
| Piwigo page | https://piwigo.org/ext/extension_view.php?eid=1122 |
| Author | Gotcha (Julien Moreau) — https://julien-moreau.fr |
