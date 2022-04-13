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
class PagSeguro_Payment_Block_Form_Ticket extends Mage_Payment_Block_Form {
    /**
     * Helper object
     *
     * @var object
     */
    protected $helper;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('pagseguropayment/form/ticket.phtml');
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    /**
     * @return PagSeguro_Payment_Helper_Data
     */
    protected function getPagseguroHelper()
    {
        if (!$this->helper) {
            $this->helper = Mage::helper('pagseguropayment');
        }
        return $this->helper;
    }
}
