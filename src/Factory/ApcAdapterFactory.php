<?php

namespace IllchukLock\Factory;

use IllchukLock\Adapter\Apc as ApcAdapter;
use IllchukLock\Apc\Apc as ApcWrapper;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ApcAdapterFactory implements FactoryInterface {

    const CONFIG_KEY = 'illchuk_lock';
    const CLASS_KEY = 'IllchukLock\Adapter\Apc';

    protected $configBootstrap = [
        'options_class' => 'IllchukLock\Options\ApcAdapter'
    ];

    public function __invoke(ContainerInterface $container, $requestedName,
    array $options = null) {
        $config = $container->has('Config') ? $container->get('Config') : [];
        if (isset($config[self::CONFIG_KEY][self::CLASS_KEY])) {
            $config = $config[self::CONFIG_KEY][self::CLASS_KEY];
        }
        else {
            $config = $this->configBootstrap;
        }

        $optionsClass = $config['options_class'];
        unset($config['options_class']);

        $options = new $optionsClass($config);
        return new ApcAdapter(
        new ApcWrapper, $options, new LockHandleFactory
        );
    }

}
