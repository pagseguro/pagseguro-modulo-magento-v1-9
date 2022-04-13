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
abstract class PagSeguro_Payment_Model_Method_Abstract extends Mage_Payment_Model_Method_Abstract
{
    protected $_isGateway = true;

    protected $_canOrder = true;

    protected $_canAuthorize = true;

    protected $_canCapture = true;

    protected $_canRefund = true;

    protected $_canVoid = true;

    protected $_canUseInternal = true;

    protected $_canUseCheckout = true;

    protected $_canUseForMultishipping = true;

    protected $_canSaveCc = false;

    protected $_canFetchTransactionInfo = true;

    protected $_canManageRecurringProfiles = false;

    protected $_customer;

    protected $_session;

    protected $_helper;

    /*
     * @param Mage_Sales_Model_Order
     */
    protected $_order = null;

    /**
     * Prepare info instance for save
     *
     * @return $this
     */
    public function prepareSave()
    {
        $info = $this->getInfoInstance();

        if ($this->_canSaveCc) {
            $info->setCcNumberEnc($info->encrypt($info->getCcNumber()));
        }

        $info->setCcNumber(null)->setCcCid(null);

        return $this;
    }

    /**
     * Get payment quote
     */
    public function getPayment()
    {
        return $this->getOrder()->getPayment();
    }

    /**
     * Get current order
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->_order ?: ($this->_order = $this->getInfoInstance()->getOrder());
    }

    /**
     * Get current quote
     *
     * @return Mage_Core_Model_Abstract|Mage_Sales_Model_Quote
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getQuote()
    {
        return $this->_getQuote();
    }

    /**
     * Set capture transaction ID and enable Void to invoice for informational purposes
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return $this|false
     * @throws Exception
     */
    public function processInvoice($invoice, $payment)
    {
        if ($payment->getLastTransId()) {
            $invoice->setTransactionId($payment->getLastTransId());
            $invoice->setCanVoidFlag(true);

            if (Mage::helper('sales')->canSendNewInvoiceEmail($payment->getOrder()->getStoreId())) {
                $invoice->setEmailSent(true);
                $invoice->sendEmail();
            }

            return $this;
        }

        return false;
    }

    /**
     * @param Varien_Object|Mage_Sales_Model_Order_Payment $payment
     * @return Mage_Payment_Model_Abstract
     * @throws Mage_Core_Exception
     * @throws Zend_Http_Client_Exception
     */
    public function void(Varien_Object $payment)
    {
        if (!$payment->getAdditionalInformation('recurring_profile')) {
            if ($payment->canVoid($payment)) {
                $this->cancelOrder($payment);
            }
        }
        return parent::void($payment);
    }

    /**
     * Check void availability
     *
     * @return bool
     */
    public function canVoid(Varien_Object $payment)
    {
        if ($payment instanceof Mage_Sales_Model_Order_Creditmemo) {
            return false;
        }
        return $this->_canVoid;
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return bool
     * @throws Mage_Core_Exception
     * @throws Zend_Http_Client_Exception
     */
    protected function cancelOrder($payment, $amount = null)
    {
        $refunded = $payment->getAdditionalInformation('pagseguro_total_refunded');
        if ($refunded) {
            return true;
        }

        $pagseguroPaymentId = $payment->getAdditionalInformation('order_id');
        $amount = $amount ?: $payment->getAmountOrdered();

        $response = $this->getChargeCc()
            ->getService()
            ->cancelCharge($pagseguroPaymentId, $this->getChargeCc()->getAmountData($amount));
        $responseData = json_decode($response->getBody());

        if (
            property_exists($responseData, 'id')
            && $responseData->id
            && $responseData->status == 'CANCELED' || $responseData->amount->summary->refunded > 0
        ) {
            $payment->setAdditionalInformation('pagseguro_total_refunded', $responseData->amount->summary->refunded);
            $payment->setAdditionalInformation('cancelled', true);
            $payment->setAdditionalInformation('cancelled_date', Mage::getSingleton('core/date')->gmtDate());
            $payment->save();
        } else {
            Mage::throwException($this->getHelper()->__('There was an error canceling your order at PagSeguro'));
        }

        return true;
    }

    /**
     * Get payment methods
     */
    public function getPaymentMethods()
    {
        $payment_methods = $this->getConfigData('payment_methods');

        if ($payment_methods != '') {
            $payment_methods = explode(',', $payment_methods);
        } else {
            $payment_methods = array();
        }

        return $payment_methods;
    }

    public function _getSession()
    {
        if (!$this->_session) {
            if (Mage::app()->getStore()->isAdmin()) {
                $this->_session = Mage::getSingleton('adminhtml/session_quote');
            } else {
                $this->_session = Mage::getSingleton('checkout/session');
            }
        }
        return $this->_session;
    }

    public function _getCustomer()
    {
        if (!$this->_customer) {
            if (Mage::app()->getStore()->isAdmin()) {
                $this->_customer = Mage::getModel('customer/customer')->load($this->_getSession()->getCustomerId());
            } else {
                $this->_customer = $this->_getSession()->getCustomer();
            }
        }
        return $this->_customer;
    }

    /**
     * Retrieves Quote
     *
     * @param null $quoteId
     * @return Mage_Sales_Model_Quote|Mage_Core_Model_Abstract
     * @throws Mage_Core_Model_Store_Exception
     */
    public function _getQuote($quoteId = null)
    {
        if (!empty($quoteId)) {
            return Mage::getModel('sales/quote')->load($quoteId);
        } else {
            return Mage::app()->getStore()->isAdmin() ? $this->_getAdminCheckout()->getQuote() : $this->_getCheckout()->getQuote();
        }
    }

    /**
     * @return Mage_Core_Model_Abstract|null
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get admin checkout session namespace
     *
     * @return Mage_Core_Model_Abstract|Mage_Adminhtml_Model_Session_Quote
     */
    protected function _getAdminCheckout()
    {
        return Mage::getSingleton('adminhtml/session_quote');
    }

    /**
     * @return PagSeguro_Payment_Helper_Data|Mage_Core_Helper_Abstract
     */
    public function getHelper()
    {
        if (!$this->_helper) {
            /** @var PagSeguro_Payment_Helper_Data helper */
            $this->_helper = Mage::helper('pagseguropayment');
        }

        return $this->_helper;
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param object $response
     * @return Mage_Sales_Model_Order_Payment
     * @throws exception
     */
    protected function setAdditionalInfo(Mage_Sales_Model_Order_Payment $payment, $response)
    {
        if (property_exists($response, 'id')) {
            $payment->setAdditionalInformation('transaction_id', $response->id);
        }

        if (property_exists($response, 'status')) {
            $payment->setAdditionalInformation('status', $response->status);
        }

        return $payment;
    }
}