<?php
class Shopware_Controllers_Frontend_PosLogin extends Enlight_Controller_Action {

    /**
     * @return array
     */
    public function getConfig(){
        $config = [];
        try{
            /** @var \Shopware\Components\Plugin\ConfigReader $configReader */
            $configReader = $this->container->get('shopware.plugin.config_reader');
            $config = $configReader->getByPluginName('HPrPrepaidCardPOS');
        }
        catch(Exception $e){
            $this->getService()->logError($e);
        }
        return $config;
    }

    /**
     * @return \HPrPrepaidCardPOS\Components\POSCustomerService|null
     */
    public function getService(){
        $service = null;
        try{
            $service = $this->container->get('hpr_pos_customer_service');
        }
        catch(Exception $e){
            //TODO
        }
        return $service;
    }

    private function simpleRedirect($action = null, $controller = null){
        try{
            $params = [];
            if($action){
                $params['action'] = $action;
            }
            if($controller){
                $params['controller'] = $action;
            }

            $this->redirect($params);
        }
        catch(Exception $e){
            $this->getService()->logError($e);
        }
    }

    public function logoutAction(){
        try{
            if($this->getService()->logOut()){
                $config = $this->getConfig();
                $controller = isset($config['logoutForwardController']) && strlen(trim($config['logoutForwardController'])) > 0 ? $config['logoutForwardController'] : 'index';
                $action = isset($config['logoutForwardAction']) && strlen(trim($config['logoutForwardAction'])) > 0 ? $config['logoutForwardAction'] : 'index';

                $this->simpleRedirect($action, $controller);
            }
        }
        catch(Exception $e){
            $this->getService()->logError($e);
        }
    }

    public function loginAction(){
        $params = $this->Request()->getParams();
        $customerLogin = false;
        try{
            if(isset($params['customernumber']) && strlen(trim($params['customernumber'])) > 0){
                $customerLogin = $this->getService()->logIn(trim($params['customernumber']));
                if($customerLogin){
                    $config = $this->getConfig();
                    $controller = isset($config['loginForwardController']) && strlen(trim($config['loginForwardController'])) > 0 ? $config['loginForwardController'] : 'index';
                    $action = isset($config['loginForwardAction']) && strlen(trim($config['loginForwardAction'])) > 0 ? $config['loginForwardAction'] : 'index';

                    $this->simpleRedirect($action, $controller);
                }
            }
        }
        catch(Exception $e){
            $this->getService()->logError($e);
        }

        if(!$customerLogin){
            $config = $this->getConfig();
            $controller = isset($config['loginFailedForwardController']) && strlen(trim($config['loginFailedForwardController'])) > 0 ? $config['loginFailedForwardController'] : 'index';
            $action = isset($config['loginFailedForwardAction']) && strlen(trim($config['loginFailedForwardAction'])) > 0 ? $config['loginFailedForwardAction'] : 'index';

            $this->simpleRedirect($action, $controller);
        }
    }

    public function indexAction(){
        $this->loginAction();
    }

    public function editAction(){
        try{
            if($this->getService()->getCurrentCustomerId() == 0){
                $config = $this->getConfig();
                $controller = isset($config['loginFailedForwardController']) && strlen(trim($config['loginFailedForwardController'])) > 0 ? $config['loginFailedForwardController'] : 'index';
                $action = isset($config['loginFailedForwardAction']) && strlen(trim($config['loginFailedForwardAction'])) > 0 ? $config['loginFailedForwardAction'] : 'index';

                $this->simpleRedirect($action, $controller);
            }
            else{
                $this->View()->loadTemplate('frontend/poslogin/edit.tpl');
            }
        }
        catch(Exception $e){
            $this->getService()->logError($e);
        }
    }

    public function clearAction(){
        $loggedOut = false;
        try{
            if($this->getService()->getCurrentCustomerId() > 0){
                $this->getService()->clearAmount();
                $this->getService()->clearCustomersOrders($this->getService()->getCurrentCustomerId());
                $params = $this->Request()->getParams();
                if(isset($params['andlogout'])){
                    $loggedOut = true;
                    $this->logoutAction();
                }
            }
        }
        catch(Exception $e){
            $this->getService()->logError($e);
        }

        if(!$loggedOut){
            $this->simpleRedirect('edit');
        }
    }

    public function changeAction(){
        try{
            $params = $this->Request()->getParams();
            if($this->getService()->getCurrentCustomerId() > 0 && isset($params['value'])){
                $this->getService()->changeAmount((float) $params['value']);
            }
        }
        catch(Exception $e){
            $this->getService()->logError($e);
        }
        $this->simpleRedirect('edit');
    }

    public function addAction(){
        try{
            $params = $this->Request()->getParams();
            if($this->getService()->getCurrentCustomerId() > 0 && isset($params['value'])){
                $this->getService()->changeAmount((float) $params['value'], true);
            }
        }
        catch(Exception $e){
            $this->getService()->logError($e);
        }
        $this->simpleRedirect('edit');
    }
}