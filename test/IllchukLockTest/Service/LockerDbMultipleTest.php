<?php

namespace IllchukLockTest\Service;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter as DbAdapter;
use IllchukLock\Service\Locker;
use IllchukLock\Adapter\DbMultiple as LockerDbMultipleAdapter;
use IllchukLock\Term\DateTimeUnit;
use IllchukLock\Options\DbMultipleAdapter as LockerDbAdapterOptions;
use IllchukLock\Options\Locker as LockerOptions;
use IllchukLock\Factory\LockHandleFactory;

/**
 * @group illchuk_lock
 */
class LockerDbMultipleTest extends \PHPUnit_Extensions_Database_TestCase {

    /**
     * @var DbAdapter
     */
    protected $dbAdapter;

    /**
     * @var TableGateway
     */
    protected $gateway;

    /**
     * @var LockerDbMultipleAdapter
     */
    protected $lockerDbAdapter;

    /**
     * @var Locker
     */
    protected $locker;

    public function setUp() {
        parent::setUp();

        $dbOptions = new LockerDbAdapterOptions;
        $dbOptions->setRegexCounts([
            '/^past$/' => 4,
            '/^simulate$/' => 3,
        ]);

        $this->gateway = new TableGateway(
        $dbOptions->getDbTable(), $this->getAdapter()
        );
        $this->lockerDbAdapter = new LockerDbMultipleAdapter(
        $this->gateway, $dbOptions, new LockHandleFactory
        );

        $lockerOptions = new LockerOptions;
        $this->locker = new Locker(
        $this->lockerDbAdapter, $lockerOptions
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
        return $this->createFlatXMLDataSet(__DIR__ . '/data/illchuk_lock-db-multiple-seed.xml');
    }

    public function testGetLockExisting() {
        $select = $this->gateway->getSql()->select();
        $select->where->like('key', '%::forever');
        $this->assertEquals(
        1, $this->gateway->selectWith($select)->count()
        );

        $this->assertFalse(
        $this->locker->takeLock('forever', new DateTimeUnit(3, 'weeks'))
        );
    }

    public function testGetLockExistingExpired() {
        $select = $this->gateway->getSql()->select();
        $select->where->like('key', '%::past');
        $this->assertEquals(
        3, $this->gateway->selectWith($select)->count()
        );

        $handles = [];
        for ($i = 0; $i < 4; $i++) {
            $handle = $this->locker->takeLock('past',
            new DateTimeUnit(17, 'days'));
            $this->assertTrue((bool) $handle);
            $handles[] = $handle;
        }
        $this->assertFalse(
        $this->locker->takeLock('past', new DateTimeUnit(1, 'minute'))
        );

        foreach ($handles as $handle) {
            $this->locker->clearLock($handle);
            $this->assertTrue((bool)
            $this->locker->takeLock('past', new DateTimeUnit(1, 'minute'))
            );
        }
        $this->assertFalse(
        $this->locker->takeLock('past', new DateTimeUnit(1, 'minute'))
        );
    }

    public function testGetLockNonExisting() {
        $select = $this->gateway->getSql()->select();
        $select->where->like('key', '%::nonexisting');
        $this->assertEmpty($this->gateway->selectWith($select)->count());

        $handle = $this->locker->takeLock('nonexisting',
        new DateTimeUnit(1, 'week'));
        $this->assertTrue((bool) $handle);
        $this->assertInstanceOf('IllchukLock\Lock\Handle', $handle);

        $this->assertFalse(
        $this->locker->takeLock('nonexisting', new DateTimeUnit(1, 'week'))
        );
    }

    public function testGetLockSimulate() {
        $key = 'simulate';
        for ($i = 0; $i < 3; $i++) {
            $handle = $this->locker->takeLock(
            $key, new DateTimeUnit(2, 'second')
            );
            $this->assertTrue((bool) $handle);
        }
        $this->assertFalse(
        $this->locker->takeLock($key, new DateTimeUnit(1, 'second'))
        );
        sleep(2);

        $handle = $this->locker->takeLock($key, new DateTimeUnit(1, 'second'));
        $this->assertTrue((bool) $handle);
        sleep(3);

        for ($i = 0; $i < 3; $i++) {
            $handle = $this->locker->takeLock($key,
            new DateTimeUnit(1, 'minute'));
            $this->assertTrue((bool) $handle);
        }
        $this->assertFalse(
        $this->locker->takeLock($key, new DateTimeUnit(1, 'second'))
        );
    }

    public function testClearExpiredLocks() {
        $this->locker->clearExpiredLocks();
        $select = $this->gateway->getSql()->select();
        $select->where->like('key', '%::past');
        $this->assertEmpty($this->gateway->selectWith($select)->count());
    }

    public function testClearExpiredLockKey() {
        $dbOptions = new LockerDbAdapterOptions;
        $dbOptions->setRegexCounts([
            '/^whatever/' => 99
        ]);
        $dbOptions->setClearAllIsCheap(false);

        $gateway = new TableGateway(
        $dbOptions->getDbTable(), $this->getAdapter()
        );
        $lockerDbAdapter = new LockerDbMultipleAdapter(
        $gateway, $dbOptions, new LockHandleFactory
        );

        $lockerOptions = new LockerOptions;
        $locker = new Locker($lockerDbAdapter, $lockerOptions);

        $locker->takeLock('ok', new DateTimeUnit(6, 'days'));
    }

    /**
     * @expectedException IllchukLock\Exception\OptionException
     * @expectedExceptionMessage Separator is not set
     */
    public function testSeparatorNotSet() {
        $dbOptions = new LockerDbAdapterOptions;
        $dbOptions->setRegexCounts([
            '/^whatever/' => 99
        ]);

        $gateway = new TableGateway(
        $dbOptions->getDbTable(), $this->getAdapter()
        );
        $lockerDbAdapter = new LockerDbMultipleAdapter(
        $gateway, $dbOptions, new LockHandleFactory
        );

        $lockerOptions = new LockerOptions;
        $lockerOptions->setSeparator('');
        $locker = new Locker($lockerDbAdapter, $lockerOptions);

        $locker->takeLock('ok', new DateTimeUnit(6, 'days'));
    }

    /**
     * @expectedException IllchukLock\Exception\OptionException
     * @expectedExceptionMessage regex_counts not specified
     */
    public function testRegexCountsNotSet() {
        $dbOptions = new LockerDbAdapterOptions;

        $gateway = new TableGateway(
        $dbOptions->getDbTable(), $this->getAdapter()
        );
        $lockerDbAdapter = new LockerDbMultipleAdapter(
        $gateway, $dbOptions, new LockHandleFactory
        );

        $lockerOptions = new LockerOptions;
        $locker = new Locker($lockerDbAdapter, $lockerOptions);

        $locker->takeLock('ok', new DateTimeUnit(6, 'days'));
    }

    /**
     * @expectedException IllchukLock\Exception\OptionException
     * @expectedExceptionMessage regex_counts must be positive
     */
    public function testRegexCountsNotPositive() {
        $dbOptions = new LockerDbAdapterOptions;
        $dbOptions->setRegexCounts([
            '/^whatever/' => 0
        ]);

        $gateway = new TableGateway(
        $dbOptions->getDbTable(), $this->getAdapter()
        );
        $lockerDbAdapter = new LockerDbMultipleAdapter(
        $gateway, $dbOptions, new LockHandleFactory);

        $lockerOptions = new LockerOptions;
        $locker = new Locker($lockerDbAdapter, $lockerOptions);

        $locker->takeLock('ok', new DateTimeUnit(6, 'days'));
    }

    /**
     * @expectedException IllchukLock\Exception\OptionException
     * @expectedExceptionMessage regex_counts pattern BAD REGEX is invalid
     */
    public function testRegexCountsInvalidPattern() {
        $dbOptions = new LockerDbAdapterOptions;
        $dbOptions->setRegexCounts([
            'BAD REGEX' => 1
        ]);

        $gateway = new TableGateway(
        $dbOptions->getDbTable(), $this->getAdapter()
        );
        $lockerDbAdapter = new LockerDbMultipleAdapter(
        $gateway, $dbOptions, new LockHandleFactory
        );

        $lockerOptions = new LockerOptions;
        $locker = new Locker($lockerDbAdapter, $lockerOptions);

        $locker->takeLock('ok', new DateTimeUnit(6, 'days'));
    }

}
