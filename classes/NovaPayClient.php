<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

class NovaPayClient
{
    /**
     * @var string
     */
    private $merchantId = null;

    /**
     * @var string
     */
    private $merchantPrivateKey = null;

    /**
     * @var bool
     */
    private $sandboxMode = false;

    /**
     * @var NovaPaySecurity
     */
    private $security;
    
    /**
     * @param string $merchantId
     * @param string $merchantPrivateKey
     * @param bool $sandboxMode
     */
    public function __construct($merchantId, $merchantPrivateKey, $sandboxMode)
    {
        $this->merchantId = $merchantId;
        $this->merchantPrivateKey = $merchantPrivateKey;
        $this->sandboxMode = $sandboxMode;
        $this->security = new NovaPaySecurity();
    }

    /**
     * @param string $resource
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
     * @return NovaPayResponse
     */
    protected function buildResponse(array $data, array $information)
    {
        $response = new NovaPayResponse();

        if ($data) {
            if (isset($data['error'])) {
                $response->addError(
                    (new NovaPayError())
                        ->setType(isset($data['type']) ? $data['type'] : '')
                        ->setCode(isset($data['code']) ? $data['code'] : '')
                        ->setMessage($data['error'])
                );
            } elseif (isset($data['errors']) && is_array($data['errors'])) {
                $type = isset($data['type']) ? $data['type'] : '';

                foreach ($data['errors'] as $item) {
                    $response->addError(
                        (new NovaPayError())
                            ->setType($type)
                            ->setCode(isset($item['dataPath']) ? $item['dataPath'] : '')
                            ->setMessage(isset($item['message']) ? $item['message'] : '')
                    );
                }
            } else {
                $response->setValues($data);
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
     * @return NovaPayResponse|null
     */
    public function doRequest($resource, array $data = array())
    {
        $handle = curl_init();
        $message = json_encode($data);
        $signature = $this->security->generateSignature(
            $message,
            $this->merchantPrivateKey
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

            return $this->buildResponse(
                is_array($data) ? $data : array(),
                $information
            );
        }
        
        return null;
    }

    /**
     * @param NovaPayObjectInterface $session
     * @return NovaPayResponse|null
     */
    public function createSession(NovaPayObjectInterface $session)
    {
        return $this->doRequest('session', $session->getNovaPayData());
    }

    /**
     * @param string $sessionId
     * @return NovaPayResponse|null
     */
    public function closeSession($sessionId)
    {
        return $this->doRequest('expire', array(
            'merchant_id' => $this->merchantId,
            'session_id' => $sessionId,
        ));
    }

    /**
     * @param string $sessionId
     * @return NovaPayResponse|null
     */
    public function getSessionStatus($sessionId)
    {
        return $this->doRequest('get-status', array(
            'merchant_id' => $this->merchantId,
            'session_id' => $sessionId,
        ));
    }

    /**
     * @param NovaPayObjectInterface $payment
     * @return NovaPayResponse|null
     */
    public function createPayment(NovaPayObjectInterface $payment)
    {
        return $this->doRequest('payment', $payment->getNovaPayData());
    }

    /**
     * @param string $sessionId
     * @param float $amount
     * @return NovaPayResponse|null
     */
    public function doPaymentCapture($sessionId, $amount)
    {
        return $this->doRequest('complete-hold', array(
            'merchant_id' => $this->merchantId,
            'session_id' => $sessionId,
            'amount' => $amount,
        ));
    }

    /**
     * @param string $sessionId
     * @return NovaPayResponse|null
     */
    public function cancelPayment($sessionId)
    {
        return $this->doRequest('void', array(
            'merchant_id' => $this->merchantId,
            'session_id' => $sessionId,
        ));
    }
    
    /**
     * @return bool
     */
    public function checkConnection()
    {
        $session = new NovaPaySession();
        $session->merchant_id = $this->merchantId;
        $session->client_phone = '+380000000000';

        $response = $this->createSession($session);
        if (!$response) {
            return false;
        }

        return (bool)$response->getValue('id');
    }
}
