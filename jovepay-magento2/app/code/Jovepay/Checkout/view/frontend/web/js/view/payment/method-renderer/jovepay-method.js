/**
 * Copyright © JOVEpay. Licensed under GPLv3 or later. See LICENSE.txt.
 */

define([
    'ko',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'jquery',
    'Magento_Checkout/js/action/place-order',
    'Magento_Checkout/js/action/select-payment-method',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/payment/additional-validators',
    'mage/url',
    'mage/translate'
], function (
    ko,
    Component,
    quote,
    $,
    placeOrderAction,
    selectPaymentMethodAction,
    customer,
    checkoutData,
    additionalValidators,
    urlBuilder,
    $t
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Jovepay_Checkout/payment/jove_pay',
            redirectMessage: ''
        },

        /**
         * @returns {exports}
         */
        initialize: function () {
            this._super();
            this.redirectMessage = ko.observable(
                $t('You will be transferred to jovepay.com to complete your purchase when using this payment method.')
            );
            return this;
        },

        /**
         * @returns {String}
         */
        getCode: function () {
            return 'jove_pay';
        },

        /**
         * @returns {Boolean}
         */
        isActive: function () {
            return true;
        },

        /**
         * Redirect buyer to JOVEpay after Magento places the order.
         */
        afterPlaceOrder: function () {
            window.location.replace(urlBuilder.build('jovepay/payment/redirect'));
        },

        /**
         * @returns {String}
         */
        getRedirectionText: function () {
            return this.redirectMessage();
        },

        /**
         * @returns {Boolean}
         */
        selectPaymentMethod: function () {
            selectPaymentMethodAction(this.getData());
            checkoutData.setSelectedPaymentMethod(this.item.method);
            return true;
        },

        /**
         * @param {*} data
         * @param {Event} event
         * @returns {Boolean}
         */
        placeOrder: function (data, event) {
            var self = this,
                placeOrder,
                emailValidationResult = customer.isLoggedIn(),
                loginFormSelector = 'form[data-role=email-with-possible-login]';

            if (event) {
                event.preventDefault();
            }

            if (!customer.isLoggedIn()) {
                $(loginFormSelector).validation();
                emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
            }

            if (emailValidationResult && this.validate() && additionalValidators.validate()) {
                this.isPlaceOrderActionAllowed(false);
                placeOrder = placeOrderAction(this.getData(), false, this.messageContainer);

                $.when(placeOrder).fail(function () {
                    self.isPlaceOrderActionAllowed(true);
                }).done(this.afterPlaceOrder.bind(this));

                return true;
            }

            return false;
        }
    });
});
