<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

class NovaPayResponse
{
    /**
     * @var int
     */
    private $code = null;

    /**
     * @var array
     */
    private $data = array();

    /**
     * @var NovaPayError[]
     */
    private $errors = array();

    /**
     * @return int|null
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param int $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = (int)$code;

        return $this;
    }
    
    /**
     * @param string $key
     * @return mixed
     */
    public function getValue($key)
    {
        if (!is_string($key)) {
            return null;
        }

        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setValue($key, $value)
    {
        if (is_string($key)) {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * @param array $values
     * @return $this
     */
    public function setValues(array $values)
    {
        foreach ($values as $key => $value) {
            $this->setValue($key, $value);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return (bool)count($this->errors);
    }

    /**
     * @return NovaPayError[]
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * @param NovaPayError $error
     * @return $this
     */
    public function addError(NovaPayError $error)
    {
        if ($error) {
            $this->errors[] = $error;
        }

        return $this;
    }
}
