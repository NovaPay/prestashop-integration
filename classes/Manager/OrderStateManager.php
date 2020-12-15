<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

namespace NovaPay\PrestaShop\Manager;

class OrderStateManager
{
    /**
     * @var string
     */
    const MODULE_NAME = 'novapay';

    /**
     * @var array
     */
    const ORDER_STATES = array(
        'NOVAPAY_OS_WAITING_FOR_PAYMENT' => array(
            'color' => '#4169E1',
            'name' => array(
                'en' => 'Waiting for NovaPay payment',
                'uk' => 'Очікується платіж NovaPay',
                'ru' => 'Ожидается платеж NovaPay',
            ),
        ),
        'NOVAPAY_OS_WAITING_FOR_PAYMENT_CONFIRMATION' => array(
            'color' => '#34209E',
            'name' => array(
                'en' => 'Waiting for NovaPay payment confirmation',
                'uk' => 'Очікується підтвердження платежу NovaPay',
                'ru' => 'Ожидается подтверждение платежа NovaPay',
            ),
        ),
        'NOVAPAY_OS_WAITING_FOR_PAYMENT_CONFIRMATION_AFTER_DELIVERY' => array(
            'color' => '#34209E',
            'name' => array(
                'en' => 'Waiting for NovaPay payment confirmation after delivery',
                'uk' => 'Очікується підтвердження платежу NovaPay після доставки',
                'ru' => 'Ожидается подтверждение платежа NovaPay после доставки',
            ),
        ),
        'NOVAPAY_OS_PAYMENT_CANCELED' => array(
            'color' => '#8f0621',
            'name' => array(
                'en' => 'NovaPay payment canceled',
                'uk' => 'Платіж NovaPay скасований',
                'ru' => 'Платеж NovaPay отменен',
            ),
        ),
    );

    /**
     * @var string
     */
    const DEFAULT_LANGUAGE_ISO_CODE = 'en';

    /**
     * @param string $state
     * @param string $color
     *
     * @return int
     */
    private function createStateId($state, $color)
    {
        $data = array(
            'module_name' => self::MODULE_NAME,
            'color' => $color,
            'unremovable' => 1,
        );

        if (\Db::getInstance()->insert('order_state', $data)) {
            $insertedId = (int)\Db::getInstance()->Insert_ID();
            \Configuration::updateGlobalValue($state, $insertedId);

            return $insertedId;
        }

        throw new Exception('Not able to insert the new order state');
    }

    /**
     * @param string $state
     * @param string $color
     *
     * @return int
     */
    private function getStateId($state, $color)
    {
        $stateId = \Configuration::getGlobalValue($state);
        
        if ($stateId === false) {
            return $this->createStateId($state, $color);
        }

        return (int)$stateId;
    }

    /**
     * @param int $orderStateId
     * @param int $languageId
     *
     * @return bool
     */
    private function stateLangAlreadyExists($orderStateId, $languageId)
    {
        $query = (new \DbQuery())
            ->select('id_order_state')
            ->from('order_state_lang')
            ->where('id_order_state = '.$orderStateId.' AND id_lang = '.$languageId);

        return (bool)\Db::getInstance()->getValue($query->build());
    }

    /**
     * @param int $orderStateId
     * @param string $orderStateName
     * @param int $languageId
     */
    private function insertNewStateLang($orderStateId, $orderStateName, $languageId)
    {
        $data = array(
            'id_order_state' => $orderStateId,
            'id_lang' => (int) $languageId,
            'name' => pSQL($orderStateName),
            'template' => 'payment',
        );

        if (!\Db::getInstance()->insert('order_state_lang', $data)) {
            throw new Exception('Not able to insert the new order state language');
        }
    }

    /**
     * @param int $orderStateId
     * @param array $orderStateName
     */
    private function createStateLangs($orderStateId, array $orderStateName)
    {
        foreach (\Language::getLanguages() as $language) {
            $languageId = (int)$language['id_lang'];

            if ($this->stateLangAlreadyExists($orderStateId, $languageId)) {
                continue;
            }
            
            $name = isset($orderStateName[$language['iso_code']]) ? $orderStateName[$language['iso_code']] :
                $orderStateName[self::DEFAULT_LANGUAGE_ISO_CODE];
            
            $this->insertNewStateLang(
                $orderStateId,
                $name,
                $languageId
            );
        }
    }

    /**
     * @param int $orderStateId
     *
     * @return bool
     */
    private function setStateIcons($orderStateId)
    {
        $iconExtension = '.gif';
        $iconToPaste = _PS_ORDER_STATE_IMG_DIR_ . $orderStateId . $iconExtension;

        if (file_exists($iconToPaste) && !is_writable($iconToPaste)) {
            return false;
        }

        $iconsFolderOrigin = _PS_MODULE_DIR_ . self::MODULE_NAME . '/views/img/order_state_icons/';
        $iconToCopy = $iconsFolderOrigin . 'waiting' . $iconExtension;

        return copy($iconToCopy, $iconToPaste);
    }

    /**
     * @return bool
     */
    public function createOrderStates()
    {
        foreach (self::ORDER_STATES as $state => $data) {
            $orderStateId = $this->getStateId($state, $data['color']);

            $this->createStateLangs($orderStateId, $data['name']);
            $this->setStateIcons($orderStateId);
        }

        return true;
    }
}
