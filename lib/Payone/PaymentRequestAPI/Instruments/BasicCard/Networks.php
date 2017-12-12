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

class Payone_PaymentRequestAPI_Instruments_BasicCard_Networks
{
    /** @var array */
    protected $_all = ['visa', 'mastercard', 'amex', 'diners', 'jcb', 'discover', 'unionpay'];

    protected $_payoneTypes = [];

    /** @var array */
    protected $_creditCardsTypesMap = [
        Payone_Api_Enum_CreditcardType::VISA            => 'visa',
        Payone_Api_Enum_CreditcardType::MASTERCARD      => 'mastercard',
        Payone_Api_Enum_CreditcardType::AMEX            => 'amex',
        Payone_Api_Enum_CreditcardType::DINERS          => 'diners',
        Payone_Api_Enum_CreditcardType::JCB             => 'jcb',
        Payone_Api_Enum_CreditcardType::DISCOVER        => 'discover',
        Payone_Api_Enum_CreditcardType::CHINA_UNION_PAY => 'unionpay'
    ];

    /**
     * Payone_PaymentRequestAPI_Instruments_BasicCard_Networks constructor.
     *
     * @param array $payoneTypes
     */
    public function __construct($payoneTypes = [])
    {
        $this->_payoneTypes = $payoneTypes;
    }

    /**
     * @return array
     */
    public function getAllowed()
    {
        if (empty($this->_payoneTypes)) {
            return $this->_all;
        }

        $mapped = array_map([$this, '_creditCardsTypeMapper'], $this->_payoneTypes);

        return array_filter($mapped);
    }

    /**
     * @param string $payoneType
     *
     * @return string|null
     */
    protected function _creditCardsTypeMapper($payoneType)
    {
        return isset($this->_creditCardsTypesMap[$payoneType]) ? $this->_creditCardsTypesMap[$payoneType] : null;
    }
}
