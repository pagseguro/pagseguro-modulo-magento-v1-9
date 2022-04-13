<?php

/**
 * Lens Recipe
 *
 * @category    Acresys
 * @package     Acresys_LensRecipe
 * @author      BlackSmith
 * @copyright   Copyright (c) 2020 Acresys
 */
class Acresys_LensRecipe_Block_Adminhtml_RecipesStatus_Edit_Tab_Form
    extends Mage_Adminhtml_Block_Widget_Form
{
    protected $_helper;

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);

        /** @var Acresys_LensRecipe_Model_RecipesStatus $recipeStatus */
        $recipeStatus = Mage::registry('lensrecipe_recipes_status');

        $fieldset = $form->addFieldset('link_form', array('legend' => $this->getHelper()->__('Recipes Status')));
        $fieldset->addField('label', 'text', array(
            'label' =>$this->getHelper()->__('Label'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'label',
        ));

        $fieldset->addField('status', 'select', array(
            'label' =>$this->getHelper()->__('Status'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'status',
            'values' => Mage::getSingleton('adminhtml/system_config_source_yesno')->toOptionArray()
        ));

        if ($recipeStatus) {
            $form->setValues($recipeStatus->getData());
            $this->setForm($form);
        }

        return parent::_prepareForm();
    }

    /**
     * @return Mage_Core_Helper_Abstract|Acresys_LensRecipe_Helper_Data
     */
    public function getHelper()
    {
        if (!$this->_helper) {
            $this->_helper = Mage::helper('lensrecipe');
        }

        return $this->_helper;
    }
}
