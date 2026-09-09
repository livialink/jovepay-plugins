<?php

  define('MODULE_PAYMENT_JOVEPAY_TEXT_ADMIN_TITLE', 'JOVEpay');
  define('MODULE_PAYMENT_JOVEPAY_TEXT_CATALOG_TITLE', zen_image ( DIR_WS_IMAGES . 'jovepay.png' ) . '<span>Pay with +100 cryptocurrencies</span>' );
  if (IS_ADMIN_FLAG === true) {
    define('MODULE_PAYMENT_JOVEPAY_TEXT_DESCRIPTION', 'JOVEpay Module');
  }
  define('MODULE_PAYMENT_JOVEPAY_TEXT_ERROR_MESSAGE', 'There has been an error processing the transaction due to');
  define('MODULE_PAYMENT_JOVEPAY_IPN_SECRET', 'jovepay_ipn_secret');