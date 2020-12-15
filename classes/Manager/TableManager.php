<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

namespace NovaPay\PrestaShop\Manager;

class TableManager
{
    /**
     * @var string
     */
    const TABLE_CART_WAREHOUSE = 'novapay_cart_warehouse';

    /**
     * @var string
     */
    const TABLE_SESSION = 'novapay_session';

    /**
     * @var string
     */
    const TABLE_PAYMENT = 'novapay_payment';

    /**
     * @return bool
     */
    public function createCartWarehouseTable()
    {
        $query = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.self::TABLE_CART_WAREHOUSE.'` (
            `id_'.self::TABLE_CART_WAREHOUSE.'` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_cart` int(10) unsigned NOT NULL,
            `reference` varchar(36) NOT NULL,
            `city_reference` varchar(36) NOT NULL,
            `delivery_price` decimal(20,6) NOT NULL,
            PRIMARY KEY (`id_'.self::TABLE_CART_WAREHOUSE.'`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8;';
            
        return \Db::getInstance()->execute($query);
    }
    
    /**
     * @return bool
     */
    public function createSessionTable()
    {
        $query = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.self::TABLE_SESSION.'` (
            `id_'.self::TABLE_SESSION.'` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `session_id` varchar(50) NOT NULL,
            `merchant_id` varchar(50) NOT NULL,
            `client_first_name` varchar(20),
            `client_last_name` varchar(20),
            `client_patronymic` varchar(20),
            `client_phone` varchar(20) NOT NULL,
            `status` varchar(50) NOT NULL,
            PRIMARY KEY (`id_'.self::TABLE_SESSION.'`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8;';
            
        return \Db::getInstance()->execute($query);
    }

    /**
     * @return bool
     */
    public function createPaymentTable()
    {
        $query = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.self::TABLE_PAYMENT.'` (
            `id_'.self::TABLE_PAYMENT.'` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `merchant_id` varchar(50) NOT NULL,
            `session_id` varchar(50) NOT NULL,
            `payment_id` varchar(50) NOT NULL,
            `external_id` varchar(9) NOT NULL,
            `amount` decimal(20,6) NOT NULL,
            `products` text NOT NULL,
            `use_hold` tinyint(1) NOT NULL,
            `delivery` text NOT NULL,
            `delivery_price` decimal(20,6) NOT NULL,
            PRIMARY KEY (`id_'.self::TABLE_PAYMENT.'`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8;';
            
        return \Db::getInstance()->execute($query);
    }

    /**
     * @return bool
     */
    public function createTables()
    {
        return $this->createCartWarehouseTable()
            && $this->createSessionTable()
            && $this->createPaymentTable();
    }

    /**
     * @return bool
     */
    public function dropCartWarehouseTable()
    {
        $query = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.self::TABLE_CART_WAREHOUSE.'`';
        return \Db::getInstance()->execute($query);
    }

    /**
     * @return bool
     */
    public function dropSessionTable()
    {
        $query = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.self::TABLE_SESSION.'`';
        return \Db::getInstance()->execute($query);
    }

    /**
     * @return bool
     */
    public function dropPaymentTable()
    {
        $query = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.self::TABLE_PAYMENT.'`';
        return \Db::getInstance()->execute($query);
    }

    /**
     * @return bool
     */
    public function dropTables()
    {
        return $this->dropCartWarehouseTable()
            && $this->dropSessionTable()
            && $this->dropPaymentTable();
    }
}
