<?php

namespace IllchukLockTest\Options;

use IllchukLock\Options\ApcAdapter as ApcAdapterOptions;

/**
 * @group illchuk_lock
 */
class ApcAdapterTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var ApcAdapterOptions
     */
    protected $options;

    public function setUp() {
        parent::setUp();

        $this->options = new ApcAdapterOptions;
    }

    public function testDefaults() {
        $defaults = [
            'apc_namespace' => 'illchuk_lock',
        ];
        foreach ($defaults as $property => $expected) {
            $this->assertEquals($expected, $this->options->{$property});
        }
    }

    public function testSetters() {
        $overrides = [
            'apc_namespace' => 'another',
        ];
        foreach ($overrides as $property => $override) {
            $this->options->{$property} = $override;
            $this->assertEquals($override, $this->options->{$property});
        }
    }

    public function testConfigOverrides() {
        $config = require __DIR__ . '/data/illchuk_lock.local.php';
        $config = $config['illchuk_lock']['IllchukLock\Adapter\Apc'];
        unset($config['options_class']);
        $options = new ApcAdapterOptions($config);
        $this->assertEquals(
        'apc_namespace_another', $options->getApcNamespace()
        );
    }

}
