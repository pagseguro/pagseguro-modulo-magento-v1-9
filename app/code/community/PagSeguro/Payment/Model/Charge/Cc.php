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
class PagSeguro_Payment_Model_Charge_Cc extends PagSeguro_Payment_Model_Charge_Abstract
{
    protected $type = 'CREDIT_CARD';
    protected $payment;
    protected $paymentCode;
    protected $prefix = '';

    /**
     * @param $paymentCode
     */
    public function setPaymentCode($paymentCode)
    {
        $this->paymentCode = $paymentCode;
    }

    /**
     * @return mixed
     */
    public function getPaymentCode()
    {
        return $this->paymentCode;
    }

    /**
     * @param $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param double $amount
     * @return object|boolean
     * @throws
     */
    public function cardChargeData($payment, $amount)
    {
        $this->setOrder($payment->getOrder());
        $this->setPayment($payment);

        $chargeData = new stdClass();
        $chargeData->reference_id = $this->getOrder()->getIncrementId();
        $chargeData->description = $this->getHelper()->__("Online Purchase - #%s", $this->getOrder()->getIncrementId());
        $chargeData->amount = $this->getChargeAmount($amount);
        $chargeData->payment_method = $this->getChargePaymentMethod();

        $chargeData->notification_urls = [
            Mage::getUrl('pagseguropayment/notification/orders')
        ];

        return $chargeData;
    }

    /**
     * @return stdClass
     */
    protected function getChargePaymentMethod()
    {
        $paymentMethod = new stdClass();
        $paymentMethod->type = $this->type;
        $paymentMethod->installments = $this->getPayment()->getAdditionalInformation("{$this->getPrefix()}cc_installments");
        $paymentMethod->capture = (bool)$this->getHelper()->getConfig('capture', $this->getPaymentCode());
        $paymentMethod->card = $this->getCardData();

        return $paymentMethod;
    }

    /**
     * @return stdClass
     */
    protected function getCardData()
    {
        $card = new stdClass();

        $encrypted = $this->getPayment()->getAdditionalInformation("{$this->getPrefix()}cc_encrypted_info");
        if ($encrypted) {
            $card->encrypted = $encrypted;
            return $card;
        }

        $token = $this->getPayment()->getAdditionalInformation("{$this->getPrefix()}cc_token");
        if ($token) {
            $card->id = $token;
        } else {
            $card->number = $this->getPayment()->getAdditionalInformation("{$this->getPrefix()}cc_number");
            $card->exp_month = $this->getPayment()->getAdditionalInformation("{$this->getPrefix()}cc_exp_month");
            $card->exp_year = $this->getPayment()->getAdditionalInformation("{$this->getPrefix()}cc_exp_year");

            $saveCard = $this->getPayment()->getAdditionalInformation("{$this->getPrefix()}cc_save_card");
            if ($saveCard) {
                $card->store = true;
            }

            $holder = new stdClass();
            $holder->name = $this->getPayment()->getAdditionalInformation("{$this->getPrefix()}cc_owner");

            $card->holder = $holder;
        }

        $card->security_code = Mage::registry("{$this->getPaymentCode()}_{$this->getPrefix()}cc_cid");
        return $card;
    }

    /**
     * @param $payment
     */
    protected function setPayment($payment)
    {
        if (!$this->payment) {
            $this->payment = $payment;
        }
    }

    /**
     * @return Mage_Sales_Model_Quote_Payment
     */
    protected function getPayment()
    {
        return $this->payment;
    }
}