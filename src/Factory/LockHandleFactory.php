<?php

namespace IllchukLock\Factory;

use IllchukLock\Lock;

class LockHandleFactory {

    public function createHandle() {
        return new Lock\Handle;
    }

}
