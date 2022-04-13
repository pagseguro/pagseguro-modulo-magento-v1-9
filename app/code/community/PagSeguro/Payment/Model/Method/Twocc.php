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
class PagSeguro_Payment_Model_Method_Twocc
    extends PagSeguro_Payment_Model_Method_Abstract
{
    /**
     * unique internal payment method identifier
     * @var string [a-z0-9_]
     */
    protected $_code = 'pagseguropayment_twocc';
    protected $_canSaveCc = true;

    protected $_canCapturePartial = false;
    protected $_canCaptureOnce = true;
    protected $_canRefundInvoicePartial = false;
    protected $_canReviewPayment = true;
    protected $_canCreateBillingAgreement = false;

    protected $_formBlockType = 'pagseguropayment/form_twocc';
    protected $_infoBlockType = 'pagseguropayment/info_twocc';

    protected $chargeCc;

    /**
     * @param mixed $data
     * @return $this|Mage_Payment_Model_Info
     * @throws Mage_Core_Exception
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        /** @var Mage_Payment_Model_Info $info */
        $info = $this->getInfoInstance();

        $firstCard = $data->getCardOne();
        $secondCard = $data->getCardTwo();

        $useFirstSavedCard = isset($firstCard['use_saved_card']) ? $firstCard['use_saved_card'] : false;
        $useSecondSavedCard = isset($secondCard['use_saved_card']) ? $secondCard['use_saved_card'] : false;

        $interestRate = $this->getHelper()->getConfig('interest_rate', $this->getCode());
        $info->setAdditionalInformation('cc_has_interest', ($interestRate) ? true : false);
        $info->setAdditionalInformation('cc_interest_rate', $interestRate);

        $this->assignCardData($info, $firstCard, 'first', $useFirstSavedCard);
        $this->assignCardData($info, $secondCard, 'second', $useSecondSavedCard);

        $cardsAmount = $info->getAdditionalInformation('first_cc_total_with_interest') + $info->getAdditionalInformation('second_cc_total_with_interest');
        if ($cardsAmount != $info->getQuote()->getGrandTotal()) {
            Mage::throwException($this->_getHelper()->__('There were an error trying to validate cards amounts'));
        }

        $info->setAdditionalInformation('base_grand_total', $data->getBaseGrandTotal());
        $info->setAdditionalInformation('cpf_cnpj', $this->getHelper()->getTaxIdValue());

        return $this;
    }

    protected function assignCardData(&$info, $cardInfo, $prefix, $useSavedCard)
    {
        /** @var PagSeguro_Payment_Helper_Installment $installmentHelper */
        $installmentHelper = Mage::helper('pagseguropayment/installment');

        if ($useSavedCard) {
            /** @var PagSeguro_Payment_Model_Card $card */
            $card = Mage::getModel('pagseguropayment/card')->load($cardInfo['cc_token']);
            $info->setAdditionalInformation("{$prefix}_cc_token", $card->getToken());
            $info->setAdditionalInformation("{$prefix}_cc_description", $card->getDescription());
        } else {
            $ccNumber = preg_replace("/[^0-9]/", '', $cardInfo['cc_number']);
            $ccExpMonth = str_pad($cardInfo['cc_exp_month'], 2, '0', STR_PAD_LEFT);
            $saveCard = isset($cardInfo['save_card']) ? $cardInfo['save_card'] : false;

            $info->setAdditionalInformation("{$prefix}_cc_type", $cardInfo['cc_type']);
            $info->setAdditionalInformation("{$prefix}_cc_number", $ccNumber);
            $info->setAdditionalInformation("{$prefix}_cc_exp_month", $ccExpMonth);
            $info->setAdditionalInformation("{$prefix}_cc_last_4", substr($ccNumber, -4));
            $info->setAdditionalInformation("{$prefix}_cc_owner", $cardInfo['cc_owner']);
            $info->setAdditionalInformation("{$prefix}_cc_exp_year", $cardInfo['cc_exp_year']);
            $info->setAdditionalInformation("{$prefix}_cc_number_enc", $info->encrypt($ccNumber));

            $info->setAdditionalInformation("{$prefix}_cc_token", false);
            $info->setAdditionalInformation("{$prefix}_cc_description", false);
            $info->setAdditionalInformation("{$prefix}_cc_save_card", $saveCard);
        }

        $secondCardInstallmentValue = $installmentHelper->getInstallmentValue($cardInfo['amount'], $cardInfo['installments'], $this->_code);
        $totalOrderWithInterest = $secondCardInstallmentValue * $cardInfo['installments'];
        $interestValue = $totalOrderWithInterest - $cardInfo['amount'];

        $info->setAdditionalInformation("{$prefix}_cc_base_amount", $cardInfo['amount']);
        $info->setAdditionalInformation("{$prefix}_cc_installments", $cardInfo['installments']);
        $info->setAdditionalInformation("{$prefix}_cc_interest_amount", $interestValue);
        $info->setAdditionalInformation("{$prefix}_cc_total_with_interest", $totalOrderWithInterest);
        $info->setAdditionalInformation("{$prefix}_cc_installment_value", $secondCardInstallmentValue);

        $ccId = isset($cardInfo['cc_cid']) ? $cardInfo['cc_cid'] : '';
        $ccId = preg_replace("/[^0-9]/", '', $ccId);

        Mage::unregister("pagseguropayment_twocc_{$prefix}_cc_cid");
        Mage::register("pagseguropayment_twocc_{$prefix}_cc_cid", $ccId);
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function order(Varien_Object $payment, $amount)
    {
        $errors = null;
        $defaultErrorMessage = $this->_getHelper()->__('There was an error processing your request. Please contact us or try again later.');
        try {
            $chargeCc = $this->getChargeCc();
            $chargeCc->setPaymentCode($this->_code);

            $firstCardAmount = $payment->getAdditionalInformation('first_cc_total_with_interest');
            $chargeCc->setPrefix('first_');
            $firstCardData = $chargeCc->cardChargeData($payment, $firstCardAmount);
            $firstCardResponse = $chargeCc->getService()->createCharge($firstCardData);

            if ($firstCardResponse) {
                $responseData = Mage::helper('core')->jsonDecode($firstCardResponse->getBody(), false);

                if (property_exists($responseData, 'id')) {
                    $firstCardChargeId = $responseData->id;
                    $payment->setCcTransId($firstCardChargeId);
                    $payment->setLastTransId($firstCardChargeId);
                    $payment->setCcTransId($firstCardChargeId);
                    $payment->setTransactionId($firstCardChargeId);

                    $payment = $this->setAdditionalInfo($payment, $responseData);
                    $this->saveCard($responseData);

                    if ($this->getHelper()->getIsDeniedState($responseData->status)) {
                        if ($this->getHelper()->getConfig('stop_processing', $this->_code)) {
                            $errors = $this->_getHelper()->__('There were an error charging the first card. Any charge created were automatically canceled');
                            Mage::throwException($errors);
                        }
                        $payment->setSkipOrderProcessing(true);
                    } else {
                        $secondCardAmount = $payment->getAdditionalInformation('second_cc_total_with_interest');
                        $chargeCc->setPrefix('second_');
                        $secondCardData = $chargeCc->cardChargeData($payment, $secondCardAmount);
                        $secondCardResponse = $chargeCc->getService()->createCharge($secondCardData);

                        if ($secondCardResponse) {
                            $responseData = Mage::helper('core')->jsonDecode($secondCardResponse->getBody(), false);
                            if (property_exists($responseData, 'id')) {
                                $this->saveCard($responseData);
                                if (!$this->getHelper()->getIsDeniedState($responseData->status)) {
                                    $this->setAdditionalInfo($payment, $responseData, 'second_cc');
                                } else if ($this->getHelper()->getConfig('stop_processing', $this->_code)) {
                                    $chargeCc->getService()->cancelCharge(
                                        $firstCardChargeId,
                                        $this->getChargeCc()->getAmountData($firstCardAmount)
                                    );

                                    $errors = $this->_getHelper()->__('There were an error charging the second card. Any charge created were automatically canceled');
                                    Mage::throwException($errors);
                                }
                            }
                        }
                    }
                } else {
                    Mage::throwException($defaultErrorMessage);
                }
            } else {
                Mage::throwException($defaultErrorMessage);
            }
        } catch (Exception $e) {
            Mage::getSingleton('checkout/session')->getQuote()->setReservedOrderId(null);
            Mage::logException($e);
            $this->getHelper()->log($e->getMessage());
            $exception = $errors ?: $defaultErrorMessage;
            Mage::throwException($exception);
        }

        return $this;
    }

    protected function saveCard($responseData)
    {
        if (
            property_exists($responseData, 'payment_method')
            && property_exists($responseData->payment_method, 'card')
            && property_exists($responseData->payment_method->card, 'store')
        ) {
            /** @var PagSeguro_Payment_Model_Card $card */
            $card = Mage::getModel('pagseguropayment/card');
            $customerId = $this->getPayment()->getOrder()->getCustomerId();
            $card->saveCardByApiResponse($responseData->payment_method->card, $customerId);
        }

    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param object $response
     * @param string $cardName
     * @return Mage_Sales_Model_Order_Payment
     * @throws Mage_Core_Exception
     */
    protected function setAdditionalInfo(Mage_Sales_Model_Order_Payment $payment, $response, $cardName = 'first_cc')
    {
        if (property_exists($response, 'id')) {
            $pagseguroOrderId = $response->id;
            $payment->setAdditionalInformation("{$cardName}_order_id", $pagseguroOrderId);
            $payment->setAdditionalInformation("{$cardName}_transaction_id", $pagseguroOrderId);
        }

        if (property_exists($response, 'status')) {
            $payment->setAdditionalInformation("{$cardName}_status", $response->status);
        }

        if (property_exists($response, 'payment_response')) {
            $payment->setAdditionalInformation("payment_response_code_{$cardName}", $response->payment_response->code);
            $payment->setAdditionalInformation("payment_response_message_{$cardName}", $response->payment_response->message);
            $payment->setAdditionalInformation("payment_response_reference_{$cardName}", $response->payment_response->reference);
        }

        return $payment;
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @return Mage_Payment_Model_Abstract
     * @throws Mage_Core_Exception
     */
    public function capture(Varien_Object $payment, $amount)
    {
        if (!$payment->getAdditionalInformation('recurring_profile')) {
            if (!$amount) {
                Mage::throwException($this->getHelper()->__('There was an error capturing your order at PagSeguro'));
            }

            if ($payment->canCapture()) {
                $firstCardId = $payment->getAdditionalInformation('first_cc_order_id');
                $secondCardId = $payment->getAdditionalInformation('second_cc_order_id');

                $firstCardAmount = $payment->getAdditionalInformation('first_cc_total_with_interest');
                $secondCardAmount = $payment->getAdditionalInformation('second_cc_total_with_interest');

                $response = $this->getChargeCc()
                    ->getService()
                    ->captureCharge($firstCardId, $this->getChargeCc()->getAmountData($firstCardAmount));
                $responseData = json_decode($response->getBody());

                if (
                    property_exists($responseData, 'id')
                    && $responseData->id
                    && $responseData->status == 'PAID'
                ) {
                    $transactionId = $responseData->id;
                    $payment->setAdditionalInformation('first_cc_captured', true);

                    $response = $this->getChargeCc()
                        ->getService()
                        ->captureCharge($secondCardId, $this->getChargeCc()->getAmountData($secondCardAmount));
                    $responseData = json_decode($response->getBody());

                    if (
                        property_exists($responseData, 'id')
                        && $responseData->id
                        && $responseData->status == 'PAID'
                    ) {
                        $payment->setAdditionalInformation('second_cc_captured', true);
                    } else {
                        Mage::throwException($this->getHelper()->__('There was an error capturing second charge at PagSeguro.'));
                    }

                    $payment->setAdditionalInformation('captured', true);
                    $payment->setAdditionalInformation('captured_date', date('Y-m-d'));
                    $payment->setParentTransactionId($transactionId);
                    $payment->save();
                } else {
                    Mage::throwException($this->getHelper()->__('There was an error capturing your order at PagSeguro'));
                }
            }
        }

        return parent::capture($payment, $amount);
    }

    /**
     * @param Varien_Object $payment
     * @param float $amount
     * @return Mage_Payment_Model_Abstract
     * @throws Mage_Core_Exception
     * @throws Zend_Http_Client_Exception
     */
    public function refund(Varien_Object $payment, $amount)
    {
        if (!$payment->getAdditionalInformation('recurring_profile')) {
            if ($payment->canRefund()) {
                $this->cancelOrder($payment, $amount);
            }
        }

        return parent::refund($payment, $amount);
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Mage_Payment_Model_Abstract
     */
    public function cancel(Varien_Object $payment)
    {
        $recurringProfile = $payment->getAdditionalInformation('recurring_profile');
        if (!$recurringProfile && $this->getHelper()->getConfig('allow_refund', $this->_code)) {
            $this->cancelOrder($payment);
        }

        return parent::cancel($payment);
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return bool
     * @throws Mage_Core_Exception
     * @throws Zend_Http_Client_Exception
     */
    protected function cancelOrder($payment, $amount = null)
    {
        $refunded = $payment->getAdditionalInformation('pagseguro_total_refunded');
        if ($refunded) {
            return true;
        }

        $firstCardId = $payment->getAdditionalInformation('first_cc_order_id');
        $secondCardId = $payment->getAdditionalInformation('second_cc_order_id');

        $firstCardAmount = $payment->getAdditionalInformation('first_cc_total_with_interest');
        $secondCardAmount = $payment->getAdditionalInformation('second_cc_total_with_interest');

        $firstCardCanceled = $payment->getAdditionalInformation('first_cc_cancelled');
        $secondCardCanceled = $payment->getAdditionalInformation('second_cc_cancelled');

        if (!$firstCardCanceled) {
            $response = $this->getChargeCc()
                ->getService()
                ->cancelCharge($firstCardId, $this->getChargeCc()->getAmountData($firstCardAmount));
            $responseData = json_decode($response->getBody());

            if (
                property_exists($responseData, 'id')
                && $responseData->id
                && $responseData->status == 'CANCELED' || $responseData->amount->summary->refunded > 0
            ) {
                $payment->setAdditionalInformation('pagseguro_first_cc_total_refunded', $responseData->amount->summary->refunded);
                $payment->setAdditionalInformation('first_cc_cancelled', true);
                $payment->setAdditionalInformation('first_cc_cancelled_date', Mage::getSingleton('core/date')->gmtDate());
                $firstCardCanceled = true;
            } else {
                Mage::throwException($this->getHelper()->__('There was an error canceling first charge at PagSeguro.'));
            }
        }

        if ($firstCardCanceled && !$secondCardCanceled) {
            $response = $this->getChargeCc()
                ->getService()
                ->cancelCharge($secondCardId, $this->getChargeCc()->getAmountData($secondCardAmount));
            $responseData = json_decode($response->getBody());

            if (
                property_exists($responseData, 'id')
                && $responseData->id
                && $responseData->status == 'CANCELED' || $responseData->amount->summary->refunded > 0
            ) {
                $payment->setAdditionalInformation('pagseguro_second_cc_total_refunded', $responseData->amount->summary->refunded);
                $payment->setAdditionalInformation('second_cc_cancelled', true);
                $payment->setAdditionalInformation('second_cc_cancelled_date', Mage::getSingleton('core/date')->gmtDate());
            } else {
                Mage::throwException($this->getHelper()->__('There was an error canceling second charge at PagSeguro.'));
            }
        }

        $payment->save();

        return true;
    }

    /**
     * @return PagSeguro_Payment_Model_Charge_Cc|false|Mage_Core_Model_Abstract|mixed|null
     */
    protected function getChargeCc()
    {
        if (!$this->chargeCc) {
            /** @var PagSeguro_Payment_Model_Charge_Cc $chargeCc */
            $this->chargeCc = Mage::getModel('pagseguropayment/charge_cc');
        }

        return $this->chargeCc;
    }
}
