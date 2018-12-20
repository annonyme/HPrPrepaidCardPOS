{extends file="parent:frontend/index/shop-navigation.tpl"}

{block name='frontend_index_search'}
    <li class="navigation--entry" role="menuitem">
        {if $hpr_pos_customer_loggedIn}
            <form method="get" action="/posLogin/edit">
                <span style="font-size:130%;margin-right:50px;">
                    <!-- TODO own customer_data from pos_plugin -->
                    <strong style="text-decoration:underline;">{$hpr_pos_customer_data.number}</strong>:
                    {if $hpr_pos_customer_free_amount !== null}
                        {if $hpr_pos_customer_free_amount == $hpr_pos_customer_amount}
                            {$hpr_pos_customer_amount} EUR
                        {else}
                            {$hpr_pos_customer_free_amount} EUR von {$hpr_pos_customer_amount} EUR
                        {/if}
                    {else}
                        {s name="has_no_balance" namespace="frontend/poslogin/hpr_poslogin"}{/s}
                    {/if}
                </span>
                <input class="btn" type="submit" value="{s name="card_maintance" namespace="frontend/poslogin/hpr_poslogin"}{/s}"/>
            </form>
        {else}
            <form method="post" action="/posLogin/login">
                <input name="customernumber" id="posloginnumber" type="text" placeholder="{s name="hpr_input_customernumber" namespace="frontend/poslogin/hpr_poslogin"}{/s}"/>
                <input class="btn" type="submit" value="OK"/>
            </form>
        {/if}
    </li>
{/block}