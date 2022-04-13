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
class PagSeguro_Payment_Block_Form_Twocc
    extends Mage_Payment_Block_Form
{
    protected $years;
    protected $months;
    protected $_helper;
    protected $helperInstallment;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('pagseguropayment/form/two_cc.phtml');
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    /**
     * Retrieve payment configuration object
     *
     * @return Mage_Payment_Model_Config|Mage_Core_Model_Abstract
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('payment/config');
    }


    public function getCcInstalments()
    {
        $installments = $this->_getHelperInstallment()->getInstallmentsInformation();
        $result = [];

        foreach ($installments as $installment) {
            $instalmentValue = $this->_getHelper()->currency($installment['value'], true, false);
            $data['value'] = $installment['installments'];
            $data['label'] = $this->__("%dx of %s", $installment['installments'], $instalmentValue);

            if ($interest = $installment['interest_rate']) {
                $total = $this->_getHelper()->currency($installment['total'], true, false);
                $data['label'] .= ' ' . $this->__('(Total of %s, interest of %s&percnt;)', $total, $interest);
            }

            $result[] = $data;
        }

        return $result;
    }

    /**
     * Retrieve available credit card types
     *
     * @return array
     */
    public function getCcAvailableTypes()
    {
        $types = $this->_getConfig()->getCcTypes();
        if ($method = $this->getMethod()) {
            $availableTypes = $method->getConfigData('cctypes');
            if ($availableTypes) {
                $availableTypes = explode(',', $availableTypes);
                foreach ($types as $code => $name) {
                    if (!in_array($code, $availableTypes)) {
                        unset($types[$code]);
                    }
                }
            }
        }
        return $types;
    }

    /**
     * Retrieve credit card expire months
     *
     * @return array
     */
    public function getCcMonths()
    {
        if (!$this->months) {
            $this->months = array(
                '' => $this->__('Months')
            );

            $this->months = array_merge($this->months, $this->_getConfig()->getMonths());
        }

        return $this->months;
    }

    /**
     * Retrieve credit card expire years
     *
     * @return array
     */
    public function getCcYears()
    {
        if (!$this->years) {
            $this->years = array(
                '' => $this->__('Year')
            );

            $this->years = array_merge($this->years, $this->_getConfig()->getYears());
        }
        return $this->years;
    }

    /**
     * @return false|PagSeguro_Payment_Model_Resource_Card_Collection
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getSavedCards()
    {
        $customer = $this->_getHelper()->getCurrentCustomer();
        if ($customerId = $customer->getId()) {
            /** @var PagSeguro_Payment_Model_Card $cards */
            $cards = Mage::getModel('pagseguropayment/card');
            return $cards->getCardsByCustomerId($customerId);
        }

        return false;
    }

    /**
     * @return string[]
     */
    public function getCardsNames()
    {
        return ['card_one', 'card_two'];
    }

    /**
     * @return PagSeguro_Payment_Helper_Data
     */
    protected function _getHelper()
    {
        if (!$this->_helper) {
            $this->_helper = Mage::helper('pagseguropayment');
        }
        return $this->_helper;
    }

    /**
     * @return PagSeguro_Payment_Helper_Installment
     */
    protected function _getHelperInstallment()
    {
        if (!$this->helperInstallment) {
            $this->helperInstallment = Mage::helper('pagseguropayment/installment');
        }
        return $this->helperInstallment;
    }

    /**
     * Retrieve field value data from payment info object
     *
     * @param   string $field
     * @return  mixed
     */
    public function getInfoData($field)
    {
        $value = '';
        try {
            $infoInstance = $this->getMethod()->getInfoInstance();
            $value = $this->escapeHtml($infoInstance->getData($field));
            if (!$value) {
                $value = $infoInstance->getAdditionalInformation($field);
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return $value;
    }
}
