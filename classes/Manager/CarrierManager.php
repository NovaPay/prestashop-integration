<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

namespace NovaPay\PrestaShop\Manager;

use NovaPay\PrestaShop\Configuration\Configuration;

class CarrierManager
{
    /**
     * @var string
     */
    const MODULE_NAME = 'novapay';

    /**
     * @var string
     */
    const CARRIER_NAME = 'Нова Пошта';

    /**
     * @var array
     */
    const CARRIER_DESCRIPTION = array(
        'en' => 'Delivery to the office of Nova Poshta',
        'uk' => 'Доставка у відділення Нової Пошти',
        'ru' => 'Доставка в отделение Новой Почты'
    );

    /**
     * @var string
     */
    const CARRIER_DEFAULT_LANGUAGE_ISO_CODE = 'en';

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param \Carrier $carrier
     */
    protected function addDescription(\Carrier $carrier)
    {
        foreach (\Language::getLanguages() as $language) {
            $id = $language['id_lang'];
            $code = $language['iso_code'];
            $carrier->delay[$id] = isset(self::CARRIER_DESCRIPTION[$code]) ?
                self::CARRIER_DESCRIPTION[$code] :
                self::CARRIER_DESCRIPTION[self::CARRIER_DEFAULT_LANGUAGE_ISO_CODE];
        }
    }
    
    /**
     * @param \Carrier $carrier
     *
     * @return bool
     */
    protected function addGroups(\Carrier $carrier)
    {
        $ids = array();
        
        if (($groups = \Group::getGroups(true)) && is_array($groups)) {
            foreach ($groups as $group) {
                $ids[] = (int)$group['id_group'];
            }
        }

        return $carrier->setGroups($ids);
    }

    /**
     * @param \Carrier $carrier
     *
     * @return bool
     */
    protected function addRangePrice(\Carrier $carrier)
    {
        $rangePrice = new \RangePrice();
        $rangePrice->id_carrier = $carrier->id;
        $rangePrice->delimiter1 = '0';
        $rangePrice->delimiter2 = '1000000';

        return $rangePrice->add();
    }

    /**
     * @param \Carrier $carrier
     *
     * @return bool
     */
    protected function addRangeWeight(\Carrier $carrier)
    {
        $rangeWeight = new \RangeWeight();
        $rangeWeight->id_carrier = $carrier->id;
        $rangeWeight->delimiter1 = '0';
        $rangeWeight->delimiter2 = '1000000';

        return $rangeWeight->add();
    }

    /**
     * @param \Carrier $carrier
     *
     * @return bool
     */
    protected function addZones(\Carrier $carrier)
    {
        if (($zones = \Zone::getZones(true)) && is_array($zones)) {
            foreach ($zones as $zone) {
                if (!$carrier->addZone($zone['id_zone'])) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * @param \Carrier $carrier
     *
     * @return bool
     */
    protected function copyImage(\Carrier $carrier)
    {
        return copy(
            _PS_MODULE_DIR_.self::MODULE_NAME.'/views/img/carrier.jpg',
            _PS_SHIP_IMG_DIR_.'/'.(int)$carrier->id.'.jpg'
        );
    }

    /**
     * @return bool
     */
    public function createCarrier()
    {
        $carrier = new \Carrier();
        $carrier->name = self::CARRIER_NAME;
        $carrier->active = false;
        $carrier->shipping_handling = false;
        $carrier->range_behavior = 0;
        $carrier->is_module = true;
        $carrier->shipping_external = true;
        $carrier->external_module_name = self::MODULE_NAME;
        $carrier->need_range = true;
        
        $this->addDescription($carrier);

        if (!$carrier->add()) {
            return false;
        }
        
        $this->addGroups($carrier);
        $this->addRangePrice($carrier);
        $this->addRangeWeight($carrier);
        $this->addZones($carrier);
        $this->copyImage($carrier);
        
        $this->configuration
            ->setCarrierId($carrier->id)
            ->setCarrierIdReference($carrier->id);
        
        return true;
    }

    /**
     * @return bool
     */
    public function deleteCarrier()
    {
        $carrier = new \Carrier($this->configuration->getCarrierId());
        if (\Validate::isLoadedObject($carrier)) {
            return $carrier->delete();
        }

        return true;
    }
}
