<?php

namespace Geidea\Payment\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

class RefundUrlBuilder implements BuilderInterface
{
    public const URL = 'url';
    public const METHOD = 'method';
    private $refundUrl;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * Constructor
     *
     * @param SubjectReader $subjectReader
     * @param ConfigInterface $config
     */
    public function __construct(
        SubjectReader $subjectReader,
        ConfigInterface $config
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;

        if ($this->config->getValue('env') === 'EGY-PROD') {
            $this->refundUrl = 'https://api.merchant.geidea.net/pgw/api/v1/direct/refund';
        } elseif ($this->config->getValue('env') === 'EGY-PREPROD') {
            $this->refundUrl = 'https://api-merchant.staging.geidea.net/pgw/api/v1/direct/refund';
        } elseif ($this->config->getValue('env') === 'UAE-PROD') {
            $this->refundUrl = 'https://api.merchant.geidea.ae/pgw/api/v1/direct/refund';
        } elseif ($this->config->getValue('env') === 'UAE-PREPROD') {
            $this->refundUrl = 'https://api-merchant.staging.geidea.ae/pgw/api/v1/direct/refund';
        } elseif ($this->config->getValue('env') === 'KSA-PROD') {
            $this->refundUrl = 'https://api.ksamerchant.geidea.net/pgw/api/v1/direct/refund';
        } elseif ($this->config->getValue('env') === 'KSA-PREPROD') {
            $this->refundUrl = 'https://api-ksamerchant.staging.geidea.net/pgw/api/v1/direct/refund';
        }
    }

    /**
     * Builds refund ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $payment = $paymentDO->getPayment();

        $storeId = $payment->getOrder()->getStoreId();

        $result = [
            self::URL => $this->refundUrl,
            self::METHOD => "POST"
        ];

        return $result;
    }
}
