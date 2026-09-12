/**
 * Copyright © JOVEpay. Licensed under GPLv3 or later. See LICENSE.txt.
 */

define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push({
        type: 'jove_pay',
        component: 'Jovepay_Checkout/js/view/payment/method-renderer/jovepay-method'
    });

    return Component.extend({});
});
