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

class Payone_PRA_Model_Cart extends Mage_Checkout_Model_Cart
{
    const PAYONE_PRA_QUOTE_ID_IN_SESSION_KEY = 'PAYONE_PRA_GPQuoteID';

    /** @var Mage_Sales_Model_Quote */
    protected $_praQuote;

    /**
     * Check if active quote has items.
     *
     * @return bool
     */
    public function cartHasItems()
    {
        return (bool)$this->getItemsCount();
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function createNewQuote()
    {
        $quote = Mage::getModel('sales/quote');
        $quote->merge($this->getQuote());

        $quote->setStoreId($this->getCheckoutSession()->getQuote()->getStoreId())
            ->setIsActive(false)
            ->setIsMultiShipping(false)
            ->removeAllItems()
            ->save();

        return $quote;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param                        $product
     * @param                        $qty
     * @param                        $superAttributes
     */
    public function addProductToQuote($quote, $product, $qty, $superAttributes)
    {
        $params = $qty;
        if (!empty($superAttributes)) {
            $params = new Varien_Object(
                [
                    'product'         => $product->getId(),
                    'super_attribute' => $superAttributes,
                    'qty'             => $qty
                ]
            );
        }

        /** @var Mage_Sales_Model_Quote_Item $item */
        $item = $quote->addProduct($product, $params);
        $item->save();
        $quote->addItem($item);
        $quote->collectTotals()->save();
        $this->setQuoteId($quote->getId());//save current quote id in session
    }

    /**
     * Quote with products to be paid.
     *
     * @param null $quoteId
     *
     * @return $this
     */
    public function setQuoteId($quoteId = null)
    {
        if ($quoteId === null &&
            $existingQuoteId = $this->getCheckoutSession()
                ->getData(
                    self::PAYONE_PRA_QUOTE_ID_IN_SESSION_KEY
                )
        ) {
            $quote = Mage::getModel('sales/quote')->load($existingQuoteId);
            $quote->delete();
        }

        $this->getCheckoutSession()
            ->setData(
                self::PAYONE_PRA_QUOTE_ID_IN_SESSION_KEY,
                $quoteId
            );

        return $this;
    }

    /**
     * Update shipping method for magento quote
     *
     * @param $shippingCode
     */
    public function setShippingMethod($shippingCode)
    {
        $quote = $this->getPraQuote();
        $shippingAddress = $quote->getShippingAddress()
            ->setShippingMethod($shippingCode)
            ->save();
        $shippingAddress->getQuote()
            ->setTotalsCollectedFlag(false)
            ->collectTotals()
            ->save();
        $shippingAddress->save();
        $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates()
            ->getShippingRatesCollection()
            ->save();
        $quote->collectTotals()->save();
    }

    /**
     * Get current quote for PRA or create if necessary
     *
     * @param bool $forceCreate
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getPraQuote($forceCreate = false)
    {
        if ($forceCreate) {
            $this->_praQuote = $this->createNewQuote();
        }

        if (is_null($this->_praQuote)) {
            $quoteId = $this->getCheckoutSession()
                ->getData(self::PAYONE_PRA_QUOTE_ID_IN_SESSION_KEY);

            if (!$quoteId) {
                $quoteId = $this->getQuote()->getId();
            }

            if ($quoteId) {
                $this->_praQuote = Mage::getModel('sales/quote')->load($quoteId);
            }

            if (!$quoteId || !$this->_praQuote || !$this->_praQuote->getId()) {
                $this->_praQuote = $this->createNewQuote();
            }
        }

        return $this->_praQuote;
    }
}
