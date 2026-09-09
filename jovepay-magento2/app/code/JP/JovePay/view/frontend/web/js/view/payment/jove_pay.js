/**
 * @copyright: Copyright © 2026 JOVEpay. All rights reserved.
 * @author   : JOVEpay <support@jovepay.com>
 */

define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component,
              rendererList) {
        'use strict';
        rendererList.push(
            {
                type: 'jove_pay',
                component: 'JP_JovePay/js/view/payment/method-renderer/jovepay-method'
            }
        );
        return Component.extend({});
    }
);