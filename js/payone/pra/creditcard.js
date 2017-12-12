var creditCardCheck = new PAYONE.Service.CreditCardCheck();

creditCardCheck.setConfig = function(config) {
    this.config = config;

    return this;
};

creditCardCheck.setConfigId = function(configId) {
    this.configId = configId;

    return this;
};

creditCardCheck.setData = function(data) {
    this.data = data;

    return this;
};

creditCardCheck.setHandler = function(handler) {
    this.handler = handler;

    return this;
};

creditCardCheck.creditcardcheck = function() {
    var configId = this.configId;

    config = this.getConfig();
    configGateway = config.gateway[configId];

    var data = this.mapRequestCreditCardCheck();

    var payoneGateway = new PAYONE.Gateway(
        configGateway,
        function(response) {
            return this.handleResponseCreditcardCheck(response, false);
        }.bind(this)
    );
    payoneGateway.call(data);
};

creditCardCheck.mapRequestCreditCardCheck = function() {
    return this.data;
};