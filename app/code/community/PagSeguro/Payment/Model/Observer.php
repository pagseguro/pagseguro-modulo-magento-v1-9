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
 * @category      PagSeguro
 * @package       PagSeguro_Payment
 * @author        PagSeguro
 * @license       http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class PagSeguro_Payment_Model_Observer extends Varien_Event_Observer
{
    protected $helper;
    protected $helperOrder;
    protected $chargeCc;

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function salesOrderPaymentPlaceEnd(Varien_Event_Observer $observer)
    {
        try {
            /** @var Mage_Sales_Model_Order_Payment $payment */
            $payment = $observer->getEvent()->getPayment();
            $methodCode = $payment->getMethod();
            $status = false;
            $message = '';

            /** @var Mage_Sales_Model_Order $order */
            $order = $payment->getOrder();

            if ($methodCode == 'pagseguropayment_onecc') {
                $tid = $payment->getAdditionalInformation('transaction_id');
                $capture = $this->getHelper()->getConfig('capture', $methodCode);

                if ($capture && $payment->getAdditionalInformation('status') == 'PAID') {
                    $status = $this->getHelper()->getConfig('captured_order_status', $methodCode);
                    $this->getOrderHelper()->createInvoice($order);
                } elseif ($payment->getAdditionalInformation('status') == 'authorized') {
                    $status = $this->getHelper()->getConfig('authorized_order_status', $methodCode);
                    $message = $this->getHelper()->__("The payment was authorized - Transaction ID: %s", (string)$tid);
                }

                if ($status) {
                    $state = $this->getOrderHelper()->getAssignedState($status);
                    $order->setState($state, $status, $message, true);
                    $order->save();
                }
            } else if ($methodCode == 'pagseguropayment_twocc') {
                $this->getOrderHelper()->updatePaymentTwoCards($payment);
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function salesOrderPaymentCancel(Varien_Event_Observer $observer)
    {
        try {
            /** @var Mage_Sales_Model_Order $order */
            $order = $observer->getEvent()->getPayment()->getOrder();
            /** @var Mage_Sales_Model_Order_Payment $payment */
            $payment = $order->getPayment();

            $methodCode = $payment->getMethod();
            if ($methodCode == 'pagseguropayment_onecc') {
                $cancelled = $payment->getAdditionalInformation('cancelled');
                if (!$cancelled) {
                    $pagseguroOrderId = $payment->getAdditionalInformation('order_id');
                    $amount = $payment->getAmountOrdered();
                    $this->getChargeCc()
                        ->getService()
                        ->cancelCharge($pagseguroOrderId, $this->getChargeCc()->getAmountData($amount));

                    $payment->setAdditionalInformation('cancelled', true);
                    $payment->setAdditionalInformation('cancelled_date', Mage::getSingleton('core/date')->gmtDate());
                    $payment->save();
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::throwException($e->getMessage());
        }

        return $this;
    }

    /**
     * @return PagSeguro_Payment_Helper_Data|Mage_Core_Helper_Abstract
     */
    protected function getHelper()
    {
        if (!$this->helper) {
            $this->helper = Mage::helper('pagseguropayment');
        }

        return $this->helper;
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

    /**
     * @return PagSeguro_Payment_Model_Charge_Cc|false|Mage_Core_Model_Abstract|mixed|null
     */
    protected function getChargeCc()
    {
        if (!$this->chargeCc) {
            /** @var PagSeguro_Payment_Model_Charge_Cc $chargeCc */
            $this->chargeCc = Mage::getModel('pagseguropayment/charge_cc');
        }

        return $this->chargeCc;
    }

}
