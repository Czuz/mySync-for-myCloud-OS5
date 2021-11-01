<?php
/**
 * Author: Czuz (https://github.com/Czuz)
 */

namespace mySync;

class RemotesConfiguration {
    // Array of remote connections
    private $remotes = array();

    public function __construct($file) {
        $conf = file_get_contents($file);
        $conf_array = preg_split("#^(\[[\w\d]+\])$#mu",$conf, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        while (count($conf_array)) {
            $name = array_shift($conf_array);
            $definition = $name . array_shift($conf_array);
            $key = trim($name,'[]') . ' (' . current(preg_filter("#type = (.+)#mu","$1",preg_grep("#^type#mu",explode(PHP_EOL,$definition)))) . ')';
            $this->remotes[$key] = $definition;
        }
    }

    /**
     * @return an array of Remotes names
     */
    public function keys() {
        return array_keys($this->remotes);
    }

    /**
     * Merges provided configuration with one stored in this object
     *
     * @param $conf - configuration to be merged
     * @return self
     */
    public function merge(RemotesConfiguration $conf) {
        if ($conf) {
            $this->remotes = array_merge($this->remotes, $conf->toArray());
        }
        return $this;
    }

    /**
     * @return configuration as a single string
     */
    public function toString() {
        return implode('',$this->remotes);
    }

    /**
     * @return configuration as an array of remotes
     */
    public function toArray() {
        return $this->remotes;
    }
}
?>
