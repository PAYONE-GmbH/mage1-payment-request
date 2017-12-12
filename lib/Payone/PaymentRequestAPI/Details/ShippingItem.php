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

class Payone_PaymentRequestAPI_Details_ShippingItem extends Payone_PaymentRequestAPI_Details_Item
{
    const TYPE_SHIPPING_OPTIONS = 'shippingOptions';

    /** @var string */
    protected $_id;

    /** @var bool */
    protected $_selected;

    /**
     * Payone_PaymentRequestAPI_Details_ShippingItem constructor.
     *
     * @param        $label
     * @param        $currency
     * @param        $value
     * @param string $id
     * @param bool   $selected
     * @param string $type
     */
    public function __construct($label, $currency, $value, $id, $selected = false, $type = self::TYPE_SHIPPING_OPTIONS)
    {
        $this->_id = $id;
        $this->_selected = $selected;

        parent::__construct($label, $currency, $value, $type);
    }

    /**
     * Build details item
     *
     * @return array
     */
    public function build()
    {
        return [
            'id'       => $this->_id,
            'label'    => $this->_label,
            'amount'   => [
                'currency' => $this->_currency,
                'value'    => $this->_value,
            ],
            'selected' => $this->_selected,
        ];
    }

    /**
     * Get item type
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }
}
