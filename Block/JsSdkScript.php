<?php

namespace Geidea\Payment\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Geidea\Payment\Gateway\Config\Config;

class JsSdkScript extends Template
{
    protected $config;

    public function __construct(
        Context $context,
        Config $config,
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($context, $data);
    }

    public function getScriptUrl()
    {
        $env = $this->config->getValue('env');
        switch ($env) {
            case 'EGY-PROD':
                return 'https://www.merchant.geidea.net/hpp/geideaCheckout.min.js';
            case 'EGY-PREPROD':
                return 'https://www-merchant.staging.geidea.net/hpp/geideaCheckout.min.js';
            case 'UAE-PROD':
                return 'https://www.merchant.geidea.ae/hpp/geideaCheckout.min.js';
            case 'UAE-PREPROD':
                return 'https://www-merchant.staging.geidea.ae/hpp/geideaCheckout.min.js';
            case 'KSA-PROD':
                return 'https://www.ksamerchant.geidea.net/hpp/geideaCheckout.min.js';
            case 'KSA-PREPROD':
                return 'https://www-ksamerchant.staging.geidea.net/hpp/geideaCheckout.min.js';
            default:
                return '';
        }
    }
}
