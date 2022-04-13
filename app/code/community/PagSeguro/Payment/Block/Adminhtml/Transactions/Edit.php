<?php

/**
 * Lens Recipe
 *
 * @category    Acresys
 * @package     Acresys_LensRecipe
 * @author      BlackSmith
 * @copyright   Copyright (c) 2020 Acresys
 */
class Acresys_LensRecipe_Block_Adminhtml_RecipesStatus_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_recipesStatus';
        $this->_blockGroup = 'lensrecipe';
        $this->_objectId = 'id';
        parent::__construct();
        $this->_updateButton('save', 'label', Mage::helper('adminhtml')->__('Save'));
        $this->_updateButton('save', 'onclick', 'save(this)');

        $this->_addButton('saveandcontinue', array(
            'label' => Mage::helper('adminhtml')->__('Save and Continue Edit'),
            'onclick' => 'saveAndContinueEdit(this)',
            'class' => 'save',
        ), -100);

        $this->_formScripts[] = "
            function saveAndContinueEdit(saveButton) {
                if (editForm.submit($('edit_form').action+'back/edit/')) {
                     var el = document.getElementsByClassName('save');
                     for (let i = 0; i < el.length; i++) {
                        el[i].disabled = true;
                     }
                }
            }
            
            function save(saveButton) {
                if (editForm.submit()) {
                     var el = document.getElementsByClassName('save');
                     for (let i = 0; i < el.length; i++) {
                        el[i].disabled = true;
                     }
                }
            }
        ";
    }

    public function getHeaderText()
    {
        return Mage::helper('lensrecipe')->__('Recipes Status');
    }
}