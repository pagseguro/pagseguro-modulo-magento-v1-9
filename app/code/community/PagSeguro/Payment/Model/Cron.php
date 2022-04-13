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
class PagSeguro_Payment_Model_Cron
{
    CONST CRON_FILE = 'pagseguropayment-cron.log';

    protected $helperOrder;

    public function consultOrderStatus()
    {
        $this->getOrderHelper()->log('STARTING CRON', self::CRON_FILE);
        $ninStatuses = array(
            'complete',
            'canceled',
            'closed',
            'holded'
        );

        $date = new DateTime('-10 DAYS'); // first argument uses strtotime parsing
        $fromDate = $date->format('Y-m-d');

        /** @var Mage_Sales_Model_Resource_Order_Collection $collection */
        $collection = Mage::getModel('sales/order')->getCollection()
            ->join(
                array('payment' => 'sales/order_payment'),
                'main_table.entity_id=payment.parent_id',
                array('payment_method' => 'payment.method')
            )
            ->addFieldToFilter('payment.method', array('like' => 'pagseguropayment_%'))
            ->addFieldToFilter('state', array('nin' => array($ninStatuses)))
            ->addFieldToFilter('created_at', array('gt' => $fromDate));

        /** @var PagSeguro_Payment_Model_Service_Order $orderService */
        $orderService = Mage::getModel('pagseguropayment/service_order');
        /** @var Mage_Sales_Model_Order $order */
        foreach ($collection as $order) {
            if ($order->getId()) {
                $payment = $order->getPayment();
                $chargeId = $payment->getTransactionId();
                if ($chargeId) {
                    $this->getOrderHelper()->log('getOrder ' . $order->getIncrementId(), self::CRON_FILE);
                    $response = $orderService->consultCharge($chargeId);
                    if ($response && $response->getBody()) {
                        $this->getOrderHelper()->updatePayment($order, json_decode($response->getBody()));
                    }
                }
            }
        }

        $this->getOrderHelper()->log('ENDING CRON', self::CRON_FILE);
    }

    /**
     * @return PagSeguro_Payment_Helper_Order|Mage_Core_Helper_Abstract
     */
    protected function getOrderHelper()
    {
        if (!$this->helperOrder) {
            $this->helperOrder = Mage::helper('pagseguropayment/order');
        }

        return $this->helperOrder;
    }
}
