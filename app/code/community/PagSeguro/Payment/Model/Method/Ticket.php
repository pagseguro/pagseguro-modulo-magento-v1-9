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
class PagSeguro_Payment_Model_Method_Ticket extends PagSeguro_Payment_Model_Method_Abstract
{
  /**
   * unique internal payment method identifier
   * @var string [a-z0-9_]
   */
  protected $_code = 'pagseguropayment_ticket';

  protected $_canCapturePartial = false;

  protected $_canCaptureOnce = true;

  protected $_canRefundInvoicePartial = false;

  protected $_canReviewPayment = true;

  protected $_canCreateBillingAgreement = false;

  protected $_formBlockType = 'pagseguropayment/form_ticket';

  protected $_infoBlockType = 'pagseguropayment/info_ticket';

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
       * @var PagSeguro_Payment_Model_Charge_Ticket $chargeTicket
       */
      $chargeTicket = Mage::getModel('pagseguropayment/charge_ticket');

      $ticketData = $chargeTicket->ticketChargeData($payment, $amount);

      $response = $chargeTicket->getService()->createCharge($ticketData);

      if ($response) {

        $responseData = Mage::helper('core')->jsonDecode($response->getBody(), false);

        if (property_exists($responseData, 'id')) {

          $payment->setLastTransId($responseData->id);

          $payment = $this->setAdditionalInfo($payment, $responseData);

          if ($this->getHelper()->getIsDeniedState($responseData->status)) {

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
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Mage_Payment_Model_Abstract
     */
    public function cancel(Varien_Object $payment)
    {
        $recurringProfile = $payment->getAdditionalInformation('recurring_profile');
        if (!$recurringProfile && $this->getHelper()->getConfig('allow_refund', 'pagseguropayment_ticket')) {
            $this->cancelOrder($payment);
        }

        return parent::cancel($payment);
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param object $response
     * @return Mage_Sales_Model_Order_Payment|void
     * @throws Mage_Core_Exception
     * @throws exception
     */
    protected function setAdditionalInfo(Mage_Sales_Model_Order_Payment $payment, $response)
    {
        if (property_exists($response, 'links')) {
            foreach ($response->links as $key => $links) {
                if (property_exists($links, 'media')) {
                    $mediaType = explode('/', $links->media)[1];
                    $payment->setAdditionalInformation($mediaType . '_ticket_url', $links->href);
                }
            }
        }

        parent::setAdditionalInfo($payment, $response);
    }
}