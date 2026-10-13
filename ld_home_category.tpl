<div class="ld-home-categories">
    <div class="container">
        {if $ld_homecat_eyebrow || $ld_homecat_title}
        <h2>{if $ld_homecat_eyebrow}{$ld_homecat_eyebrow|escape:'html':'UTF-8'} {/if}{if $ld_homecat_title}<span class="big">{$ld_homecat_title|escape:'html':'UTF-8'}</span>{/if}</h2>
        {/if}
        {if $ld_homecat_intro}
        <p class="desc">{$ld_homecat_intro|escape:'html':'UTF-8'}</p>
        {/if}
        <div class="row">
            {foreach from=$ld_categories item=cat}
            <div class="col-12 col-sm-6 col-lg-3">
                {if $cat.link}
                    <a href="{$cat.link|escape:'html':'UTF-8'}" class="bloc" style="background-image: url({$cat.image|escape:'html':'UTF-8'});">
                        <h4>{$cat.label|escape:'html':'UTF-8'}</h4>
                        {if $cat.description}<p class="desc">{$cat.description|escape:'html':'UTF-8'}</p>{/if}
                        <div class="lien">{$ld_homecat_btn_text|escape:'html':'UTF-8'} <span class="liens-arrow">❯</span></div>
                    </a>
                {else}
                    <div class="bloc" style="background-image: url({$cat.image|escape:'html':'UTF-8'});">
                        <h4>{$cat.label|escape:'html':'UTF-8'}</h4>
                        {if $cat.description}<p class="desc">{$cat.description|escape:'html':'UTF-8'}</p>{/if}
                    </div>
                {/if}
            </div>
            {/foreach}
        </div>
    </div>
</div>
