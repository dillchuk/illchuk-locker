<?php

namespace IllchukLockTest\Term;

use IllchukLock\Term\DateTimeEnd;
use DateTime;
use DateTimeZone;
use Faker\Factory as FakerFactory;

/**
 * @group illchuk_lock
 */
class DateTimeEndTest extends \PHPUnit_Framework_TestCase {

    public function testGood() {
        $endDate = new DateTime('+1 year');
        $termEndDate = (new DateTimeEnd($endDate))->getEndDate();
        $this->assertEquals(
        $termEndDate->getTimestamp(), $endDate->getTimestamp()
        );
    }

    public function testInputIsCloned() {
        $endDate = new DateTime('+1 year');
        $termEndDate = (new DateTimeEnd($endDate))->getEndDate();
        $this->assertNotSame($termEndDate, $endDate);

        $endDate->modify('+1 second');
        $this->assertNotEquals(
        $termEndDate->getTimestamp(), $endDate->getTimestamp()
        );
    }

    public function testOutputIsCloned() {
        $term = new DateTimeEnd(new DateTime('+1 year'));
        $output = $term->getEndDate();
        $this->assertNotSame($output, $term->getEndDate());

        $output->modify('+1 second');
        $this->assertNotEquals(
        $output->getTimestamp(), $term->getEndDate()->getTimestamp()
        );
    }

    /**
     * @expectedException IllchukLock\Exception\RuntimeException
     * @expectedExceptionMessage returned date in the past
     */
    public function testEndDatePast() {
        $term = new DateTimeEnd(new DateTime('yesterday'));
        $term->getEndDate();
    }

    public function testTimezoneDefault() {
        $faker = FakerFactory::create();
        $endDate = new DateTime('+1 year', new DateTimeZone($faker->timezone));

        $term = new DateTimeEnd($endDate);
        $this->assertEquals(
        date_default_timezone_get(),
        $term->getEndDate()->getTimezone()->getName()
        );
    }

}
