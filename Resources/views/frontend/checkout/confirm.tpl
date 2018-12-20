{extends file="parent:frontend/checkout/confirm.tpl"}

{* IgnoreAGB should be configurated to true !!!! *}

{block name='frontend_checkout_confirm_tos_panel'}{/block}

{block name='frontend_checkout_confirm_information_addresses'}{/block}

{block name='frontend_checkout_confirm_information_payment'}{/block}

{block name="frontend_checkout_confirm_additional_features"}{/block}

{block name='frontend_checkout_confirm_submit'}
    {* Submit order button *}
    {if !$hpr_pos_can_buy}
        <span>cant-order-snippet</span>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}