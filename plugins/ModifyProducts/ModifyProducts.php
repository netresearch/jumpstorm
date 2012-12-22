<?php
namespace ModifyProducts;

use \Mage as Mage;
use Netresearch\Config\Base as BaseConfig;
use Netresearch\Logger;
use Netresearch\PluginInterface as JumpstormPlugin;

/**
 * set some product data
 */
class ModifyProducts implements JumpstormPlugin
{
    protected $config;

    public function __construct(BaseConfig $config)
    {
        $this->config = $config;
    }

    protected function getPluginName()
    {
        return current(explode('\\', __CLASS__));
    }

    public function execute()
    {
        Mage::app('admin');
        $settings = $this->config->plugins->{$this->getPluginName()};
        if ($settings instanceof BaseConfig) {
            foreach ($settings as $sku=>$setting) {
                if ($setting instanceof BaseConfig) {
                    $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
                    if (!$product->getId()) {
                        Logger::error('Product with SKU %s not found', array($sku), false);
                        continue;
                    }
                    $this->modifyProduct($product, $setting);
                } else {
                    Logger::error('Invalid configuration for SKU %s', array($sku), false);
                }
            }
        } else {
            Logger::error('Invalid configuration for plugin %s', array($this->getPluginName()), false);
        }
    }

    protected function modifyProduct(\Mage_Catalog_Model_Product $product, BaseConfig $settings)
    {
        foreach ($settings as $attribute=>$value) {
            $product->setData($attribute, $value);
            Logger::log('* change attribute <comment>%s</comment> of product #%d: "%s"', array(
                $attribute,
                $product->getId(),
                $value
            ));
        }
        $product->save();
    }
}



