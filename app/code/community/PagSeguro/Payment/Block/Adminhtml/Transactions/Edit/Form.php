<?php

/**
 * Lens Recipe
 *
 * @category    Acresys
 * @package     Acresys_LensRecipe
 * @author      BlackSmith
 * @copyright   Copyright (c) 2020 Acresys
 */
class Acresys_LensRecipe_Block_Adminhtml_RecipesStatus_Edit_Form
    extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
                'method' => 'post',
                'enctype' => 'multipart/form-data'
            )
        );

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }

}
