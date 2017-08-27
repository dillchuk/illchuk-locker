<?php

namespace IllchukLockTest\Service;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter as DbAdapter;
use IllchukLock\Service\Locker;
use IllchukLock\Adapter\Db as LockerDbAdapter;
use IllchukLock\Term\DateTimeUnit;
use IllchukLock\Options\DbAdapter as LockerDbAdapterOptions;
use IllchukLock\Options\Locker as LockerOptions;
use IllchukLock\Lock;
use IllchukLock\Factory\LockHandleFactory;

/**
 * @group illchuk_lock
 */
class LockerDbTest extends \PHPUnit_Extensions_Database_TestCase {

    /**
     * @var DbAdapter
     */
    protected $dbAdapter;

    /**
     * @var TableGateway
     */
    protected $gateway;

    /**
     * @var LockerDbAdapter
     */
    protected $lockerDbAdapter;

    /**
     * @var Locker
     */
    protected $locker;

    public function setUp() {
        parent::setUp();

        $dbOptions = new LockerDbAdapterOptions;
        $this->gateway = new TableGateway(
        $dbOptions->getDbTable(), $this->getAdapter()
        );
        $this->lockerDbAdapter = new LockerDbAdapter(
        $this->gateway, $dbOptions, new LockHandleFactory
        );

        $throttleOptions = new LockerOptions;
        $this->locker = new Locker(
        $this->lockerDbAdapter, $throttleOptions
        );
    }

    protected function getAdapter() {
        if ($this->dbAdapter) {
            return $this->dbAdapter;
        }
        $config = include __DIR__ . '/../../dbadapter.php';
        $config = $config['db'];
        $config['driver'] = 'PDO';
        $this->dbAdapter = new DbAdapter($config);
        return $this->dbAdapter;
    }

    protected function getConnection() {
        return $this->createDefaultDBConnection($this->getAdapter()->getDriver()->getConnection()->getResource());
    }

    protected function getDataSet() {
        return $this->createFlatXMLDataSet(__DIR__ . '/data/illchuk_lock-db-seed.xml');
    }

    public function testGetLockExisting() {
        $this->assertEquals(
        1, $this->gateway->select(['key' => 'forever'])->count()
        );

        $this->assertFalse(
        $this->locker->takeLock('forever', new DateTimeUnit(1, 'week'))
        );
    }

    public function testGetLockExistingExpired() {
        $this->assertEquals(
        1, $this->gateway->select(['key' => 'past'])->count()
        );

        $handle = $this->locker->takeLock('past', new DateTimeUnit(1, 'week'));
        $this->assertTrue((bool) $handle);
        $this->assertInstanceOf('IllchukLock\Lock\Handle', $handle);
    }

    public function testGetLockNonExisting() {
        $this->assertEquals(
        0, $this->gateway->select(['key' => 'nonexisting'])->count()
        );

        $handle = $this->locker->takeLock('nonexisting',
        new DateTimeUnit(1, 'week'));
        $this->assertTrue((bool) $handle);
        $this->assertInstanceOf('IllchukLock\Lock\Handle', $handle);

        $this->assertFalse(
        $this->locker->takeLock('nonexisting', new DateTimeUnit(7, 'years'))
        );
    }

    public function testGetLockSimulate() {
        $key = 'simulate';
        $handle = $this->locker->takeLock($key, new DateTimeUnit(1, 'second'));
        $this->assertTrue((bool) $handle);
        $this->assertInstanceOf('IllchukLock\Lock\Handle', $handle);

        $this->assertFalse(
        $this->locker->takeLock($key, new DateTimeUnit(1, 'second'))
        );

        /**
         * Pardon the wait.
         */
        usleep(1005000);

        $handle = $this->locker->takeLock($key, new DateTimeUnit(1, 'second'));
        $this->assertTrue((bool) $handle);
        $this->assertInstanceOf('IllchukLock\Lock\Handle', $handle);
    }

    public function testTakeAndClearLock() {
        $key = 'take-and-clear';
        $handle = $this->locker->takeLock(
        $key, new DateTimeUnit(88, 'years')
        );
        $this->assertTrue((bool) $handle);
        $this->assertInstanceOf('IllchukLock\Lock\Handle', $handle);
        $this->assertCount(
        1, $this->gateway->select(['key' => $key])
        );

        $this->locker->clearLock($handle);
        $this->assertEmpty($this->gateway->select(['key' => $key]));

        /**
         * And again.
         */
        $this->locker->clearLock($handle);
    }

    public function testClearLockInvalid() {
        $this->locker->clearLock(new Lock\Handle);
    }

    public function testGetOptions() {
        $this->assertInstanceOf(
        '\IllchukLock\Options\Locker', $this->locker->getOptions()
        );
    }

    public function testClearExpiredLocks() {
        $this->locker->clearExpiredLocks();
        $this->assertEquals(
        0, $this->gateway->select(['key' => 'past'])->count()
        );
    }

    /**
     * @expectedException IllchukLock\Exception\PhantomLockException
     */
    public function testPhantomLock() {
        $adapterMock = $this->getMock(
        'IllchukLock\Adapter\Db', ['setLock'],
        [$this->gateway, $this->lockerDbAdapter->getOptions(), new LockHandleFactory]
        );
        $handle = new Lock\Handle;
        $adapterMock->expects($this->any())
        ->method('setLock')->will($this->returnValue($handle));
        $adapterMock->expects($this->any())
        ->method('verifyLock')->will($this->returnValue(false));

        $locker = new Locker($adapterMock, new LockerOptions);
        $locker->takeLock('phantom', new DateTimeUnit(10, 'years'));
    }

    public function testSetLockReturnsFalse() {
        $adapterMock = $this->getMock(
        'IllchukLock\Adapter\Db', ['setLock'],
        [$this->gateway, $this->lockerDbAdapter->getOptions(), new LockHandleFactory]
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
        $this->locker->takeLock('bad::key', new DateTimeUnit(11, 'months'));
    }

}
