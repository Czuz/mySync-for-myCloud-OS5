<?php
/**
 * Author: Czuz (https://github.com/Czuz)
 *
 * TODO:
 * - cacheing
 */

namespace mySync;

class LogFile {

    const LOG_ERROR_PATTERN = "/(FAILED)/";
    const LOG_WARNING_PATTERN = "/ERROR :/";

    // Flag indicating whether file exist
    private $exist = false;

    // Log file name with full path
    private $fullname;

    // Log file name
    private $basename;

    // Date and time the file has been last modified - used for cacheing
    private $modtime;

    // Number of logged errors
    private $errNo = 0;

    // Number of logged warnings
    private $warnNo = 0;

    // Number of search result
    private $matchNo = null;

    // File content in the form of array
    private $file_content = null;

    public function __construct($fullname) {
        $this->init($fullname);
    }

    /**
     * Method calculates and sets matchNo property basing on the $query result
     *
     * @param $query - a search text
     * @return reference to current LogFile
     */
    public function calculateMatchStatistics($query) {
        if ($this->exist && !empty($query)) {
            $file_content = file_get_contents($this->fullname);
            preg_match_all("%$query%i",$file_content,$matches,PREG_PATTERN_ORDER);
            $this->matchNo = count($matches[0]);
        } else {
            $this->matchNo = null;
        }
        return $this;
    }

    /**
     * Method retrieves data matching $query parameter and sets file_content property
     *
     * @param $query - a search text
     * @return reference to current LogFile
     */
    public function retrieveAndProcessContents($query = null) {
        $file_content = null;
        if ($this->exist) {
            $file_content = file_get_contents($this->fullname);
            $file_content = explode(PHP_EOL, $file_content);
            if (!empty($query)) {
                $file_content = (preg_filter(["%$query%i"], ["$0"], $file_content));
            }
            $file_content = array_map(function($key, $line) {
                $haserror = preg_match(self::LOG_ERROR_PATTERN,$line);
                $haswarning = preg_match(self::LOG_WARNING_PATTERN,$line);;
                return [
                    "lineno"        => $key,
                    "line"          => $line,
                    "haserror"      => $haserror,
                    "haswarning"    => $haswarning,
                ];
            }, array_keys($file_content), $file_content);
        }

        $this->file_content = array_values(array_reverse($file_content));
        return $this;
    }

    /**
     * Method to check whether file exists in filesystem
     *
     * @return true - when file exist; false - when file does not exist
     */
    public function exist() {
        return $this->exist;
    }

    /**
     * Returns an array with basic LogFile statistics
     *
     * @return an array with selected object properties
     */
    public function getStatistics() {

        return $this->exist ? [
                "basename"  => $this->basename,
                "errno"     => $this->errNo,
                "warnno"    => $this->warnNo,
                "matchno"   => $this->matchNo,
            ] : null;
    }

    /**
     * Returns an array with LogFile content
     *
     * @return an array with LogFile content
     */
    public function getContent() {

        return $this->exist ? $this->file_content : null;
    }

    /**
     * Object initialization
     * Sets the properties basing on fullname
     */
    private function init($fullname) {
        if (file_exists($fullname)) {
            $this->exist = true;
            $this->fullname = $fullname;
            $this->basename = basename($fullname);
            $this->modtime = filemtime($fullname);
            $file_content = file_get_contents($this->fullname);
            preg_match_all(self::LOG_ERROR_PATTERN,$file_content,$matches,PREG_PATTERN_ORDER);
            $this->errNo = count($matches[0]);
            preg_match_all(self::LOG_WARNING_PATTERN,$file_content,$matches,PREG_PATTERN_ORDER);
            $this->warnNo = count($matches[0]);
        }
    }
}

?>
