{* Light / dark switch (#81): one navbar item, light / dark mode only. Included at the
   right end of the main navbar (menubar.tpl) and of the photo page's navbar
   (picture_nav.tpl, the page has no main navbar). The moon offers the dark mode, the
   sun the light one, as on the core standard pages. js/mode-switch.js writes the core
   'mode' cookie (Skins::COOKIE) and reloads: the server picks the skin. The label
   shows in the collapsed menu only, like the photo page's other actions. *}
{if isset($bd_color) && $bd_color.toggle}
{if $bd_color.next == 'dark'}{assign var=bd_mode_label value='bd_switch_to_dark'|@translate}{else}{assign var=bd_mode_label value='bd_switch_to_light'|@translate}{/if}
{combine_script id='bootstrap_darkroom.mode_switch' path='themes/bootstrap_darkroom/js/mode-switch.js' load='footer'}
                <li class="nav-item bd-mode-switch">
                    <button type="button" class="nav-link bd-mode-switch__btn" data-mode="{$bd_color.next}" title="{$bd_mode_label|escape:'html'}" aria-label="{$bd_mode_label|escape:'html'}">
                        <i class="{if $bd_color.icons == 'gallery'}gallery-icon-{else}fas fa-fw fa-{/if}{if $bd_color.mode == 'dark'}sun{else}moon{/if}" aria-hidden="true"></i><span class="d-lg-none ml-2">{$bd_mode_label|escape:'html'}</span>
                    </button>
                </li>
{/if}
