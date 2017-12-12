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

class Payone_PRA_Model_AddressBuilder extends Mage_Core_Model_Abstract
{
    /**
     * Convert PRA address data to magento address data
     *
     * @param $params
     *
     * @return array
     */
    protected function _buildAddressData($params)
    {
        if (!$this->_validateAddressData($params)) {
            return [];
        }

        $recipient = $params['recipient'] ?: '';
        $addressLine = isset($params['addressLine']) && is_array($params['addressLine']) ?
            implode(' ', $params['addressLine']) : $params['addressLine'];

        return [
            'prefix'                 => '',
            'firstname'              => $this->_getFirstName($recipient),
            'middlename'             => '',
            'lastname'               => $this->_getLastName($recipient),
            'suffix'                 => '',
            'company'                => $params['organization'] ?: '',
            'street'                 => $addressLine ?: '',
            'city'                   => $params['city'] ?: '',
            'country_id'             => $params['country'] ?: '',
            'region'                 => $params['region'] ?: '',
            'postcode'               => $params['postalCode'] ?: '',
            'telephone'              => $params['phone'] ?: '',
            'save_in_address_book'   => 1,
            'collect_shipping_rates' => 1
        ];
    }

    protected function _validateAddressData($params)
    {
        return true;
    }

    /**
     * Get first name from PRA address
     *
     * @param $recipient
     *
     * @return bool|string
     */
    protected function _getFirstName($recipient)
    {
        return substr($recipient, 0, strripos($recipient, " "));
    }

    /**
     * Get last name from PRA address
     *
     * @param $recipient
     *
     * @return bool|string
     */
    protected function _getLastName($recipient)
    {
        return substr(strrchr($recipient, " "), 1);
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param array                  $params
     *
     * @return $this
     */
    public function addShippingAddress($quote, $params)
    {
        $quote->getShippingAddress()
            ->addData($this->_buildAddressData($params))
            ->save();

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param array                  $params
     *
     * @return $this
     */
    public function addBillingAddress($quote, $params)
    {
        $quote->getBillingAddress()
            ->addData($this->_buildAddressData($params))
            ->save();

        return $this;
    }
}
