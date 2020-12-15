<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_0_1(NovaPay $module)
{
    return Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'novapay_cart_warehouse`
        ADD `delivery_price` DECIMAL(20,6) NOT NULL;');
}
