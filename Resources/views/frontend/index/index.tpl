{extends file="parent:frontend/index/index.tpl"}

{block name="frontend_index_header_javascript_jquery_lib" append}
    <script src="{link file='frontend/_public/src/js/jquery.customerloginautofocus.js'}"></script>
{/block}

{block name='frontend_index_top_bar_container'}
{/block}

{block name='frontend_index_logo_container'}
{/block}

{block name='frontend_index_shop_navigation'}
    {include file="frontend/index/shop-navigation.tpl"}
{/block}