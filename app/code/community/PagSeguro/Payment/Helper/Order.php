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
class PagSeguro_Payment_Helper_Order extends PagSeguro_Payment_Helper_Data
{
    protected $helper;

    /**
     * @param Mage_Sales_Model_Order $order
     * @param null $transactionId
     * @param string $code
     * @param string $message
     * @throws Mage_Core_Exception
     */
    public function createInvoice(Mage_Sales_Model_Order $order, $transactionId = null, $code = 'pagseguropayment_onecc', $message = '')
    {
        if ($order->canInvoice()) {
            /** @var Mage_Sales_Model_Order_Payment $payment */
            $payment = $order->getPayment();
            if (!$transactionId) {
                $transactionId = $payment->getAdditionalInformation('transaction_id');
            }

            /** @var Mage_Sales_Model_Order_Invoice $invoice */
            $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
            $invoice->register();
            $invoice->sendEmail(true);
            $invoice->setEmailSent(true);
            $invoice->getOrder()->setCustomerNoteNotify(true);
            $invoice->getOrder()->setIsInProcess(true);
            $invoice->setTransactionId($transactionId);
            $invoice->setCanVoidFlag(true);

            $payment->setAdditionalInformation('captured', true);
            $payment->setAdditionalInformation('captured_date', Mage::getSingleton('core/date')->gmtDate());

            $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($payment)
                ->addObject($invoice->getOrder());
            $transactionSave->save();

            $this->log('Order: ' . $order->getIncrementId() . " - invoice created");

            $status = $this->getConfig('captured_order_status', $code);
            if ($status) {
                $message = $message ?: $this->__("The payment was confirmed - Transaction ID: %s", (string)$transactionId);
                $order->addStatusHistoryComment($message, $status)->setIsCustomerNotified(true);
                $order->save();
            }
        }
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param $message
     * @throws Exception
     */
    public function cancelOrder($order, $message)
    {
        if ($order->canCancel() && !$order->isCanceled()) {
            $message = $this->__('Payment Canceled. %s', $message);
            $order->addStatusHistoryComment($message, Mage_Sales_Model_Order::STATE_CANCELED)
                ->setIsCustomerNotified(true);

            $order->sendOrderUpdateEmail(true, $message);
            $order->cancel();
            $order->save();
        }
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param stdClass $response
     * @return string
     */
    public function updatePayment($order, $response)
    {
        $message = $this->__("Order synchronized with status <strong>%s</strong>", $response->status);

        try {
            if (property_exists($response, 'status')) {
                $payment = $order->getPayment();
                $currentStatus = $payment->getAdditionalInformation('status');

                if ($payment->getMethod() == 'pagseguropayment_twocc') {
                    $this->updatePaymentTwoCards($payment, $response);
                    return $message;
                }

                if ($currentStatus != $response->status) {
                    if ($response->status == 'PAID') {
                        $this->createInvoice($order);
                        $message = $this->__('Order approved');
                    } else if ($response->status == 'DECLINED' || $response->status == 'CANCELED') {
                        $this->cancelOrder($order, $response->payment_response->message);
                        $message = $this->__('Order cancelled');
                    }

                    $payment->setAdditionalInformation('status', $response->status);
                    $payment->save();
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $message;
    }

    /**
     * @param $payment
     * @param false|object $response
     * @throws Mage_Core_Exception
     */
    public function updatePaymentTwoCards($payment, $response = false)
    {
        $methodCode = 'pagseguropayment_twocc';
        $message = '';
        $status = '';

        $firstCardTid = $payment->getAdditionalInformation('first_cc_transaction_id');
        $secondCardTid = $payment->getAdditionalInformation('second_cc_transaction_id');

        if ($response) {
            /** @var PagSeguro_Payment_Model_Charge_Cc $chargeCc */
            $chargeCc = Mage::getModel('pagseguropayment/charge_cc');

            if ($response->id == $firstCardTid) {
                $firstCardStatus = $response->status;
                $secondCardCharge = $chargeCc->getService()->consultCharge($secondCardTid);
                $secondCardCharge = json_decode($secondCardCharge->getBody());
                $secondCardStatus = $secondCardCharge->status;
            } else {
                $secondCardStatus = $response->status;
                $firstCardCharge = $chargeCc->getService()->consultCharge($firstCardTid);
                $secondCardCharge = json_decode($firstCardCharge->getBody());
                $firstCardStatus = $secondCardCharge->status;
            }

            if (
                $payment->getAdditionalInformation('first_cc_status') == $firstCardStatus
                && $payment->getAdditionalInformation('second_cc_status') == $secondCardStatus
            ) {
                return $this;
            }

            $payment->setAdditionalInformation('first_cc_status', $firstCardStatus);
            $payment->setAdditionalInformation('second_cc_status', $secondCardStatus);
        }

        $firstCardStatus = $payment->getAdditionalInformation('first_cc_status');
        $secondCardStatus = $payment->getAdditionalInformation('second_cc_status');

        $capture = $this->getConfig('capture', $methodCode);

        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();

        if ($capture) {
            if ($firstCardStatus == 'PAID' && $secondCardStatus == 'PAID') {
                $message = $this->__("The payment for first card was captured - Transaction ID: %s", (string)$firstCardTid);
                $message .= "\n" . $this->__("The payment for second was captured - Transaction ID: %s", (string)$secondCardTid);
                $this->createInvoice($order, $firstCardTid, $methodCode, $message);
            } else {
                $message = $this->__('There were an error on payment so the order was canceled');
                if (!$this->getIsDeniedState($firstCardStatus)) {
                    $message .= $this->__("The payment for first card was denied - Transaction ID: %s", (string)$firstCardTid);
                } else {
                    $message .= "\n" . $this->__('The payment for second card was denied - Transaction ID: %s', (string)$secondCardTid);
                }
                $this->cancelOrder($order, $message);
            }
        } else {
            if ($firstCardStatus == 'AUTHORIZED' && $secondCardStatus == 'AUTHORIZED') {
                $status = $this->getConfig('authorized_order_status', $methodCode);
                $message .= $this->__("The payment for first card was authorized - Transaction ID: %s", (string)$firstCardTid);
                $message .= "\n" . $this->__("The payment for second card was authorized - Transaction ID: %s", (string)$firstCardTid);
            } else {
                $message = $this->__('There were an error on payment so the order was canceled');
                if (!$this->getIsDeniedState($firstCardStatus)) {
                    $message .= $this->__("The payment for first card was denied - Transaction ID: %s", (string)$firstCardTid);
                } else {
                    $message .= "\n" . $this->__("The payment for second card was denied - Transaction ID: %s", (string)$firstCardTid);
                }
                $this->cancelOrder($order, $message);
            }
        }

        if ($status) {
            $state = $this->getAssignedState($status);
            $order->setState($state, $status, $message, true);
            $order->save();
        }

        $payment->save();
    }

    /**
     * @param $request
     * @param $response
     * @param boolean $orderId
     * @param boolean $code
     */
    public function saveTransaction($request, $response, $orderId = null, $code = null)
    {
        try {
            $pagseguroPaymentId = null;
            if (is_object($request) || is_array($request)) {
                if (!$orderId && property_exists($request, 'reference_id')) {
                    $orderId = $request->reference_id;
                }

                $request = json_encode($request);
            }

            if (is_object($response) || is_array($response)) {
                $pagseguroPaymentId = property_exists($response, 'id') ? $response->id : null;
                if (!$orderId && property_exists($response, 'reference_id')) {
                    $orderId = $response->reference_id;
                }

                $response = json_encode($response);
            }

            /** @var PagSeguro_Payment_Model_Transaction $transaction */
            $transaction = Mage::getModel('pagseguropayment/transaction');
            $transaction->setOrderId($orderId);
            $transaction->setPagseguropaymentId($pagseguroPaymentId);
            $transaction->setRequest($request);
            $transaction->setResponse($response);
            $transaction->setCode($code);
            $transaction->setCreatedAt(date('Y-m-d H:i:s'));
            $transaction->save();
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Get the assigned state of an order status
     *
     * @param string order_status
     * @return string
     */
    public function getAssignedState($status)
    {
        /** @var Mage_Sales_Model_Resource_Order_Status_Collection $item */
        $item = Mage::getResourceModel('sales/order_status_collection')
            ->joinStates()
            ->addFieldToFilter('main_table.status', $status);

        /** @var Mage_Sales_Model_Order_Status $status */
        $status = $item->getFirstItem();

        return $status->getState();
    }
}
