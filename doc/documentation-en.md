#Documentation
This module implements the Payment Request API (PRA) for the Payone_Core module.

It offers an easy and intuitive checkout implementation for your customers. Customer data like address or payment
information is being saved in your chrome profile, which offers a quick way to proceed the checkout process.

Currently the PRA integration is only supported by the following browsers:

* Chrome 53 and above on Android
* Chrome 61 and above on Desktop & iOS
* Edge 15 and above on Desktop

Currently the PRA integration offers only the following payment methods:

* creditcard

The creditcard settings and the processing will be handled by the Payone_Core module.

## Backend configuration

The module specific configuration is located within the Payone general configuration section.

### Activate Paynow button
This option enables or disables the whole PRA Module.

### Redirect to success page
This option tells the module if the customer will be sent to the usual magento success page or if he stays on the
page where the PRA checkout process started.
