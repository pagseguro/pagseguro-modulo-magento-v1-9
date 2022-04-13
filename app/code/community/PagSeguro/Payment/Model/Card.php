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

/**
 * Class PagSeguro_Payment_Model_Card
 * @method PagSeguro_Payment_Model_Resource_Card _getResource()
 * @method PagSeguro_Payment_Model_Resource_Card getResource()
 * @method PagSeguro_Payment_Model_Card setCustomerId(int $value)
 * @method int getCustomerId()
 * @method PagSeguro_Payment_Model_Card setToken(string $value)
 * @method string getToken()
 * @method PagSeguro_Payment_Model_Card setDescription(string $value)
 * @method string getDescription()
 * @method PagSeguro_Payment_Model_Card setCreatedAt(string $value)
 * @method string getCreatedAt()
 * @method PagSeguro_Payment_Model_Card setUpdatedAt(string $value)
 * @method string getUpdatedAt()
 */
class PagSeguro_Payment_Model_Card extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('pagseguropayment/card');
    }

    public function getCardsByCustomerId($customerId)
    {
        /** @var PagSeguro_Payment_Model_Resource_Card_Collection $collection */
        $collection = $this->getResourceCollection()
            ->addFieldToFilter('customer_id', $customerId);

        return $collection;
    }

    public function saveCardByApiResponse($response, $customerId)
    {
        $this->setCustomerId($customerId);
        $this->setToken($response->id);
        $this->setDescription("{$response->brand} - {$response->first_digits}****{$response->last_digits}");
        $this->setCreatedAt(date('Y-m-d H:i:s'));
        $this->setUpdatedAt(date('Y-m-d H:i:s'));
        $this->save();
    }
}