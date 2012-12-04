<?php
namespace Netresearch\Source;

use Netresearch\Logger;
use Netresearch\Source\SourceInterface;
use Netresearch\Source\Base as Source;

use \Exception as Exception;

class MagentoConnect extends Source implements SourceInterface
{
    public function copy($target, $branch = 'master')
    {
        chmod($this->baseTarget . '/mage', 0777);
        passthru("cd {$this->baseTarget};./mage mage-setup;");
        // identifier given: magentoconnect://community/some_key
        // what we need: community some_key
        $identifier = str_replace('/', ' ', substr($this->source, strlen('magentoconnect://')));
        
        $command = "cd {$this->baseTarget};./mage download $identifier";
        $response = exec($command);

        $path = str_replace('Saved to: ', '', $response);
        exec("mkdir $target;cd $target;tar -xzvf $path");
    }
}
