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
class PagSeguro_Payment_Model_Order_Pix extends PagSeguro_Payment_Model_Order_Abstract
{

  /**
   * Pix Order Data.
   *
   * @param Mage_Sales_Model_Order_Payment $payment
   * @param double $amount
   * @return object|boolean
   * @throws
   */
  public function pixOrderData($payment, $amount)
  {

    $this->setOrder($payment->getOrder());

    $orderData = new stdClass();

    $orderData->reference_id = $this->getOrder()->getIncrementId();

    $orderData->customer = $this->getCustomerData();

    $orderData->qr_codes = $this->getQrCodeAmount($amount);

    //$orderData->shipping = $this->getShippingData();

    $orderData->notification_urls = [Mage::getUrl('pagseguropayment/notification/orders')];

    return $orderData;

  } // end pixOrderData;

  /**
   * Get Customer data for the order.
   *
   * @since 1.0.0
   *
   * @return object Customer Data
   */
  public function getCustomerData()
  {

    /** @var PagSeguro_Payment_Helper_Data $helper */
    $helper = Mage::helper('pagseguropayment');

    $customerId = $helper->getCurrentCustomer()->getId();

    if ($customerId) {

      $mage_customer = Mage::getModel('customer/customer')->load($customerId);

      $customer = new stdClass();

      $customer->name = $mage_customer->getName();

      $customer->tax_id = $this->getHelper()->getTaxIdValue();

      $customer->email = $mage_customer->getEmail();

      //$customer->phones = $this->getTelephone();

      return $customer;

    } // end if;

  } // end getCustomerData;

  /**
   * Get QR Code Amount
   *
   * @since 1.0.0
   *
   * @param int $amount
   * @return object
   */
  public function getQrCodeAmount($order_amount) {

    $qrCodeAmount = new stdClass();

    $qrCodeAmount->amount = new stdClass();

    $qrCodeAmount->amount->value = (int)($order_amount * 100);

    return array($qrCodeAmount);

  } // end getQrCodeAmount;

  /**
   * Get PIX items data;
   *
   * @since 1.0.0
   *
   * @param int $amount
   * @return object Items data
   */
  public function getItemsData($amount)
  {

    $itemsData = new stdClass();

    $itemsData->reference_id = $this->getOrder()->getIncrementId();

    $itemsData->name = $this->getHelper()->__("Online Purchase - #%s", $this->getOrder()->getIncrementId());

    $itemsData->quantity = 1;

    $itemsData->unit_amount = $amount;

    return $itemsData;

  } // end getItemsData

  /**
   * Get Shipping Address
   *
   * @return object Shipping Address
   */
  public function getShippingData()
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

  } // end getShippingData;

}
