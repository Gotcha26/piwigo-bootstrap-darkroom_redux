{* about.tpl: the About page (#75). Assembled by ThemeController::assignAbout()
   into $BD_ABOUT; this template only lays it out.

   - notice:  the webmaster's legal notice, sanitized HTML (About::renderNotice);
   - lineage: one sentence per theme in use, already escaped (About::lineageHtml);
              a child theme adds its own through the bd_about_lineage event;
   - piwigo:  what is shown of Piwigo: 'simple' (one line), 'original' (the
              core's about text), 'none';
   - credits: component families, plain text, escaped below.

   A child theme never overrides this file: it declares, the parent renders
   (see include/about.php). $about_msgs stays a slot for plugins. *}
<nav class="navbar navbar-expand-lg navbar-contextual {$theme_config->navbar_contextual_style} {$theme_config->navbar_contextual_bg} sticky-top mb-5">
  <div class="container{if $theme_config->fluid_width}-fluid{/if}">
    <div class="navbar-brand mr-auto"><a href="{$U_HOME}">{'Home'|@translate}</a>{$LEVEL_SEPARATOR}<a href>{$BD_ABOUT.title|escape:'html'}</a></div>
    <ul class="navbar-nav justify-content-end">
      {if !empty($PLUGIN_INDEX_ACTIONS)}{$PLUGIN_INDEX_ACTIONS}{/if}
    </ul>
  </div>
</nav>

{include file='infos_errors.tpl'}

<div class="container{if $theme_config->fluid_width}-fluid{/if}">
  <div class="card bd-about">
    <h4 class="card-header">{$BD_ABOUT.title|escape:'html'}</h4>
    <div class="card-body">
{if $BD_ABOUT.notice != ''}
      <div class="bd-about-notice">{$BD_ABOUT.notice}</div>
{/if}

{if not empty($about_msgs)}
{foreach from=$about_msgs item=elt}
      {$elt}
{/foreach}
{/if}

{if $BD_ABOUT.piwigo == 'original'}
      <div class="bd-about-piwigo">{$ABOUT_MESSAGE}</div>
{/if}

      <div class="bd-about-lineage">
{foreach from=$BD_ABOUT.lineage item=line}
        <p>{$line}</p>
{/foreach}
{if $BD_ABOUT.piwigo == 'simple'}
        <p>{'bd_about_powered_by'|@translate|sprintf:'<a href="https://piwigo.org" target="_blank" rel="noopener">Piwigo</a>'}</p>
{/if}
      </div>

{if not empty($BD_ABOUT.credits)}
      <h5 class="mt-4">{'bd_about_components'|@translate}</h5>
{/if}
{foreach from=$BD_ABOUT.credits item=group}
      <h6 class="mt-3">{$group.title|escape:'html'}</h6>
      <ul class="list-unstyled">
{foreach from=$group.items item=item}
        <li>{if $item.url != ''}<a href="{$item.url|escape:'html'}" target="_blank" rel="noopener">{$item.name|escape:'html'}</a>{else}{$item.name|escape:'html'}{/if}{if $item.author != ''} — {$item.author|escape:'html'}{/if}{if $item.license != ''} ({$item.license|escape:'html'}){/if}</li>
{/foreach}
      </ul>
{/foreach}
    </div>
  </div>
</div>
