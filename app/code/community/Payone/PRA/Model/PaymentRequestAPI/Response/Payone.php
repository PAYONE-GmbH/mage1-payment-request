<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU General Public License (GPL 3)
 * that is bundled with this package in the file LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Payone_PRA to newer
 * versions in the future. If you wish to customize Payone_PRA for your
 * needs please refer to magento documentation for more information.
 *
 * @category    Payone
 * @package     Payone_PRA
 * @author      Andrzej Rosiek <service@e3n.de>
 * @copyright   Copyright (c) 2017 (https://e3n.de)
 * @license     http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 */

class Payone_PRA_Model_PaymentRequestAPI_Response_Payone extends Mage_Core_Model_Abstract
    implements Payone_PRA_Model_PaymentRequestAPI_Response_ElementInterface
{
    /** @var Payone_PRA_Helper_Data */
    protected $_helper;

    /** @var array */
    protected $_errors;

    public function _construct()
    {
        $this->_helper = Mage::helper('payone_pra');
        $this->_errors = $this->validate();
    }

    public function validate()
    {
        if (!$this->_errors) {
            $data = $this->getData();
            $this->_errors = [];

            if (!Zend_Validate::is($data, 'NotEmpty') ||
                !Zend_Validate::is($data['config'], 'NotEmpty') ||
                !Zend_Validate::is($data['configId'], 'NotEmpty') ||
                !Zend_Validate::is($data['data'], 'NotEmpty') ||
                !Zend_Validate::is($data['data']['cardholder'], 'NotEmpty') ||
                !Zend_Validate::is($data['data']['cardpan'], 'NotEmpty') ||
                !Zend_Validate::is($data['data']['cardexpiremonth'], 'NotEmpty') ||
                !Zend_Validate::is($data['data']['cardexpiremonth'], 'NotEmpty') ||
                !Zend_Validate::is($data['data']['cardexpireyear'], 'NotEmpty')
            ) {
                $this->_errors[] = $this->_helper->__('Wrong data provided');
            }
        }

        if (!Zend_Validate::is($data['data']['cardpan'], 'CreditCard')) {
            $this->_errors[] = $this->_helper->__('Card number is not valid');
        }

        if (empty($this->_errors)) {
            return true;
        }

        return $this->_errors;
    }

    public function getCardHolder()
    {
        $data = $this->getData('data');

        return $data['cardholder'];
    }

    public function getCardPan()
    {
        $data = $this->getData('data');

        return $data['cardpan'];
    }

    public function getCardExpireMonth()
    {
        $data = $this->getData('data');

        return $data['cardexpiremonth'];
    }

    public function getCardExpireYear()
    {
        $data = $this->getData('data');

        return $data['cardexpireyear'];
    }
}
