<html>
    <head>
        <style type="text/css">
            @media print {
                @page{
                    padding: 0;
                    margin: 0;
                }

                #content{
                   position: relative;
                    width: {$hpr_pos_print_mm}mm;
                    margin: 5mm;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                }

                td.description {
                    border-bottom: 0.1mm solid #000000;
                    text-align: left;
                    vertical-align: top;
                    padding: 2mm;
                }

                td.price {
                    border-bottom: 0.1mm solid #000000;
                    text-align: right;
                    vertical-align: top;
                    padding: 2mm;
                }

                .full {
                    font-weight: bold;
                    border-bottom: 0.2mm double #000000;
                }

                .button-container{
                    display: none;
                }
            }
        </style>
    </head>
    <body>
        <div id="content">
            <div>
                {$hpr_pos_print_header}
            </div>
            <div>
                <strong>{$hpr_pos_date}</strong>
            </div>
            <hr>
            <table>
                {foreach $hprpos_orders as $order}
                    {foreach $order.details as $detail}
                        <tr>
                            <td class="description">
                                {$detail.articleName}
                                {if $detail.quantity > 1}
                                    {$detail.quantity}x
                                {/if}
                            </td>
                            <td class="price">{$detail.price}&nbsp;EUR</td>
                        </tr>
                    {/foreach}
                    <tr>
                        <td></td>
                        <td class="price full"><strong>{$hpr_pos_fullpay}&nbsp;EUR</strong></td>
                    </tr>
                {/foreach}
            </table>
            <hr>
            <div>
                {$hpr_pos_print_footer}
            </div>
            <div id="button-container">
                <button onclick="window.close()">{s name="close_button_label" namespace="frontend/poslogin/hpr_poslogin"}{/s}</button>
            </div>
        </div>
        <script type="text/javascript">
            try{
                window.print();
            }
            catch(e){
                console.log(e);
            }
        </script>
    </body>
</html>