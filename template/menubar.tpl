<!-- Start of menubar.tpl -->
<ul class="navbar-nav{if !$theme_config->quicksearch_navbar} ml-auto{/if}">
{assign var="discover_menu_exists" value=false}
{foreach from=$blocks key=id item=block}
{if not empty($block->template)}
{if $id != "mbMenu" && $id != "mbSpecials" && $id != "mbIdentification"}
{include file=$block->template|@get_extent:$id }
{/if}
{if $discover_menu_exists == false && ($id == "mbSpecials" or $id == "mbMenu")}
    <li class="nav-item dropdown">                                                                                                                                                   
        <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">{'Discover'|@translate}</a>
        <div class="dropdown-menu dropdown-menu-right" role="menu">
        {if not empty($blocks.mbMenu->template)}
        {include file=$blocks.mbMenu->template}
        {/if}
        {if not empty($blocks.mbSpecials->template)}
        {if not empty($blocks.mbMenu->template)}
            <div class="dropdown-divider"></div>
        {/if}
        {include file=$blocks.mbSpecials->template}
        {/if}
        </div>
    </li>
{assign var="discover_menu_exists" value=true}
{/if}
{else}
{$block->raw_content}
{/if}
{/foreach}

{* use foreach again for plugin compatibility, no idea why $blocks.mbIdentification->template breaks SocialConnect, for example *}
{foreach from=$blocks key=id item=block}
{if not empty($block->template) && $id == "mbIdentification"}
{include file=$block->template|@get_extent:$id }
{/if}
{/foreach}
{* Light / dark switch (#81), last item, light / dark mode only. The moon offers the
   dark mode, the sun the light one, as on the core standard pages. It writes the core
   'mode' cookie (Skins::COOKIE) and reloads: the server picks the skin. *}
{if isset($bd_color) && $bd_color.toggle}
{if $bd_color.next == 'dark'}{assign var=bd_mode_label value='bd_switch_to_dark'|@translate}{else}{assign var=bd_mode_label value='bd_switch_to_light'|@translate}{/if}
    <li class="nav-item bd-mode-switch">
        <button type="button" class="nav-link bd-mode-switch__btn" id="bd-mode-switch" data-mode="{$bd_color.next}" title="{$bd_mode_label|escape:'html'}" aria-label="{$bd_mode_label|escape:'html'}">
            <i class="{if $bd_color.icons == 'gallery'}gallery-icon-{else}fas fa-{/if}{if $bd_color.mode == 'dark'}sun{else}moon{/if}" aria-hidden="true"></i><span class="bd-mode-switch__label">{$bd_mode_label|escape:'html'}</span>
        </button>
        <script>{literal}(function(){var b=document.getElementById('bd-mode-switch');if(!b)return;b.addEventListener('click',function(){document.cookie='mode='+b.getAttribute('data-mode')+';max-age=2592000;path=/;SameSite=Lax';window.location.reload();});})();{/literal}</script>
    </li>
{/if}
</ul>
<!-- End of menubar.tpl -->
