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
class PagSeguro_Payment_CardsController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function deleteAction()
    {
        /** @var PagSeguro_Payment_Helper_Data $helper */
        $helper = Mage::helper('pagseguropayment');

        /** @var Mage_Core_Model_Session $customerSession */
        $customerSession = Mage::getSingleton('core/session');
        try {
            if ($cardId = $this->getRequest()->getParam('card_id')) {
                /** @var PagSeguro_Payment_Model_Card $card */
                $card = Mage::getModel('pagseguropayment/card')->load($cardId);
                if ($card->getCustomerId() == $helper->getCurrentCustomer()->getId()) {
                    $card->delete();
                    $customerSession->addSuccess($this->__('Card deleted with success!'));
                    return $this->_redirectReferer();
                }

                $customerSession->addError($this->__('The card does not belong to current logged customer'));
                return $this->_redirectReferer();
            }

            $customerSession->addError($this->__('Card not found'));
            return $this->_redirectReferer();
        } catch (Exception $e) {
            $customerSession->addError($e->getMessage());
            return $this->_redirectReferer();
        }
    }
}