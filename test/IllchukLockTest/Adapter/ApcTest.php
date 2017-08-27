<?php

namespace IllchukLockTest\Adapter;

use IllchukLock\Service\Locker;
use IllchukLock\Adapter\Apc as ApcAdapter;
use IllchukLock\Apc\Apc as ApcWrapper;
use IllchukLock\Options\ApcAdapter as ApcOptions;
use IllchukLock\Options\Locker as LockerOptions;
use IllchukLock\Factory\LockHandleFactory;
use IllchukLock\Lock;
use IllchukLock\Term\DateTimeUnit;
use DateTime;
use Zend\Math\Rand;

/**
 * @group illchuk_lock
 */
class ApcTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var ApcAdapter
     */
    protected $apcAdapter;

    /**
     * @var Locker
     */
    protected $locker;

    public function setUp() {
        parent::setUp();

        $this->apcAdapter = new ApcAdapter(
        new ApcWrapper, new ApcOptions, new LockHandleFactory
        );

        $this->locker = new Locker(
        $this->apcAdapter, new LockerOptions
        );
    }

    public function testVacuous() {
        $vacuous = ['beginTransaction', 'commit', 'rollback', 'clearExpiredLock'];
        foreach ($vacuous as $method) {
            $this->apcAdapter->$method();
        }
    }

    public function testCreateLockHandleLooped() {
        $factoryMock = $this->getMock('IllchukLock\Factory\LockHandleFactory');
        $factoryMock->expects($this->any())
        ->method('createHandle')->will($this->returnValue(new Lock\Handle));

        $apcAdapter = new ApcAdapter(new ApcWrapper, new ApcOptions,
        $factoryMock);

        $locker = new Locker($apcAdapter, new LockerOptions);

        /**
         * First lock works, second tries to get a new handle but can't.
         */
        $ttl = new DateTimeUnit(80, 'seconds');
        $locker->takeLock('handleOk', $ttl);
        $this->assertFalse($locker->takeLock('handleRepeats', $ttl));
    }

    public function testCannotAdd() {
        $apcMock = $this->getMock('IllchukLock\Apc\Apc');
        $apcMock->expects($this->any())
        ->method('add')->will($this->returnValue(false));
        $apcAdapter = new ApcAdapter(
        $apcMock, new ApcOptions, new LockHandleFactory
        );
        $locker = new Locker($apcAdapter, new LockerOptions);

        $ttl = new DateTimeUnit(80, 'seconds');
        for ($i = 0; $i < 4; $i++) {
            $this->assertFalse($locker->takeLock(__FUNCTION__, $ttl));
        }
    }

    public function testSetLockPast() {
        $this->assertFalse(
        $this->apcAdapter->setLock('past', new DateTime('2000-01-01'))
        );
        $this->assertTrue(
        (bool) $this->apcAdapter->setLock('past',
        (new DateTime())->modify('+1 hour'))
        );
    }

    public function testApcExtensionWorking() {
        if (extension_loaded('apc')) {
            echo 'APC EXTENSION: apc' . PHP_EOL;
        }
        if (extension_loaded('apcu')) {
            echo 'APC EXTENSION: apcu' . PHP_EOL;
        }
        $this->assertTrue(extension_loaded('apc') || extension_loaded('apcu'));
        $rand = Rand::getString(10, 'asdf');
        $addMethod = extension_loaded('apcu') ? 'apcu_add' : 'apc_add';
        $this->assertTrue($addMethod($rand, true, 100));
        $this->assertFalse($addMethod($rand, true, 100));
    }

}
