<?php

namespace Geidea\Payment\Controller\Payment;

use Geidea\Payment\Gateway\Config\Config;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Directory\Api\CountryInformationAcquirerInterface;

class CreateSession extends Action
{
    protected $paymentTokenRepository;
    protected $resultJsonFactory;
    protected $customerSession;
    protected $checkoutSession;
    protected $curl;
    protected $config;
    protected $url;
    protected $request;
    protected $jsonFactory;
    protected $session;
    private $logger;
    private $countryInformation;

    public function __construct(
        Context $context,
        PaymentTokenManagementInterface $paymentTokenRepository,
        JsonFactory $resultJsonFactory,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        Curl $curl,
        Config $config,
        UrlInterface $url,
        RequestInterface $request,
        JsonFactory $jsonFactory,
        SessionManagerInterface $session,
        LoggerInterface $logger,
        CountryInformationAcquirerInterface $countryInformation
    ) {
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->curl = $curl;
        $this->config = $config;
        $this->url = $url;
        $this->session = $session;
        parent::__construct($context);
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
        $this->countryInformation = $countryInformation;
    }

    public function execute()
    {
        $this->logger->debug("within session create Geidea");
        $quote = $this->checkoutSession->getQuote();
        $shippingAddress = $quote->getShippingAddress();
        $billingAddress = $quote->getBillingAddress();
        $tokenId = $this->request->getParam('tokenId');
        $saveCardFlag = $this->request->getParam('savecard');
        $storeId = $this->session->getStoreId();
        $baseMediaUrl = $this->url->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]);
        $baseMediaUrl .= 'geidea/';
        $merchantLogoUrl = '';
        $relativeMerchantLogoUrl = $this->config->getValue("merchantLogo", $storeId);
        if ($relativeMerchantLogoUrl != '') {
            $origMerchantLogoUrl = $baseMediaUrl . $relativeMerchantLogoUrl;
            $merchantLogoUrl = str_replace('http://', 'https://', $origMerchantLogoUrl);
        }
        $sessionRequestPayload = array(
            'merchantPublicKey' => $this->config->getValue('merchantKey'),
            'apiPassword' => $this->config->getValue('merchantPassword'),
            'callbackUrl' =>  $this->url->getBaseUrl() . "/geidea/payment/callback",
            'amount' => number_format(round($quote->getBaseGrandTotal(), 2), 2),
            'currency' => $quote->getBaseCurrencyCode(),
            'merchantReferenceId' =>  (string) $quote->getReservedOrderId(),
            'initiatedBy' => 'Internet',
            "cardOnFile" => ($saveCardFlag == "true") ? true : false,
            "tokenId" => ($tokenId == 'NEW') ? null : $tokenId,
            'customer' => array(
                'create' => false,
                'setDefaultMethod' => false,
                'email' => $billingAddress->getEmail(),
                'phoneNumber' => null,
                'address' => array(
                    'billing' => array(
                        'country' => $this
                            ->countryInformation
                            ->getCountryInfo($billingAddress->getCountryId())
                            ->getThreeLetterAbbreviation(),
                        'street' => implode(' ', $billingAddress->getStreet()),
                        'city' => $billingAddress->getCity(),
                        'postalCode' =>  $billingAddress->getPostcode(),
                    ),
                    'shipping' => array(
                        'country' => $this
                            ->countryInformation
                            ->getCountryInfo($shippingAddress->getCountryId())
                            ->getThreeLetterAbbreviation(),
                        'street' => implode(' ', $shippingAddress->getStreet()),
                        'city' => $shippingAddress->getCity(),
                        'postalCode' =>  $shippingAddress->getPostcode(),
                    ),
                ),
            ),
            'appearance' => array(
                'merchant' => array(
                    'logoUrl' => $merchantLogoUrl,
                ),
                'showAddress' => ($this->config->getValue('addressEnabled') == 1) ? true : false,
                'showEmail' => ($this->config->getValue('emailEnabled') == 1) ? true : false,
                'showPhone' => ($this->config->getValue('phoneEnabled') == 1) ? true : false,
                'receiptPage' => ($this->config->getValue('receiptEnabled') == 1) ? true : false,
                'styles' => array(
                    'hideGeideaLogo' => false,
                    'headerColor' => $this->config->getValue("headerColor", $storeId),
                    'hppProfile' => $this->config->getValue('hpp'),
                ),
            ),
            'order' => array(
                'integrationType' => 'Plugin',
            ),
            'platform' => array(
                'name' => "Magento",
                'version' => "22.2222",
                'pluginVersion' => "22",
                'partnerId' => "222",
            ),
        );

        $response = $this->sendGiRequest(
            'https://api.merchant.geidea.net/payment-intent/api/v1/direct/session',
            $this->config->getValue('merchantKey'),
            $this->config->getValue('merchantPassword'),
            json_encode($sessionRequestPayload)
        );

        // $responseBody = array();
        // if ($response === false) {
        //     $responseBody['error'] = 'Failed to connect to the gateway.';
        //     // $responseBody['cancelUrl'] = $data['cancelUrl'];
        // } else {
        //     $responseArray = json_decode($response, true);
        //     if ($responseArray && isset($responseArray['session'])) {
        //         $responseBody = $responseArray;
        //         // $responseBody['returnUrl'] = $data['returnUrl'];
        //         // $responseBody['cancelUrl'] = $data['cancelUrl'];
        //     } else {
        //         $responseBody['error'] = 'Invalid response from the gateway.';
        //     }
        // }

        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData((json_decode($response)));
        return $resultJson;
    }

    function sendGiRequest($gatewayUrl, $merchantKey, $password, $values, $method = 'POST')
    {
        $origString = $merchantKey . ":" . $password;
        $authKey = base64_encode($origString);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $gatewayUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $values,
            CURLOPT_HTTPHEADER => array(
                'accept: application/json',
                'Authorization: Basic ' . $authKey,
                'content-type: application/json'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
}
