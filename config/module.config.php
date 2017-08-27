<?php

use IllchukLock\Factory\LockerFactory;
use IllchukLock\Factory\ApcLockerFactory;

return [
    'service_manager' => [
        'aliases' => [
            'IllchukLock' => 'IllchukLock\Service\Locker',
        ],
        'abstract_factories' => [
            IllchukLock\Factory\DbAdapterAbstractFactory::class,
        ],
        'factories' => [
            'illchuk_lock_config' => 'IllchukLock\Factory\ConfigFactory',
            'illchuk_lock_apc' => ApcLockerFactory::class,
            'IllchukLock\Service\Locker' => LockerFactory::class,
            'IllchukLock\Options\Locker' => 'IllchukLock\Factory\LockerOptionsFactory',
            'IllchukLock\Adapter\Apc' => 'IllchukLock\Factory\ApcAdapterFactory'
        ],
    ],
];
