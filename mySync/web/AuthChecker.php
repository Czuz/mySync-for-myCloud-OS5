<?php
namespace mySync;

class AuthChecker {
    /**
     * Static public method that checks whether application is protected on the server from unauthorized calls like the one below.
     * If not, access to page is blocked.
     *
     * @return $header array
     * */
    public static function isProtected() {
        $curlSession = curl_init();
        curl_setopt($curlSession, CURLOPT_URL, 'http://localhost/apps/mySync/AuthChecker.php');
        curl_exec($curlSession);
        $response_code = curl_getinfo($curlSession, CURLINFO_HTTP_CODE);
        curl_close($curlSession);

        if ($response_code == 403) {
            return true;
        } else {
            http_response_code(403);
            die('Forbidden');
        }
    }
}
?>
