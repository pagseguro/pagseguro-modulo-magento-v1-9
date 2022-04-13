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
class PagSeguro_Payment_Helper_Data extends Mage_Core_Helper_Data
{

  const LOG_FILE = 'pagseguropayment.log';

  protected $transactionStates = array(
    'AUTHORIZED' => array(
      'type' => 'authorized',
      'title' => 'Authorized',
      'description' => 'Order authorized.'
    ),
    'WAITING' => array(
      'type' => 'pending',
      'title' => 'Waiting Payment',
      'description' => 'Order waiting payment.'
    ),
    'PAID' => array(
      'type' => 'paid',
      'title' => 'Paid',
      'description' => 'Order paid.'
    ),
    'CANCELED' => array(
      'type' => 'not_approved',
      'title' => 'Payment Canceled',
      'description' => 'Order payment canceled.'
    ),
    'DECLINED' => array(
      'type' => 'not_approved',
      'title' => 'Payment Declined',
      'description' => 'Order payment declined.'
    ),
  );

  /**
   * Get Methods.
   *
   * @param string $paymentMethod
   * @return void
   */
  public function getMethods($paymentMethod = 'pagseguropayment_onecc')
  {
    /**
     * @var PagSeguro_Payment_Model_Source_CcType $methods
     */
    $brands = Mage::getModel('pagseguropayment/source_ccType')->toOptionArray();

    $allowedBrands = $this->getConfig('allowed_brands', $paymentMethod);

    $allowedBrands = explode(',', $allowedBrands);

    $result = [];

    foreach ($brands as $key => $method) {

      if (in_array($method['value'], $allowedBrands)) {

        array_push(
          $result,
          array(
            'value' => $method['value'],
            'label' => $method['label'],
            'slug' => $this->slugify($method['label'])
          )
        );

      } // end if;

    } // end foreach;

    return $result;

  } // end getMethods;

  /**
   * Check if a method is avaialable.
   *
   * @param string $code
   * @param int $grandTotal
   * @param integer $multiplier
   * @return void
   */
  public function getMethodIsAvailable($code, $grandTotal, $multiplier = 1)
  {

    $minInstallment = $this->getConfig('minimum_installments_value', $code) * $multiplier;

    $isAvailable = true;

    if ($minInstallment && $minInstallment > $grandTotal) {

      $response['min_value'] = $minInstallment;

      $isAvailable = false;

    } // end if;

    $response['is_available'] = $isAvailable;

    return $response;

  } // end getMethodIsAvailable;

  /**
   * Get document from Customer;
   *
   * @since 1.0.0
   *
   * @param null $customer
   * @return mixed
   * @throws Mage_Core_Model_Store_Exception
   */
  public function getTaxIdValue($customer = null)
  {

    $customer = $customer ? $customer : $this->getSession()->getQuote()->getCustomer();

    $documentValue = null;

    $personTypeAttribute = $this->getConfig('person_type_attribute');

    $businessPersonValue = $this->getConfig('business_person_attribute_value');

    if ($personTypeAttribute && $customer->getData($personTypeAttribute) == $businessPersonValue) {

      $cnpjAttribute = $this->getConfig('cnpj_customer_attribute');

      $documentValue = $customer->getData($cnpjAttribute);

    } else {

      $taxvatValue = $this->getConfig('taxvat_customer_attribute');

      $documentValue = $customer->getData($taxvatValue);

    } // end if;

    return $this->digits($documentValue);

  } // end getTaxIdValue;

    /**
     * Receive a transaction state string code and return the state number
     *
     * @param $searchState
     * @return null|string
     */
    public function getIsDeniedState($searchState)
    {
        foreach ($this->transactionStates as $code => $state) {
            if ($code == $searchState) {
                if ($state['type'] == 'not_approved') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param $region
     * @return mixed|string
     */
    public function getAddressRegionCode($region)
    {
        if (strlen($region) > 2) {
            $stateName = explode(' ', $region);
            $firstLetter = isset($stateName[0][0]) ? $stateName[0][0] : '';
            $secondLetter = isset(end($stateName)[0]) ? end($stateName)[0] : '';
            $region = strtoupper($firstLetter . $secondLetter);
        }

        return $region;
    }

    public function encryptRequestAuthentication($payload)
    {
        $token = $this->getConfig('token');
        return hash('sha256', $token . '-' . $payload);
    }

    /**
     * Slugify string
     *
     * @param string $phrase
     * @return string
     */
    public function slugify($str)
    {
        $str = Mage::helper('core')->removeAccents($str);
        $urlKey = preg_replace('#[^0-9a-z+]+#i', '-', $str);
        $urlKey = strtolower($urlKey);
        $urlKey = trim($urlKey, '-');
        return $urlKey;
    }

    /**
     * @param $string
     * @return mixed
     */
    public function digits($string)
    {
        return preg_replace('/[^0-9]/', '', $string);
    }

    /**
     * @param $string
     * @return string|string[]|null
     */
    public function formattedString($string)
    {
        return preg_replace('/[^A-Za-z0-9\-]/', '', $string);
    }

    /**
     * @return string
     */
    public function getCardImagePath()
    {
        return Mage::getBaseUrl('js') . 'pagseguropayment' . DS . 'cards' . DS;
    }

    /**
     * @param $date
     * @param string $format
     * @return string
     * @throws Exception
     */
    public function getDate($date, $format = 'Y-m-d')
    {
        $date = new DateTime($date);
        return $date->format($format);
    }

    /**
     * Get Endpoint
     * @param $endpoint string
     * @return mixed
     */
    public function getEndpoint($endpoint, $id = null)
    {
        $fullEndpoint = Mage::getStoreConfig('pagseguropayment/endpoints/' . $endpoint);
        $url = str_replace(
            ['{{id}}'],
            [$id],
            $fullEndpoint
        );
        return $url;
    }


  /**
   * Get Current Customer.
   *
   * @return Mage_Customer_Model_Customer|null
   * @throws Mage_Core_Model_Store_Exception
   */
  public function getCurrentCustomer()
  {

    /**
     *  @var Mage_Customer_Model_Session $customerSession
     */
    $customerSession = Mage::getSingleton('customer/session');

    if ($customerSession->getCustomer()) {

      $customer = $customerSession->getCustomer();

    } else {

      $customer = $this->getSession()->getCustomer();

    } // end if;

    return $customer ? $customer : Mage::getModel('customer/customer');

  } // end getCurrentCustomer;

  /**
   * Get Session
   *
   * @return Mage_Core_Model_Abstract|Mage_Checkout_Model_Session|Mage_Adminhtml_Model_Session_Quote
   * @throws Mage_Core_Model_Store_Exception
   */
  public function getSession()
  {

    if (Mage::app()->getStore()->isAdmin()) {

      return Mage::getSingleton('adminhtml/session_quote');

    } // end if;

    return Mage::getSingleton('checkout/session');

  } // end getSession;

  /**
   * Get the Pagseguro_Payment Config
   *
   * @since 1.0.0
   *
   * @param $config
   * @param string $path
   * @return mixed
   */
  public function getConfig($config, $path = 'pagseguropayment_settings')
  {

    return Mage::getStoreConfig('payment/' . $path . '/' . $config);

  } // end getConfig

  /**
   * Get Public Key
   *
   * @since 1.0.0
   *
   * @return void
   */
  public function getPublicKey()
  {

    $publicKey = $this->getConfig('public_key');

    if (!$publicKey) {

      /**
       * @var PagSeguro_Payment_Model_Service_Authentication $authenticationService
       */
      $authenticationService = Mage::getModel('pagseguropayment/service_authentication');

      $response = $authenticationService->validateToken();

      if ($response || $response->getStatus() == 200) {

        $information = json_decode($response->getBody());

        $publicKey = $information->public_key;

        /**
         * @var Mage_Core_Model_Config $coreConfig
         */
        $coreConfig = Mage::getModel('core/config');

        $coreConfig->saveConfig('payment/pagseguropayment_settings/public_key', $publicKey);

      }

    } // end if;

    return $publicKey;

  } // end getPublicKey;

    /**
     * Log the message
     * @param string $message
     * @param string $file
     */
    public function log($message, $file = null)
    {
        $file = ($file) ? $file : self::LOG_FILE;
        Mage::log($message, Zend_Log::INFO, $file);
    }
}