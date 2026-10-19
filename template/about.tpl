<nav class="navbar navbar-expand-lg navbar-contextual {$theme_config->navbar_contextual_style} {$theme_config->navbar_contextual_bg} sticky-top mb-5">
    <div class="container{if $theme_config->fluid_width}-fluid{/if}">
        <div class="navbar-brand mr-auto"><a href="{$U_HOME}">{'Home'|@translate}</a>{$LEVEL_SEPARATOR}<a href>{'About'|@translate}</a></div>
        <ul class="navbar-nav justify-content-end">
             {if !empty($PLUGIN_INDEX_ACTIONS)}{$PLUGIN_INDEX_ACTIONS}{/if}
        </ul>
    </div>
</nav>

{include file='infos_errors.tpl'}

<div class="container{if $theme_config->fluid_width}-fluid{/if}">
    <div class="card">
        <h4 class="card-header">{'About'|@translate}</h4>
        <div class="card-body">
          {$ABOUT_MESSAGE}
{if isset($THEME_ABOUT) }
          {$THEME_ABOUT}
{/if}

{if not empty($about_msgs)}
{foreach from=$about_msgs item=elt}
{$elt}
{/foreach}
{/if}
          <p>{'bd_about_theme'|@translate} {'bd_credits_license'|@translate}</p>
{if not empty($bd_credits)}
{foreach from=$bd_credits item=group}
          <h5 class="mt-4">{$group.title}</h5>
          <ul class="list-unstyled">
{foreach from=$group.items item=item}
            <li><a href="{$item.url}">{$item.name}</a> — {$item.author} ({$item.license})</li>
{/foreach}
          </ul>
{/foreach}
{/if}
        </div>
    </div>
</div>
