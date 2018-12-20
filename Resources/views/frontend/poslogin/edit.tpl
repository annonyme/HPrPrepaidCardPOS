{extends file="frontend/index/index.tpl"}

{block name="frontend_index_content"}
    <style type="text/css">
        .sidebar--categories-navigation{
            display:none;
        }
    </style>
    <br/>
    <div class="panel has--border">
        <div class="panel--header primary">{s name="logout" namespace="frontend/poslogin/hpr_poslogin"}{/s}</div>
        <div class="panel--body is--wide">
            <form method="get" action="/posLogin/logout">
                <input class="btn" type="submit" value="{s name="do_logout_customer" namespace="frontend/poslogin/hpr_poslogin"}{/s}"/>
            </form>
        </div>
    </div>
    <br/>
    <div class="panel has--border">
        <div class="panel--header primary">{s name="reset_balance" namespace="frontend/poslogin/hpr_poslogin"}{/s}</div>
        <div class="panel--body is--wide">
            <form method="get" action="/posLogin/clear">
                <strong>
                {if $hpr_pos_customer_amount < 0}
                    {$hpr_pos_customer_amount} EUR {s name="has_to_be_payed" namespace="frontend/poslogin/hpr_poslogin"}{/s}
                {else}
                    {$hpr_pos_customer_amount} EUR {s name="was_not_used" namespace="frontend/poslogin/hpr_poslogin"}{/s}
                {/if}
                </strong>
                <hr/>
                <input type="hidden" name="andlogout" value="true"/>
                <a class="btn" target="_blank" href="/posOrders/index">{s name="print_receipt" namespace="frontend/poslogin/hpr_poslogin"}{/s}</a>
                <input class="btn" type="submit" value="{s name="reset_and_logout" namespace="frontend/poslogin/hpr_poslogin"}{/s}"/>
            </form>
        </div>
    </div>
    <br/>
    <div class="panel has--border">
        <div class="panel--header primary">{s name="add_to_balance" namespace="frontend/poslogin/hpr_poslogin"}{/s}</div>
        <div class="panel--body is--wide">
            <form method="get" action="/posLogin/add">
                <input type="hidden" value="5" name="value"/>
                <input class="btn" type="submit" value="{s name="add_5_eur" namespace="frontend/poslogin/hpr_poslogin"}{/s}"/>
            </form><br/>
            <form method="get" action="/posLogin/add">
                <input type="hidden" value="10" name="value"/>
                <input class="btn" type="submit" value="{s name="add_10_eur" namespace="frontend/poslogin/hpr_poslogin"}{/s}"/>
            </form><br/>
            <form method="get" action="/posLogin/add">
                <input type="hidden" value="20" name="value"/>
                <input class="btn" type="submit" value="{s name="add_20_eur" namespace="frontend/poslogin/hpr_poslogin"}{/s}"/>
            </form><br/>
            <form method="get" action="/posLogin/add">
                <input type="hidden" value="25" name="value"/>
                <input class="btn" type="submit" value="{s name="add_25_eur" namespace="frontend/poslogin/hpr_poslogin"}{/s}"/>
            </form><br/>
            <form method="get" action="/posLogin/add">
                <input type="hidden" value="50" name="value"/>
                <input class="btn" type="submit" value="{s name="add_50_eur" namespace="frontend/poslogin/hpr_poslogin"}{/s}"/>
            </form><br/>
            <form method="get" action="/posLogin/add">
                <input type="hidden" value="100" name="value"/>
                <input class="btn" type="submit" value="{s name="add_100_eur" namespace="frontend/poslogin/hpr_poslogin"}{/s}"/>
            </form>
        </div>
    </div>
    <br/>
    <div class="panel has--border">
        <div class="panel--header primary">{s name="set_balance" namespace="frontend/poslogin/hpr_poslogin"}{/s}</div>
        <div class="panel--body is--wide">
            <form method="get" action="/posLogin/add">
                <input type="number" value="{$hpr_pos_customer_amount}" step="0.01" name="value"/>
                <input class="btn" type="submit" value="{s name="do_set_balance" namespace="frontend/poslogin/hpr_poslogin"}{/s}"/>
            </form>
        </div>
    </div>
{/block}