<?php
namespace HPrPrepaidCardPOS\Components;

use Doctrine\DBAL\Connection;
use Shopware\Bundle\AccountBundle\Service\RegisterServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;
use Shopware\Components\Logger;
use Shopware\Components\Plugin\ConfigReader;
use Shopware\Models\Country\Country;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Customer\Group;
use Shopware\Models\Order\Order;

class POSCustomerService{
    /** @var Connection */
    private $dbal = null;
    private $config = [];

    /**
     * APIPriceWarningService constructor.
     * @param ConfigReader|null $configReader
     * @param Connection|null $dbal
     */
    public function __construct(ConfigReader $configReader = null, Connection $dbal = null){
        if($configReader){
            $this->config = $configReader->getByPluginName('HPrPrepaidCardPOS');
        }
        if($dbal){
            $this->dbal=$dbal;
        }
    }

    /**
     * @param $entity
     * @return mixed
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function persistAndFlush($entity){
        Shopware()->Models()->persist($entity);
        Shopware()->Models()->flush($entity);
        return $entity;
    }


    public function logError(\Exception $e){
        try{
            /** @var Logger $logger */
            $logger = Shopware()->Container()->get('pluginlogger');
            if($logger){
                $logger->warn($e->getMessage() . ': ' . $e->getTraceAsString());
            }
        }
        catch(\Exception $e){
            //nothing
        }
    }

    private function setLastLogin($uid){
        try{
            $sql = "UPDATE s_user SET lastlogin = now() WHERE id = :uid";
            $stmt = $this->dbal->prepare($sql);
            $stmt->execute(['uid' => $uid]);
        }
        catch(\Exception $e){
            $this->logError($e);
        }
    }

    private function createNewUser($customerNumber = ''){
        try{
            if(strlen(trim($customerNumber)) > 0){
                $customer = new Customer();
                $customer->setActive(true);
                $customer->setAccountMode(Customer::ACCOUNT_MODE_CUSTOMER);
                $customer->setEmail($customerNumber.'@pos.de');
                $customer->setGroup(Shopware()->Models()->find(Group::class, 1));
                $customer->setNumber($customerNumber);
                $customer->setPassword('9c07ce21f71b85a44265bdf1eaed0164');
                $customer->setEncoderName('md5');
                $customer->setFirstname($customerNumber);
                $customer->setLastname($customerNumber);
                $customer->setSalutation('mr');
                $customer->setAttribute(new \Shopware\Models\Attribute\Customer());

                /** @var ShopContextInterface $context */
                $context = Shopware()->Container()->get('shopware_storefront.context_service')->getShopContext();
                /** @var RegisterServiceInterface $registerService */
                $registerService = Shopware()->Container()->get('shopware_account.register_service');

                //Shipping and Delivery Adress
                $shipping = new Address();
                $shipping->setSalutation('mr');
                $shipping->setFirstname('dummy');
                $shipping->setLastname('dummy');
                $shipping->setStreet('dummy');
                $shipping->setZipcode('00000');
                $shipping->setCity('dummy');
                $shipping->setCountry(Shopware()->Models()->find(Country::class, 2));
                $shipping->setCustomer($customer);
                $billing = $shipping;

                //save
                $registerService->register(
                    $context->getShop(),
                    $customer,
                    $billing,
                    $shipping
                );

                //init attr-fields
                if($customer->getId() > 0 && $customer->getAttribute()){
                    $customer->getAttribute()->setHprBalance(0);
                    $customer->getAttribute()->setHprOverdue(isset($this->config['create_on_login_overdue']) && $this->config['create_on_login_overdue'] == 'yes');

                    $this->persistAndFlush($customer);
                }
                else{
                    throw new \Exception('setting attributes for customer ' . $customerNumber . ' failed (id: ' . $customer->getId() . ')');
                }
            }
        }
        catch(\Exception $e){
            $this->logError($e);
        }
    }

    /**
     * @param string $number
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function logIn(string $number){
        $stmt = $this->dbal->prepare("SELECT * from s_user WHERE customernumber = :num");
        $stmt->execute(['num' => $number]);
        $rows = $stmt->fetchAll();

        if(count($rows) == 0 && isset($this->config['create_on_login']) && $this->config['create_on_login'] == 'yes'){
            $this->createNewUser($number);

            $stmt->execute(['num' => $number]);
            $rows = $stmt->fetchAll();
        }

        if(count($rows) == 1 && isset($rows[0]['id']) && intval($rows[0]['id']) > 0){
            $user = $rows[0];

            $stmt = $this->dbal->prepare("SELECT * FROM s_core_customergroups WHERE groupkey = :key");
            $stmt->execute(['key' => $user['customergroup']]);

            $rows = $stmt->fetchAll();
            if(count($rows) == 1 && isset($rows[0]['id']) && intval($rows[0]['id']) > 0){
                $group = $rows[0];

                Shopware()->Session()->offsetSet('sUserMail', $user['email']);
                Shopware()->Session()->offsetSet('sUserPassword', $user['password']);
                Shopware()->Session()->offsetSet('sUserId', (int) $user['id']);

                Shopware()->Session()->offsetSet('sUserGroup', $user['customergroup']);
                Shopware()->Session()->offsetSet('sUserGroupData', $group);

                //old
                Shopware()->System()->sUSERGROUPDATA = $group;
                Shopware()->System()->sUSERGROUP = $user['customergroup'];

                $this->setLastLogin((int) $user['id']);

                return true;
            }
        }

        return false;
    }

    private function regenerateSessionId(){
        try{
            $oldSessionId = session_id();

            session_regenerate_id(true);
            $newSessionId = session_id();
            session_write_close();
            session_start();

            Shopware()->Session()->offsetSet('sessionId', $newSessionId);
            Shopware()->Container()->reset('SessionId');
            Shopware()->Container()->set('SessionId', $newSessionId);

            $stmt = $sessions = [
                's_order_basket' => 'sessionID',
                's_user' => 'sessionID',
                's_order_comparisons' => 'sessionID',
            ];

            foreach ($sessions as $tableName => $column) {
                $sql = "UPDATE " . $tableName . " SET " . $column . " = :new WHERE " . $column . " = :old";
                $stmt = $this->dbal->prepare($sql);
                $stmt->execute(['new' => $newSessionId, 'old' => $oldSessionId]);
            }
        }
        catch(\Exception $e){
            $this->logError($e);
        }
    }

    public function logOut(){
        if(Shopware()->Session()->offsetGet('sUserId') > 0){
            Shopware()->Session()->unsetAll();
            //clear Basket
            Shopware()->Modules()->Basket()->clearBasket();
            //regen SessionId
            $this->regenerateSessionId();
            return true;
        }
        return false;
    }

    public function getCurrentCustomerId(): int{
        $id = Shopware()->Session()->sUserId;
        if($id === null){
            $id = 0;
        }
        return $id;
    }

    /**
     * @return float
     */
    public function getCurrentAmount(){
        $result = null;
        try{
            //TODO load via doctrine
            $stmt = $this->dbal->prepare("SELECT hpr_balance FROM s_user_attributes WHERE userID = :uid");
            $stmt->execute(['uid' => $this->getCurrentCustomerId()]);

            $rows = $stmt->fetchAll();
            if(count($rows) == 1){
                $result = floatval($rows[0]['hpr_balance']);
            }
        }
        catch(\Exception $e){
            $this->logError($e);
        }

        if($result === null){
            $this->clearAmount();
            $result = 0.00;
        }
        return $result;
    }

    public function getBasketAmount(){
        $result = 0;
        try{
            $value = Shopware()->Modules()->Basket()->sGetAmount();
            if($value && count($value) > 0){
                $result = $value['totalAmount'];
            }
        }
        catch(\Exception $e){
            $this->logError($e);
        }
        return $result;
    }

    public function getFreeAmount(){
        return ($this->getCurrentAmount() ? : 0) - $this->getBasketAmount();
    }

    /**
     * @return \Shopware\Components\Api\Resource\Order
     */
    private function getOrderResource(): \Shopware\Components\Api\Resource\Order{
        return \Shopware\Components\Api\Manager::getResource('order');
    }

    private function getOrderIdByNumber($number){
        $sql = "SELECT id FROM s_order WHERE ordernumber = :number";
        $stmt = $this->dbal->prepare($sql);
        $stmt->execute(['number' => $number]);
        $rows = $stmt->fetchAll();

        return count($rows) > 0 ? $rows[0]['id'] : null;
    }

    /**
     * @param $id
     * @return array
     * @throws \Shopware\Components\Api\Exception\NotFoundException
     * @throws \Shopware\Components\Api\Exception\ParameterMissingException
     */
    private function loadOrderByResource($id): array{
        return $this->getOrderResource()->getOne($id);
    }

    public function calculateNewCustomerAmount($orderNumber){
        try{
            $amount = $this->getCurrentAmount();
            if($amount !== null){ //TODO remove later
                $order = $this->loadOrderByResource($this->getOrderIdByNumber($orderNumber));
                if($order){
                    $orderAmount = (float) $order['invoiceAmount'];

                    if($orderAmount){
                        $sql = "UPDATE s_user_attributes SET hpr_balance = :amount WHERE userID = :uid";
                        $stmt = $this->dbal->prepare($sql);
                        $stmt->execute(['amount' => ($amount - $orderAmount), 'uid' => $this->getCurrentCustomerId()]);
                    }
                }
            }
        }
        catch(\Exception $e){
            $this->logError($e);
        }
    }

    public function clearAmount(){
        $this->changeAmount();
    }

    public function changeAmount($amount = 0.00, $add = false){
        try{
            if($add){
                $amount = $this->getCurrentAmount() + $amount;
            }

            $sql = "UPDATE s_user_attributes SET hpr_balance = :amount WHERE userID = :uid";
            $stmt = $this->dbal->prepare($sql);
            $stmt->execute(['amount' => (float) $amount, 'uid' => $this->getCurrentCustomerId()]);
        }
        catch(\Exception $e){
            $this->logError($e);
        }
    }

    /**
     * @return int|null
     */
    public function getCustomerId(){
        return Shopware()->Session()->sUserId;
    }

    /**
     * @return \Shopware\Components\Api\Resource\Customer
     */
    public function getCustomerResource()
    {
        return \Shopware\Components\Api\Manager::getResource('Customer');
    }

    /**
     * @return array|\Shopware\Models\Customer\Customer
     * @throws \Shopware\Components\Api\Exception\NotFoundException
     * @throws \Shopware\Components\Api\Exception\ParameterMissingException
     */
    public function getCustomerData(){
        return $this->getCustomerResource()->getOne($this->getCustomerId());
    }

    private function createAttributes(Customer $customer){
        try{
            $customer->setAttribute(new \Shopware\Models\Attribute\Customer());
            $this->persistAndFlush($customer);
        }
        catch(\Exception $e){
            $this->logError($e);
        }
    }

    /**
     * @return mixed
     */
    public function getNoLimit(){
        $noLimit = false;
        try{
            if($this->getCustomerId()){
                /** @var Customer $customer */
                $customer = Shopware()->Models()->find(Customer::class, $this->getCustomerId());
                if($customer->getAttribute() === null){
                    $this->createAttributes($customer);
                }
                $noLimit = $customer->getAttribute()->getHprOverdue();
            }
        }
        catch (\Exception $e){
            $this->logError($e);
        }
        return $noLimit;
    }

    /**
     * @param int $customerId
     */
    public function clearCustomersOrders($customerId = 0){
        try{
            $sql = "UPDATE s_order SET status = :sid   WHERE userID = :cid AND status = 0";
            $sid = isset($this->config['status_id']) && strlen(trim($this->config['status_id'])) > 0 ? (int) $this->config['status_id'] : 2;
            $stmt = $this->dbal->prepare($sql);
            $stmt->execute(['sid' => $sid, 'cid' => $customerId]);
        }
        catch(\Exception $e){
            $this->logError($e);
        }
    }
}