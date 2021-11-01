<?php
/**
 * Author: Czuz (https://github.com/Czuz)
 */
namespace mySync;

include_once("LogFile.php");
include_once("AuthChecker.php");

try {
    if (AuthChecker::isProtected()) {
        $logViewer = new LogViewer;
        exit($logViewer->processAPIRequest());
        }
} catch (\Throwable $th) {
    exit($th);
}

class LogViewer {

    //this is the path (folder) on the system where the log files are stored
    private $logFolderPath;

    //this is the pattern to pick all log files in the $logFilePath
    private $logFilePattern;

    //this is a combination of the LOG_FOLDER_PATH and LOG_FILE_PATTERN
    private $fullLogFilePath = "";

    /**
     * These are the constants representing the
     * various API commands there are
     */
    private const API_FILE_QUERY_PARAM = "f";
    private const API_SEARCH_QUERY_PARAM = "s";
    private const API_CMD_LIST = "list";
    private const API_CMD_VIEW = "view";
    private const API_CMD_GET = "get";
    private const API_CMD_DELETE = "delete";

    public function __construct() {
        $this->init();
    }

    /**
     * API Request Handler
     *
     * @return Response JSON
     * */
    public function processAPIRequest() {

        $command = $this->getInput("action");

        if($command === self::API_CMD_LIST) {
            //respond with a list of all the files
            $response["status"] = true;
            $response["log_files"] = $this->listLogFiles(
                filter_var($this->getInput(self::API_SEARCH_QUERY_PARAM),FILTER_SANITIZE_STRING)
            );
        }
        else if($command === self::API_CMD_VIEW) {
            $file = basename(filter_var($this->getInput(self::API_FILE_QUERY_PARAM),FILTER_SANITIZE_STRING));
            $query = filter_var($this->getInput(self::API_SEARCH_QUERY_PARAM),FILTER_SANITIZE_STRING);

            if(empty($file)) {
                $response["status"] = false;
                $response["error"]["message"] = "File not selected";
                $response["error"]["code"] = 400;
            } else {
                $file = $this->logFolderPath . "/" . $file;

                if(!file_exists($file)) {
                    $response["status"] = false;
                    $response["error"]["message"] = "File: [" . json_encode($file) . "] does not exist";
                    $response["error"]["code"] = 404;
                } else {
                    $response["status"] = true;
                    $response["log_content"] = $this->viewLogFile($file, $query);
                }
            }
        }
        else if($command === self::API_CMD_GET) {
            $file = basename(filter_var($this->getInput(self::API_FILE_QUERY_PARAM),FILTER_SANITIZE_STRING));

            if(empty($file)) {
                $response["status"] = false;
                $response["error"]["message"] = "File not selected";
                $response["error"]["code"] = 400;
            } else {
                $file = $this->logFolderPath . "/" . $file;

                if(!file_exists($file)) {
                    $response["status"] = false;
                    $response["error"]["message"] = "File: [" . json_encode($file) . "] does not exist";
                    $response["error"]["code"] = 404;
                } else {
                    $response["status"] = true;
                    $response["file"] =  $this->downloadFile($file);
                }
            }
        }
        else if($command === self::API_CMD_DELETE) {
            $file = basename(filter_var($this->getInput(self::API_FILE_QUERY_PARAM),FILTER_SANITIZE_STRING));

            if(empty($file)) {
                $response["status"] = false;
                $response["error"]["message"] = "File not selected";
                $response["error"]["code"] = 400;
            } else {
                $file = $this->logFolderPath . "/" . $file;

                if(!file_exists($file)) {
                    $response["status"] = false;
                    $response["error"]["message"] = "File: [" . json_encode($file) . "] does not exist";
                    $response["error"]["code"] = 404;
                } else {
                    //TODO
                    $response["status"] = false;
                    $response["error"]["message"] = "Unsupported Query Command [" . $command . "]";
                    $response["error"]["code"] = 405;
                }
            }
        }
        else {
            $response["status"] = false;
            $response["error"]["message"] = "Unsupported Query Command [" . $command . "]";
            $response["error"]["code"] = 405;
        }

        return json_encode($response);
    }

    // API Methods

    /**
     * API Method for LIST operation
     *
     * @param $query - a search text
     * @return LIST Response
     */
    private function listLogFiles($query) {
        $files = glob($this->fullLogFilePath);

        $files = array_reverse($files);
        $files = array_filter($files, 'is_file');
        if (is_array($files)) {
            foreach ($files as $k => $file) {
                $logFile = new LogFile($file);
                if(!empty($query) && $logFile->exist()) {
                    $logFile->calculateMatchStatistics($query);
                }
                $files[$k] = $logFile->getStatistics();
            }
        }
        return array_values($files);
    }

    /**
     * API Method for VIEW operation
     *
     * @param $file - the file that will be returned to Front End
     * @return VIEW Response
     */
    private function viewLogFile($file, $query = null) {
        $logFile = new LogFile($file);

        if($logFile->exist()) {
            return $logFile->retrieveAndProcessContents($query)->getContent();
        } else {
            return null;
        }
    }

    /**
     * API Method for GET operation
     *
     * Return raw file contents for download purposes
     * This should only be called if the file exists
     * hence, the file exist check has ot be done by the caller
     *
     * @param $file - the file that will be deleted
     * @return GET Response
     */
    private function downloadFile($file) {
        return file_get_contents($file);
    }

    /**
     * API Method for DELETE operation
     *
     * @param $file - the file that will be deleted
     * @return DELETE Response
     */
    private function deleteLogFile($file) {
        //TODO
    }

    /**
     * Bootstrap the library
     * sets the configuration variables
     */
    private function init() {
        //configure the log folder path and the file pattern for all the logs in the folder
        $this->logFolderPath = "/mnt/HD/HD_a2/.systemfile/mySync/log"; //TODO
        $this->logFilePattern = "*[0-9].log";

        //concatenate to form Full Log Path
        $this->fullLogFilePath = $this->logFolderPath . "/" . $this->logFilePattern;
    }

    /**
     * This function will return value of indicated POST parameter
     *
     * @param $param - POST parameter name
     * @return the value of given POST parameter
     */
    private function getInput($param) {
        $value = htmlspecialchars($_POST[$param]);
        return empty($value) ? null : $value;
    }
}
?>