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
class PagSeguro_Payment_Block_Customer_Cards extends Mage_Customer_Block_Account_Dashboard
{
    /**
     * Customer cards collection
     *
     * @var PagSeguro_Payment_Model_Resource_Card_Collection
     */
    protected $collection;

    /**
     * Initializes collection
     */
    protected function _construct()
    {
        $customerId = Mage::getSingleton('customer/session')->getCustomerId();
        $this->collection = Mage::getModel('pagseguropayment/card')->getCollection();
        $this->collection->addFieldToFilter('customer_id', $customerId);
    }

    /**
     * Gets collection items count
     *
     * @return int
     */
    public function count()
    {
        return $this->collection->getSize();
    }

    /**
     * @return PagSeguro_Payment_Model_Resource_Card_Collection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @return string
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getRemoveLink()
    {
        return Mage::getUrl('pagseguropayment/cc/delete', array('_secure' => Mage::app()->getStore()->isCurrentlySecure()));
    }
}
