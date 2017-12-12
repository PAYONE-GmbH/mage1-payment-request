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

class Payone_PRA_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Get active quote
     *
     * @return Payone_PRA_Model_Cart
     */
    public function getPraCart()
    {
        return Mage::getSingleton('payone_pra/cart');
    }

    public function getConfig()
    {
        return Mage::helper('payone_pra/config');
    }

    public function jsonEncode($result)
    {
        return Mage::helper('core')->jsonEncode($result);
    }

    public function jsonDecode($result)
    {
        return Mage::helper('core')->jsonDecode($result);
    }
}
