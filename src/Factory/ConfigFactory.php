<?php

namespace IllchukLock\Factory;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ConfigFactory implements FactoryInterface {

    const CONFIG_KEY = 'illchuk_lock';

    public function __invoke(ContainerInterface $container, $requestedName,
    array $options = null) {
        $config = $container->get('Config');
        return isset($config[self::CONFIG_KEY]) ? $config[self::CONFIG_KEY] : [];
    }

}
