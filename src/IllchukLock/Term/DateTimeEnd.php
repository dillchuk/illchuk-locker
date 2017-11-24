<?php

namespace IllchukLock\Term;

use DateTime;
use DateTimeZone;

/**
 * End date in, end date out.
 */
class DateTimeEnd extends AbstractTerm {

    /**
     * @var DateTime
     */
    protected $endDate;

    /**
     * @param DateTime $endDate
     */
    public function __construct($endDate) {
        $this->endDate = clone $endDate;
        $this->endDate->setTimezone(
        new DateTimeZone(date_default_timezone_get())
        );
    }

    /**
     * @return DateTime
     */
    protected function getEndDateInternal() {
        return clone $this->endDate;
    }

}
