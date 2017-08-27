<?php

namespace IllchukLockTest;

use IllchukLock\Module;

/**
 * @group illchuk_lock
 */
class ModuleTest extends \PHPUnit_Framework_TestCase {

    protected $module;

    public function setUp() {
        parent::setUp();

        $this->module = new Module;
    }

    public function testGetConfig() {
        $config = $this->module->getConfig();
        $this->assertTrue(isset($config['service_manager']));
    }

    public function testGetAutoloaderConfig() {
        $config = $this->module->getAutoloaderConfig();
        $namespace = $config['Zend\Loader\StandardAutoloader']['namespaces'];
        $this->assertEquals('IllchukLock', key($namespace));
        $this->assertRegExp('#/IllchukLock#', current($namespace));
    }

}
