<?php
/**
 * Copyright © JOVEpay. Licensed under GPLv3 or later. See LICENSE.txt.
 */
declare(strict_types=1);

namespace Jovepay\Checkout\Api;

/**
 * IPN webhook API for JOVEpay payment notifications.
 */
interface IpnManagementInterface
{
    /**
     * Process an IPN callback from JOVEpay.
     *
     * @param string|null $param Unused placeholder required by webapi route binding
     * @return string JSON-encoded response body
     */
    public function getPost($param = null);
}
