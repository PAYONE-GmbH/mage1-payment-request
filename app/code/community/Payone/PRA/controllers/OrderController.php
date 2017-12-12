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
 * Class Payone_PRA_OrderController
 *
 * /payone/order/*
 */
class Payone_PRA_OrderController extends Mage_Core_Controller_Front_Action
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
     * get config to check CC by PayOne API gate
     */
    public function getconfigAction()
    {
        if (!$this->getRequest()->isAjax() || !$this->_helper->getConfig()->isEnabled()) {
            $this->_forward('noRoute');

            return;
        }

        $result = [];
        try {
            $instrument = Mage::getSingleton(
                'payone_pra/paymentRequestAPI_response_instrument',
                json_decode($this->getRequest()->getParam('instrument'), true)
            );

            if (($errors = $instrument->validate()) !== true) {
                Mage::throwException($errors[0]);
            }

            $result['config'] = $this->_getConfig($instrument);
            $result['configId'] = $instrument->getConfigId($this->_cart->getPraQuote());
            $result['data'] = [
                'cardexpiremonth' => $instrument->getExpiryMonth(),
                'cardexpireyear'  => $instrument->getExpiryYear(),
                'cardtype'        => $instrument->getCartOption(),
                'cardholder'      => $instrument->getCardholderName(),
                'cardpan'         => $instrument->getCardNumber()
            ];
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
        } catch (Exception $e) {
            $result['error'] = $this->_helper->__('An error occurred while buying. Please review the log and try again.');
            Mage::logException($e);
        }

        $this->getResponse()->setBody($this->_helper->jsonEncode($result));
    }

    /**
     * Get payone config
     *
     * @param $instrument
     *
     * @return mixed
     */
    protected function _getConfig($instrument)
    {
        $quote = $this->_cart->getPraQuote();
        $payment = $quote->getPayment();
        $payment->importData([
            'method' => $instrument->getPaymentMethodCode($quote)
        ]);
        $quote->save();
        $paymentMethod = $payment->getMethodInstance();
        $formBlock = $this->getLayout()->createBlock('payone_core/payment_method_form_creditcard');
        $formBlock->setData('method', $paymentMethod);

        return $formBlock->getClientApiConfig();
    }

    /**
     * Place order
     *
     * /payone/order/place
     */
    public function placeAction()
    {
        if (!$this->getRequest()->isAjax() || !$this->_helper->getConfig()->isEnabled()) {
            $this->_forward('noRoute');

            return;
        }

        $result = [];
        try {
            $payment = $this->_updatePaymentData();
            $quote = $this->_cart->getPraQuote();

            foreach ($quote->getAllAddresses() as $address) {
                // magento is forcing region_id for germany, it is impossible to get it using PaymentRequestAPI now
                $address->setShouldIgnoreValidation(true);
            }

            $service = Mage::getModel('sales/service_quote', $quote);
            $service->submitAll();

            if ($orderId = $service->getOrder()->getId()) {
                $this->_cart->getCheckoutSession()
                    ->setLastQuoteId($quote->getId())
                    ->setLastSuccessQuoteId($quote->getId());
                $this->_cart->getCheckoutSession()
                    ->setLastOrderId($orderId);
            }

            if ($redirectUrl = $payment->getMethodInstance()->getRedirectUrl()) {
                $result['redirectUrl'] = $redirectUrl;
            }

            $this->_cart->getPraQuote()
                ->setIsActive(false)
                ->save();

            $result['success'] = 1;
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
        } catch (Exception $e) {
            $result['error'] = $this->_helper->__('An error occurred while buying. Please review the log and try again.');
            Mage::logException($e);
        }

        $this->getResponse()->setBody($this->_helper->jsonEncode($result));
    }

    protected function _updatePaymentData()
    {
        /** @var Payone_PRA_Model_PaymentRequestAPI_Response_Payone $payone */
        $payone = Mage::getSingleton(
            'payone_pra/paymentRequestAPI_response_payone',
            json_decode($this->getRequest()->getParam('payone'), true)
        );
        /** @var Payone_PRA_Model_PaymentRequestAPI_Response_Validation $validation */
        $validation = Mage::getSingleton(
            'payone_pra/paymentRequestAPI_response_validation',
            json_decode($this->getRequest()->getParam('validation'), true)
        );
        $quote = $this->_cart->getPraQuote();
        $payment = $quote->getPayment();
        $data = [
            'method'                           => $payment->getMethod(),
            'cc_owner'                         => $payone->getCardHolder(),
            'payone_creditcard_cc_type_select' => $payone->getData('configId') . '_' . $validation->getData('cardtype'),
            'cc_type'                          => $validation->getData('cardtype'),
            'cc_number'                        => $payone->getCardPan(),
            'cc_exp_month'                     => $payone->getCardExpireMonth(),
            'cc_exp_year'                      => $payone->getCardExpireYear(),
            'payone_pseudocardpan'             => $validation->getData('pseudocardpan'),
            'cc_number_enc'                    => $validation->getData('truncatedcardpan'),
            'payone_config_payment_method_id'  => $payone->getData('configId'),
            'payone_config'                    => $this->_helper->jsonEncode($payone->getConfig()),
            'checks'                           => Mage_Payment_Model_Method_Abstract::CHECK_USE_CHECKOUT
                | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_COUNTRY
                | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_CURRENCY
                | Mage_Payment_Model_Method_Abstract::CHECK_ORDER_TOTAL_MIN_MAX
                | Mage_Payment_Model_Method_Abstract::CHECK_ZERO_TOTAL
        ];
        $payment->importData($data);

        $payment->save();
        $quote->save();

        return $payment;
    }
}
