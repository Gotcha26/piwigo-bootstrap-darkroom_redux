{* bd-body.tpl: tabbed body of the admin panel (shared ADMIN-SHELL, TABS, ACCORDION).
   One <form> wraps the three tab panels: the dirty tracker listens on it, and the
   save posts every field at once. It never submits (no action, type="button" save).

   Storage contract: every name="" below is a key of Config::$defaults, unchanged
   from the former settings template. Dependent fields are hidden while their parent
   is off (#32), with the hidden attribute, never disabled: a disabled field would be
   left out of the POST and its stored value wiped; a hidden one is still posted.
   Their dependency is declared by data-bd-when: "<checkbox name>" or
   "<field name>=<value>". The server renders the initial hidden state, matching
   that rule, so that rows do not show and then vanish on load; bd-admin.js keeps
   it in sync afterwards.

   Every field carries a caption and a tooltip (shared TPL-BRICKS § documenting a
   field, #29): the caption (.jm-field-desc, bd_desc_*) says what the field IS, the
   tooltip (bd_tip_*) says HOW to use it. Tooltips may hold <br> and <code>, so they
   are printed unescaped; captions are escaped. *}
<form id="bd-settings-form" class="jm-cols" autocomplete="off" novalidate>

{* ===================================================================== Appearance *}
<div id="bd-tab-appearance" class="jm-tab-panel{if $JM_TABS_ACTIVE == 'appearance'} is-active{/if}" role="tabpanel">

<section class="jm-section is-open" data-section-id="bd-colour" data-accordion-group="bd-appearance">
  {include file=$JM_SECTION_HEADER_PARTIAL title='bd_section_colour'|@translate icon_glyph='&#9680;&#65038;' expanded=true}
  <div class="jm-section-body">
    <div class="jm-field">
      <div class="jm-field-header">
        <span class="jm-field-label">{'Color theme'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_bootstrap_theme'|@translate}</span></span>
        </span>
      </div>
      <span class="jm-field-desc">{'bd_desc_bootstrap_theme'|@translate|escape}</span>
      <div class="jm-field-controls">
        {include file=$BD_CHOICE_GRID_PARTIAL name='bootstrap_theme' groups=$BD_VARIANTS value=$theme_config->bootstrap_theme label='Color theme'|@translate}
      </div>
    </div>
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
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_fluid_width'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_fluid_width'|@translate|escape}</span>
      </div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-fluid_width" name="fluid_width" value="1"{if $theme_config->fluid_width} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row" data-bd-when="fluid_width"{if !$theme_config->fluid_width} hidden{/if}>
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-fluid_width_col_xxl">{'Use 6 colums for viewports >= 1680px'|@translate|escape}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_fluid_width_col_xxl'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_fluid_width_col_xxl'|@translate|escape}</span>
      </div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-fluid_width_col_xxl" name="fluid_width_col_xxl" value="1"{if $theme_config->fluid_width_col_xxl} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>

    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-logo_image_enabled">{'Site logo'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_logo_image_enabled'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_logo_image_enabled'|@translate|escape}</span>
      </div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-logo_image_enabled" name="logo_image_enabled" value="1"{if $theme_config->logo_image_enabled} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row" data-bd-when="logo_image_enabled"{if !$theme_config->logo_image_enabled} hidden{/if}>
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-logo_image_path">{'Path'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_logo_image_path'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_logo_image_path'|@translate|escape}</span>
      </div>
      {* Informative placeholder (shared PLACEHOLDER-FIELD): an image of the maintainer's
         gallery, as a path relative to the Piwigo folder, since header.tpl prefixes it
         with the root URL. *}
      <div class="jm-field-controls"><input type="text" class="jm-input jm-input--wide" id="bd-f-logo_image_path" name="logo_image_path" value="{$theme_config->logo_image_path|escape:'html'}" placeholder="i.php?/upload/2026/01/08/20260108151136-96d856d6-th.jpg"></div>
    </div>

    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-page_header">{'Banner style'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_page_header'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_page_header'|@translate|escape}</span>
      </div>
      <div class="jm-field-controls">
        <select class="jm-select" id="bd-f-page_header" name="page_header">
          <option value="jumbotron"{if $theme_config->page_header == 'jumbotron'} selected{/if}>{'Jumbotron'|@translate}</option>
          <option value="fancy"{if $theme_config->page_header == 'fancy'} selected{/if}>{'Hero image'|@translate}</option>
          <option value="none"{if $theme_config->page_header == 'none'} selected{/if}>{'Disabled'|@translate}</option>
        </select>
      </div>
    </div>
    <div class="jm-field jm-field--row" data-bd-when="page_header=fancy"{if $theme_config->page_header != 'fancy'} hidden{/if}>
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-page_header_image">{'Background image'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_page_header_image'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_page_header_image'|@translate|escape}</span>
      </div>
      {* Informative placeholder: an image of the maintainer's gallery, full URL. *}
      <div class="jm-field-controls"><input type="text" class="jm-input jm-input--wide" id="bd-f-page_header_image" name="page_header_image" value="{$theme_config->page_header_image|escape:'html'}" placeholder="https://galerie.julien-moreau.fr/i.php?/upload/2026/01/08/20260108151136-96d856d6-xl.jpg"></div>
    </div>
    <div class="jm-field jm-field--row" data-bd-when="page_header=fancy"{if $theme_config->page_header != 'fancy'} hidden{/if}>
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-page_header_both_navs">{'Integrate lower navbar'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_page_header_both_navs'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_page_header_both_navs'|@translate|escape}</span>
      </div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-page_header_both_navs" name="page_header_both_navs" value="1"{if $theme_config->page_header_both_navs} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row" data-bd-when="page_header=fancy"{if $theme_config->page_header != 'fancy'} hidden{/if}>
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-page_header_full">{'Span the full viewport height'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_page_header_full'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_page_header_full'|@translate|escape}</span>
      </div>
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
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_category_wells'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_category_wells'|@translate|escape}</span>
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
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-cat_descriptions">{'Display category description in grid view'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_cat_descriptions'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_cat_descriptions'|@translate|escape}</span>
      </div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-cat_descriptions" name="cat_descriptions" value="1"{if $theme_config->cat_descriptions} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-cat_nb_images">{'Display number of images in album and subalbums'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_cat_nb_images'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_cat_nb_images'|@translate|escape}</span>
      </div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-cat_nb_images" name="cat_nb_images" value="1"{if $theme_config->cat_nb_images} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-thumbnail_caption">{'Show image caption'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_thumbnail_caption'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_thumbnail_caption'|@translate|escape}</span>
      </div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-thumbnail_caption" name="thumbnail_caption" value="1"{if $theme_config->thumbnail_caption} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-thumbnail_desc">{'Use description rather than title for images'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_thumbnail_desc'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_thumbnail_desc'|@translate|escape}</span>
      </div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-thumbnail_desc" name="thumbnail_desc" value="1"{if $theme_config->thumbnail_desc} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-thumbnail_linkto">{'Link thumbnail to'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_thumbnail_linkto'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_thumbnail_linkto'|@translate|escape}</span>
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
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_thumbnail_cat_desc'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_thumbnail_cat_desc'|@translate|escape}</span>
      </div>
      <div class="jm-field-controls">
        <select class="jm-select" id="bd-f-thumbnail_cat_desc" name="thumbnail_cat_desc">
          <option value="simple"{if $theme_config->thumbnail_cat_desc == 'simple'} selected{/if}>{'Simple'|@translate}</option>
          <option value="advanced"{if $theme_config->thumbnail_cat_desc == 'advanced'} selected{/if}>{'Advanced'|@translate}</option>
        </select>
      </div>
    </div>
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-thumbnail_nb_images">{'Display number of images in breadcrumb'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_thumbnail_nb_images'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_thumbnail_nb_images'|@translate|escape}</span>
      </div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-thumbnail_nb_images" name="thumbnail_nb_images" value="1"{if $theme_config->thumbnail_nb_images} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
  </div>
</section>

<section class="jm-section" data-section-id="bd-picture" data-accordion-group="bd-appearance">
  {include file=$JM_SECTION_HEADER_PARTIAL title='bd_section_picture'|@translate icon_glyph='&#9635;&#65038;' expanded=false}
  <div class="jm-section-body">
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-picture_info">{'Picture info display position'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_picture_info'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_picture_info'|@translate|escape}</span>
      </div>
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
    <div class="jm-field">
      <div class="jm-field-header">
        <label class="jm-field-label" for="bd-f-custom_css">{'Custom CSS'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_custom_css'|@translate}</span></span>
        </label>
      </div>
      <span class="jm-field-desc">{'bd_desc_custom_css'|@translate|escape}</span>
      <textarea class="jm-input jm-input--wide bd-code" id="bd-f-custom_css" name="custom_css" rows="12" spellcheck="false">{$theme_config->custom_css|escape:'html'}</textarea>
      {* CodeMirror from the shared asset store (#21): core first, then the CSS mode.
         Without it, the textarea stays a plain textarea. *}
      {jm_vendor_asset key="codemirror/codemirror.min.js"}
      {jm_vendor_asset key="codemirror/mode/css.min.js"}
    </div>
  </div>
</section>

{* Shared "Maintenance & advanced options" (#22): export / import, accordion mode.
   Inside the form, harmless: its buttons and file input carry no name, so they are
   neither posted nor seen by the dirty tracker. *}
{if isset($JM_MAINTENANCE_PARTIAL)}{include file=$JM_MAINTENANCE_PARTIAL}{/if}

</div>{* /appearance *}

{* ===================================================================== Components *}
<div id="bd-tab-components" class="jm-tab-panel{if $JM_TABS_ACTIVE == 'components'} is-active{/if}" role="tabpanel">

<section class="jm-section is-open" data-section-id="bd-slick" data-accordion-group="bd-components">
  {include file=$JM_SECTION_HEADER_PARTIAL title='bd_section_slick'|@translate icon_glyph='&#8646;&#65038;' expanded=true}
  <div class="jm-section-body">
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-slick_enabled">{'Enabled'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_slick_enabled'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_slick_enabled'|@translate|escape}</span>
      </div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-slick_enabled" name="slick_enabled" value="1"{if $theme_config->slick_enabled} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-slick_lazyload">{'lazyLoad method'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_slick_lazyload'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_slick_lazyload'|@translate|escape}</span>
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
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_slick_infinite'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_slick_infinite'|@translate|escape}</span>
      </div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-slick_infinite" name="slick_infinite" value="1"{if $theme_config->slick_infinite} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-slick_centered">{'Center mode'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_slick_centered'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_slick_centered'|@translate|escape}</span>
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
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_photoswipe'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_photoswipe'|@translate|escape}</span>
      </div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-photoswipe" name="photoswipe" value="1"{if $theme_config->photoswipe} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-photoswipe_interval">{'Autoplay interval'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_photoswipe_interval'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_photoswipe_interval'|@translate|escape}</span>
      </div>
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
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-quicksearch_navbar">{'bd_label_quicksearch_navbar'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_quicksearch_navbar'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_quicksearch_navbar'|@translate|escape}</span>
      </div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-quicksearch_navbar" name="quicksearch_navbar" value="1"{if $theme_config->quicksearch_navbar} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
  </div>
</section>

<section class="jm-section" data-section-id="bd-comments" data-accordion-group="bd-components">
  {include file=$JM_SECTION_HEADER_PARTIAL title='Comments'|@translate icon_glyph='&#9993;&#65038;' expanded=false}
  <div class="jm-section-body">
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-comments_type">{'Comments'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_comments_type'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_comments_type'|@translate|escape}</span>
      </div>
      <div class="jm-field-controls">
        <select class="jm-select" id="bd-f-comments_type" name="comments_type">
          <option value="piwigo"{if $theme_config->comments_type == 'piwigo'} selected{/if}>{'Piwigo'|@translate}</option>
          <option value="disqus"{if $theme_config->comments_type == 'disqus'} selected{/if}>{'Disqus'|@translate}</option>
        </select>
      </div>
    </div>
    <div class="jm-field jm-field--row" data-bd-when="comments_type=disqus"{if $theme_config->comments_type != 'disqus'} hidden{/if}>
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-comments_disqus_shortname">{'Disqus shortname'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_comments_disqus_shortname'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_comments_disqus_shortname'|@translate|escape}</span>
      </div>
      <div class="jm-field-controls"><input type="text" class="jm-input jm-input--wide" id="bd-f-comments_disqus_shortname" name="comments_disqus_shortname" value="{$theme_config->comments_disqus_shortname|escape:'html'}"></div>
    </div>
  </div>
</section>

<section class="jm-section" data-section-id="bd-tagcloud" data-accordion-group="bd-components">
  {include file=$JM_SECTION_HEADER_PARTIAL title='Tag cloud'|@translate icon_glyph='&#9729;&#65038;' expanded=false}
  <div class="jm-section-body">
    <div class="jm-field jm-field--row">
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-tag_cloud_type">{'Tag cloud'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_tag_cloud_type'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_tag_cloud_type'|@translate|escape}</span>
      </div>
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
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-social_enabled">{'Enabled'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_social_enabled'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_social_enabled'|@translate|escape}</span>
      </div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-social_enabled" name="social_enabled" value="1"{if $theme_config->social_enabled} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    {foreach from=['social_twitter' => 'Twitter', 'social_facebook' => 'Facebook', 'social_pinterest' => 'Pinterest', 'social_vk' => 'VK'] key=bd_key item=bd_network}
    <div class="jm-field jm-field--row" data-bd-when="social_enabled"{if !$theme_config->social_enabled} hidden{/if}>
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-{$bd_key}">{$bd_network|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{"bd_tip_$bd_key"|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{"bd_desc_$bd_key"|@translate|escape}</span>
      </div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-{$bd_key}" name="{$bd_key}" value="1"{if $theme_config->$bd_key} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
    {/foreach}
    <div class="jm-field jm-field--row" data-bd-when="social_enabled"{if !$theme_config->social_enabled} hidden{/if}>
      <div class="jm-field-label-col">
        <label class="jm-field-label" for="bd-f-social_buttons">{'Use colored share buttons instead of icons'|@translate}
          <span class="jm-tooltip-wrap"><button type="button" class="jm-tooltip-btn">i</button><span class="jm-tooltip-text">{'bd_tip_social_buttons'|@translate}</span></span>
        </label>
        <span class="jm-field-desc">{'bd_desc_social_buttons'|@translate|escape}</span>
      </div>
      <div class="jm-field-controls"><label class="jm-switch"><input type="checkbox" id="bd-f-social_buttons" name="social_buttons" value="1"{if $theme_config->social_buttons} checked{/if}><span class="jm-switch-track"></span></label></div>
    </div>
  </div>
</section>

</div>{* /social *}

</form>
