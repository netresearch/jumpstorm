<?php
namespace Netresearch\Source;

use Netresearch\Logger;
use Netresearch\Source\SourceInterface;
use Netresearch\Source\Base as Source;

use \Exception as Exception;

class MagentoConnect extends Source implements SourceInterface
{
    protected $magentoRoot;

    public function copy($target, $branch = 'master')
    {
        if (is_null($this->magentoRoot)) {
            throw new Exception("Please provide path to mage shell script (i.e. Magento root directory)");
        }

        $mage = $this->magentoRoot . '/mage';
        chmod($mage, 0777);
        exec("$mage mage-setup");

        // identifier given: magentoconnect://community/some_key
        // what we need: community some_key
        $identifier = str_replace('/', ' ', substr($this->source, strlen('magentoconnect://')));

        $command = "$mage download $identifier";
        $response = exec($command);

        $path = str_replace('Saved to: ', '', $response);
        exec("mkdir $target; cd $target; tar -xzvf $path");
    }

    public function setMagentoRoot($path)
    {
        $this->magentoRoot = $path;
    }
}
