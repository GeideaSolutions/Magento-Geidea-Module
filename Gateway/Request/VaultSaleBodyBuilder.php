<?php
namespace Geidea\Payment\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

class VaultSaleBodyBuilder implements BuilderInterface
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * Constructor
     *
     * @param SubjectReader $subjectReader
     */
    public function __construct(SubjectReader $subjectReader)
    {
        $this->subjectReader = $subjectReader;
    }

    /**
     * Builds vault payment body request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $payment = $paymentDO->getPayment();

        $order = $payment->getOrder();

        $extensionAttributes = $payment->getExtensionAttributes();

        $token = $extensionAttributes->getVaultPaymentToken();
        if ($token === null) {
            throw new CommandException('The Payment Token is not available to perform the request.');
        }

        $result = [
            'body' => [
                'amount' => round($order->getBaseGrandTotal(), 2),
                'currency' => $order->getOrderCurrencyCode(),
                'tokenId' => $token->getGatewayToken()
            ]
        ];

        return $result;
    }
}
