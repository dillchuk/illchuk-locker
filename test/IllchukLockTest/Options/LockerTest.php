<?php

namespace IllchukLockTest\Options;

use IllchukLock\Options\Locker as LockerOptions;

/**
 * @group illchuk_lock
 */
class LockerTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var DbAdapterOptions
     */
    protected $options;

    public function setUp() {
        parent::setUp();

        $this->options = new LockerOptions;
    }

    public function testDefaults() {
        $defaults = [
            'separator' => '::',
            'adapter_class' => 'IllchukLock\Adapter\Db',
            'verify_lock' => true,
        ];
        foreach ($defaults as $property => $expected) {
            $this->assertEquals($expected, $this->options->{$property});
        }
    }

    public function testSetters() {
        $overrides = [
            'separator' => 'another',
            'adapter_class' => 'another',
            'verify_lock' => false,
        ];
        foreach ($overrides as $property => $override) {
            $this->options->{$property} = $override;
            $this->assertEquals($override, $this->options->{$property});
        }
    }

    public function testConfigOverrides() {
        $config = require __DIR__ . '/data/illchuk_lock.local.php';
        $options = new LockerOptions($config['illchuk_lock']['locker']);
        $this->assertEquals('separator_another', $options->getSeparator());
        $this->assertEquals('adapter_class_another', $options->getAdapterClass());
        $this->assertEquals('verify_lock_another', $options->getVerifyLock());
    }

}
