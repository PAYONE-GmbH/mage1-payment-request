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

class Payone_PRA_Model_PaymentRequestAPI_Response_Instrument extends Mage_Core_Model_Abstract
    implements Payone_PRA_Model_PaymentRequestAPI_Response_ElementInterface
{
    /** @var Payone_PRA_Helper_Data */
    protected $_helper;

    /** @var array */
    protected $_creditCardsPatterns = [
        Payone_Api_Enum_CreditcardType::VISA            => '/^4[0-9]{12}(?:[0-9]{3})?$/',
        Payone_Api_Enum_CreditcardType::MASTERCARD      => '/^5[1-5][0-9]{14}$/',
        Payone_Api_Enum_CreditcardType::AMEX            => '/^3[47][0-9]{13}$/',
        Payone_Api_Enum_CreditcardType::DINERS          => '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',
        Payone_Api_Enum_CreditcardType::JCB             => '/^(?:2131|1800|35\d{3})\d{11}$/',
        Payone_Api_Enum_CreditcardType::DISCOVER        => '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
        Payone_Api_Enum_CreditcardType::CHINA_UNION_PAY => '/^(62|88)\d+$/'
    ];

    /** @var string */
    protected $_cartOption;

    /** @var Payone_Core_Model_Payment_Method_Creditcard */
    protected $_paymentMethodConfig;

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
                !Zend_Validate::is($data['details'], 'NotEmpty')
            ) {
                $this->_errors[] = $this->_helper->__('Wrong data provided');
            }

            if (!Zend_Validate::is($data['details']['cardNumber'], 'NotEmpty')) {
                $this->_errors[] = $this->_helper->__('Card number is required');
            }

            if (!$this->getCartOption() || !Zend_Validate::is($data['details']['cardNumber'], 'CreditCard')) {
                $this->_errors[] = $this->_helper->__('Card number is not valid');
            }

            if (!Zend_Validate::is($data['details']['expiryMonth'], 'NotEmpty')) {
                $this->_errors[] = $this->_helper->__('Card number is required');
            }

            if (!Zend_Validate::is($data['details']['expiryYear'], 'NotEmpty')) {
                $this->_errors[] = $this->_helper->__('Card number is required');
            }
        }


        if (empty($this->_errors)) {
            return true;
        }

        return $this->_errors;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return string
     */
    public function getPaymentMethodCode($quote)
    {
        $paymentMethodConfig = $this->getPaymentMethodConfig($quote);

        return Payone_Core_Model_System_Config_PaymentMethodCode::PREFIX . $paymentMethodConfig->getCode();
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return int
     */
    public function getConfigId($quote)
    {
        $paymentMethodConfig = $this->getPaymentMethodConfig($quote);

        return $paymentMethodConfig->getId();
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return Payone_Core_Model_Config_Payment_Method
     */
    public function getPaymentMethodConfig($quote)
    {
        if (!$this->_paymentMethodConfig) {
            $cartOption = $this->getCartOption();
            /** @var Payone_Core_Model_Payment_Method_Creditcard $paymentMethods */
            $paymentMethods = Mage::getSingleton('payone_core/payment_method_creditcard')
                ->getAllConfigsByQuote($quote);

            /** @var Payone_Core_Model_Config_Payment_Method $paymentMethod */
            foreach ($paymentMethods as $paymentMethod) {
                $types = $paymentMethod->getTypes();
                if ($paymentMethod->getEnabled() &&
                    !empty($types) &&
                    in_array($cartOption, $types)
                ) {
                    $this->_paymentMethodConfig = $paymentMethod;

                    return $this->_paymentMethodConfig;
                }
            }
        }

        return $this->_paymentMethodConfig;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return $this
     */
    public function addBillingAddress($quote)
    {
        $details = $this->getData('details');

        if (isset($details['billingAddress'])) {
            Mage::getSingleton('payone_pra/addressBuilder')
                ->addBillingAddress($quote, $details['billingAddress']);
        }

        return $this;
    }

    /**
     * @return bool|string
     */
    public function getCartOption()
    {
        if ($this->_cartOption === null) {
            $cardNumber = $this->getCardNumber();
            $this->_cartOption = false;

            foreach ($this->_creditCardsPatterns as $payoneCardOption => $creditCardsPattern) {
                if (preg_match($creditCardsPattern, $cardNumber)) {
                    $this->_cartOption = $payoneCardOption;
                    break;
                }
            }
        }

        return $this->_cartOption;
    }

    public function getCardNumber()
    {
        $details = $this->getDetails();

        return $details['cardNumber'];
    }

    public function getExpiryMonth()
    {
        $details = $this->getDetails();

        return $details['expiryMonth'];
    }

    public function getExpiryYear()
    {
        $details = $this->getDetails();

        return $details['expiryYear'];
    }

    public function getCardholderName()
    {
        $details = $this->getDetails();

        return $details['cardholderName'];
    }
}
