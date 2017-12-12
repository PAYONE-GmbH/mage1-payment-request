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

use Payone_PaymentRequestAPI_Instruments_BasicCard as BasicCardInstrument;
use Payone_PaymentRequestAPI_InstrumentsBuilder as InstrumentsBuilder;
use Payone_PaymentRequestAPI_DetailsBuilder as DetailsBuilder;
use Payone_PaymentRequestAPI_Details_Item as DetailsItem;
use Payone_PaymentRequestAPI_Details_ShippingItem as ShippingItem;
use Payone_PaymentRequestAPI_Details_Error as Error;
use Payone_PaymentRequestAPI_Details_ModifiersBuilder as ModifiersBuilder;
use Payone_PaymentRequestAPI_Details_Modifiers_ModifierBuilder as ModifierBuilder;

class Payone_PRA_Model_PaymentRequestAPI extends Mage_Core_Model_Abstract
{
    /** @var InstrumentsBuilder */
    protected $_instrumentsBuilder;

    /** @var DetailsBuilder */
    protected $_detailsBuilder;

    /** @var DetailsBuilder */
    protected $_modifiersBuilder;

    /** @var Payone_PRA_Helper_Data */
    protected $_helper;

    public function _construct()
    {
        $this->_instrumentsBuilder = new InstrumentsBuilder();
        $this->_detailsBuilder = new DetailsBuilder();
        $this->_modifiersBuilder = new ModifiersBuilder();
        $this->_helper = Mage::helper('payone_pra');
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return $this
     */
    public function addBasicCardInstrument($quote)
    {
        if ($this->_instrumentsBuilder->exists(BasicCardInstrument::class)) {
            return $this;
        }

        $allowedTypes = [];
        $configPaymentMethods = Mage::getSingleton('payone_core/payment_method_creditcard')
            ->getAllConfigsByQuote($quote);

        /** @var Payone_Core_Model_Config_Payment_Method $paymentMethod */
        foreach ($configPaymentMethods as $configPaymentMethod) {
            if ($configPaymentMethod->getEnabled() && !empty($types = $configPaymentMethod->getTypes())) {
                $allowedTypes = array_merge($allowedTypes, $types);
            }
        }

        $basicCardInstrument = new BasicCardInstrument(array_unique($allowedTypes));
        $this->_instrumentsBuilder->addInstruments($basicCardInstrument);

        return $this;
    }

    /**
     * Get all instruments as array
     *
     * @return array
     */
    public function getAllowedInstruments()
    {
        return $this->_instrumentsBuilder->build();
    }

    /**
     * @return array
     */
    public function getDetails()
    {
        return $this->_detailsBuilder->build();
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return $this
     */
    public function addTotalsToDetailsBuilder($quote)
    {
        $totals = $quote->getTotals();

        foreach ($totals as $total) {
            $code = $total->getCode() == 'grand_total' ? DetailsItem::TYPE_TOTAL : DetailsItem::TYPE_DISPLAY_ITEMS;
            $this->_detailsBuilder->addItem(new DetailsItem(
                $this->_helper->__($total->getTitle()),
                $quote->getStoreCurrencyCode(),
                (float)$total->getValue(),
                $code
            ));
        }

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return $this
     */
    public function addShippingToDetailsBuilder($quote)
    {
        $address = $quote->getShippingAddress();
        $shippingMethods = $address->getGroupedAllShippingRates();

        if (empty($shippingMethods)) {
            $quote->setTotalsCollectedFlag(false)
                ->collectTotals()
                ->save();
            $address->collectShippingRates()
                ->save();
            $shippingMethods = $address->getGroupedAllShippingRates();
        }

        if(empty($shippingMethods)){
            $this->_detailsBuilder->addItem(new Error($this->_helper->__('Shipping methods not available')));
        }

        foreach ($shippingMethods as $code => $addressRates) {
            /** @var Mage_Sales_Model_Quote_Address_Rate $rate */
            foreach ($addressRates as $rate) {
                $this->_detailsBuilder->addItem(new ShippingItem(
                    $rate->getCarrierTitle() . ': ' . $rate->getMethodTitle(),
                    $quote->getStoreCurrencyCode(),
                    (float)$rate->getPrice(),
                    $rate->getCode()
                ));
            }
        }

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return $this
     */
    public function addModifiersToDetailsBuilder($quote)
    {
        $paymentMethods = Mage::getSingleton('payone_core/payment_method_creditcard')
            ->getAllConfigsByQuote($quote);

        foreach ($paymentMethods as $paymentMethod) {
            if ($paymentMethod->getEnabled() && !empty($types = $paymentMethod->getTypes())) {
                //see Payone_Core_Block_Payment_Method_Form_Abstract::_calcFeePrice()
                $feeConfig = $paymentMethod->getFeeConfigForQuote($quote);

                if (!is_array($feeConfig) || !isset($feeConfig['fee_config']) || empty($feeConfig['fee_config'])) {
                    continue;
                }

                $price = $feeConfig['fee_config'];

                if (isset($feeConfig['fee_type'][0]) && $feeConfig['fee_type'][0] == 'percent') {
                    $price = $quote->getGrandTotal() * $price / 100;
                }

                if ($price <= 0) {
                    continue;
                }

                $modifier = new ModifierBuilder();
                $modifier->addItem(new DetailsItem(
                    $this->_helper->__('%s handling fee', $paymentMethod->getName()),
                    $quote->getStoreCurrencyCode(),
                    (float)$price,
                    DetailsItem::TYPE_ADDITIONAL_DISPLAY_ITEMS
                ));
                $modifier->addItem(new DetailsItem(
                    $this->_helper->__('Grand Total'),
                    $quote->getStoreCurrencyCode(),
                    (float)($quote->getGrandTotal() + $price),
                    DetailsItem::TYPE_TOTAL
                ));
                $basicCardInstrument = new BasicCardInstrument($types);
                $modifier->addInstrument($basicCardInstrument);

                $this->_modifiersBuilder->addModifier($modifier);
            }
        }

        $this->_detailsBuilder->addModifiersBuilder($this->_modifiersBuilder);

        return $this;
    }
}
