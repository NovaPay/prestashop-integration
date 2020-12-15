<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

namespace NovaPay\PrestaShop\Manager;

class Exception extends \PrestaShopExceptionCore
{
    /**
     * @var int
     */
    const HTTP_CODE = 400;

    /**
     * @var string|array
     */
    private $messages;

    /**
     * @param string|array $messages
     */
    public function __construct($messages)
    {
        parent::__construct();

        $this->messages = $messages;
    }

    /**
     * @return array
     */
    public function getArrayMessages()
    {
        return (array)$this->messages;
    }

    /**
     * @return int
     */
    public function getHTTPCode()
    {
        return $this::HTTP_CODE;
    }
}
