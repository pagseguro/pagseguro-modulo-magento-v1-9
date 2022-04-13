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
 */
class PagSeguro_Payment_Block_Adminhtml_Transactions extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_transactions';
        $this->_blockGroup = 'pagseguropayment';
        $this->_headerText = Mage::helper('pagseguropayment')->__('PagSeguro Transactions');
        parent::__construct();
        $this->_removeButton('add');

    }
}