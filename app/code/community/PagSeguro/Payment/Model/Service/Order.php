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
class PagSeguro_Payment_Model_Service_Order extends PagSeguro_Payment_Model_Service_Api
{
  /**
   * Make the request to create the charge.
   *
   * @since 1.0.0
   *
   * @param array   $chargeData
   * @return object Zend_Http_Response
   * @throws Zend_Http_Client_Exception
   */
  public function createCharge($chargeData)
  {

    $path = $this->getHelper()->getEndpoint('charge');

    return $this->doPostRequest($path, $chargeData);

  } // end createCharge

  /**
   * Make the request to create the order.
   *
   * @since 1.0.0
   *
   * @param array   $chargeData
   * @return object Zend_Http_Response
   * @throws Zend_Http_Client_Exception
   */
  public function createOrder($orderData)
  {

    $path = $this->getHelper()->getEndpoint('orders');

    return $this->doPostRequest($path, $orderData);

  } // end createOrder;

  /**
   * Do a consult on a charge.
   *
   * @since 1.0.0
   *
   * @param $chargeId
   * @return Zend_Http_Response
   * @throws Zend_Http_Client_Exception
   */
  public function consultCharge($chargeId)
  {

    $path = $this->getHelper()->getEndpoint('consult', $chargeId);

    return $this->doGetRequest($path);

  } // end consultCharge;

  /**
   * Cancels a charge.
   *
   * @param $chargeId
   * @param $data
   * @return Zend_Http_Response
   * @throws Zend_Http_Client_Exception
   */
  public function cancelCharge($chargeId, $data)
  {

    $path = $this->getHelper()->getEndpoint('cancel', $chargeId);

    return $this->doPostRequest($path, $data);

  } // end cancelCharge;

  /**
   * Captures a charge.
   *
   * @since 1.0.0
   *
   * @param $chargeId
   * @param $data
   * @return Zend_Http_Response
   * @throws Zend_Http_Client_Exception
   */
  public function captureCharge($chargeId, $data)
  {

    $path = $this->getHelper()->getEndpoint('capture', $chargeId);

    return $this->doPostRequest($path, $data);

  } // end captureCharge;

} // end PagSeguro_Payment_Model_Service_Order;
