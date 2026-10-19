{* bd-choice-grid.tpl: single-choice visual grid, written as a shared partial
   waiting for promotion (#17). Nothing here knows what the choices are.

   Parameters (passed to {include}):
     name   : name of the radio group (the posted field)
     groups : [{title, items: [{value, label, img}]}], titles and labels already translated
     value  : value to check
     label  : accessible name of the whole group
   Styles: admin/assets/css/_bd-choice-grid.scss. *}
<div class="bd-choice-grid" role="radiogroup" aria-label="{$label|escape:'html'}">
  {foreach from=$groups item=group}
  <fieldset class="bd-choice-grid__group">
    <legend class="bd-choice-grid__title">{$group.title|escape:'html'}</legend>
    <div class="bd-choice-grid__items">
      {foreach from=$group.items item=item}
      <label class="bd-choice-card">
        <input type="radio" class="bd-choice-card__input" name="{$name|escape:'html'}" value="{$item.value|escape:'html'}"{if $item.value == $value} checked{/if}>
        <span class="bd-choice-card__body">
          {if $item.img}<img class="bd-choice-card__img" src="{$item.img|escape:'html'}" alt="" loading="lazy">{else}<span class="bd-choice-card__img" aria-hidden="true"></span>{/if}
          <span class="bd-choice-card__label">{$item.label|escape:'html'}</span>
        </span>
      </label>
      {/foreach}
    </div>
  </fieldset>
  {/foreach}
</div>
