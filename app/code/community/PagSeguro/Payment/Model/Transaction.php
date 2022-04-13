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
 * Class PagSeguro_Payment_Model_Transaction
 * @method PagSeguro_Payment_Model_Resource_Transaction _getResource()
 * @method PagSeguro_Payment_Model_Resource_Transaction getResource()
 * @method PagSeguro_Payment_Model_Transaction setOrderId(int $value)
 * @method int getOrderId()
 * @method PagSeguro_Payment_Model_Transaction setPagseguropaymentId(string $value)
 * @method string getPagseguropaymentId()
 * @method PagSeguro_Payment_Model_Transaction setRequest(string $value)
 * @method string getRequest()
 * @method PagSeguro_Payment_Model_Transaction setResponse(string $value)
 * @method string getResponse()
 * @method PagSeguro_Payment_Model_Transaction setCode(int $value)
 * @method int getCode()
 * @method PagSeguro_Payment_Model_Transaction setCreatedAt(string $value)
 * @method string getCreatedAt()
 */
class PagSeguro_Payment_Model_Transaction extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('pagseguropayment/transaction');
    }

}