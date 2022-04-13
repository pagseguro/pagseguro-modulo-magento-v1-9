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
class PagSeguro_Payment_Block_Adminhtml_Transactions_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * PagSeguro_Payment_Block_Adminhtml_Transactions_Grid constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('transactions_grid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    /**
     * @return PagSeguro_Payment_Block_Adminhtml_Transactions_Grid
     */
    protected function _prepareCollection()
    {
        /** @var PagSeguro_Payment_Model_Resource_Transaction_Collection $collection */
        $collection = Mage::getResourceModel('pagseguropayment/transaction_collection');
        $collection->setOrder('entity_id');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * @return PagSeguro_Payment_Block_Adminhtml_Transactions_Grid
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn('order_id',
            array(
                'header' => $this->__('Order Id'),
                'align' => 'left',
                'index' => 'order_id'
            )
        );

        $this->addColumn('pagseguropayment_id',
            array(
                'header' => $this->__('PagSeguro Id'),
                'align' => 'left',
                'index' => 'pagseguropayment_id'
            )
        );

        $this->addColumn('request',
            array(
                'header' => $this->__('Request'),
                'align' => 'left',
                'index' => 'request'
            )
        );

        $this->addColumn('code',
            array(
                'header' => $this->__('Code'),
                'align' => 'left',
                'index' => 'code'
            )
        );

        $this->addColumn('response',
            array(
                'header' => $this->__('Response'),
                'align' => 'left',
                'index' => 'response'
            )
        );

        $this->addColumn('created_at',
            array(
                'header' => $this->__('Created At'),
                'align' => 'left',
                'index' => 'created_at',
                'type' => 'date'
            )
        );

        return parent::_prepareColumns();
    }
}