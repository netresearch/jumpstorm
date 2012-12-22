<?php
namespace Reindex;

use \Mage as Mage;
use Netresearch\Config\Base as BaseConfig;
use Netresearch\Logger;
use Netresearch\PluginInterface as JumpstormPlugin;

/**
 * flush Magento cache
 */
class Reindex implements JumpstormPlugin
{
    protected $config;

    public function __construct(BaseConfig $config)
    {
        $this->config = $config;
    }

    public function execute()
    {
        $settings = $this->config->plugins->Reindex;
        if ($settings instanceof BaseConfig) {
            $processes = $this->_getProcesses($settings);
            foreach ($processes as $process) {
                $code = $process->getIndexer()->getName();
                /* @var $process Mage_Index_Model_Process */
                try {
                    Logger::comment('* rebuilding %s', array(trim($code)));
                    //$process->reindexEverything();
                    Logger::log('* Index <info>%s</info> was rebuilt successfully', array(trim($code)));
                } catch (Mage_Core_Exception $e) {
                    Logger::error('An error occured while rebuilding index %s: %s', array(trim($code), $e->getMessage()), false);
                } catch (Exception $e) {
                    Logger::error('A strange error occured while rebuilding index %s: %s', array(trim($code), $e->getMessage()), false);
                }
            }
        } else {
            Logger::error('Invalid configuration for plugin Reindex', array(), false);
        }
    }

    protected function _getProcesses($setting)
    {
        $processes = array();
        if ($setting == 'all') {
            $collection = $this->_getIndexer()->getProcessesCollection();
            foreach ($collection as $process) {
                $processes[] = $process;
            }
        } else if (!empty($setting)) {
            foreach ($setting as $code) {
                $process = $this->_getIndexer()->getProcessByCode(trim($code));
                if (!$process) {
                    Logger::error('Unknown indexer with code %s', array(trim($code)), false);
                } else {
                    $processes[] = $process;
                }
            }
        }
        return $processes;
    }

    /**
     * Get Indexer instance
     *
     * @return Mage_Index_Model_Indexer
     */
    protected function _getIndexer()
    {
        return Mage::getSingleton('index/indexer');
    }
}


