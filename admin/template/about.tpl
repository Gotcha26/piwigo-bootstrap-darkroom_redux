{combine_css path="themes/bootstrap_darkroom/admin/css/admin.css"}
<div class="titrePage">
    <h2>{'Bootstrap Darkroom'|@translate} {$TABSHEET_TITLE}</h2>
</div>
<div id="configContent" class="bd-about">
    <h3>{'Bootstrap Darkroom'|@translate}</h3>
    <h5>{'By'|@translate}: Thomas Kuther, Gotcha</h5>

    <p>{'A mobile-ready theme based on Bootstrap 4'|@translate}</p>

    <p>{'Source'|@translate}: <a href="https://github.com/Gotcha26/piwigo-bootstrap-darkroom_redux" target="_blank">https://github.com/Gotcha26/piwigo-bootstrap-darkroom_redux</a>
    </p>

    <p>{'Download'|@translate}: <a href="https://github.com/Gotcha26/piwigo-bootstrap-darkroom_redux/releases" target="_blank">https://github.com/Gotcha26/piwigo-bootstrap-darkroom_redux/releases</a>
    </p>

    <p>{'If you find any issues, please report them to the'|@translate} <a href="https://github.com/Gotcha26/piwigo-bootstrap-darkroom_redux/issues" target="_blank">{'Issue tracker'|@translate}</a>
    </p>

    <p class="bd-about-upstream">{'Upstream project'|@translate}: <a href="https://github.com/Piwigo/piwigo-bootstrap-darkroom" target="_blank">Bootstrap Darkroom</a>
        &mdash; Thomas Kuther &mdash;
        <a href="https://github.com/Piwigo/piwigo-bootstrap-darkroom/wiki" target="_blank">{'Documentation'|@translate}</a>,
        <a href="http://piwigo.org/ext/extension_view.php?eid=831" target="_blank">{'extension page'|@translate}</a>
    </p>

    <h3>{'Privacy'|@translate}</h3>

    <p class="bd-about-warning">{'Some color themes load their web fonts from Google servers each time a page is displayed. The IP address of your visitors, their browser and the address of the page they are viewing are then sent to a third party. The following color themes are affected:'|@translate}</p>

    <p class="ident">bootswatch-cosmo, bootswatch-cyborg, bootswatch-darkly, bootswatch-flatly,
        bootswatch-journal, bootswatch-lumen, bootswatch-lux, bootswatch-materia,
        bootswatch-minty, bootswatch-sandstone, bootswatch-simplex, bootswatch-sketchy,
        bootswatch-solar, bootswatch-spacelab, bootswatch-superhero, bootswatch-united,
        bootswatch-yeti</p>

    <p>{'Every other color theme, including the default one, serves its fonts from your own server.'|@translate}</p>

    <h3>{'Third-party components'|@translate}</h3>

    <p>{'This theme redistributes the components listed below. Versions are those actually shipped.'|@translate}</p>

    <h4>{'Libraries'|@translate}</h4>
    <table class="bd-about-table">
        <tr><th>{'Component'|@translate}</th><th>{'Version'|@translate}</th><th>{'License'|@translate}</th></tr>
        <tr><td><a href="https://getbootstrap.com" target="_blank">Bootstrap</a></td><td>4.3.1</td><td>MIT</td></tr>
        <tr><td><a href="https://bootswatch.com" target="_blank">Bootswatch</a></td><td>4.3.1</td><td>MIT</td></tr>
        <tr><td><a href="https://fezvrasta.github.io/bootstrap-material-design" target="_blank">Bootstrap Material Design</a></td><td>4.1.2</td><td>MIT</td></tr>
        <tr><td><a href="https://lipis.github.io/bootstrap-social/" target="_blank">Bootstrap Social</a></td><td>5.1.1</td><td>MIT</td></tr>
        <tr><td><a href="https://jquery.com" target="_blank">jQuery</a></td><td>3.4.1</td><td>MIT</td></tr>
        <tr><td><a href="https://github.com/benmajor/jQuery-Touch-Events" target="_blank">jQuery Touch Events</a></td><td>1.0.9</td><td>MIT</td></tr>
        <tr><td><a href="https://popper.js.org" target="_blank">Popper.js</a></td><td>1.15.0</td><td>MIT</td></tr>
        <tr><td><a href="https://photoswipe.com" target="_blank">PhotoSwipe</a></td><td>4.1.3</td><td>MIT</td></tr>
        <tr><td><a href="https://kenwheeler.github.io/slick/" target="_blank">Slick</a></td><td>1.6.0 (fork)</td><td>MIT</td></tr>
        <tr><td><a href="https://selectize.dev" target="_blank">Selectize</a></td><td>0.12.6</td><td>Apache-2.0</td></tr>
    </table>

    <h4>{'Bundled scripts'|@translate}</h4>

    <p>{'The scripts below were imported from the'|@translate} <a href="https://github.com/Philio/bootstrapdefault" target="_blank">Bootstrap Default</a> {'theme by Phil Bayfield. They were not written for this theme.'|@translate}</p>

    <table class="bd-about-table">
        <tr><th>{'Component'|@translate}</th><th>{'Version'|@translate}</th><th>{'License'|@translate}</th></tr>
        <tr><td><a href="https://github.com/carhartl/jquery-cookie" target="_blank">jQuery Cookie</a> &mdash; Klaus Hartl</td><td>1.4.1</td><td>MIT</td></tr>
        <tr><td>Equal Heights &mdash; Rob Glazebrook</td><td>1.01</td><td>GPL-3.0-or-later</td></tr>
        <tr><td>jQuery.awesomeCloud &mdash; Russ Porosky, IndyArmy Network</td><td>0.2</td><td>{'not stated'|@translate}</td></tr>
        <tr><td>rating.js &mdash; {'Piwigo core, via Bootstrap Default'|@translate}</td><td>&mdash;</td><td>GPL-2.0</td></tr>
        <tr><td>theme.js &mdash; {'Bootstrap Default, extended since'|@translate}</td><td>&mdash;</td><td>Apache-2.0</td></tr>
    </table>

    <h4>{'Icons and fonts'|@translate}</h4>
    <table class="bd-about-table">
        <tr><th>{'Component'|@translate}</th><th>{'Version'|@translate}</th><th>{'License'|@translate}</th></tr>
        <tr><td><a href="https://fontawesome.com" target="_blank">Font Awesome Free</a></td><td>5.11.2</td><td>CC BY 4.0, SIL OFL 1.1, MIT</td></tr>
        <tr><td><a href="https://thenounproject.com/DmitryBaranovskiy/collection/photo/" target="_blank">Photography Icons</a> &mdash; Dmitry Baranovskiy</td><td>&mdash;</td><td><a href="https://creativecommons.org/licenses/by/3.0/us/" target="_blank">CC BY 3.0 US</a></td></tr>
        <tr><td>PT Sans</td><td>0.0.35</td><td>SIL OFL 1.1</td></tr>
        <tr><td>Roboto</td><td>0.0.35</td><td>Apache-2.0</td></tr>
    </table>

    <h4>{'Web fonts served by Google'|@translate}</h4>
    <p class="ident">Cabin Sketch, Lato, Montserrat, Neucha, News Cycle, Nunito Sans, Open Sans,
        Roboto, Source Sans Pro, Ubuntu &mdash; SIL OFL 1.1, Apache-2.0, UFL 1.0</p>

    <h3>{'License'|@translate}</h3>
    <p>Copyright 2017 Thomas Kuther &mdash; <a href="http://www.apache.org/licenses/LICENSE-2.0" target="_blank">Apache License 2.0</a></p>

    <p class="bd-about-note">{'This list is maintained by hand and may lag behind the shipped files.'|@translate}</p>
</div>
