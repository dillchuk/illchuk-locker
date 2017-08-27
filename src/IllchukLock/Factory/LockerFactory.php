<?php

namespace IllchukLock\Factory;

use IllchukLock\Service\Locker;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class LockerFactory implements FactoryInterface {

    public function __invoke(ContainerInterface $container, $requestedName,
    array $options = null) {
        $options = $container->get('IllchukLock\Options\Locker');
        return new Locker(
        $container->get($options->getAdapterClass()), $options
        );
    }

}
