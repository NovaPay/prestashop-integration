<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

class NovaPayCallbackModuleFrontController extends NovaPayFrontController
{
    public function postProcess()
    {
        $message = Tools::file_get_contents('php://input');
        
        $isValidSignature = (new NovaPay\Security())->verifySignature(
            $message,
            $_SERVER['HTTP_X_SIGN'],
            $this->module->configuration->getServerPublicKey()
        );
        
        if ($isValidSignature) {
            $data = json_decode($message, true);

            if (isset($data['status']) && $data['status'] && isset($data['id']) && $data['id']) {
                $this->module->synchronize(
                    $data['id'],
                    $data['status'],
                    isset($data['external_id']) ? (int)$data['external_id'] : null
                );
            }
        }

        exit;
    }
}
