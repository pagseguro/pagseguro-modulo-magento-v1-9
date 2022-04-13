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
class PagSeguro_Payment_Model_Method_Pix extends PagSeguro_Payment_Model_Method_Abstract
{
  /**
   * Unique internal payment method identifier.
   *
   * @var string
   */
  protected $_code = 'pagseguropayment_pix';

  protected $_formBlockType = 'pagseguropayment/form_pix';

  protected $_infoBlockType = 'pagseguropayment/info_pix';

  /**
   * @param Mage_Sales_Model_Order_Payment $payment
   * @param float $amount
   * @return $this
   * @throws Mage_Core_Exception
   */
  public function order(Varien_Object $payment, $amount)
  {

    $errors = null;

    $errorMessage = Mage::helper('payment')->__('There was an error processing your request. Please contact us or try again later.');

    try {
      /**
       *  @var PagSeguro_Payment_Model_Order_Pix $orderPix
       */
      $orderPix = Mage::getModel('pagseguropayment/order_pix');

      $pixData = $orderPix->pixOrderData($payment, $amount);

      if (!$pixData) {

        Mage::throwException($errorMessage);

      } // end if;

      $response = $orderPix->getService()->createOrder($pixData);

      if ($response) {

        $responseData = Mage::helper('core')->jsonDecode($response->getBody(), false);

        if (property_exists($responseData, 'id')) {

          $payment->setLastTransId($responseData->id);

          $payment = $this->setAdditionalInfo($payment, $responseData);

          if (!$responseData->qr_codes) {

            if ($this->getHelper()->getConfig('stop_processing')) {

              $errors = $this->getHelper()->__('The transaction wasn\'t authorized by the issuer, please check your data and try again');

              Mage::throwException($errors);

            } // end if;

            $payment->setSkipOrderProcessing(true);

          } // end if;

        } else {

          Mage::throwException($errorMessage);

        } // end if;

      } else {

        Mage::throwException($errorMessage);

      } // end if;

    } catch (Exception $e) {

      Mage::getSingleton('checkout/session')->getQuote()->setReservedOrderId(null);

      Mage::logException($e);

      $this->getHelper()->log($e->getMessage());

      $exception = $errors ?: $errorMessage;

      Mage::throwException($exception);

    } // end try;

    return $this;

  } // end order;

  /**
   * Canel Order.
   *
   * @param Mage_Sales_Model_Order_Payment $payment
   * @return Mage_Payment_Model_Abstract
   */
  public function cancel(Varien_Object $payment)
  {

    $recurringProfile = $payment->getAdditionalInformation('recurring_profile');

    if (!$recurringProfile && $this->getHelper()->getConfig('allow_refund', 'pagseguropayment_pix')) {

      $this->cancelOrder($payment);

    }

    return parent::cancel($payment);

  } // end cancel;

  /**
   * @param Mage_Sales_Model_Order_Payment $payment
   * @param object $response
   * @return Mage_Sales_Model_Order_Payment|void
   * @throws Mage_Core_Exception
   * @throws exception
   */
  protected function setAdditionalInfo(Mage_Sales_Model_Order_Payment $payment, $response)
  {

    if (property_exists($response, 'qr_codes')) {

      foreach ($response->qr_codes as $key => $value) {

        if (property_exists($value, 'links')) {

          foreach ($value->links as $qrcode_key => $qrcode_value) {

            if ($qrcode_value->media === 'image/png') {

              $payment->setAdditionalInformation('qrcode_image', $qrcode_value->href);

            } // end if;

          } // end foreach;

        } // end if;

      } // end foreach;

    } // end if;

    parent::setAdditionalInfo($payment, $response);

  } // end setAdditionalInfo;

} // end PagSeguro_Payment_Model_Method_Pix
