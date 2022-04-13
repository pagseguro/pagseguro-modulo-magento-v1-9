<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to PagSeguro so we can send you a copy immediately.
 *
 * @category   PagSeguro
 * @package    PagSeguro_Payment
 * @author     PagSeguro
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class PagSeguro_Payment_Model_Charge_Abstract
{
    protected $pagseguro_service;
    protected $helper;
    protected $type;
    protected $order;

    /**
     * @param Mage_Sales_Model_Order $order
     */
    protected function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    protected function getOrder()
    {
        return $this->order;
    }

    /**
     * @param $amount
     * @return stdClass
     */
    protected function getChargeAmount($amount)
    {
        $chargeAmount = new stdClass();
        $chargeAmount->value = (int)($amount * 100);
        $chargeAmount->currency = "BRL";
        return $chargeAmount;
    }

    /**
     * @param $amount
     * @return stdClass
     */
    public function getAmountData($amount)
    {
        $refundAmount = new stdClass();

        $value = new stdClass();
        $value->value = (int)($amount * 100);

        $refundAmount->amount = $value;
        return $refundAmount;
    }

    /**
     * PagSeguro Order Service
     * @return PagSeguro_Payment_Model_Service_Order|false
     */
    public function getService()
    {
        if (!$this->pagseguro_service) {
            /** @var PagSeguro_Payment_Model_Service_Order pagseguropayment */
            $this->pagseguro_service = Mage::getModel('pagseguropayment/service_order');
        }

        return $this->pagseguro_service;
    }

    /**
     * @return PagSeguro_Payment_Helper_Data|Mage_Core_Helper_Abstract
     */
    public function getHelper()
    {
        if (!$this->helper) {
            /** @var PagSeguro_Payment_Helper_Data helper */
            $this->helper = Mage::helper('pagseguropayment');
        }

        return $this->helper;
    }
}
