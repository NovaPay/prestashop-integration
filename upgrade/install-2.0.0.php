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

function upgrade_module_2_0_0(NovaPay $module)
{
    return (new NovaPay\PrestaShop\Manager\CarrierManager($module->configuration))->createCarrier()
        && (new NovaPay\PrestaShop\Manager\OrderStateManager())->createOrderStates()
        && (new NovaPay\PrestaShop\Manager\TableManager())->createTables()
        && Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'novapay_payment`
            ADD `payment_id` VARCHAR(50) NOT NULL AFTER `session_id`,
            ADD `delivery` TEXT NOT NULL,
            ADD `delivery_price` DECIMAL(20,6) NOT NULL;')
        && $module->addOverride('Hook')
        && $module->unregisterHook('displayBeforeCarrier')
        && $module->registerHooks();
}
