<?php

namespace IllchukLockTest\Adapter;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter as DbAdapter;
use IllchukLock\Service\Locker;
use IllchukLock\Adapter\Db as LockerDbAdapter;
use IllchukLock\Options\DbAdapter as LockerDbAdapterOptions;
use IllchukLock\Options\Locker as LockerOptions;
use IllchukLock\Factory\LockHandleFactory;
use IllchukLock\Lock;
use IllchukLock\Term\DateTimeUnit;

/**
 * @group illchuk_lock
 */
class DbTest extends \PHPUnit_Extensions_Database_TestCase {

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

        $this->locker = new Locker(
        $this->lockerDbAdapter, new LockerOptions
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
        return $this->createFlatXMLDataSet(__DIR__ . '/data/illchuk_lock-seed.xml');
    }

    public function testClearExpiredLock() {
        $this->assertNotEmpty($this->gateway->select(['key' => 'past']));
        $this->assertNotEmpty($this->gateway->select(['key' => 'past2']));

        $this->lockerDbAdapter->clearExpiredLock('past');

        $this->assertEmpty($this->gateway->select(['key' => 'past']));
        $this->assertNotEmpty($this->gateway->select(['key' => 'past2']));

        $this->lockerDbAdapter->clearExpiredLock();

        $this->assertEmpty($this->gateway->select(['key' => 'past2']));
        $this->assertNotEmpty($this->gateway->select(['key' => 'forever']));
    }

    public function testCreateLockHandleLooped() {
        $dbOptions = new LockerDbAdapterOptions;

        $gateway = new TableGateway(
        $dbOptions->getDbTable(), $this->getAdapter()
        );

        $factoryMock = $this->getMock('IllchukLock\Factory\LockHandleFactory');
        $factoryMock->expects($this->any())
        ->method('createHandle')->will($this->returnValue(new Lock\Handle));

        $lockerDbAdapter = new LockerDbAdapter(
        $gateway, $dbOptions, $factoryMock
        );

        $locker = new Locker($lockerDbAdapter, new LockerOptions);

        /**
         * First lock works, second tries to get a new handle but can't.
         */
        $ttl = new DateTimeUnit(80, 'seconds');
        $locker->takeLock('handleOk', $ttl);
        $this->assertFalse($locker->takeLock('handleRepeats', $ttl));
    }

    public function testClearLock() {
        $ttl = new DateTimeUnit(80, 'seconds');
        $handle = $this->locker->takeLock(__FUNCTION__, $ttl);
        $this->assertInstanceOf('IllchukLock\Lock\Handle', $handle);
        $badHandle = $this->locker->takeLock(__FUNCTION__, $ttl);
        $this->assertFalse($badHandle);
        $this->locker->clearLock($handle);
        $handle = $this->locker->takeLock(__FUNCTION__, $ttl);
        $this->assertInstanceOf('IllchukLock\Lock\Handle', $handle);
    }

}
