<?php

namespace IllchukLockTest\Term;

use IllchukLock\Term\DateTimeUnit;
use DateTime;

/**
 * @group illchuk_lock
 */
class AbstractTermTest extends \PHPUnit_Framework_TestCase {

    /**
     * @expectedException IllchukLock\Exception\RuntimeException
     * @expectedExceptionMessage returned date in the past
     */
    public function testEndDatePast() {
        $termMock = $this->getMock(
        'IllchukLock\Term\DateTimeUnit', ['getEndDateInternal'], [1, 'month']
        );
        $termMock->expects($this->once())
        ->method('getEndDateInternal')
        ->will($this->returnValue(new DateTime('yesterday')));

        $termMock->getEndDate();
    }

}
