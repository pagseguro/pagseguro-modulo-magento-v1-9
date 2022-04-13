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
class PagSeguro_Payment_Model_Charge_Ticket extends PagSeguro_Payment_Model_Charge_Abstract
{
    protected $type = 'BOLETO';

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param double $amount
     * @return object|boolean
     * @throws
     */
    public function ticketChargeData($payment, $amount)
    {
        $this->setOrder($payment->getOrder());
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
        $ticket = new stdClass();
        $daysToAdd = $this->getHelper()->getConfig('days_to_expire', 'pagseguropayment_ticket') ?: 1;
        $ticket->due_date = $this->getHelper()->getDate("+{$daysToAdd} DAYS");
        $ticket->holder = $this->getHolderData($this->getOrder()->getCustomerId());
        $ticket->instruction_lines = $this->getInstructionLines();

        $paymentMethod = new stdClass();
        $paymentMethod->type = $this->type;
        $paymentMethod->boleto = $ticket;

        return $paymentMethod;
    }

    /**
     * @return stdClass
     */
    private function getInstructionLines()
    {
        $instructionLines = new stdClass();
        $instructionLines->line_1 = $this->getHelper()->getConfig('line_one', 'pagseguropayment_ticket');
        $instructionLines->line_2 = $this->getHelper()->getConfig('line_two', 'pagseguropayment_ticket');
        return $instructionLines;
    }

    /**
     * @param $customerId
     * @return stdClass
     */
    private function getHolderData($customerId)
    {
        $customer = Mage::getModel('customer/customer')->load($customerId);
        $holder = new stdClass();
        $holder->name = $customer->getName();
        $holder->tax_id = $this->getHelper()->getTaxIdValue();
        $holder->email = $customer->getEmail();
        $holder->address = $this->getHolderAddress();
        return $holder;
    }

    /**
     * @return stdClass
     */
    private function getHolderAddress()
    {
        $billingAddress = $this->getOrder()->getBillingAddress();
        $address = new stdClass();
        $address->country = $billingAddress->getCountry();
        $address->region = $billingAddress->getRegion();
        $address->region_code = $this->getHelper()->getAddressRegionCode($billingAddress->getRegionCode());
        $address->city = $billingAddress->getCity();
        $address->postal_code = $this->getHelper()->digits($billingAddress->getPostcode());
        $address->street = $this->getHelper()->formattedString($billingAddress->getStreet1());
        $address->number = $billingAddress->getStreet2();
        $address->locality = $billingAddress->getStreet4();
        return $address;
    }
}
