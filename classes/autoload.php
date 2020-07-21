<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

spl_autoload_register(function ($className) {
    if (file_exists($file = dirname(__FILE__).DIRECTORY_SEPARATOR.$className.'.php')) {
        require_once($file);
    }
});
