<?php
namespace HPrPrepaidCardPOS\Components;

use Doctrine\DBAL\Connection;
use Shopware\Components\Plugin\ConfigReader;

class POSOrdersService{
    /** @var Connection */
    private $dbal = null;
    private $config = [];
    /** @var POSCustomerService */
    private $service = null;

    public function __construct(ConfigReader $configReader = null, Connection $dbal = null, POSCustomerService $service){
        if($configReader){
            $this->config = $configReader->getByPluginName('HPrPrepaidCardPOS');
        }
        if($dbal){
            $this->dbal=$dbal;
        }
        if($service){
            $this->service = $service;
        }
    }

    /**
     * @param int $customerId
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getOpenOrderIds($customerId = 0){
        $ids = [];
        try{
            $sql = "SELECT id FROM s_order WHERE userID = :uid AND status = 0";
            $stmt = $this->dbal->prepare($sql);
            $stmt->execute(['uid' => $customerId]);

            foreach ($stmt->fetchAll() as $row){
                $ids[] = $row['id'];
            }
        }
        catch(\Exception $e){
            $this->service->logError($e);
        }
        return $ids;
    }

    /**
     * @return \Shopware\Components\Api\Resource\Order
     */
    public function getOrderResource(){
        return \Shopware\Components\Api\Manager::getResource('order');
    }

    public function loadOrderByResource($id){
        return $this->getOrderResource()->getOne($id);
    }

    /**
     * @param int $customerId
     * @return array
     */
    public function getOpenOrders($customerId = 0){
        $ids = $this->getOpenOrderIds($customerId);
        $orders = [];
        foreach ($ids as $id){
            $orders[] = $this->loadOrderByResource($id);
        }
        return $orders;
    }

    /**
     * @return array
     */
    public function getOpenOrdersByCurrentCustomer(){
        return $this->getOpenOrders($this->service->getCurrentCustomerId());
    }
}