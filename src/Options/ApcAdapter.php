<?php

namespace IllchukLock\Options;

class ApcAdapter extends AbstractAdapter {

    protected $apcNamespace = 'illchuk_lock';

    /**
     * @return string
     */
    public function getApcNamespace() {
        return $this->apcNamespace;
    }

    /**
     * @param string $dbAdapterClass
     * @return ApcAdapter
     */
    public function setApcNamespace($apcNamespace) {
        $this->apcNamespace = $apcNamespace;
        return $this;
    }

}
