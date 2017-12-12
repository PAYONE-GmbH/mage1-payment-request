var PayonePRA = Class.create();

PayonePRA.prototype = {
    options: {
        quoteCheckUrl: "",
        collectDetailsUrl: "",
        collectShippingUrl: "",
        setShippingUrl: "",
        productId: false,
        productType: false,
        successUrl: '',
        shouldRedirect: false,
        refreshAfterSuccess: false
    },
    praRequest: null,
    details: {},
    instruments: {},
    payonePaymentResponse: {},
    instrumentResponse: {},

    //init method
    initialize: function(options) {
        this.options = options;
    },

    //start payment process with question
    buyWithModal: function() {
        this.options.refreshAfterSuccess = false;
        var productAddToCartFormTemp = new VarienForm('product_addtocart_form');
        if (!productAddToCartFormTemp || !productAddToCartFormTemp.validator.validate()) {
            return;
        }
        this.ajax(this.options.quoteCheckUrl, {})
            .then(function(oXHR) {
                var result = this.checkAjax(oXHR);
                this.showModal(result);
            }.bind(this))
            .catch(this.cancelProcess.bind(this));
    },

    //init PRA modal without any dialog/question
    buyFromCart: function() {
        this.options.refreshAfterSuccess = false;
        this.ajax(this.options.collectDetailsUrl, {'separated': 0})
            .then(function(oXHR) {
                var result = this.checkAjax(oXHR);
                this.initPRA(result);
            }.bind(this))
            .catch(this.cancelProcess.bind(this));
    },

    //init PRA, show PRA modal
    initPaymentRequestAPI: function() {
        var options = {requestShipping: true};
        this.praRequest = new PaymentRequest(this.instruments, this.details, options);
        this.praRequest.addEventListener('shippingaddresschange', this.collectShippingMethods.bind(this));
        this.praRequest.show()
            .then(function(instrumentResponse) {
                this.instrumentResponse = instrumentResponse;

                return this.checkCCAndPlaceOrder();
            }.bind(this))
            .catch(this.cancelProcess.bind(this));
    },

    //save shipping address in quote and collect available shipping method + update PRA details
    collectShippingMethods: function(evt) {
        var promise = this.ajax(this.options.collectShippingUrl, evt.currentTarget.shippingAddress)
            .then(function(oXHR) {
                var result = this.checkAjax(oXHR);
                this.updateDetails(result);

                return this.details;
            }.bind(this));
        this.praRequest.addEventListener('shippingoptionchange', this.setShippingMethod.bind(this));
        evt.updateWith(promise);
    },

    //set selected shipping method in quote
    setShippingMethod: function(evt) {
        var promise = this.ajax(this.options.setShippingUrl, {shipping_code: evt.currentTarget.shippingOption})
            .then(function(oXHR) {
                var result = this.checkAjax(oXHR);
                this.updateDetails(result);
                this.details.shippingOptions.each(function(shippingOption) {
                    if (shippingOption.id === evt.currentTarget.shippingOption) {
                        shippingOption.selected = true;

                        throw $break;
                    }
                });

                return this.details;
            }.bind(this));

        evt.updateWith(promise);
    },

    //reset PRA params
    resetPayment: function() {
        this.praRequest = null;
        this.details = {};
        this.instruments = {};
        this.payonePaymentResponse = {};
        this.instrumentResponse = {};

        return this;
    },

    //savePayoment method and place order
    placeOrder: function() {
        var data = this.payonePaymentResponse;

        return this.ajax(this.options.placeOrderUrl, data)
            .then(function(oXHR) {
                var response = this.checkAjax(oXHR);

                if (!response.success) {
                    return this.instrumentResponse.complete('fail');
                }

                if (response.redirectUrl) {
                    this.options.shouldRedirect = false;
                    this.options.refreshAfterSuccess = false;
                    location.href = response.redirectUrl;
                }

                return this.instrumentResponse.complete('success');
            }.bind(this))
            .then(function(instrumentResponse) {
                this.successAction();
                this.resetPayment();

                return instrumentResponse;
            }.bind(this))
    },

    //After order successfully placed redirect the customer or leave on product page.
    successAction: function() {
        if (this.options.shouldRedirect && this.options.successUrl) {
            location.href = this.options.successUrl;

            return;
        }

        if (!this.options.shouldRedirect && this.options.refreshAfterSuccess) {
            window.location.reload();

            return;
        }
    },

    //check CC with payone API
    checkCCAndPlaceOrder: function() {
        Object.extend(this.payonePaymentResponse, {'instrument': Object.toJSON(this.instrumentResponse.toJSON())});

        return this.ajax(this.options.getPaymentConfigUrl, this.payonePaymentResponse)
            .then(function(oXHR) {
                var response = this.checkAjax(oXHR);
                creditCardCheck.setHandler(this)
                    .setConfig(response.config)
                    .setConfigId(response.configId)
                    .setData(response.data);
                Object.extend(this.payonePaymentResponse, {'payone': Object.toJSON(response)});

                return creditCardCheck.creditcardcheck();
            }.bind(this));
    },

    //prepare instruments to PRA init
    prepareInstruments: function(response) {
        this.instruments = response.instruments;

        return this;
    },

    //prepare networks, details and then init PRA
    initPRA: function(response) {
        this.updateDetails(response)
            .prepareInstruments(response)
            .initPaymentRequestAPI();
    },

    //update PRA details
    updateDetails: function(response) {
        if (!response.details) {
            return this;
        }

        Object.extend(this.details, response.details);

        return this;
    },

    //get quantity from form input
    getQty: function() {
        return $('qty').value;
    },

    failureAction: function(oXHR) {
        if (oXHR.responseJSON && oXHR.responseJSON.error) {
            throw oXHR.responseJSON.error;
        }

        throw 'There was some problem during ajax request!';
    },

    cancelProcess: function(errorMessage) {
        this.resetPayment();
        console.error('Payment Request API error: ', errorMessage);
        alert(errorMessage);
    },

    //show modal with the question, buy separately or with items in cart?
    showModal: function(response) {
        var separated = false;

        if (response.quoteHasItems) {
            separated = true;
            if (confirm(Translator.translate('Would you like to buy other items from cart as well?'))) {
                separated = false;
                this.options.refreshAfterSuccess = true;
            }
        }

        var data = this.addSuperAttributes({
            'productId': this.options.productId,
            'qty': this.getQty(),
            'separated': separated ? 1 : 0
        });

        this.ajax(
            this.options.collectDetailsUrl,
            data
        ).then(function(oXHR) {
            var result = this.checkAjax(oXHR);
            this.initPRA(result);
        }.bind(this))
            .catch(this.cancelProcess.bind(this));
    },

    //prepare super_attributes if configurable product
    addSuperAttributes: function(data) {
        if (this.options.productType != 'configurable') {
            return data;
        }

        var superAttributes = {};

        spConfig.settings.each(function(element) {
            var attributeId = element.id.replace(/[a-z]*/, '') + "";
            superAttributes[attributeId] = spConfig.state[attributeId];
        }.bind(this));

        return Object.extend(data, {'superAttributes': Object.toJSON(superAttributes)});
    },

    //check ajax response
    checkAjax: function(oXHR) {
        var response = oXHR.responseJSON;

        if (!response || response.error) {
            return this.failureAction(oXHR);
        }

        return response;
    },

    //send ajax request
    ajax: function(url, data) {
        Object.extend(data, {'ajax': 1});
        return new Promise(function(resolve, reject) {
            new Ajax.Request(
                url,
                {
                    method: "POST",
                    parameters: data,
                    asynchronous: true,
                    onFailure: reject,
                    onSuccess: resolve
                }
            );
        });
    },

    //see PAYONE.Handler.CreditCardCheck.{handlerName}
    haveToValidate: function() {
        return true;
    },

    //see PAYONE.Handler.CreditCardCheck.{handlerName}
    handleResponse: function(response, blIsHostedIframe) {
        if (response.status != 'VALID') {
            // Failure
            if (typeof response.customermessage != 'undefined') {
                throw response.customermessage;
            } else if (typeof response.errormessage != 'undefined') {
                throw response.errormessage;
            }

            throw 'Gate validation failed!'
        }

        Object.extend(this.payonePaymentResponse, {'validation': Object.toJSON(response)});

        return this.placeOrder();
    }
};
