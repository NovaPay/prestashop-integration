<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

namespace NovaPay\PrestaShop\Configuration;

class ConfigurationAbstract
{
    /**
     * @var array
     */
    protected $configurationList = array();

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $shopId
     *
     * @return string|bool
     */
    protected function setValue($key, $value, $shopId = null)
    {
        if (!$shopId) {
            $shopId = \Context::getContext()->shop->id;
        }
        
        return \Configuration::updateValue($key, $value, false, null, (int)$shopId);
    }

    /**
     * @return bool
     */
    public function initialize()
    {
        $result = true;

        foreach (\Shop::getShops(false, null, true) as $shopId) {
            foreach ($this->configurationList as $name => $value) {
                if (\Configuration::hasKey($name, null, null, (int)$shopId) === false) {
                    $result &= $this->setValue($name, $value, $shopId);
                }
            }
        }

        return $result;
    }

    /**
     * @param string $key
     *
     * @return string|bool
     */
    protected function getValue($key)
    {
        return \Configuration::get($key, null, null, (int)\Context::getContext()->shop->id);
    }

    /**
     * @return bool
     */
    public function delete()
    {
        $result = true;

        foreach (array_keys($this->configurationList) as $name) {
            $result &= \Configuration::deleteByName($name);
        }

        return $result;
    }
}
