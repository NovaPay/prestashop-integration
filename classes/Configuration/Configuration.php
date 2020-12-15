<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

namespace NovaPay\PrestaShop\Configuration;

class Configuration extends ConfigurationAbstract
{
    public function __construct()
    {
        $this->configurationList = array(
            'NOVAPAY_NOVA_POSHTA_CARRIER_ID' => 0,
            'NOVAPAY_NOVA_POSHTA_CARRIER_ID_REFERENCE' => 0,
            'NOVAPAY_MERCHANT_ID' => '',
            'NOVAPAY_MERCHANT_PRIVATE_KEY' => '',
            'NOVAPAY_MERCHANT_PRIVATE_KEY_PASSWORD' => '',
            'NOVAPAY_SERVER_PUBLIC_KEY' => '',
            'NOVAPAY_CONNECTION_CHECKED' => '',
            'NOVAPAY_TWO_STEP_PAYMENT' => false,
            'NOVAPAY_SHOP_DIMENSION_UNIT_ID' => 0,
            'NOVAPAY_SHOP_WEIGHT_UNIT_ID' => 0,
            'NOVAPAY_SANDBOX_MODE' => false,
        );
    }

    /**
     * @return int
     */
    public function getCarrierId()
    {
        return (int)$this->getValue('NOVAPAY_NOVA_POSHTA_CARRIER_ID');
    }

    /**
     * @param int $carrierId
     *
     * @return $this
     */
    public function setCarrierId($carrierId)
    {
        $this->setValue('NOVAPAY_NOVA_POSHTA_CARRIER_ID', (int)$carrierId);

        return $this;
    }

    /**
     * @return int
     */
    public function getCarrierIdReference()
    {
        return (int)$this->getValue('NOVAPAY_NOVA_POSHTA_CARRIER_ID_REFERENCE');
    }

    /**
     * @param int $carrierIdReference
     *
     * @return $this
     */
    public function setCarrierIdReference($carrierIdReference)
    {
        $this->setValue('NOVAPAY_NOVA_POSHTA_CARRIER_ID_REFERENCE', (int)$carrierIdReference);

        return $this;
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
     *
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
     *
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
    public function getMerchantPrivateKeyPassword()
    {
        return $this->getValue('NOVAPAY_MERCHANT_PRIVATE_KEY_PASSWORD');
    }

    /**
     * @param string $password
     *
     * @return $this
     */
    public function setMerchantPrivateKeyPassword($password)
    {
        if (is_string($password)) {
            $this->setValue('NOVAPAY_MERCHANT_PRIVATE_KEY_PASSWORD', $password);
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
     *
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
     *
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
     *
     * @return $this
     */
    public function setTwoStepPayment($twoStepPayment)
    {
        $this->setValue('NOVAPAY_TWO_STEP_PAYMENT', (bool)$twoStepPayment);

        return $this;
    }
    
    /**
     * @return int
     */
    public function getShopDimensionUnitId()
    {
        return (int)$this->getValue('NOVAPAY_SHOP_DIMENSION_UNIT_ID');
    }

    /**
     * @param int $dimensionUnitId
     *
     * @return $this
     */
    public function setShopDimensionUnitId($dimensionUnitId)
    {
        $this->setValue('NOVAPAY_SHOP_DIMENSION_UNIT_ID', (int)$dimensionUnitId);

        return $this;
    }
    
    /**
     * @return int
     */
    public function getShopWeightUnitId()
    {
        return (int)$this->getValue('NOVAPAY_SHOP_WEIGHT_UNIT_ID');
    }

    /**
     * @param int $weightUnitId
     *
     * @return $this
     */
    public function setShopWeightUnitId($weightUnitId)
    {
        $this->setValue('NOVAPAY_SHOP_WEIGHT_UNIT_ID', (int)$weightUnitId);

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
     *
     * @return $this
     */
    public function setSandboxMode($sandboxMode)
    {
        $this->setValue('NOVAPAY_SANDBOX_MODE', (bool)$sandboxMode);

        return $this;
    }
}
