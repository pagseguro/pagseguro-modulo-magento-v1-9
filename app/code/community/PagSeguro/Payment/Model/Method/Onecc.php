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
class PagSeguro_Payment_Model_Method_Onecc
    extends PagSeguro_Payment_Model_Method_Abstract
{
    /**
     * unique internal payment method identifier
     * @var string [a-z0-9_]
     */
    protected $_code = 'pagseguropayment_onecc';
    protected $_canSaveCc = true;


    protected $_canCapturePartial = false;
    protected $_canCaptureOnce = true;
    protected $_canRefundInvoicePartial = false;
    protected $_canReviewPayment = true;
    protected $_canCreateBillingAgreement = false;

    protected $_formBlockType = 'pagseguropayment/form_onecc';
    protected $_infoBlockType = 'pagseguropayment/info_onecc';

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

        $ccCid = preg_replace("/[^0-9]/", '', $data->getCcCid());
        $installments = $data->getInstallments();
        $useSavedCard = $data->getUseSavedCard();
        $grandTotal = $data->getBaseGrandTotal();

        $cpfCnpj = $this->getHelper()->getTaxIdValue();

        if ($useSavedCard) {
            /** @var PagSeguro_Payment_Model_Card $card */
            $card = Mage::getModel('pagseguropayment/card')->load($data->getCcToken());
            $info->setAdditionalInformation('cc_token', $card->getToken());
            $info->setAdditionalInformation('cc_description', $card->getDescription());

            $ccType = $card->getBrand();
        } else {
            $ccType = $data->getCcType();
            $ccNumber = preg_replace("/[^0-9]/", '', $data->getCcNumber());
            $ccExpMonth = str_pad($data->getCcExpMonth(), 2, '0', STR_PAD_LEFT);
            $saveCard = $data->getSaveCard();

            $info->setCcNumber($ccNumber);
            $info->setCcExpMonth($ccExpMonth);
            $info->setCcLast4(substr($ccNumber, -4));
            $info->setCcOwner($data->getCcOwner());
            $info->setCcExpYear($data->getCcExpYear());
            $info->setCcNumberEnc($info->encrypt($ccNumber));

            $info->setAdditionalInformation('cc_encrypted_info', $data->getEncrypted());
            $info->setAdditionalInformation('cc_owner', $data->getCcOwner());
            $info->setAdditionalInformation('cc_number', $ccNumber);
            $info->setAdditionalInformation('cc_exp_month', $ccExpMonth);
            $info->setAdditionalInformation('cc_exp_year', $data->getCcExpYear());

            $info->setAdditionalInformation('cc_token', false);
            $info->setAdditionalInformation('cc_description', false);
            $info->setAdditionalInformation('cc_save_card', $saveCard);
        }

        $info->setAdditionalInformation('cpf_cnpj', $cpfCnpj);
        $interestRate = $this->getHelper()->getConfig('interest_rate', $this->getCode());
        $installmentsWithoutInterest = $this->getHelper()->getConfig('installments_without_interest_rate', $this->getCode());
        if ($installmentsWithoutInterest >= $installments) {
            $interestRate = null;
        }

        $installmentsValue = Mage::helper('pagseguropayment/installment')->getInstallmentValue($grandTotal, $installments);
        if ($installments > 1) {
            $totalOrderWithInterest = $installmentsValue * $installments;
            $interestValue = $totalOrderWithInterest - $grandTotal;
            $info->setAdditionalInformation('cc_interest_amount', $interestValue);
            $info->setAdditionalInformation('cc_total_with_interest', $totalOrderWithInterest);
            $info->setAdditionalInformation('cc_interest_value', $installmentsValue);
        }

        $info->setAdditionalInformation('cc_has_interest', ($interestRate) ? true : false);
        $info->setAdditionalInformation('cc_interest_rate', $interestRate);
        $info->setAdditionalInformation('cc_installment_value', $installmentsValue);
        $info->setAdditionalInformation('cc_installments', $installments);
        $info->setAdditionalInformation('installments', $installments);
        $info->setAdditionalInformation('base_grand_total', $grandTotal);

        $info->setCcType($ccType);
        $info->setCcInstallments($installments);

        Mage::unregister('pagseguropayment_onecc_cid');
        Mage::register('pagseguropayment_onecc_cid', $ccCid);

        return $this;
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
            $chargeData = $chargeCc->cardChargeData($payment, $amount);
            $response = $chargeCc->getService()->createCharge($chargeData);

            if ($response) {
                $responseData = Mage::helper('core')->jsonDecode($response->getBody(), false);

                if (property_exists($responseData, 'id')) {
                    $tid = $responseData->id;
                    $payment->setCcTransId($tid);
                    $payment->setLastTransId($tid);

                    $payment = $this->setAdditionalInfo($payment, $responseData);

                    if ($this->getHelper()->getIsDeniedState($responseData->status)) {
                        if ($this->getHelper()->getConfig('stop_processing', 'pagseguropayment_onecc')) {
                            $errors = $this->getHelper()->__('The transaction wasn\'t authorized by the issuer, please check your data and try again');
                            Mage::throwException($errors);
                        }
                        $payment->setSkipOrderProcessing(true);
                    }

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

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param object $response
     * @return Mage_Sales_Model_Order_Payment
     * @throws Mage_Core_Exception
     */
    protected function setAdditionalInfo(Mage_Sales_Model_Order_Payment $payment, $response)
    {
        if (property_exists($response, 'id')) {
            $pagseguroOrderId = $response->id;
            $payment->setAdditionalInformation('order_id', $pagseguroOrderId);
            $payment->setCcTransId($pagseguroOrderId);
            $payment->setTransactionId($pagseguroOrderId);
            $payment->setAdditionalInformation('transaction_id', $pagseguroOrderId);
        }

        if (property_exists($response, 'status')) {
            $payment->setAdditionalInformation('status', $response->status);
        }

        if (property_exists($response, 'payment_response')) {
            $payment->setAdditionalInformation('payment_response_code', $response->payment_response->code);
            $payment->setAdditionalInformation('payment_response_message', $response->payment_response->message);
            $payment->setAdditionalInformation('payment_response_reference', $response->payment_response->reference);
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
                $pagseguroOrderId = $payment->getAdditionalInformation('order_id');
                $response = $this->getChargeCc()
                    ->getService()
                    ->captureCharge($pagseguroOrderId, $this->getChargeCc()->getAmountData($amount));

                $responseData = json_decode($response->getBody());

                if (
                    property_exists($responseData, 'id')
                    && $responseData->id
                    && $responseData->status == 'PAID'
                ) {
                    $transactionId = $responseData->id;
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
     * @param Varien_Object|Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @return PagSeguro_Payment_Model_Method_Onecc
     * @throws Mage_Core_Exception
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
        if (!$recurringProfile && $this->getHelper()->getConfig('allow_refund', 'pagseguropayment_onecc')) {
            $this->cancelOrder($payment);
        }

        return parent::cancel($payment);
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
