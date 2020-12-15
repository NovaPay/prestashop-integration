<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

spl_autoload_register(function ($className) {
    $file = dirname(__FILE__).DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $className).'.php';
    if (!file_exists($file)) {
        $file = dirname(__FILE__).DIRECTORY_SEPARATOR.str_replace(array('NovaPay\\PrestaShop\\', '\\'), array('', DIRECTORY_SEPARATOR), $className).'.php';
        if (!file_exists($file)) {
            $file = dirname(__FILE__).DIRECTORY_SEPARATOR.$className.'.php';
            if (!file_exists($file)) {
                $file = null;
            }
        }
    }
    
    if ($file) {
        require_once($file);
    }
});
