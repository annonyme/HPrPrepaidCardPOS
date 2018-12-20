<?php
class Shopware_Controllers_Frontend_PosOrders extends Enlight_Controller_Action {
    private $template = 'frontend/posorders/index.tpl';

    /**
     * @return \HPrPrepaidCardPOS\Components\POSOrdersService
     */
    private function getService(){
        return $this->container->get('hpr_pos_order_service');
    }

    private function getConfig(){
        /** @var \Shopware\Components\Plugin\ConfigReader $configReader */
        $configReader = $this->container->get('shopware.plugin.config_reader');
        return $configReader->getByPluginName('HPrPrepaidCardPOS');
    }

    public function indexAction(){
        $template = $this->template;
        $config = $this->getConfig();
        if(isset($config['template_receipt']) && strlen(trim($config['template_receipt'])) > 0){
            $template = trim($config['template_receipt']);
        }

        $this->View()->loadTemplate($template);
        $orders = $this->getService()->getOpenOrdersByCurrentCustomer();
        $this->View()->assign('hprpos_orders', $orders);
        $fullPay = 0.00;
        foreach ($orders as $order){
            if(isset($order['invoiceAmount'])){
                $fullPay += (float) $order['invoiceAmount'];
            }
        }
        $this->View()->assign('hpr_pos_fullpay', $fullPay);
        $this->View()->assign('hpr_pos_date', date('d.m.Y H:i:s'));

        $this->View()->assign('hpr_pos_print_mm', isset($config['print_mm']) && intval($config['print_mm']) > 0 ? (int) $config['print_mm']: 58);
        $this->View()->assign('hpr_pos_print_header', isset($config['print_header']) ? trim($config['print_header']) : '');
        $this->View()->assign('hpr_pos_print_footer', isset($config['print_footer']) ? trim($config['print_footer']) : '');
    }
}