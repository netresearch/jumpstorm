<?php
/**
 * Modman wrapper
 *
 * @category Magento_Toolbox
 * @package  Jumpstorm
 * @author   Thomas Birke <tbirke@netextreme.de>
 * @license  OSL 3.0
 * @link     https://github.com/quafzi/jumpstorm
 */
namespace Netresearch;

use \Exception as Exception;

/**
 * Modman wrapper
 *
 * @category Magento_Toolbox
 * @package  Jumpstorm
 * @author   Thomas Birke <tbirke@netextreme.de>
 * @license  OSL 3.0
 * @link     https://github.com/quafzi/jumpstorm
 */
class Modman
{
    protected $root;

    /**
     * set project's root directory
     *
     * @param string $path Path to root directory
     *
     * @return void
     */
    public function setRoot($path)
    {
        $this->root = $path;
    }

    /**
     * execute a modman command
     *
     * @param string $command Modman command
     *
     * @return int Return value
     */
    public function call($command)
    {
        $modman = dirname(__FILE__) . '/../../vendor/colinmollenhour/modman/modman';
        passthru("cd {$this->root};$modman $command", $return);
        return $return;
    }
}
