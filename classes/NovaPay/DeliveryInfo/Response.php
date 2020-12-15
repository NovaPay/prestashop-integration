<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

namespace NovaPay\DeliveryInfo;

use NovaPay\Response as BaseResponse;

class Response extends BaseResponse
{
    /**
     * @var bool
     */
    private $success = false;

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->success;
    }

    /**
     * @param bool $success
     *
     * @return $this
     */
    public function setSuccess($success)
    {
        $this->success = (bool)$success;

        return $this;
    }
}
