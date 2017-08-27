<?php

namespace IllchukLock\Term;

use IllchukLock\Exception\RuntimeException;
use DateTime;

abstract class AbstractTerm {

    /**
     * @return DateTime
     */
    protected abstract function getEndDateInternal();

    /**
     * @return DateTime
     */
    public function getEndDate() {
        $date = $this->getEndDateInternal();
        if ($date < new DateTime) {
            throw new RuntimeException('getEndDateInternal returned date in the past');
        }
        return $date;
    }

}
