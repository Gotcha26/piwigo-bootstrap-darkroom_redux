{* bd-body.tpl: tabbed body of the admin panel (shared ADMIN-SHELL, TABS, ACCORDION).
   One <form> wraps the three tab panels: the dirty tracker listens on it, and the
   save posts every field at once. It never submits (no action, type="button" save).

   Storage contract: every name="" below is a key of Config::$defaults, unchanged
   from the former settings template. Dependent fields are greyed out (.is-disabled +
   inert), never disabled: a disabled field would be left out of the POST and its
   stored value wiped. Their dependency is declared by data-bd-when:
   "<checkbox name>" or "<field name>=<value>". *}
<form id="bd-settings-form" class="jm-cols" autocomplete="off" novalidate>

{* ===================================================================== Appearance *}
<div id="bd-tab-appearance" class="jm-tab-panel{if $JM_TABS_ACTIVE == 'appearance'} is-active{/if}" role="tabpanel">

<section class="jm-section is-open" data-section-id="bd-colour" data-accordion-group="bd-appearance">
  {include file=$JM_SECTION_HEADER_PARTIAL title='bd_section_colour'|@translate icon_glyph='&#9680;&#65038;' expanded=true}
  <div class="jm-section-body">
    <p class="jm-field-desc">{'bd_colour_desc'|@translate|escape}</p>
    {include file=$BD_CHOICE_GRID_PARTIAL name='bootstrap_theme' groups=$BD_VARIANTS value=$theme_config->bootstrap_theme label='Color theme'|@translate}
    {* Derived from the variant by bd-admin.js, as before. *}
    <input type="hidden" name="navbar_main_style" value="{$theme_config->navbar_main_style|escape:'html'}">
    <input type="hidden" name="navbar_main_bg" value="{$theme_config->navbar_main_bg|escape:'html'}">
    <input type="hidden" name="navbar_contextual_style" value="{$theme_config->navbar_contextual_style|escape:'html'}">
    <input type="hidden" name="navbar_contextual_bg" value="{$theme_config->navbar_contextual_bg|escape:'html'}">
    {* No control left for these two: carried as is, so that a save does not reset them. *}
    <input type="hidden" name="bootswatch_theme" value="{$theme_config->bootswatch_theme|escape:'html'}">
    <input type="hidden" name="material_color" value="{$theme_config->material_color|escape:'html'}">
  </div>
</section>

<section class="jm-section" data-section-id="bd-layout" data-accordion-group="bd-appearance">
  {include file=$JM_SECTION_HEADER_PARTIAL title='bd_section_layout'|@translate icon_glyph='&#9638;&#65038;' expanded=false}
  <div class="jm-section-body">
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-fluid_width">{'Full width layout'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'Use full width containers that span the entire width of the viewport'|@translate|escape}</span></span>
        </label>
      </div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-fluid_width" name="fluid_width" value="1"{if $theme_config->fluid_width} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row" data-bd-when="fluid_width">
      <div class="jm-field-label-col"><label class="jm-field-label" for="bd-f-fluid_width_col_xxl">{'Use 6 colums for viewports >= 1680px'|@translate|escape}</label></div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-fluid_width_col_xxl" name="fluid_width_col_xxl" value="1"{if $theme_config->fluid_width_col_xxl} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>

    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-logo_image_enabled">{'Site logo'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'Display a site logo image instead of plain text'|@translate|escape}</span></span>
        </label>
      </div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-logo_image_enabled" name="logo_image_enabled" value="1"{if $theme_config->logo_image_enabled} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row" data-bd-when="logo_image_enabled">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-logo_image_path">{'Path'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'The path to the image, relative to your Piwigo installation folder'|@translate|escape}</span></span>
        </label>
      </div>
      <div class="jm-field-controls"><input type="text" class="jm-input jm-input--wide" id="bd-f-logo_image_path" name="logo_image_path" value="{$theme_config->logo_image_path|escape:'html'}" placeholder="relative/path/to/image"></div>
    </div>

    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col"><label class="jm-field-label" for="bd-f-page_header">{'Banner style'|@translate}</label></div>
      <div class="jm-field-controls">
        <select class="jm-select" id="bd-f-page_header" name="page_header">
          <option value="jumbotron"{if $theme_config->page_header == 'jumbotron'} selected{/if}>{'Jumbotron'|@translate}</option>
          <option value="fancy"{if $theme_config->page_header == 'fancy'} selected{/if}>{'Hero image'|@translate}</option>
          <option value="none"{if $theme_config->page_header == 'none'} selected{/if}>{'Disabled'|@translate}</option>
        </select>
      </div>
    </div>
    <div class="jm-field jm-field--row" data-bd-when="page_header=fancy">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-page_header_image">{'Background image'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'URL or releative path to the image'|@translate|escape}</span></span>
        </label>
      </div>
      <div class="jm-field-controls"><input type="text" class="jm-input jm-input--wide" id="bd-f-page_header_image" name="page_header_image" value="{$theme_config->page_header_image|escape:'html'}"></div>
    </div>
    <div class="jm-field jm-field--row" data-bd-when="page_header=fancy">
      <div class="jm-field-label-col"><label class="jm-field-label" for="bd-f-page_header_both_navs">{'Integrate lower navbar'|@translate}</label></div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-page_header_both_navs" name="page_header_both_navs" value="1"{if $theme_config->page_header_both_navs} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row" data-bd-when="page_header=fancy">
      <div class="jm-field-label-col"><label class="jm-field-label" for="bd-f-page_header_full">{'Span the full viewport height'|@translate}</label></div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-page_header_full" name="page_header_full" value="1"{if $theme_config->page_header_full} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
  </div>
</section>

<section class="jm-section" data-section-id="bd-albums" data-accordion-group="bd-appearance">
  {include file=$JM_SECTION_HEADER_PARTIAL title='bd_section_albums'|@translate icon_glyph='&#9638;&#65038;' expanded=false}
  <div class="jm-section-body">
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-category_wells">{'Display categories as Bootstrap media wells'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'This will display categories as media wells with squared thumbnails, similar to the smartpocket mobile theme.'|@translate|escape}</span></span>
        </label>
      </div>
      <div class="jm-field-controls">
        <select class="jm-select" id="bd-f-category_wells" name="category_wells">
          <option value="never"{if $theme_config->category_wells == 'never'} selected{/if}>{'Never'|@translate}</option>
          <option value="always"{if $theme_config->category_wells == 'always'} selected{/if}>{'Always'|@translate}</option>
          <option value="mobile_only"{if $theme_config->category_wells == 'mobile_only'} selected{/if}>{'On mobile devices only'|@translate}</option>
        </select>
      </div>
    </div>
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col"><label class="jm-field-label" for="bd-f-cat_descriptions">{'Display category description in grid view'|@translate}</label></div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-cat_descriptions" name="cat_descriptions" value="1"{if $theme_config->cat_descriptions} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col"><label class="jm-field-label" for="bd-f-cat_nb_images">{'Display number of images in album and subalbums'|@translate}</label></div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-cat_nb_images" name="cat_nb_images" value="1"{if $theme_config->cat_nb_images} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col"><label class="jm-field-label" for="bd-f-thumbnail_caption">{'Show image caption'|@translate}</label></div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-thumbnail_caption" name="thumbnail_caption" value="1"{if $theme_config->thumbnail_caption} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col"><label class="jm-field-label" for="bd-f-thumbnail_desc">{'Use description rather than title for images'|@translate}</label></div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-thumbnail_desc" name="thumbnail_desc" value="1"{if $theme_config->thumbnail_desc} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-thumbnail_linkto">{'Link thumbnail to'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_linkto'|@translate|escape}</span></span>
        </label>
      </div>
      <div class="jm-field-controls">
        <select class="jm-select" id="bd-f-thumbnail_linkto" name="thumbnail_linkto">
          <option value="picture"{if $theme_config->thumbnail_linkto == 'picture'} selected{/if}>{'Picture details page'|@translate}</option>
          <option value="photoswipe"{if $theme_config->thumbnail_linkto == 'photoswipe'} selected{/if}>{'PhotoSwipe Slideshow'|@translate}</option>
          <option value="photoswipe_mobile_only"{if $theme_config->thumbnail_linkto == 'photoswipe_mobile_only'} selected{/if}>{'Photoswipe Slideshow (Mobile devices only)'|@translate}</option>
        </select>
      </div>
    </div>
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-thumbnail_cat_desc">{'Description display style'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'Simple'|@translate}: {'center-aligned h5 heading'|@translate|escape}. {'Advanced'|@translate}: {'left-aligned free text for advanced descriptions'|@translate|escape}.</span></span>
        </label>
      </div>
      <div class="jm-field-controls">
        <select class="jm-select" id="bd-f-thumbnail_cat_desc" name="thumbnail_cat_desc">
          <option value="simple"{if $theme_config->thumbnail_cat_desc == 'simple'} selected{/if}>{'Simple'|@translate}</option>
          <option value="advanced"{if $theme_config->thumbnail_cat_desc == 'advanced'} selected{/if}>{'Advanced'|@translate}</option>
        </select>
      </div>
    </div>
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col"><label class="jm-field-label" for="bd-f-thumbnail_nb_images">{'Display number of images in breadcrumb'|@translate}</label></div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-thumbnail_nb_images" name="thumbnail_nb_images" value="1"{if $theme_config->thumbnail_nb_images} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
  </div>
</section>

<section class="jm-section" data-section-id="bd-picture" data-accordion-group="bd-appearance">
  {include file=$JM_SECTION_HEADER_PARTIAL title='bd_section_picture'|@translate icon_glyph='&#9635;&#65038;' expanded=false}
  <div class="jm-section-body">
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col"><label class="jm-field-label" for="bd-f-picture_info">{'Picture info display position'|@translate}</label></div>
      <div class="jm-field-controls">
        <select class="jm-select" id="bd-f-picture_info" name="picture_info">
          <option value="cards"{if $theme_config->picture_info == 'cards'} selected{/if}>{'Card grid below the image'|@translate}</option>
          <option value="tabs"{if $theme_config->picture_info == 'tabs'} selected{/if}>{'Tabs below the image'|@translate}</option>
          <option value="sidebar"{if $theme_config->picture_info == 'sidebar'} selected{/if}>{'Sidebar (like Boostrap Default)'|@translate}</option>
          <option value="disabled"{if $theme_config->picture_info == 'disabled'} selected{/if}>{'Disabled'|@translate}</option>
        </select>
      </div>
    </div>
  </div>
</section>

<section class="jm-section" data-section-id="bd-css" data-accordion-group="bd-appearance">
  {include file=$JM_SECTION_HEADER_PARTIAL title='bd_section_css'|@translate icon_glyph='&#10100;&#10101;' expanded=false}
  <div class="jm-section-body">
    <p class="jm-field-desc">{'bd_css_desc'|@translate|escape}</p>
    <div class="jm-field">
      <label class="jm-field-label" for="bd-f-custom_css">{'Custom CSS'|@translate}</label>
      <textarea class="jm-input jm-input--wide bd-code" id="bd-f-custom_css" name="custom_css" rows="12" spellcheck="false">{$theme_config->custom_css|escape:'html'}</textarea>
    </div>
  </div>
</section>

</div>{* /appearance *}

{* ===================================================================== Components *}
<div id="bd-tab-components" class="jm-tab-panel{if $JM_TABS_ACTIVE == 'components'} is-active{/if}" role="tabpanel">

<section class="jm-section is-open" data-section-id="bd-slick" data-accordion-group="bd-components">
  {include file=$JM_SECTION_HEADER_PARTIAL title='bd_section_slick'|@translate icon_glyph='&#8646;&#65038;' expanded=true}
  <div class="jm-section-body">
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-slick_enabled">{'Enabled'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'Enable the slick carousel below the main image on the picture page'|@translate|escape}.</span></span>
        </label>
      </div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-slick_enabled" name="slick_enabled" value="1"{if $theme_config->slick_enabled} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-slick_lazyload">{'lazyLoad method'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">ondemand {'will load the image as soon as you slide to it'|@translate|escape}. progressive {'loads all images one after another when the page loads (use carefully!)'|@translate|escape}.</span></span>
        </label>
      </div>
      <div class="jm-field-controls">
        <select class="jm-select" id="bd-f-slick_lazyload" name="slick_lazyload">
          <option value="ondemand"{if $theme_config->slick_lazyload == 'ondemand'} selected{/if}>ondemand</option>
          <option value="progressive"{if $theme_config->slick_lazyload == 'progressive'} selected{/if}>progressive</option>
        </select>
      </div>
    </div>
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-slick_infinite">{'Infinite looping'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'Endlessly scroll through album images'|@translate|escape}</span></span>
        </label>
      </div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-slick_infinite" name="slick_infinite" value="1"{if $theme_config->slick_infinite} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-slick_centered">{'Center mode'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'Display the currently selected image in the middle. Works best with infinite looping enabled.'|@translate|escape}</span></span>
        </label>
      </div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-slick_centered" name="slick_centered" value="1"{if $theme_config->slick_centered} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
  </div>
</section>

<section class="jm-section" data-section-id="bd-photoswipe" data-accordion-group="bd-components">
  {include file=$JM_SECTION_HEADER_PARTIAL title='PhotoSwipe' icon_glyph='&#9974;&#65038;' expanded=false}
  <div class="jm-section-body">
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-photoswipe">{'Enabled'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'Enable PhotoSwipe fullscreen slideshow. Disable if you prefer to use Plugins like Fotorama or Piwigo\'s default slideshow.'|@translate|escape}</span></span>
        </label>
      </div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-photoswipe" name="photoswipe" value="1"{if $theme_config->photoswipe} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col"><label class="jm-field-label" for="bd-f-photoswipe_interval">{'Autoplay interval'|@translate}</label></div>
      <div class="jm-field-controls">
        <input type="number" class="jm-input" id="bd-f-photoswipe_interval" name="photoswipe_interval" value="{$theme_config->photoswipe_interval|escape:'html'}" min="1000" max="50000" step="100">
        <span class="bd-unit">{'milliseconds'|@translate}</span>
      </div>
    </div>
  </div>
</section>

<section class="jm-section" data-section-id="bd-quicksearch" data-accordion-group="bd-components">
  {include file=$JM_SECTION_HEADER_PARTIAL title='Quick search'|@translate icon_glyph='&#8981;&#65038;' expanded=false}
  <div class="jm-section-body">
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col"><label class="jm-field-label" for="bd-f-quicksearch_navbar">{'Quick search'|@translate} {'directly in the navigation bar'|@translate}</label></div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-quicksearch_navbar" name="quicksearch_navbar" value="1"{if $theme_config->quicksearch_navbar} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
  </div>
</section>

<section class="jm-section" data-section-id="bd-comments" data-accordion-group="bd-components">
  {include file=$JM_SECTION_HEADER_PARTIAL title='Comments'|@translate icon_glyph='&#9993;&#65038;' expanded=false}
  <div class="jm-section-body">
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col"><label class="jm-field-label" for="bd-f-comments_type">{'Comments'|@translate}</label></div>
      <div class="jm-field-controls">
        <select class="jm-select" id="bd-f-comments_type" name="comments_type">
          <option value="piwigo"{if $theme_config->comments_type == 'piwigo'} selected{/if}>{'Piwigo'|@translate}</option>
          <option value="disqus"{if $theme_config->comments_type == 'disqus'} selected{/if}>{'Disqus'|@translate}</option>
        </select>
      </div>
    </div>
    <div class="jm-field jm-field--row" data-bd-when="comments_type=disqus">
      <div class="jm-field-label-col"><label class="jm-field-label" for="bd-f-comments_disqus_shortname">{'Disqus shortname'|@translate}</label></div>
      <div class="jm-field-controls"><input type="text" class="jm-input jm-input--wide" id="bd-f-comments_disqus_shortname" name="comments_disqus_shortname" value="{$theme_config->comments_disqus_shortname|escape:'html'}"></div>
    </div>
  </div>
</section>

<section class="jm-section" data-section-id="bd-tagcloud" data-accordion-group="bd-components">
  {include file=$JM_SECTION_HEADER_PARTIAL title='Tag cloud'|@translate icon_glyph='&#9729;&#65038;' expanded=false}
  <div class="jm-section-body">
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col"><label class="jm-field-label" for="bd-f-tag_cloud_type">{'Tag cloud'|@translate}</label></div>
      <div class="jm-field-controls">
        <select class="jm-select" id="bd-f-tag_cloud_type" name="tag_cloud_type">
          <option value="basic"{if $theme_config->tag_cloud_type == 'basic'} selected{/if}>{'Basic'|@translate}</option>
          <option value="html5"{if $theme_config->tag_cloud_type == 'html5'} selected{/if}>{'HTML 5 canvas'|@translate}</option>
        </select>
      </div>
    </div>
  </div>
</section>

</div>{* /components *}

{* ===================================================================== Social media *}
<div id="bd-tab-social" class="jm-tab-panel{if $JM_TABS_ACTIVE == 'social'} is-active{/if}" role="tabpanel">

<section class="jm-section is-open" data-section-id="bd-social" data-accordion-group="bd-social">
  {include file=$JM_SECTION_HEADER_PARTIAL title='bd_section_social'|@translate icon_glyph='&#8689;&#65038;' expanded=true}
  <div class="jm-section-body">
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col"><label class="jm-field-label" for="bd-f-social_enabled">{'Enabled'|@translate}</label></div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-social_enabled" name="social_enabled" value="1"{if $theme_config->social_enabled} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row" data-bd-when="social_enabled">
      <div class="jm-field-label-col"><label class="jm-field-label" for="bd-f-social_twitter">{'Twitter'|@translate}</label></div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-social_twitter" name="social_twitter" value="1"{if $theme_config->social_twitter} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row" data-bd-when="social_enabled">
      <div class="jm-field-label-col"><label class="jm-field-label" for="bd-f-social_facebook">{'Facebook'|@translate}</label></div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-social_facebook" name="social_facebook" value="1"{if $theme_config->social_facebook} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row" data-bd-when="social_enabled">
      <div class="jm-field-label-col"><label class="jm-field-label" for="bd-f-social_pinterest">{'Pinterest'|@translate}</label></div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-social_pinterest" name="social_pinterest" value="1"{if $theme_config->social_pinterest} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row" data-bd-when="social_enabled">
      <div class="jm-field-label-col"><label class="jm-field-label" for="bd-f-social_vk">{'VK'|@translate}</label></div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-social_vk" name="social_vk" value="1"{if $theme_config->social_vk} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row" data-bd-when="social_enabled">
      <div class="jm-field-label-col"><label class="jm-field-label" for="bd-f-social_buttons">{'Use colored share buttons instead of icons'|@translate}</label></div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-social_buttons" name="social_buttons" value="1"{if $theme_config->social_buttons} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
  </div>
</section>

</div>{* /social *}

</form>
