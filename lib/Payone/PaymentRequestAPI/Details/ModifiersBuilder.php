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

use Payone_PaymentRequestAPI_Details_Modifiers_ModifierBuilder as ModifierBuilder;

class Payone_PaymentRequestAPI_Details_ModifiersBuilder
{
    const IN_DETAILS_FIELD_KEY = 'modifiers';

    /** @var ModifierBuilder[] */
    protected $_modifiers = [];


    public function addModifier(ModifierBuilder $modifier)
    {
        $this->_modifiers[] = $modifier;
    }

    public function build()
    {
        $result = [];

        foreach ($this->_modifiers as $modifier) {
            $result[] = $modifier->build();
        }

        return $result;
    }
}
