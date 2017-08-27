<?php

namespace IllchukLockTest\Term;

use IllchukLock\Term\DateTimeUnit;

/**
 * @group illchuk_lock
 */
class DateTimeUnitTest extends \PHPUnit_Framework_TestCase {

    public function testSmoke() {
        $units = ['secs', 'minutes', 'hours', 'days', 'weeks', 'months', 'years'];
        foreach ($units as $unit) {
            $dateTimeUnit = new DateTimeUnit(1, $unit);
            $dateTimeUnit->getEndDate();
        }
    }

    /**
     * @expectedException IllchukLock\Exception\RuntimeException
     * @expectedExceptionMessage term must be positive
     */
    public function testTermNonPositive() {
        new DateTimeUnit(0, 'month');
    }

}
