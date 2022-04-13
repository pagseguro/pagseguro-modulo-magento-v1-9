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
class PagSeguro_Payment_Model_Service_Api extends Zend_Service_Abstract
{
  /**
   * @var PagSeguro_Payment_Helper_Data
   */
  protected $helper;

  /**
   * @var string Auth Token
   */
  protected $token = null;

  /**
   * Do API Requests.
   *
   * @since 1.0.0
   *
   * @param array $params
   * @return Zend_Http_Response
   * @throws Zend_Http_Client_Exception
   */
  public function doPostRequest($path, $params = null)
  {

    $requestParams['method'] = Zend_Http_Client::POST;

    $requestParams['body'] = $params;

    $requestParams['path'] = $path;

    return $this->doRequest($requestParams);

  } // end doPostRequest;

  /**
   * @param $params
   * @return Zend_Http_Response
   * @throws Zend_Http_Client_Exception
   */
  public function doGetRequest($path, $params = null)
  {

    $requestParams['method'] = Zend_Http_Client::GET;

    $requestParams['query'] = $params;

    $requestParams['path'] = $path;

    return $this->doRequest($requestParams);

  } // end doGetRequest;

  /**
   * @param $params
   * @return Zend_Http_Response
   * @throws Zend_Http_Client_Exception
   */
  private function doRequest($params)
  {

    $method = null;

    $token = $this->getHelper()->getConfig('token');

    $this->getHttpClient()->resetParameters(true);

    $this->getHttpClient()->setHeaders('Content-Type', 'application/json');

    $this->getHttpClient()->setHeaders('Authorization', 'Bearer ' . $token);

    $this->getHttpClient()->setHeaders('x-api-version', '4.0');

    $this->getHttpClient()->setHeaders('cms-description', 'magentov4-v.' . Mage::getVersion());

    $path = "";

    $method = isset($params['method']) ? $params['method'] : Zend_Http_Client::GET;

    $requestData = null;

    if (isset($params['path'])) {

      $path = $params['path'];

    } // end if;

    if (isset($params['query'])) {

      $this->getHttpClient()->setParameterGet($params['query']);

      $requestData = $params['query'];

    } // end if;

    if (isset($params['post'])) {

      $this->getHttpClient()->setParameterPost($params['post']);

    } // end if;

    if (isset($params['body'])) {

      $this->getHttpClient()->setRawData(json_encode($params['body']), 'UTF-8');

      $requestData = $params['body'];

    } // end if;

    $url = $this->getServiceUrl() . $path;

    $this->getHttpClient()->setUri($url);

    $this->getHttpClient()->setMethod($method);

    $response = $this->getHttpClient()->request();

    if ($this->getHelper()->getConfig('enable_log')) {

      $this->saveRequestLog();

      $this->saveResponseLog();

    } // end if;

    $this->saveTransaction($requestData, $response->getBody(), $response->getStatus());

    return $response;

  } // end doRequest;

  /**
   * Logging Requests sent to Api
   *
   * @since 1.0.0
   *
   * @return void
   */
  public function saveRequestLog()
  {

    $helper = $this->getHelper();

    $helper->log('=====================');

    $helper->log('REQUEST');

    $helper->log($this->getHttpClient()->getLastRequest());

  } // end saveRequestLog;

  /**
   * Logging Response returned from Api
   *
   * @since 1.0.0
   *
   * @return void.
   */
  public function saveResponseLog()
  {

    $helper = $this->getHelper();

    $helper->log('RESPONSE');

    $helper->log($this->getHttpClient()->getLastResponse());

  } // end saveResponseLog;

  /**
   * Save Transaction
   *
   * @since 1.0.0
   *
   * @param $request
   * @param $response
   * @param $code
   * @return void.
   */
  public function saveTransaction($request, $response, $code)
  {
    /**
     * @var PagSeguro_Payment_Helper_Order $helper
     * */
    $helper = Mage::helper('pagseguropayment/order');

    $helper->saveTransaction($request, json_decode($response), '', $code);

  } // end saveTransaction;

  /**
   * Get Service URL.
   *
   * @return void
   */
  protected function getServiceUrl()
  {

    $url = $this->getHelper()->getConfig('api_url');

    if ($this->getHelper()->getConfig('sandbox')) {

      $url = $this->getHelper()->getConfig('sandbox_url');

    } // end if;

    return $url;

  } // end getServiceUrl;

  /**
   * Get Helper object.
   *
   * @since 1.0.0
   *
   * @return PagSeguro_Payment_Helper_Data
   */
  protected function getHelper()
  {

    if (!$this->helper) {

      $this->helper = Mage::helper('pagseguropayment');

    } // end if;

    return $this->helper;

  } // end getHelper;

} // end PagSeguro_Payment_Model_Service_Api;
