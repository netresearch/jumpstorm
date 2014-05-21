<?php
namespace Netresearch;

use Netresearch\Config\Base as BaseConfig;

interface PluginInterface
{
    public function __construct(BaseConfig $config);

    public function execute();
}

