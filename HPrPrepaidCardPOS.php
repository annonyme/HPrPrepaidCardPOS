<?php
namespace HPrPrepaidCardPOS;

use HPrPrepaidCardPOS\Components\POSCustomerService;
use Shopware\Bundle\AttributeBundle\Service\ConfigurationStruct;
use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;

class HPrPrepaidCardPOS extends Plugin{

    public function install(InstallContext $context)
    {
        parent::install($context);

        $this->updateAttributeFields();
    }

    public function update(UpdateContext $context)
    {
        parent::update($context);

        $this->updateAttributeFields();
    }

    public function uninstall(UninstallContext $context)
    {
        parent::uninstall($context);

        $this->deleteAttributeFields();
    }

    private function updateAttributeFields(){
        /** @var CrudService $service */
        $service = $this->container->get('shopware_attribute.crud_service');
        $list = $service->getList('s_user_attributes');

        $balance = false;
        $overdue = false;

        /** @var ConfigurationStruct $item */
        foreach ($list as $item){
            if($item->getColumnName() == 'hpr_balance'){
                $balance = true;
            }
            else if($item->getColumnName() == 'hpr_overdue'){
                $overdue = true;
            }
        }

        if(!$balance){
            try{
                $service->update('s_user_attributes', 'hpr_balance', 'float',
                    [
                        'label' => 'Prepaid Guthaben',
                        'displayInBackend' => true,
                    ]
                );
            }
            catch(\Exception $e){
                $this->getService()->logError($e);
            }
        }
        if(!$overdue){
            try{
                $service->update('s_user_attributes', 'hpr_overdue', 'boolean',
                    [
                        'label' => 'Guthaben überschreitbar',
                        'displayInBackend' => true,
                    ]
                );
            }
            catch(\Exception $e){
                $this->getService()->logError($e);
            }
        }
        Shopware()->Models()->generateAttributeModels(['s_user_attributes']);
    }

    private function deleteAttributeFields(){
        /** @var CrudService $service */
        $service = $this->container->get('shopware_attribute.crud_service');
        $list = $service->getList('s_user_attributes');

        $balance = false;
        $overdue = false;

        /** @var ConfigurationStruct $item */
        foreach ($list as $item){
            if($item->getColumnName() == 'hpr_balance'){
                $balance = true;
            }
            else if($item->getColumnName() == 'hpr_overdue'){
                $overdue = true;
            }
        }

        if($balance){
            try{
                $service->delete('s_user_attributes', 'hpr_balance');
            }
            catch(\Exception $e){
                $this->getService()->logError($e);
            }
        }
        if($overdue){
            try{
                $service->delete('s_user_attributes', 'hpr_overdue');
            }
            catch(\Exception $e){
                $this->getService()->logError($e);
            }
        }
        Shopware()->Models()->generateAttributeModels(['s_user_attributes']);
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'postCheckoutFinish',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend' => 'addTemplateDir',
            'Enlight_Controller_Action_PostDispatchSecure_Widgets' => 'addTemplateDir',
            'Enlight_Controller_Action_PreDispatch_Widgets_Listing' => 'addTemplateDir',
        ];
    }

    /**
     * @return array
     */
    private function getConfig(){
        return $this->container->get('shopware.plugin.config_reader')->getByPluginName($this->getName());
    }

    /**
     * @return POSCustomerService|null
     */
    public function getService()
    {
        $service = null;
        try{
            $service = $this->container->get('hpr_pos_customer_service');
        }
        catch(\Exception $e){
            //$service = new POSCustomerService();
        }
        return $service;
    }

    /**
     * @param \Enlight_Controller_ActionEventArgs $args
     */
    public function addTemplateDir(\Enlight_Controller_ActionEventArgs $args)
    {
        try{
            $args->getSubject()->View()->addTemplateDir($this->getPath() . '/Resources/views/');

            $config = $this->getConfig();

            $customerCurrentAmount = $this->getService()->getCurrentAmount();
            $customerFreeAmount = $this->getService()->getFreeAmount();
            $basketAmount = (float) $this->getService()->getBasketAmount();
            $noLimit = (boolean) $this->getService()->getNoLimit();
            if($customerCurrentAmount !== null){
                $args->getSubject()->View()->assign('hpr_pos_customer_loggedIn', $this->getService()->getCustomerId() !== null);
                $args->getSubject()->View()->assign('hpr_pos_customer_data', $this->getService()->getCustomerData());

                $args->getSubject()->View()->assign('hpr_pos_customer_amount', $customerCurrentAmount);
                $args->getSubject()->View()->assign('hpr_pos_customer_free_amount', $customerFreeAmount);
                $args->getSubject()->View()->assign('hpr_pos_basket_amount', $basketAmount);

                $args->getSubject()->View()->assign('hpr_pos_customer_nolimit', $noLimit);
                $args->getSubject()->View()->assign('hpr_pos_can_buy', $noLimit || ($customerFreeAmount >= $basketAmount));
            }
        }
        catch(\Exception $e){
            $this->getService()->logError($e);
        }
    }

    /**
     * @param \Enlight_Controller_ActionEventArgs $args
     * @throws \Exception
     */
    public function postCheckoutFinish(\Enlight_Controller_ActionEventArgs $args)
    {
        $request = $args->getSubject()->Request();
        if($request->getActionName() == 'finish'){
            $orderNumber = $args->getSubject()->View()->getAssign('sOrderNumber');
            try{
                $this->getService()->calculateNewCustomerAmount($orderNumber);
                $this->getService()->logOut();

                //TODO forward/redirect
                $args->getSubject()->redirect(['controller' => 'index', 'action' => 'index']);
            }
            catch(\Exception $e){
                $this->getService()->logError($e);
            }
        }
    }
}