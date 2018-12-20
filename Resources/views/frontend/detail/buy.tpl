{extends file="parent:frontend/detail/buy.tpl"}

{* "Buy now" button *}
{block name="frontend_detail_buy_button"}
    {if $hpr_pos_customer_free_amount >= $sArticle.price || $hpr_pos_customer_nolimit}
        {$smarty.block.parent}
    {else}
        <span>not-enough-money-snippet</span>
    {/if}
{/block}