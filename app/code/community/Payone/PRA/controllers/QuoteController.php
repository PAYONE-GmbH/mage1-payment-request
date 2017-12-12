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

/**
 * Class Payone_PRA_QuoteController
 *
 * route /payone/quote/*
 */
class Payone_PRA_QuoteController extends Mage_Core_Controller_Front_Action
{
    /** @var Payone_PRA_Helper_Data */
    protected $_helper;

    /** @var Payone_PRA_Model_Cart */
    protected $_cart;

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_helper = Mage::helper('payone_pra');
        $this->_cart = $this->_helper->getPraCart();
    }

    /**
     * @inheritdoc
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $this->getResponse()->clearHeaders()->setHeader(
            'Content-type',
            'application/json'
        );
    }

    /**
     * Check if items in cart
     *
     * /payone/quote/check
     */
    public function checkAction()
    {
        if (!$this->getRequest()->isAjax() || !$this->_helper->getConfig()->isEnabled()) {
            $this->_forward('noRoute');

            return;
        }

        $result = [];

        try {
            $result['quoteHasItems'] = $this->_cart->cartHasItems();
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
        } catch (Exception $e) {
            $result['error'] = $this->_helper->__('An error occurred while buying. Please review the log and try again.');
            Mage::logException($e);
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    /**
     * Create new Quote for single product.
     *
     * /payone/quote/details
     */
    public function detailsAction()
    {
        $productId = $this->getRequest()->getParam('productId');

        if (!$this->getRequest()->isAjax() ||
            !$this->_helper->getConfig()->isEnabled()
        ) {
            $this->_forward('noRoute');

            return;
        }

        $product = false;

        if ($productId && is_numeric($productId)) {
            /** @var Mage_Catalog_Model_Product $product */
            $product = Mage::getModel('catalog/product')
                ->setStoreId($this->_cart->getCheckoutSession()->getQuote()->getStoreId())
                ->load($productId);

            if (!$product->getId()) {
                $this->_forward('noRoute');

                return;
            }
        }

        $result = [];

        try {
            $separated = $this->getRequest()->getParam('separated');
            $this->_cart->setQuoteId();//reset used quote
            $quote = $this->_cart->getQuote();

            if (!$quote->getId()) {
                $this->_cart->init();
                $quote = $this->_cart->getQuote();
                $quote->save();
                $separated = false;//do not create fresh quote any more
            }

            if ($separated) {
                $quote = $this->_cart->getPraQuote(true);
            }

            if ($product && $product->getId()) {
                $this->_cart->addProductToQuote(
                    $quote,
                    $product,
                    $this->getRequest()->getParam('qty') ?: 1,
                    $this->_helper->jsonDecode($this->getRequest()->getParam('superAttributes'))
                );
            }

            $result['instruments'] = Mage::getSingleton('payone_pra/paymentRequestAPI')
                ->addBasicCardInstrument($this->_cart->getPraQuote())
                ->getAllowedInstruments();
            $result['details'] = Mage::getSingleton('payone_pra/paymentRequestAPI')
                ->addTotalsToDetailsBuilder($this->_cart->getPraQuote())
                ->getDetails();
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
        } catch (Exception $e) {
            $result['error'] = $this->_helper->__('An error occurred while buying. Please review the log and try again.');
            Mage::logException($e);
        }

        $this->getResponse()->setBody($this->_helper->jsonEncode($result));
    }

    /**
     * Get shipping method based on address data
     *
     * /payone/quote/shipping
     */
    public function shippingAction()
    {
        if (!$this->getRequest()->isAjax() ||
            !$this->_helper->getConfig()->isEnabled()
        ) {
            $this->_forward('noRoute');

            return;
        }

        $result = [];

        try {
            $params = $this->getRequest()->getParams();
            Mage::getSingleton('payone_pra/addressBuilder')
                ->addShippingAddress($this->_cart->getPraQuote(), $params)
                ->addBillingAddress($this->_cart->getPraQuote(), $params);
            $result['details'] = Mage::getSingleton('payone_pra/paymentRequestAPI')
                ->addTotalsToDetailsBuilder($this->_cart->getPraQuote())
                ->addShippingToDetailsBuilder($this->_cart->getPraQuote())
                ->getDetails();
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
        } catch (Exception $e) {
            $result['error'] = $this->_helper->__('An error occurred while buying. Please review the log and try again.');
            Mage::logException($e);
        }

        $this->getResponse()->setBody($this->_helper->jsonEncode($result));
    }

    public function setshippingAction()
    {
        $shippingCode = $this->getRequest()->getParams('shipping_code');
        if (!$this->getRequest()->isAjax() ||
            !$this->_helper->getConfig()->isEnabled() ||
            !$shippingCode
        ) {
            $this->_forward('noRoute');

            return;
        }

        $result = [];

        try {
            $shippingCode = $this->getRequest()->getParam('shipping_code');
            $this->_cart->setShippingMethod($shippingCode);
            $result['details'] = Mage::getSingleton('payone_pra/paymentRequestAPI')
                ->addTotalsToDetailsBuilder($this->_cart->getPraQuote())
                ->addModifiersToDetailsBuilder($this->_cart->getPraQuote())
                ->getDetails();
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
        } catch (Exception $e) {
            $result['error'] = $this->_helper->__('An error occurred while buying. Please review the log and try again.');
            Mage::logException($e);
        }

        $this->getResponse()->setBody($this->_helper->jsonEncode($result));
    }
}
