<?php

/**
 * Lens Recipe
 *
 * @category    Acresys
 * @package     Acresys_LensRecipe
 * @author      BlackSmith
 * @copyright   Copyright (c) 2020 Acresys
 */
class Acresys_LensRecipe_Block_Adminhtml_RecipesStatus_Edit_Tabs
    extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('filter_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('lensrecipe')->__('General'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('form_section', array(
            'label' => Mage::helper('lensrecipe')->__('General'),
            'title' => Mage::helper('lensrecipe')->__('General'),
            'content' => $this->getLayout()->createBlock('lensrecipe/adminhtml_recipesStatus_edit_tab_form')->toHtml(),
        ));

        return parent::_beforeToHtml();
    }
}
