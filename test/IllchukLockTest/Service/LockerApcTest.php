<?php

namespace IllchukLockTest\Service;

use IllchukLock\Service\Locker;
use IllchukLock\Adapter\Apc as ApcAdapter;
use IllchukLock\Apc\Apc as ApcExt;
use IllchukLock\Term\DateTimeUnit;
use IllchukLock\Options\ApcAdapter as ApcAdapterOptions;
use IllchukLock\Options\Locker as LockerOptions;
use IllchukLock\Lock;
use IllchukLock\Factory\LockHandleFactory;

/**
 * @group illchuk_lock
 */
class LockerApcTest extends \PHPUnit_Framework_TestCase {

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

        $apcOptions = new ApcAdapterOptions;
        $this->apcAdapter = new ApcAdapter(
        new ApcExt, $apcOptions, new LockHandleFactory
        );

        $lockerOptions = new LockerOptions;
        $this->locker = new Locker(
        $this->apcAdapter, $lockerOptions
        );
    }

    public function testGetLockSimulate() {
        $key = 'simulate';
        $ttl = new DateTimeUnit(1, 'second');
        $handle = $this->locker->takeLock($key, $ttl);
        $this->assertInstanceOf('IllchukLock\Lock\Handle', $handle);

        $keyApc = "illchuk_lock::{$key}";

        $apc = new ApcExt;
        $this->assertTrue($apc->fetch($keyApc));
        $this->assertFalse($this->locker->takeLock($key, $ttl));

        /**
         * You'd think you can sleep(1 or 2) then apc_add should work again.
         * But for me apc_add and sleep don't play nice.  So, assume
         * apc_add works and just manually clear to get on with it.
         */
        $apc->delete($keyApc);

        $handle = $this->locker->takeLock($key, $ttl);
        $this->assertInstanceOf('IllchukLock\Lock\Handle', $handle);
    }

    public function testTakeAndClearLock() {
        $key = 'take-and-clear';
        $handle = $this->locker->takeLock(
        $key, new DateTimeUnit(1, 'hour')
        );
        $this->assertInstanceOf('IllchukLock\Lock\Handle', $handle);
        $keyApc = "illchuk_lock::{$key}";
        $apc = new ApcExt;
        $this->assertTrue($apc->fetch($keyApc));

        $this->locker->clearLock($handle);
        $this->assertFalse($apc->fetch($keyApc));

        /**
         * And again.
         */
        $this->locker->clearLock($handle);
        $this->assertFalse($apc->fetch($keyApc));
    }

    public function testClearLockInvalid() {
        $this->locker->clearLock(new Lock\Handle);
    }

    public function testClearExpiredLocks() {
        $this->locker->clearExpiredLocks();
    }

    /**
     * @expectedException IllchukLock\Exception\PhantomLockException
     */
    public function testPhantomLock() {
        $adapterMock = $this->getMock(
        'IllchukLock\Adapter\Apc', ['setLock'],
        [new ApcExt, $this->apcAdapter->getOptions(), new LockHandleFactory]
        );
        $handle = new Lock\Handle;
        $adapterMock->expects($this->any())
        ->method('setLock')->will($this->returnValue($handle));
        $adapterMock->expects($this->any())
        ->method('verifyLock')->will($this->returnValue(false));

        $locker = new Locker($adapterMock, new LockerOptions);
        $locker->takeLock('phantom', new DateTimeUnit(1, 'hour'));
    }

    public function testSetLockReturnsFalse() {
        $adapterMock = $this->getMock(
        'IllchukLock\Adapter\Apc', ['setLock'],
        [new ApcExt, $this->apcAdapter->getOptions(), new LockHandleFactory]
        );
        $adapterMock->expects($this->any())
        ->method('setLock')->will($this->returnValue(false));

        $locker = new Locker(
        $adapterMock, new LockerOptions
        );
        $this->assertFalse($locker->takeLock(
        'setLockReturnsFalse', new DateTimeUnit(3, 'minutes'))
        );
    }

    /**
     * @expectedException IllchukLock\Exception\RuntimeException
     * @expectedExceptionMessage key contained reserved separator
     */
    public function testTakeLockWithSeparator() {
        $this->locker->takeLock('bad::key', new DateTimeUnit(1, 'hour'));
    }

}
