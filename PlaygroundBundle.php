<?php

namespace Runalyze\Bundle\PlaygroundBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class PlaygroundBundle extends Bundle
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('config.yml');
    }
}
