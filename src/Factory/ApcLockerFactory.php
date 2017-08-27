<?php

namespace IllchukLock\Factory;

use IllchukLock\Service\Locker;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * A shortcut for APC adapter, rather than modifying locker config.
 */
class ApcLockerFactory implements FactoryInterface {

    public function __invoke(ContainerInterface $container, $requestedName,
    array $options = null) {
        $adapterClass = 'IllchukLock\Adapter\Apc';
        $options = clone $container->get('IllchukLock\Options\Locker');
        $options->setAdapterClass($adapterClass);
        return new Locker(
        $container->get($options->getAdapterClass()), $options
        );
    }

}
