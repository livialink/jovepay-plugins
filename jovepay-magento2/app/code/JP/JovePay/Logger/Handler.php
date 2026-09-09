<?php
/**
 * @copyright: Copyright © 2026 JOVEpay. All rights reserved.
 * @author   : JOVEpay <support@jovepay.com>
 */

namespace JP\JovePay\Logger;

use \Monolog\Logger as MonologLogger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = MonologLogger::INFO;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/jovepay.log';
}
