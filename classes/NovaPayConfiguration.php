<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

class NovaPayConfiguration
{
    /**
     * @var array
     */
    protected $configurationList = array(
        'NOVAPAY_MERCHANT_ID' => '',
        'NOVAPAY_MERCHANT_PRIVATE_KEY' => '',
        'NOVAPAY_SERVER_PUBLIC_KEY' => '',
        'NOVAPAY_CONNECTION_CHECKED' => '',
        'NOVAPAY_TWO_STEP_PAYMENT' => false,
        'NOVAPAY_SANDBOX_MODE' => false,
    );

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $shopId
     * @return string|bool
     */
    protected function setValue($key, $value, $shopId = null)
    {
        if (!$shopId) {
            $shopId = Context::getContext()->shop->id;
        }
        
        return Configuration::updateValue($key, $value, false, null, (int)$shopId);
    }

    /**
     * @return bool
     */
    public function initialize()
    {
        $result = true;

        foreach (Shop::getShops(false, null, true) as $shopId) {
            foreach ($this->configurationList as $name => $value) {
                if (Configuration::hasKey($name, null, null, (int)$shopId) === false) {
                    $result &= $this->setValue($name, $value, $shopId);
                }
            }
        }

        return $result;
    }

    /**
     * @param string $key
     * @return string|bool
     */
    protected function getValue($key)
    {
        return Configuration::get($key, null, null, (int)Context::getContext()->shop->id);
    }
    
    /**
     * @return string|bool
     */
    public function getMerchantId()
    {
        return $this->getValue('NOVAPAY_MERCHANT_ID');
    }

    /**
     * @param string $merchantId
     * @return $this
     */
    public function setMerchantId($merchantId)
    {
        if (is_string($merchantId)) {
            $this->setValue('NOVAPAY_MERCHANT_ID', $merchantId);
        }

        return $this;
    }

    /**
     * @return string|bool
     */
    public function getMerchantPrivateKey()
    {
        return $this->getValue('NOVAPAY_MERCHANT_PRIVATE_KEY');
    }

    /**
     * @param string $merchantPrivateKey
     * @return $this
     */
    public function setMerchantPrivateKey($merchantPrivateKey)
    {
        if (is_string($merchantPrivateKey)) {
            $this->setValue('NOVAPAY_MERCHANT_PRIVATE_KEY', $merchantPrivateKey);
        }

        return $this;
    }

    /**
     * @return string|bool
     */
    public function getServerPublicKey()
    {
        return $this->getValue('NOVAPAY_SERVER_PUBLIC_KEY');
    }

    /**
     * @param string $serverPublicKey
     * @return $this
     */
    public function setServerPublicKey($serverPublicKey)
    {
        if (is_string($serverPublicKey)) {
            $this->setValue('NOVAPAY_SERVER_PUBLIC_KEY', $serverPublicKey);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isConnectionChecked()
    {
        return (bool)$this->getValue('NOVAPAY_CONNECTION_CHECKED');
    }

    /**
     * @param bool $connectionChecked
     * @return $this
     */
    public function setConnectionChecked($connectionChecked)
    {
        $this->setValue('NOVAPAY_CONNECTION_CHECKED', (bool)$connectionChecked);

        return $this;
    }

    /**
     * @return bool
     */
    public function isTwoStepPayment()
    {
        return (bool)$this->getValue('NOVAPAY_TWO_STEP_PAYMENT');
    }

    /**
     * @param bool $twoStepPayment
     * @return $this
     */
    public function setTwoStepPayment($twoStepPayment)
    {
        $this->setValue('NOVAPAY_TWO_STEP_PAYMENT', (bool)$twoStepPayment);

        return $this;
    }

    /**
     * @return bool
     */
    public function isSandboxMode()
    {
        return (bool)$this->getValue('NOVAPAY_SANDBOX_MODE');
    }

    /**
     * @param bool $sandboxMode
     * @return $this
     */
    public function setSandboxMode($sandboxMode)
    {
        $this->setValue('NOVAPAY_SANDBOX_MODE', (bool)$sandboxMode);

        return $this;
    }

    /**
     * @return bool
     */
    public function delete()
    {
        $result = true;

        foreach (array_keys($this->configurationList) as $name) {
            $result &= Configuration::deleteByName($name);
        }

        return $result;
    }
}
