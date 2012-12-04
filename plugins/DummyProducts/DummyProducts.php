<?php
namespace DummyProducts;

use \Mage as Mage;
use Netresearch\Config;
use Netresearch\Logger;
use Netresearch\PluginInterface as JumpstormPlugin;

/**
 * create some sample products
 */
class DummyProducts implements JumpstormPlugin
{
    protected $config;

    public function __construct(Config $config)
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
        if (!Mage::getSingleton('fastsimpleimport/import')) {
            Logger::error(
                'Could not create dummy products. Please add extension %s to be installed',
                array('git://github.com/avstudnitz/AvS_FastSimpleImport.git'),
                false
            );
            return;
        }
        $settings = $this->config->plugins->{$this->getPluginName()};

        $countOfSimpleProducts       = 0;
        $countOfConfigurableProducts = 0;
        $countOfBundleProducts       = 0;
        $countOfGroupedProducts      = 0;
        $countOfVirtualProducts      = 0;
        $countOfDownloadProducts     = 0;

        if ($settings instanceof \Zend_Config) {
            if ($settings->simpleProducts) {
                $countOfSimpleProducts = $settings->simpleProducts;
            }
            if ($settings->configurableProducts) {
                $countOfConfigurableProducts = $settings->configurableProducts;
            }
            if ($settings->bundleProducts) {
                $countOfBundleProducts = $settings->bundleProducts;
            }
            if ($settings->groupedProducts) {
                $countOfGroupedProducts = $settings->groupedProducts;
            }
            if ($settings->virtualProducts) {
                $countOfVirtualProducts = $settings->virtualProducts;
            }
            if ($settings->downloadProducts) {
                $countOfDownloadProducts = $settings->downloadProducts;
            }
        } else {
            $countOfSimpleProducts = $settings;
        }
        $data = array();
        $data = $this->getSimpleProducts($countOfSimpleProducts);
        /*
         * @todo
        $data = array_merge($data, $this->getConfigurableProducts($countOfConfigurableProducts));
        $data = array_merge($data, $this->getBundleProducts($countOfBundleProducts));
        $data = array_merge($data, $this->getGroupedProducts($countOfGroupedProducts));
        $data = array_merge($data, $this->getVirtualProducts($countOfVirtualProducts));
        $data = array_merge($data, $this->getDownloadProducts($countOfDownloadProducts));
        */

        $this->import($data);
    }

    protected function getUniqueCode($length)
    {
        $code = md5(uniqid(rand(), true));
        return ($length != "") ? substr($code, 0, $length) : $code;
    }

    protected function getBasicData()
    {
        $randomString = $this->getUniqueCode(20);
        return array(
            'sku'                    => $randomString,
            '_type'                  => 'simple',
            '_attribute_set'         => 'Default',
            '_product_websites'      => 'base',
            '_category'              => rand(22, 34),
            'name'                   => $randomString,
            'price'                  => 0.99,
            'special_price'          => 0.90,
            'cost'                   => 0.50,
            'description'            => 'Default',
            'short_description'      => 'Default',
            'media_gallery'          => 'inner',
            'meta_title'             => 'Default',
            'meta_description'       => 'Default',
            'meta_keywords'          => 'Default',
            'weight'                 => 11,
            'status'                 => 1,
            'visibility'             => 4,
            'tax_class_id'           => 2,
            'qty'                    => 100,
            'is_in_stock'            => 1,
            'enable_googlecheckout'  => '1',
            'gift_message_available' => '0',
            'url_key'                => strtolower($randomString),
        );
    }

    protected function getSimpleProducts($count)
    {
        $data = array();
        for ($i = 1; $i <= $count; $i++) {
            $data[] = $this->getBasicData();
        }
        return $data;
    }

    protected function import($data)
    {
        include('import.php');
    }
}



