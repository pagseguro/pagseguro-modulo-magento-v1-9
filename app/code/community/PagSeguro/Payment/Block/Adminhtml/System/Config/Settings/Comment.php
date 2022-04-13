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
class PagSeguro_Payment_Block_Adminhtml_System_Config_Settings_Comment
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $instructions = '<div class="instructions">
                            <strong>IMPORTANTE</strong>
                            <p>Para utilizar o módulo é preciso estar cadastrado no PagSeguro</p>
                            <p>Utilize o token fornecidas por eles</p>
                        </div>';


        return $instructions;
    }
}
