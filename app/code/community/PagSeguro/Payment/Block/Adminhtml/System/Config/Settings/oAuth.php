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
class PagSeguro_Payment_Block_Adminhtml_System_Config_Settings_OAuth
    extends Mage_Adminhtml_Block_System_Config_Form_Field
    implements Varien_Data_Form_Element_Renderer_Interface
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $buttonBlock = Mage::app()->getLayout()->createBlock('adminhtml/widget_button');

        $data = array(
            'label' => Mage::helper('adminhtml')->__('Validate Token'),
            'onclick'   => 'setLocation(\'' . Mage::helper('adminhtml')->getUrl("pagseguropayment_admin/authentication/validateToken") . '\')',
            'class' => '',
        );

        $html = $buttonBlock->setData($data)->toHtml();

        return $html;
    }
}
