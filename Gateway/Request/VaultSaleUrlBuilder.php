<?php

namespace Geidea\Payment\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

class VaultSaleUrlBuilder implements BuilderInterface
{
    public const URL = 'url';
    public const METHOD = 'method';
    private $payByTokenUrl;

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
            $this->payByTokenUrl = 'https://api.merchant.geidea.net/pgw/api/v1/direct/pay/token';
        } elseif ($this->config->getValue('env') === 'EGY-PREPROD') {
            $this->payByTokenUrl = 'https://api-merchant.staging.geidea.net/pgw/api/v1/direct/pay/token';
        } elseif ($this->config->getValue('env') === 'UAE-PROD') {
            $this->payByTokenUrl = 'https://api.merchant.geidea.ae/pgw/api/v1/direct/pay/token';
        } elseif ($this->config->getValue('env') === 'UAE-PREPROD') {
            $this->payByTokenUrl = 'https://api-merchant.staging.geidea.ae/pgw/api/v1/direct/pay/token';
        } elseif ($this->config->getValue('env') === 'KSA-PROD') {
            $this->payByTokenUrl = 'https://api.ksamerchant.geidea.net/pgw/api/v1/direct/pay/token';
        } elseif ($this->config->getValue('env') === 'KSA-PREPROD') {
            $this->payByTokenUrl = 'https://api-ksamerchant.staging.geidea.net/pgw/api/v1/direct/pay/token';
        }
    }

    /**
     * Builds ENV request
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
            self::URL => $this->payByTokenUrl,
            self::METHOD => "POST"
        ];

        return $result;
    }
}
