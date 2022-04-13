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
class PagSeguro_Payment_Helper_Installment extends PagSeguro_Payment_Helper_Data
{
    /**
     * @param string $code
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getInstallmentsInformation($code = 'pagseguropayment_onecc')
    {
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $this->getSession()->getQuote();

        $installmentsInformation = array();
        $paymentAmount = $quote->getBaseGrandTotal();
        $installments = $this->getConfig('max_installments', $code);
        $installmentsWithoutInterest = (int)$this->getConfig(
            'installments_without_interest_rate',
            $code
        );

        $i = 1;
        $installmentsInformation[$i] = array(
            'installments' => 1,
            'value' => $paymentAmount,
            'total' => $paymentAmount,
            'interest_rate' => 0,
        );

        for ($i = 2; $i <= $installments; $i++) {
            if (($installments > $installmentsWithoutInterest) && ($i > $installmentsWithoutInterest)) {
                $interestRate = $this->getConfig('interest_rate', $code);
                $value = $this->getInstallmentValue($paymentAmount, $i);
                if (!$value)
                    continue;

            } else {
                $interestRate = 0;
                $value = $paymentAmount / $i;
            }

            if ($value < $this->getConfig('minimum_installments_value', $code) && $i > 1) {
                continue;
            }

            $installmentsInformation[$i] = array(
                'installments' => $i,
                'value' => $value,
                'total' => $value * $i,
                'interest_rate' => $interestRate,
            );
        }

        return $installmentsInformation;
    }

    /**
     * @param $total
     * @param $installments
     * @param string $code
     * @return bool|float|int
     */
    public function getInstallmentValue($total, $installments, $code = 'pagseguropayment_onecc')
    {
        $installmentsWithoutInterestRate = (int)$this->getConfig('installments_without_interest_rate', $code);
        $interestRate = $this->getConfig('interest_rate', $code);
        $interestType = $this->getConfig('interest_type', $code);

        $interestRate = (float)(str_replace(',', '.', $interestRate)) / 100;

        if ($installments > 0) {
            $installmentValue = $total / $installments;
        } else {
            $installmentValue = $total;
        }

        try {
            if ($installments > $installmentsWithoutInterestRate && $interestRate > 0) {
                switch ($interestType) {
                    case 'price':
                        $value = $total * (($interestRate * pow((1 + $interestRate), $installments)) / (pow((1 + $interestRate), $installments) - 1));
                        $installmentValue = round($value, 2);
                        break;
                    case 'compound':
                        //M = C * (1 + i)^n
                        $installmentValue = ($total * pow(1 + $interestRate, $installments)) / $installments;
                        break;
                    case 'simple':
                        //M = C * ( 1 + ( i * n ) )
                        $installmentValue = ($total * (1 + ($installments * $interestRate))) / $installments;
                        break;
                }
            }
        } catch (Exception $e) {
            $this->log($e->getMessage());
        } finally {
            return $installmentValue;
        }
    }
}