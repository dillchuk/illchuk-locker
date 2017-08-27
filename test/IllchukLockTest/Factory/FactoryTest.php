<?php

namespace IllchukLockTest\Factory;

use Zend\Db\Adapter\Adapter as ZendDbAdapter;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\Config as ServiceManagerConfig;

/**
 * @group illchuk_lock
 */
class FactoryTest extends \PHPUnit_Framework_TestCase {

    protected $serviceManager;

    /**
     * @var ZendDbAdapter
     */
    protected $adapter;
    protected $configMock = [
        'IllchukLock\Adapter\Db' =>
        ['options_class' => 'IllchukLock\Options\DbAdapter'],
        'IllchukLock\Adapter\DbMultiple' =>
        ['options_class' => 'IllchukLock\Options\DbMultipleAdapter'],
        'IllchukLock\Adapter\Apc' =>
        ['options_class' => 'IllchukLock\Options\ApcAdapter']
    ];

    public function setUp() {
        parent::setUp();
        $config = include __DIR__ . '/../../../config/module.config.php';
        $this->serviceManager = new ServiceManager($config['service_manager']);

        $this->serviceManager->setFactory('Zend\Db\Adapter\Adapter',
        function($sm) {
            return $this->getAdapter();
        });

        $this->serviceManager->setFactory('Config',
        function($sm) {
            return [
                'illchuk_lock' => $this->configMock
            ];
        });
    }

    protected function getAdapter() {
        if ($this->adapter) {
            return $this->adapter;
        }
        $config = include __DIR__ . '/../../dbadapter.php';
        $config = $config['db'];
        $config['driver'] = 'PDO';
        $this->adapter = new ZendDbAdapter($config);
        return $this->adapter;
    }

    public function testConfigFactory() {
        $config = $this->serviceManager->get('illchuk_lock_config');
        $this->assertEquals($this->configMock, $config);
    }

    public function testThrottleFactory() {
        $class = 'IllchukLock\Service\Locker';
        $instance = $this->serviceManager->get($class);
        $this->assertInstanceOf($class, $instance);

        /**
         * Alias.
         */
        $this->assertSame(
        $instance, $this->serviceManager->get('IllchukLock')
        );
    }

    public function testDbAdapterFactory() {
        $class = 'IllchukLock\Adapter\Db';
        $instance = $this->serviceManager->get($class);
        $this->assertInstanceOf($class, $instance);
    }

    public function testDbMultipleAdapterFactory() {
        $class = 'IllchukLock\Adapter\DbMultiple';
        $instance = $this->serviceManager->get($class);
        $this->assertInstanceOf($class, $instance);
    }

    public function testApcAdapterFactory() {
        $class = 'IllchukLock\Adapter\Apc';
        $instance = $this->serviceManager->get($class);
        $this->assertInstanceOf($class, $instance);
    }

    public function testWithoutFactoryConfig() {
        $this->serviceManager->setAllowOverride(true);
        $this->serviceManager->setFactory('Config',
        function($sm) {
            return [];
        });

        $class = 'IllchukLock\Service\Locker';
        $instance = $this->serviceManager->get($class);
        $this->assertInstanceOf($class, $instance);
    }

    public function testWithoutFactoryConfigApc() {
        $this->serviceManager->setAllowOverride(true);
        $this->serviceManager->setFactory('Config',
        function($sm) {
            return [];
        });
        $optionsKey = 'IllchukLock\Options\Locker';
        $options = $this->serviceManager->get($optionsKey);
        $options->setAdapterClass('IllchukLock\Adapter\Apc');
        $this->serviceManager->setService($optionsKey, $options);

        $class = 'IllchukLock\Service\Locker';
        $instance = $this->serviceManager->get($class);
        $this->assertInstanceOf($class, $instance);
    }

    public function testWithoutFactoryConfigApcThrottle() {
        $this->serviceManager->setAllowOverride(true);
        $this->serviceManager->setFactory('Config',
        function($sm) {
            return [];
        });
        $optionsKey = 'IllchukLock\Options\Locker';
        $options = $this->serviceManager->get($optionsKey);
        $this->serviceManager->setService($optionsKey, $options);

        $class = 'illchuk_lock_apc';
        $instance = $this->serviceManager->get($class);
        $this->assertEquals(
        'IllchukLock\Adapter\Apc', $instance->getOptions()->getAdapterClass()
        );
    }

}
