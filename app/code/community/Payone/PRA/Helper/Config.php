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

class Payone_PRA_Helper_Config extends Payone_PRA_Helper_Data
{
    const XPATH_PAY_NOW_IS_ACTIVE = 'payone_general/pra/paynow_active';
    const XPATH_PAY_NOW_SUCCESS_REDIRECT = 'payone_general/pra/success_redirect';

    /**
     * Check if paynow functionality is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return Mage::getStoreConfig(self::XPATH_PAY_NOW_IS_ACTIVE) && $this->isPayOneCoreCCEnabled();
    }

    /**
     * Check if any PayOne-CC payment method is enabled
     *
     * @return bool
     */
    protected function isPayOneCoreCCEnabled()
    {
        /** @var Payone_Core_Helper_Config $helper */
        $helper = Mage::helper('payone_core/config');
        $paymentMethods = $helper->getConfigPayment()->getMethods();

        if (!is_array($paymentMethods) ||
            empty($paymentMethods) ||
            !class_exists(Payone_Core_Model_System_Config_PaymentMethodCode::class)
        ) {
            return false;
        }

        $prefix = Payone_Core_Model_System_Config_PaymentMethodCode::PREFIX;
        $ccMethodCode = Payone_Core_Model_System_Config_PaymentMethodCode::CREDITCARD;

        foreach ($paymentMethods as $paymentMethod) {
            if ($prefix . $paymentMethod->getCode() == $ccMethodCode &&
                $paymentMethod->getEnabled()
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if PRA should redirect to success page after order placed.
     *
     * @return mixed
     */
    public function shouldRedirectToSuccessPage()
    {
        return Mage::getStoreConfig(self::XPATH_PAY_NOW_SUCCESS_REDIRECT);
    }
}
