<div class="pswp" tabindex="-1" role="dialog" aria-hidden="true">
     <div class="pswp__bg"></div>
     <div class="pswp__scroll-wrap">
           <div class="pswp__container">
             <div class="pswp__item"></div>
             <div class="pswp__item"></div>
             <div class="pswp__item"></div>
           </div>
           <div class="pswp__ui pswp__ui--hidden">
             <div class="pswp__top-bar">
                 <div class="pswp__counter"></div>
                 <button class="pswp__button pswp__button--close" title="{'Close (Esc)'|@translate}"></button>
{if $theme_config->social_enabled}
                 <button class="pswp__button pswp__button--share" title="{'Share'|@translate}"></button>
{/if}
                 <button class="pswp__button pswp__button--fs" title="{'Toggle fullscreen'|@translate}"></button>
                 <button class="pswp__button pswp__button--zoom" title="{'Zoom in/out'|@translate}"></button>
                 <button class="pswp__button pswp__button--details" title="{'Picture details'|@translate}"></button>
                 <button class="pswp__button pswp__button--autoplay" title="{'AutoPlay'|@translate}"></button>
                 <div class="pswp__preloader">
                     <div class="pswp__preloader__icn">
                       <div class="pswp__preloader__cut">
                         <div class="pswp__preloader__donut"></div>
                       </div>
                     </div>
                 </div>
             </div>
             <div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap">
                 <div class="pswp__share-tooltip"></div>
             </div>
             <button class="pswp__button pswp__button--arrow--left" title="{'Previous (arrow left)'|@translate}"></button>
             <button class="pswp__button pswp__button--arrow--right" title="{'Next (arrow right)'|@translate}"></button>
             <div class="pswp__caption">
                 <div class="pswp__caption__center"></div>
             </div>
         </div>
     </div>
{* EXIF panel (#90): sibling of the scroll-wrap, so PhotoSwipe's gestures never see its clicks. Driven by js/exif-panel.js. *}
     <div id="pswp-exif" class="bd-tab-panel pswp-exif d-none" data-ws="{$ROOT_URL}ws.php" data-empty="{'No EXIF data for this photo'|translate|escape}" data-error="{'EXIF data could not be loaded'|translate|escape}">
         <div class="handle">
             <button type="button" class="pswp-exif__toggle" aria-expanded="false" aria-controls="pswp-exif-content" aria-label="{'EXIF data (i)'|translate|escape}" title="{'EXIF data (i)'|translate|escape}"><span class="fas fa-info" aria-hidden="true"></span></button>
         </div>
         <div id="pswp-exif-content" class="info">
             <h4 class="pswp-exif__title"></h4>
             <div class="pswp-exif__summary"></div>
             <dl class="pswp-exif__table"></dl>
             <p class="pswp-exif__status"></p>
         </div>
     </div>
</div>
