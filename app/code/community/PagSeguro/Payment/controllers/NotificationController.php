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
class PagSeguro_Payment_NotificationController extends Mage_Core_Controller_Front_Action
{
    public function ordersAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this;
        }

        /** @var PagSeguro_Payment_Helper_Order $helper */
        $helper = Mage::helper('pagseguropayment/order');

        $response = $this->getRequest()->getRawBody();
        if (property_exists($response, 'reference_id')) {
            $incrementId = $response->reference_id;

            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
            $helper->updatePayment($order, json_decode($response));
        }
    }
}