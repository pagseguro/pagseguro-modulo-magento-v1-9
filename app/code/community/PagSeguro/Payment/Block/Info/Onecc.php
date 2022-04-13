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
class PagSeguro_Payment_Block_Info_Onecc extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('pagseguropayment/info/one_cc.phtml');
        $this->setModuleName('Mage_Payment');
    }

    /**
     * Retrieve current order model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        $order = Mage::registry('current_order');

        if (!$order) {
            if ($this->getInfo() instanceof Mage_Sales_Model_Order_Payment) {
                $order = $this->getInfo()->getOrder();
            }
            if ($this->getInfo() instanceof Mage_Sales_Model_Quote_Payment) {
                $order = $this->getInfo()->getQuote();
            }
        }

        return ($order);
    }
}
