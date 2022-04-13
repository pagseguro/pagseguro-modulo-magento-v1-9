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
class PagSeguro_Payment_Block_Info_Pix extends Mage_Payment_Block_Info
{

  /**
   * Pix Helper
   *
   * @var PagSeguro_Payment_Helper_Data
   */
  protected $helper;

  /**
   * Get the pix template in the construct.
   *
   * @since 1.0.0
   *
   * @return void
   */
  protected function _construct()
  {

    parent::_construct();
    $this->setTemplate('pagseguropayment/info/pix.phtml');
    $this->setModuleName('Mage_Payment');

  } // end _construct;

  /**
   * Get Pagseguro Helper
   *
   * @since 1.0.0
   *
   * @return PagSeguro_Payment_Helper_Data
   */
  protected function getPagseguroHelper()
  {

    if (!$this->helper) {

      $this->helper = Mage::helper('pagseguropayment');

    } // end if;

    return $this->helper;

  } // end getPagseguroHelper;

} // end PagSeguro_Payment_Block_Info_Pix;
