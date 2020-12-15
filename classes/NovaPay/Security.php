<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

namespace NovaPay;

class Security
{
    /**
     * @param string $message
     * @param mixed $privateKey
     * @param string $password
     *
     * @return string|false
     */
    public function generateSignature($message, $privateKey, $password)
    {
        if (!openssl_sign($message, $signature, openssl_pkey_get_private($privateKey, $password))) {
            return false;
        }
        
        return base64_encode($signature);
    }

    /**
     * @param string $message
     * @param string $signature
     * @param mixed $publicKey
     *
     * @return bool
     */
    public function verifySignature($message, $signature, $publicKey)
    {
        if (!is_string($signature)) {
            return false;
        }
        
        $signature = base64_decode($signature, true);
        if ($signature === false) {
            return false;
        }
        
        $result = openssl_verify($message, $signature, $publicKey);
        
        return $result === 1;
    }
}
