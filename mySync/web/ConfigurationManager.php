<?php
/**
 * Author: Czuz (https://github.com/Czuz)
 */
namespace mySync;

include_once("RemotesConfiguration.php");
include_once("AuthChecker.php");
use Exception;

try {
    if (AuthChecker::isProtected()) {
        $configurationManager = new ConfigurationManager;
        exit($configurationManager->processAPIRequest());
    }
} catch (\Throwable $th) {
    exit($th);
}

class ConfigurationManager {

    //this is the path (folder) on the system where the configuration files are stored
    private $confFolderPath;

    //this is the path (folder) on the system where the startup scripts are stored
    private $appFolderPath;

    //this are the configuration files managed by this class
    private $confFiles;

    //these are full paths to the configuration files and backup configuration files on the system
    private $fullConfFilePath;
    private $fullBakFilePath;

    // these are the constants for the API_CONF_TYPE_PARAM
    private const CONF_TYPE_REMOTES = 1;
    private const CONF_TYPE_FLOWS = 2;

    /**
     * These are the constants representing the
     * various API commands there are
     */
    private const API_CONF_TYPE_PARAM = "type";
    private const API_CONF_OVERRIDE_PARAM = "override";
    private const API_CONF_RESTART_PARAM = "restart";
    private const API_CONF_BACKUP_PARAM = "backup";
    private const API_CONF_INPUT_PARAM = "input";
    private const API_CMD_GET = "get";
    private const API_CMD_SET = "set";
    private const API_CMD_RESTORE = "restore";

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
        $confType = $this->getInput(self::API_CONF_TYPE_PARAM);

        if($command === self::API_CMD_GET) {
            if(array_key_exists($confType, $this->fullConfFilePath)) {
                $response["status"] = true;
                $response["data"] = $this->getConf($confType);
            } else {
                $response["status"] = false;
                $response["error"]["message"] = "Unsupported Configuration Type [" . $confType . "]";
                $response["error"]["code"] = 501;
            }
        }
        else if($command === self::API_CMD_SET) {
            $restart = filter_var($this->getInput($this::API_CONF_RESTART_PARAM), FILTER_VALIDATE_BOOLEAN);
            $backup = filter_var($this->getInput($this::API_CONF_BACKUP_PARAM), FILTER_VALIDATE_BOOLEAN);

            try {
                // Status initialization - it will be overrided if something goes wrong
                $response["status"] = true;

                // Make backup
                if ($backup) $this->makeBackup($confType);

                // Save new configuration
                if ($confType == $this::CONF_TYPE_REMOTES) {
                    // Validate inputs
                    if (!count($_FILES)) {
                        $response["status"] = false;
                        $response["error"]["message"] = "File has not been provided";
                        $response["error"]["code"] = 400;
                    } else if ($_FILES['remotes']["errors"]) {
                        $response["status"] = false;
                        $response["error"]["message"] = "Error while uploading a file - try again";
                        $response["error"]["code"] = 400;
                    } else {
                        $override = filter_var($this->getInput($this::API_CONF_OVERRIDE_PARAM), FILTER_VALIDATE_BOOLEAN);
                        $this->saveRemotes($_FILES['remotes'], $override);
                    }
                } else {
                    $input = strval($this->getInput($this::API_CONF_INPUT_PARAM));

                    // Validate inputs
                    if (preg_match_all("#[^\s[:print:]]#mu",$input)) {
                        $response["status"] = false;
                        $response["error"]["message"] = "Configuration does not pass validation";
                        $response["error"]["code"] = 400;
                    } else {
                        $this->saveFlows($input);
                    }
                }

                // Restart
                if ($restart) $this->restart();
            } catch(Exception $e) {
                $response["status"] = false;
                $response["error"]["message"] = $e->getMessage();
                $response["error"]["code"] = 500;
            }
        }
        else if ($command === self::API_CMD_RESTORE) {
            $restart = filter_var($this->getInput($this::API_CONF_RESTART_PARAM), FILTER_VALIDATE_BOOLEAN);
            $backup = filter_var($this->getInput($this::API_CONF_BACKUP_PARAM), FILTER_VALIDATE_BOOLEAN);

            try {
                // Status initialization - it will be overrided if something goes wrong
                $response["status"] = true;

                // Restore backup
                $this->restoreConf($confType);

                // Restart
                if ($restart) $this->restart();
            } catch(Exception $e) {
                $response["status"] = false;
                $response["error"]["message"] = $e->getMessage();
                $response["error"]["code"] = 500;
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
     * API Method for GET operation
     *
     * @param $type - a type of configuration to retrieve
     * @return GET Response
     */
    private function getConf($type) {
        $file = $this->fullConfFilePath[$type];
        $bak_file = $this->fullBakFilePath[$type];
        $conf = null;
        $backup = false;
        $backup_time = null;

        if ($type == self::CONF_TYPE_REMOTES) {
            $conf = (new RemotesConfiguration($file))->keys();
        } else if ($type == self::CONF_TYPE_FLOWS) {
            $conf = file_get_contents($file);
        }

        if (file_exists($bak_file)) {
            $backup = true;
            $backup_time = date ("Y-m-d H:i:s", filemtime($bak_file));
        }

        return [
            "configuration" => $conf,
            "hasbackup"     => $backup,
            "backuptime"    => $backup_time,
        ];
    }

    /**
     * Method to make a backup - part of API SET operation
     *
     * @param $type - a type of configuration to retrieve
     * @throws Exception with error message
     */
    private function makeBackup($type) {
        $dir = $this->confFolderPath;
        $file = $this->fullConfFilePath[$type];
        $bak_file = $this->fullBakFilePath[$type];
        $continue = true;
        $result = "";

        if (!is_dir($dir) || !is_writable($dir)) {
            $continue = false;
            $result = "Configuration directory [" . $dir . "] does not exist or is not writable";
        }

        // Backup
        try {
            if ($continue) {
                if (!copy($file,$bak_file)) {
                    $continue = false;
                    $result = "Backup error [from: " . $file . ", to: " . $bak_file . "]";
                }
            }
        } catch(Exception $e) {
            $continue = false;
            $result = $e->getMessage();
        }

        if (!empty($result)) throw new Exception($result);
    }

    /**
     * Method to save Flows configuration - part of API SET operation
     *
     * @param $input - contents of configuration to save
     * @throws Exception with error message
     */
    private function saveFlows($input) {
        $dir = $this->confFolderPath;
        $file = $this->fullConfFilePath[$this::CONF_TYPE_FLOWS];
        $continue = true;
        $result = "";

        if (!is_dir($dir) || !is_writable($dir)) {
            $continue = false;
            $result = "Configuration directory [" . $dir . "] does not exist or is not writable";
        }

        try {
            if ($continue) {
                if (!file_put_contents($file, preg_replace('~\R~u', "\n", $input))) {
                    $continue = false;
                    $result = "Error while saving a file [" . $file . "]";
                }
            }
        } catch(Exception $e) {
            $continue = false;
            $result = $e->getMessage();
        }

        if (!empty($result)) throw new Exception($result);
    }

    /**
     * Method to save remotes configuration - part of API SET operation
     *
     * @param $file_dsc - file descriptor with uploaded new configuration
     * @param $override - flag indicating whether configuration has to be replaced or appended
     * @throws Exception with error message
     */
    private function saveRemotes($file_dsc, $override) {
        $dir = $this->confFolderPath;
        $file = $this->fullConfFilePath[$this::CONF_TYPE_REMOTES];
        $continue = true;
        $result = "";

        if (!is_dir($dir) || !is_writable($dir)) {
            $continue = false;
            $result = "Configuration directory [" . $dir . "] does not exist or is not writable";
        }

        if ($continue && (!is_file($file_dsc["tmp_name"]) || !is_readable($file_dsc["tmp_name"]))) {
            $continue = false;
            $result = "I can't find input file [" . $file_dsc["name"] . "] on the server";
        }

        try {
            if ($continue) {
                $conf = null;
                if ($override) {
                    $conf = new RemotesConfiguration($file_dsc["tmp_name"]);
                } else {
                    $conf = (new RemotesConfiguration($file))->merge(new RemotesConfiguration($file_dsc["tmp_name"]));
                }

                if ($conf && !file_put_contents($file, $conf->toString())) {
                    $continue = false;
                    $result = "Error while saving a file [" . $file . "]";
                }

                // unlink($file_dsc["tmp_name"]);
            }
        } catch(Exception $e) {
            $continue = false;
            $result = $e->getMessage();
        }

        if (!empty($result)) throw new Exception($result);
    }

    /**
     * Method to restart service - part of API SET and RESTORE operation
     *
     * @throws Exception with error message
     */
    private function restart() {
        $dir = $this->appFolderPath;
        shell_exec("cd " . $dir . " && ./stop.sh && ./start.sh .");
    }

    /**
     * Method to restore configuration from backup - part of API RESTORE operation
     *
     * @param $type - a type of configuration to restore from backup
     * @throws Exception with error message
     */
    private function restoreConf($type) {
        $dir = $this->confFolderPath;
        $file = $this->fullConfFilePath[$type];
        $bak_file = $this->fullBakFilePath[$type];
        $continue = true;
        $result = "";

        if (!is_dir($dir) || !is_writable($dir)) {
            $continue = false;
            $result = "Configuration directory [" . $dir . "] does not exist or is not writable";
        }

        if ($continue && (!file_exists($bak_file) || !is_readable($bak_file))) {
            $continue = false;
            $result = "Backup file [" . $bak_file . "] does not exist or is not writable";
        }

        if ($continue && (file_exists($file) && !is_writable($file))) {
            $continue = false;
            $result = "Configuration file [" . $file . "] is not writable";
        }

        // Restore
        try {
            if ($continue) {
                if (!copy($bak_file,$file)) {
                    $continue = false;
                    $result = "Backup restore serror [from: " . $bak_file . ", to: " . $file . "]";
                }
            }
        } catch(Exception $e) {
            $continue = false;
            $result = $e->getMessage();
        }

        if (!empty($result)) throw new Exception($result);
    }

    /**
     * Bootstrap the library
     * sets the configuration variables
     */
    private function init() {
        //configure the configuration folder path
        $this->confFolderPath = "/mnt/HD/HD_a2/.systemfile/mySync/etc"; //TODO
        $this->appFolderPath = "/mnt/HD/HD_a2/Nas_Prog/mySync";
        $this->confFiles = array( 1 => "rclone.conf", 2 => "rclone_job_def.conf" );

        //concatenate to form Full Log Path
        $this->fullConfFilePath = array_map(function($file):string { return $this->confFolderPath . "/" . $file; }, $this->confFiles);
        $this->fullBakFilePath = array_map(function($file):string { return $this->confFolderPath . "/" . $file . ".bak"; }, $this->confFiles);
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