<?php
namespace SetRewriteBase;

use \Mage as Mage;
use Netresearch\Config;
use Netresearch\Logger;
use Netresearch\PluginInterface as JumpstormPlugin;

/**
 * set RewriteBase in .htaccess
 *
 * You will probably need to call this plugin with parameter "/" to make your Magento work with VirtualDocumentRoot
 */
class SetRewriteBase implements JumpstormPlugin
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function execute()
    {
        $htaccess = $this->config->common->magento->target . DIRECTORY_SEPARATOR . '.htaccess';
        $rewriteBase = $this->config->plugins->SetRewriteBase;
        if ('/' == substr($rewriteBase, 0, 1)) {
            passthru(str_replace(
                'htaccess',
                $htaccess,
                'mv htaccess htaccess.orig;sed "s|#RewriteBase /magento/|RewriteBase /|g" htaccess.orig > htaccess'
            ));
        }
    }
}


