<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

namespace NovaPay;

use NovaPay\DeliveryInfo\Request as DeliveryInfoRequest;
use NovaPay\DeliveryInfo\Response as DeliveryInfoResponse;

class Client
{
    /**
     * @var string
     */
    const DELIVERY_INFO_RESOURCE = 'delivery-info';

    /**
     * @var string
     */
    private $merchantId = null;

    /**
     * @var string
     */
    private $merchantPrivateKey = null;

    /**
     * @var string
     */
    private $merchantPrivateKeyPassword = null;

    /**
     * @var bool
     */
    private $sandboxMode = false;

    /**
     * @var Security
     */
    private $security;
    
    /**
     * @param string $merchantId
     * @param string $merchantPrivateKey
     * @param string $merchantPrivateKeyPassword
     * @param bool $sandboxMode
     */
    public function __construct($merchantId, $merchantPrivateKey, $merchantPrivateKeyPassword, $sandboxMode)
    {
        $this->merchantId = $merchantId;
        $this->merchantPrivateKey = $merchantPrivateKey;
        $this->merchantPrivateKeyPassword = $merchantPrivateKeyPassword;
        $this->sandboxMode = $sandboxMode;
        $this->security = new Security();
    }

    /**
     * @param string $resource
     *
     * @return string
     */
    public function getUrl($resource)
    {
        $url =  $this->sandboxMode ?
            'https://api-qecom.novapay.ua/v1' :
            'https://api-ecom.novapay.ua/v1';

        if ($resource) {
            $url .= '/'.$resource;
        }

        return $url;
    }

    /**
     * @param array $data
     * @param array $information
     *
     * @return DeliveryInfoResponse
     */
    protected function buildDeliveryInfoResponse(array $data, array $information)
    {
        $response = new DeliveryInfoResponse();
        
        if ($data) {
            if (isset($data['success'])) {
                $response->setSuccess($data['success']);
            }

            if (isset($data['data']) && is_array($data['data'])) {
                $response->setData($data['data']);
            }

            if (isset($data['errors'])) {
                $errors = $data['errors'];
                if (is_array($errors)) {
                    $errorsCount = count($errors);
                    if ($errorsCount > 0) {
                        $errorCodes = null;
                        $errorCodesCount = 0;

                        if (isset($data['errorCodes'])) {
                            $errorCodes = $data['errorCodes'];
                            if (is_array($errorCodes)) {
                                $errorCodesCount = count($errorCodes);
                            }
                        }

                        if ($errorCodesCount == $errorsCount) {
                            foreach ($errors as $key => $message) {
                                $response->addError(
                                    (new Error())
                                        ->setCode(isset($errorCodes[$key]) ? $errorCodes[$key] : '')
                                        ->setMessage($message)
                                );
                            }
                        } else {
                            foreach ($errors as $message) {
                                $response->addError((new Error())->setMessage($message));
                            }
                        }
                    }
                }
            }
        }
        
        if (isset($information['http_code'])) {
            $response->setCode($information['http_code']);
        }
        
        return $response;
    }

    /**
     * @param array $data
     * @param array $information
     *
     * @return Response
     */
    protected function buildResponse(array $data, array $information)
    {
        $response = new Response();

        if ($data) {
            if (isset($data['error'])) {
                $response->addError(
                    (new Error())
                        ->setType(isset($data['type']) ? $data['type'] : '')
                        ->setCode(isset($data['code']) ? $data['code'] : '')
                        ->setMessage($data['error'])
                );
            } elseif (isset($data['errors']) && is_array($data['errors'])) {
                $type = isset($data['type']) ? $data['type'] : '';

                foreach ($data['errors'] as $item) {
                    $response->addError(
                        (new Error())
                            ->setType($type)
                            ->setCode(isset($item['dataPath']) ? $item['dataPath'] : '')
                            ->setMessage(isset($item['message']) ? $item['message'] : '')
                    );
                }
            } else {
                $response->setData($data);
            }
        }
        
        if (isset($information['http_code'])) {
            $response->setCode($information['http_code']);
        }
        
        return $response;
    }
    
    /**
     * @param string $resource
     * @param array $data
     *
     * @return Response|DeliveryInfoResponse|null
     */
    public function doRequest($resource, array $data = array())
    {
        if (!isset($data['merchant_id'])) {
            $data['merchant_id'] = $this->merchantId;
        }

        $handle = curl_init();
        $message = json_encode($data);
        $signature = $this->security->generateSignature(
            $message,
            $this->merchantPrivateKey,
            $this->merchantPrivateKeyPassword
        );
        $httpHeader = array(
            'Content-Type: application/json',
            'x-sign: '.$signature,
        );
        
        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($handle, CURLOPT_POSTFIELDS, $message);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $httpHeader);
        curl_setopt($handle, CURLOPT_URL, $this->getUrl($resource));
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, 0);

        $result = curl_exec($handle);
        $information = curl_getinfo($handle);
        
        curl_close($handle);
        
        if ($result !== false) {
            $data = json_decode($result, true);
            if (!is_array($data)) {
                $data = array('data' => $result);
            }

            if ($resource === self::DELIVERY_INFO_RESOURCE) {
                return $this->buildDeliveryInfoResponse($data, $information);
            }

            return $this->buildResponse($data, $information);
        }
        
        return null;
    }

    /**
     * @param DataSourceInterface $session
     *
     * @return Response|null
     */
    public function createSession(DataSourceInterface $session)
    {
        return $this->doRequest('session', $session->getNovaPayData());
    }

    /**
     * @param string $sessionId
     *
     * @return Response|null
     */
    public function closeSession($sessionId)
    {
        return $this->doRequest('expire', array('session_id' => $sessionId));
    }

    /**
     * @param string $sessionId
     *
     * @return Response|null
     */
    public function getSessionStatus($sessionId)
    {
        return $this->doRequest('get-status', array('session_id' => $sessionId));
    }

    /**
     * @param DataSourceInterface $payment
     *
     * @return Response|null
     */
    public function createPayment(DataSourceInterface $payment)
    {
        return $this->doRequest('payment', $payment->getNovaPayData());
    }

    /**
     * @param string $sessionId
     * @param float $amount
     *
     * @return Response|null
     */
    public function doPaymentCapture($sessionId, $amount)
    {
        return $this->doRequest('complete-hold', array(
            'session_id' => $sessionId,
            'amount' => $amount,
        ));
    }

    /**
     * @param string $sessionId
     *
     * @return Response|null
     */
    public function cancelPayment($sessionId)
    {
        return $this->doRequest('void', array('session_id' => $sessionId));
    }

    /**
     * @param string $sessionId
     *
     * @return Response|null
     */
    public function confirmDeliveryHold($sessionId)
    {
        return $this->doRequest('confirm-delivery-hold', array('session_id' => $sessionId));
    }

    /**
     * @param Delivery $delivery
     *
     * @return Response|null
     */
    public function getDeliveryPrice(Delivery $delivery)
    {
        return $this->doRequest('delivery-price', $delivery->getNovaPayData());
    }

    /**
     * @param DeliveryInfoRequest $request
     *
     * @return DeliveryInfoResponse|null
     */
    public function getDeliveryInfo(DeliveryInfoRequest $request)
    {
        return $this->doRequest(self::DELIVERY_INFO_RESOURCE, $request->getNovaPayData());
    }
    
    /**
     * @param string $sessionId
     *
     * @return mixed
     */
    public function printExpressWaybill($sessionId)
    {
        return $this->doRequest('print-express-waybill', array('session_id' => $sessionId));
    }
    
    /**
     * @return bool
     */
    public function checkConnection()
    {
        $session = new \NovaPaySession();
        $session->merchant_id = $this->merchantId;
        $session->client_phone = '+380000000000';

        $response = $this->createSession($session);
        if (!$response) {
            return false;
        }

        return (bool)$response->getValue('id');
    }
}
