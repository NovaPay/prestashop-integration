<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

namespace NovaPay\DeliveryInfo;

use NovaPay\DataSourceInterface;

class Request implements DataSourceInterface
{
    /**
     * @var string
     */
    private $modelName = '';

    /**
     * @var string
     */
    private $methodName = '';

    /**
     * @var array
     */
    private $methodProperties = array();

    /**
     * @param string $modelName
     *
     * @return $this
     */
    public function setModelName($modelName)
    {
        $this->modelName = (string)$modelName;

        return $this;
    }

    /**
     * @param string $methodName
     *
     * @return $this
     */
    public function setMethodName($methodName)
    {
        $this->methodName = (string)$methodName;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return $this
     */
    public function setMethodProperty($key, $value)
    {
        $this->methodProperties[$key] = $value;

        return $this;
    }

    /**
     * @param array $properties
     *
     * @return $this
     */
    public function setMethodProperties(array $properties)
    {
        $this->methodProperties = $properties;

        return $this;
    }

    /**
     * @return array
     */
    public function getNovaPayData()
    {
        return array(
            'modelName' => $this->modelName,
            'calledMethod' => $this->methodName,
            'methodProperties' => $this->methodProperties,
        );
    }
}
