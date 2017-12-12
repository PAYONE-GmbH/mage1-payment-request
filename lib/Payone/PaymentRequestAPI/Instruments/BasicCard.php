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

use Payone_PaymentRequestAPI_Instruments_BasicCard_Networks as Networks;
use Payone_PaymentRequestAPI_Instruments_InstrumentInterface as InstrumentInterface;

class Payone_PaymentRequestAPI_Instruments_BasicCard implements InstrumentInterface
{
    protected $_supportedMethods = ['basic-card'];

    /** @var Networks */
    protected $_networks;

    public function __construct(array $payoneTypes = [])
    {
        $this->_networks = new Networks($payoneTypes);
    }

    /**
     * @return array
     */
    public function build()
    {
        return [
            'supportedMethods' => $this->_supportedMethods,
            'data'             => [
                'supportedNetworks' => $this->_networks->getAllowed()
            ]
        ];
    }
}
