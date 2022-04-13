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
class PagSeguro_Payment_Model_Total_Quote_Interest
    extends Mage_Sales_Model_Quote_Address_Total_Abstract
{

    protected $_helper;
    protected $_code = 'pagseguropayment_interest';

    public function __construct()
    {
        $this->setCode($this->_code);
    }

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);

        $this->_setAmount(0);
        $this->_setBaseAmount(0);

        $items = $this->_getAddressItems($address);
        if (!count($items)) {
            return $this;
        }

        $interestAmount = 0;
        $quote = $address->getQuote();
        if (strpos($address->getQuote()->getPayment()->getMethod(), 'pagseguropayment_') !== false) {
            $payment = Mage::app()->getRequest()->getPost('payment');
            $grandTotal = isset($payment['base_grand_total']) ? $payment['base_grand_total'] : $address->getSubtotal();

            if ($address->getQuote()->getPayment()->getMethod() == 'pagseguropayment_onecc') {
                if (isset($payment['installments']) && $payment['installments'] > 1) {
                    $installments = $payment['installments'];
                    $installmentsValue = $this->_getHelper()->getInstallmentValue($grandTotal, $installments);
                    $totalOrderWithInterest = $installmentsValue * $installments;
                    $interestAmount = $totalOrderWithInterest - $grandTotal;
                }
            } elseif ($address->getQuote()->getPayment()->getMethod() == 'pagseguropayment_twocc') {
                if (
                    (isset($payment['card_one']['installments']) && $payment['card_one']['installments'] > 1)
                    || (isset($payment['card_two']['installments']) && $payment['card_two']['installments'] > 1)
                ) {
                    $ccOneInstallments = $payment['card_one']['installments'];
                    $ccOneAmount = $payment['card_one']['amount'];

                    $ccTwoInstallments = $payment['card_two']['installments'];
                    $ccTwoAmount = $payment['card_two']['amount'];

                    $ccOneValue = $this->_getHelper()
                        ->getInstallmentValue($ccOneAmount, $ccOneInstallments, 'pagseguropayment_twocc');
                    $ccTwoValue = $this->_getHelper()
                        ->getInstallmentValue($ccTwoAmount, $ccTwoInstallments, 'pagseguropayment_twocc');
                    $interestAmount = (($ccOneValue * $ccOneInstallments) + ($ccTwoValue * $ccTwoInstallments)) - $grandTotal;
                }
            }
        }

        $address->setPagseguropaymentInterestAmount($interestAmount);
        $address->setBasePagseguropaymentInterestAmount($interestAmount);
        $quote->setPagseguropaymentInterestAmount($interestAmount);
        $quote->setBasePagseguropaymentInterestAmount($interestAmount);

        $this->_addAmount($interestAmount);
        $this->_addBaseAmount($interestAmount);

        return $this;
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $amount = $address->getPagseguropaymentInterestAmount();
        if ($amount > 0) {
            $address->addTotal(array(
                'code' => $this->getCode(),
                'title' => Mage::helper('pagseguropayment')->__('Interest'),
                'value' => $amount
            ));
        }

        return $this;
    }

    /**
     * @return PagSeguro_Payment_Helper_Installment|Mage_Core_Helper_Abstract
     */
    protected function _getHelper()
    {
        if (!$this->_helper) {
            $this->_helper = Mage::helper('pagseguropayment/installment');
        }

        return $this->_helper;
    }
}