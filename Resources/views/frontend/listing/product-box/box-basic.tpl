{extends file="parent:frontend/listing/product-box/box-basic.tpl"}

{block name="frontend_listing_box_article_buy"}
    {if {config name="displayListingBuyButton"}}
        <div class="product--btn-container">
            {if $sArticle.allowBuyInListing && ($hpr_pos_customer_free_amount >= $sArticle.price || $hpr_pos_customer_nolimit)}
                {include file="frontend/listing/product-box/button-buy.tpl"}
            {else}
                {include file="frontend/listing/product-box/button-detail.tpl"}
            {/if}
        </div>
    {/if}
{/block}