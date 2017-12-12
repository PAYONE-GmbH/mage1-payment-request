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

class Payone_PaymentRequestAPI_Details_Item
{
    const TYPE_TOTAL = 'total';
    const TYPE_DISPLAY_ITEMS = 'displayItems';
    const TYPE_ADDITIONAL_DISPLAY_ITEMS = 'additionalDisplayItems';


    /** @var string */
    protected $_label;

    /** @var array */
    protected $_amount;

    /** @var string */
    protected $_currency;

    /** @var float */
    protected $_value;

    /** @var string: displayItems, total */
    protected $_type;

    /**
     * Payone_PaymentRequestAPI_Details_Item constructor.
     *
     * @param        $label
     * @param        $currency
     * @param        $value
     * @param string $type
     */
    public function __construct($label, $currency, $value, $type = self::TYPE_DISPLAY_ITEMS)
    {
        $this->_label = $label;
        $this->_currency = $currency;
        $this->_value = $value;
        $this->_type = $type;
    }

    /**
     * Build details item
     *
     * @return array
     */
    public function build()
    {
        return [
            'label'  => $this->_label,
            'amount' => [
                'currency' => $this->_currency,
                'value'    => $this->_value,
            ]
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
