<?php

namespace IllchukLock\Options;

use Zend\Stdlib\AbstractOptions;

class Locker extends AbstractOptions {

    protected $separator = '::';
    protected $adapterClass = 'IllchukLock\Adapter\Db';
    protected $verifyLock = true;

    /**
     * @return string
     */
    public function getSeparator() {
        return $this->separator;
    }

    /**
     * @param string $separator
     * @return Throttle
     */
    public function setSeparator($separator) {
        $this->separator = $separator;
        return $this;
    }

    /**
     * @return string
     */
    public function getAdapterClass() {
        return $this->adapterClass;
    }

    /**
     * @param string $adapterClass
     * @return Throttle
     */
    public function setAdapterClass($adapterClass) {
        $this->adapterClass = $adapterClass;
        return $this;
    }

    /**
     * @return bool
     */
    public function getVerifyLock() {
        return $this->verifyLock;
    }

    /**
     * @param bool $verifyLock
     * @return Throttle
     */
    public function setVerifyLock($verifyLock) {
        $this->verifyLock = $verifyLock;
        return $this;
    }

}
