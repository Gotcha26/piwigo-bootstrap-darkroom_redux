{* bd-landing.tpl: root of the admin panel (shared ADMIN-SHELL pattern).
   Holds only the root wrapper, the call to the shared shell and the JS config.
   The shell renders header, tabs, body, help & support, savebar and credits.
   No <link> here: the stylesheets go to head_elements from PHP. *}
<div class="jm-plugin-admin" id="bd-admin"{if $BD_THEME eq 'dark'} data-theme="dark"{/if}>
  {if isset($JM_ADMIN_SHELL_PARTIAL) && $JM_ADMIN_SHELL_PARTIAL}
    {include file=$JM_ADMIN_SHELL_PARTIAL}
  {else}
    <p class="bd-shell-missing">{'bd_shell_missing'|@translate}</p>
  {/if}
</div>

<script>
window.BDAdmin = {
  pwgToken: '{$PWG_TOKEN}',
  saveUrl:  '{$BD_SAVE_URL|escape:'javascript'}',
  {* Already JSON from json_encode() (tags, ampersands and quotes escaped). *}
  strings:  {$BD_STRINGS_JSON}
};
</script>
<script src="{$BD_ADMIN_JS}"></script>
