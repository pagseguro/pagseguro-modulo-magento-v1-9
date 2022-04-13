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
class PagSeguro_Payment_Adminhtml_OAuthgController
    extends Mage_Adminhtml_Controller_Action
{

    protected $_helper;

    public function validateTokenAction()
    {
        try {
            /** @var PagSeguro_Payment_Model_Service_Authentication $authenticationService */
            $authenticationService = Mage::getModel('pagseguropayment/service_authentication');
            $response = $authenticationService->validateToken();

            if (!$response || $response->getStatus() == 401) {
                Mage::getSingleton('adminhtml/session')->addError(
                    $this->_getHelper()->__('There was an error trying to validate your token')
                );
            } else {
                Mage::getSingleton('adminhtml/session')->addSuccess($this->_getHelper()->__('Token is valid'));
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        $this->_redirectReferer();
        return $this;
    }

    /**
     * @return PagSeguro_Payment_Helper_Data|Mage_Core_Helper_Abstract
     */
    protected function getHelper()
    {
        if (!$this->_helper) {
            /** @var PagSeguro_Payment_Helper_Data _helper */
            $this->_helper = Mage::helper('pagseguropayment');
        }

        return $this->_helper;
    }
}
