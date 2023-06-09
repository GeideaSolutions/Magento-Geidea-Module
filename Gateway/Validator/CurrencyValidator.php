<?php

namespace Geidea\Payment\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

use Geidea\Payment\Gateway\Config\Config;

class CurrencyValidator extends AbstractValidator
{
    /**
     * @var Config
     */
    private $config;

    /**
     * Constructor
     *
     * @param ResultInterfaceFactory $resultFactory
     * @param Config $config
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        Config $config
    ) {
        $this->config = $config;
        parent::__construct($resultFactory);
    }
    
    /**
     * Validate currency
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $isValid = true;
        $storeId = $validationSubject['storeId'];

        $currencies = explode(
            ',',
            $this->config->getValue('currencies', $storeId)
        );

        if (!in_array($validationSubject['currency'], $currencies)) {
            $isValid = false;
        }

        return $this->createResult($isValid);
    }
}
