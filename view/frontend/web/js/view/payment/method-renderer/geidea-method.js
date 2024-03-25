define([
    'ko',
    'jquery',
    'mage/translate',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Ui/js/model/messageList',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Geidea_Payment/js/action/set-payment-method',
    'Magento_Vault/js/view/payment/vault-enabler'
],
    function (
        ko, $, $t,
        Component, messageList, additionalValidators,
        setPaymentMethodAction, VaultEnabler) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Geidea_Payment/payment/geidea',
                paymentActionError: $t('Something went wrong with your request. Please try again later.'),
                processing: ko.observable(false)
            },

            initialize: function () {
                var self = this;
                self._super();
                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode(this.getVaultCode());
                this.selectedTokenId = ko.observable("NEW")
                this.saveCard = ko.observable(false)
                return self;
            },

            getCheckoutImage: function () {
                return this.clientConfig.checkoutIcon;
            },

            getCode: function () {
                return 'geidea_payment';
            },

            isActive: function () {
                return true;
            },

            getData: function () {
                var data = {
                    'method': this.getCode(),
                    'additional_data': {}
                };
                this.vaultEnabler.visitAdditionalData(data);
                return data;
            },

            setPaymentMethod: function (reject) {
                var deferred = $.Deferred();
                setPaymentMethodAction(this.messageContainer, this.getData()).done(function () {
                    return deferred.resolve();
                }).fail(function (response) {
                    var error;
                    try {
                        error = JSON.parse(response.responseText);
                    } catch (exception) {
                        error = this.paymentActionError;
                    }
                    return reject(new Error(error));
                }.bind(this));
                return deferred.promise();
            },

            reserve: function (reject) {
                var params = {
                    'quote_id': window.checkoutConfig.quoteData['entity_id']
                };
                this.processing(true);
                var deferred = $.Deferred();
                $.post(this.clientConfig.reserveUrl, params).done(function (data) {
                    if (data.success)
                        return deferred.resolve(data);

                    return reject(new Error(data['error_message']));
                }).fail(function (jqXHR, textStatus, err) {
                    return reject(err);
                }.bind(this));
                return deferred.promise();
            },
            startPayment: function (data, reject) {
                var self = this;
                console.log("within start payment", data);
                var createSessionResponse = this.createSession(data)
                console.log("session created", createSessionResponse);
                if (createSessionResponse.responseCode !== '000') {
                    self.processing(false);
                    self.addError("Error creating Payment Session")
                }
                var deferred = $.Deferred();
                var onSuccess = function (_message, _statusCode) {
                    return deferred.resolve();
                }
                var onError = function (error) {
                    self.processing(false);
                    self.addError("Checkout Error")
                    return deferred.reject()
                }
                var onCancel = function () {
                    self.processing(false);
                    self.addError("Checkout Cancelled");
                    return deferred.reject()
                }
                const api = new GeideaCheckout(onSuccess, onError, onCancel);
                api.startPayment(createSessionResponse.session.id);
                return deferred.promise();
            },

            authorize: function (resolve, reject) {
                var params = {
                    'quote_id': window.checkoutConfig.quoteData['entity_id']
                };
                var deferred = $.Deferred();
                $.post(this.clientConfig.authorizeUrl, params).done(function (data) {
                    if (data.success)
                        return resolve(data);

                    return reject(new Error(data['error_message']));
                }).fail(function (jqXHR, textStatus, err) {
                    return reject(err);
                }.bind(this));
                return deferred.promise();
            },

            continueToGeidea: function (data, event) {
                if (event) { event.preventDefault(); }
                if (this.validate() && additionalValidators.validate()) {
                    $.Deferred(function (deferred) {
                        this.setPaymentMethod.bind(this, deferred.reject)()
                            .then(this.reserve.bind(this, deferred.reject))
                            .then(function (data) {
                                return this.startPayment.bind(this, data, deferred.reject)();
                            }.bind(this))
                            .then(this.authorize.bind(this, deferred.resolve, deferred.reject));
                    }.bind(this))
                        .promise()
                        .done(function (data) {
                            $.mage.redirect(window.BASE_URL + 'checkout/onepage/success/');
                        }.bind(this))
                        .fail(function (err) {
                            this.processing(false);
                            this.addError(err.message);
                        }.bind(this));
                    return false;
                }
                return false;
            },

            addError: function (message) {
                messageList.addErrorMessage({
                    message: message
                });
            },

            isVaultEnabled: function () {
                return this.vaultEnabler.isVaultEnabled();
            },

            getVaultCode: function () {
                return this.vaultCode;
            },

            fetchSavedCards: function () {
                var result
                $.ajax({
                    url: window.BASE_URL + '/geidea/payment/listcards',
                    type: 'GET',
                    async: false,
                    dataType: 'json',
                    success: function (response) {
                        var savedCardList = []
                        console.log('Received data from ListSavedCards:', response);
                        if (response) {
                            for (let i = 0; i < response.length; i++) {
                                var details = JSON.parse(response[i].details)
                                var cardname = ""
                                var iconUrl = ""
                                if (details.type == "VI") {
                                    cardname = "Visa";
                                    iconUrl = require.toUrl('Geidea_Payment/images/visa.svg')
                                }
                                if (details.type == "MC") {
                                    cardname = "MasterCard";
                                    iconUrl = require.toUrl('Geidea_Payment/images/mastercard.svg')
                                }
                                if (details.type == "MA") {
                                    cardname = "Mada";
                                    iconUrl = require.toUrl('Geidea_Payment/images/mada.svg')
                                }
                                savedCardList.push(
                                    {
                                        token: response[i].token,
                                        text: cardname + " ending in " + details.maskedCC + " (expires " + details.expirationDate + ")",
                                        iconUrl: iconUrl
                                    }
                                )
                            }
                        }
                        savedCardList.push(
                            {
                                token: "NEW",
                                text: "Use a new payment method",
                                iconUrl: ""
                            }
                        )
                        result = savedCardList
                    },
                    error: function (xhr, status, error) {
                        // console.log('Error occurred:', error);
                    }
                });
                return ko.observableArray(result);
            },
            createSession: function (input) {
                var baseUrl = window.BASE_URL;
                var result
                $.ajax({
                    url: baseUrl + '/geidea/payment/createsession?tokenId=' + this.selectedTokenId() + '&savecard=' + this.saveCard(),
                    type: 'GET',
                    async: false,
                    dataType: 'json',
                    success: function (response) {
                        result = response
                    },
                    error: function (xhr, status, error) {
                        result = error
                    }
                });
                return result
            }
        });
    }
);