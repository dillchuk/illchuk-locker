<?php

namespace IllchukLock\Factory;

use IllchukLock\Options\Locker as LockerOptions;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class LockerOptionsFactory implements FactoryInterface {

    const CONFIG_KEY = 'locker';

    public function __invoke(ContainerInterface $container, $requestedName,
    array $options = null) {
        $config = $container->get('illchuk_lock_config');
        return new LockerOptions(
        isset($config[self::CONFIG_KEY]) ? $config[self::CONFIG_KEY] : []
        );
    }

}
