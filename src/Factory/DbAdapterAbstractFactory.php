<?php

namespace IllchukLock\Factory;

use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Zend\Db\TableGateway\TableGateway;
use Interop\Container\ContainerInterface;
use IllchukLock\Factory\LockHandleFactory;

class DbAdapterAbstractFactory implements AbstractFactoryInterface {

    const CONFIG_KEY = 'illchuk_lock';

    protected $configBootstrap = [
        'IllchukLock\Adapter\Db' => [
            'options_class' => 'IllchukLock\Options\DbAdapter'
        ],
        'IllchukLock\Adapter\DbMultiple' => [
            'options_class' => 'IllchukLock\Options\DbMultipleAdapter'
        ],
    ];

    /**
     * @var array
     */
    protected $config;

    /**
     * @param  ContainerInterface $container
     * @return array
     */
    protected function getConfig(ContainerInterface $container) {
        if (is_array($this->config)) {
            return $this->config;
        }

        $config = $container->has('Config') ? $container->get('Config') : [];
        if (isset($config[self::CONFIG_KEY])) {
            $this->config = $config[self::CONFIG_KEY];
        }
        else {
            $this->config = $this->configBootstrap;
        }
        return $this->config;
    }

    public function __invoke(ContainerInterface $container, $requestedName,
    array $options = null) {
        $config = $this->getConfig($container)[$requestedName];
        $optionsClass = $config['options_class'];
        unset($config['options_class']);

        $options = new $optionsClass($config);
        $gateway = new TableGateway(
        $options->getDbTable(), $container->get($options->getDbAdapterClass())
        );
        return new $requestedName($gateway, $options, new LockHandleFactory);
    }

    public function canCreate(ContainerInterface $container, $requestedName) {
        $config = $this->getConfig($container);

        $optionsClass = isset($config[$requestedName]['options_class']) ?
        $config[$requestedName]['options_class'] : null;
        if (!is_subclass_of($optionsClass, 'Zend\Stdlib\AbstractOptions')) {
// @codeCoverageIgnoreStart
            return false;
        }
// @codeCoverageIgnoreEnd

        return is_subclass_of(
        $requestedName, 'IllchukLock\Adapter\AbstractAdapter'
        );
    }

}
