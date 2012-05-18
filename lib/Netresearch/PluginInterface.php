<?php
namespace Netresearch;

use Netresearch\Config;

interface PluginInterface
{
    public function __construct(Config $config);

    public function execute();
}

