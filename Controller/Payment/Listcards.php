<?php

namespace Geidea\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Customer\Model\Session as CustomerSession;

class Listcards extends Action
{
    protected $paymentTokenRepository;
    protected $resultJsonFactory;
    protected $customerSession;
    protected $paymentTokenManagement;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CustomerSession $customerSession,
        PaymentTokenManagementInterface $paymentTokenManagement
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->paymentTokenManagement = $paymentTokenManagement;
    }

    public function execute()
    {
        $resultData = array();
        $resultJson = $this->resultJsonFactory->create();
        if ($this->customerSession->isLoggedIn()) {
            $customerId = $this->customerSession->getCustomerId();
            $paymentTokens = $this->paymentTokenManagement->getListByCustomerId($customerId);
            foreach ($paymentTokens as $paymentToken) {
                $tokenCustomerId = $paymentToken->getCustomerId();
                if ($tokenCustomerId == $customerId) {
                    $tokenData = $paymentToken->getData(PaymentTokenInterface::GATEWAY_TOKEN);
                    $tokenDetails = $paymentToken->getData(PaymentTokenInterface::DETAILS);
                    $isActive = $paymentToken->getData(PaymentTokenInterface::IS_ACTIVE);
                    $isVisible = $paymentToken->getData(PaymentTokenInterface::IS_VISIBLE);
                    $data = ['token' => $tokenData, 'details' => $tokenDetails,  'isActive' => $isActive,  'isVisible' => $isVisible];
                    if ($isVisible && $isActive) {
                        array_push($resultData, $data);
                    }
                    $resultJson->setData(($resultData));
                }
            }
            return $resultJson;
        } else {
            $resultJson->setData(($resultData));
            return $resultJson;
        }
    }
}
