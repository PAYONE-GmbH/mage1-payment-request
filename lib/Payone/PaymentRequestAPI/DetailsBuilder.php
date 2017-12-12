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

use Payone_PaymentRequestAPI_Details_Item as DetailsItem;
use Payone_PaymentRequestAPI_Details_ModifiersBuilder as ModifiersBuilder;

class Payone_PaymentRequestAPI_DetailsBuilder
{
    /** @var DetailsItem[] */
    protected $_items = [];

    /** @var  ModifiersBuilder */
    protected $_modifiersBuilder;

    /**
     * @param DetailsItem $item
     */
    public function addItem(DetailsItem $item)
    {
        $this->_items[] = $item;
    }

    /**
     * @param ModifiersBuilder $modifiers
     */
    public function addModifiersBuilder(ModifiersBuilder $modifiers)
    {
        $this->_modifiersBuilder = $modifiers;
    }

    /**
     * Build details data array
     *
     * @return array
     */
    public function build()
    {
        $result = [];

        foreach ($this->_items as $item) {
            if ($item->getType() === DetailsItem::TYPE_TOTAL) {
                $result[$item->getType()] = $item->build();
                continue;
            }

            if (!isset($result[$item->getType()])) {
                $result[$item->getType()] = [];
            }

            $result[$item->getType()][] = $item->build();
        }

        if ($this->_modifiersBuilder && $modifiers = $this->_modifiersBuilder->build()) {
            $result[ModifiersBuilder::IN_DETAILS_FIELD_KEY] = $modifiers;
        }

        return $result;
    }
}
