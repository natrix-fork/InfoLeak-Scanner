<?php

/**
 *   Automatic Detection of Information Leakage Vulnerabilities in
 *   Web Applications.
 *
 *   Copyright (C) 2015-2018 Ruhr University Bochum
 *
 *   @author Yakup Ates <Yakup.Ates@rub.de
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class Control{
    public $to_analyse = TRUE;

    private $messages;
    private $url;       /* controlled by client */
    private $punycode_url; /* punycode converted URL */
    private $source;    /* controlled by website */
    private $header;    /* controlled by website */

    private $dangerLevel; /* not used */
    private $userAgent;
    private $callbackurls = array();
    private $scannerHasError = FALSE;
    private $scannerErrorMessage = NULL;

    /**
     * Set this manually to filter local IP addresses!
     */
    private $bcast; // = "192.168.0.255";
    private $smask; // = "255.255.255.0";

    public function __construct($url, $ua) {
        $this->messages = new Messages();
        $this->setUserAgent($ua);

        $this->url = $url;
        $this->punycode_url = $this->punycodeUrl($url);
        $this->punycode_url = $this->checkURL($this->punycode_url);

        if ($this->url !== FALSE) {
            if ($this->checkRedir() === TRUE) {
                /* URL seems to be OK. Set source code. */
                $error_code = $this->setSource();
                if ($error_code > 0) {
                    $this->to_analyse = FALSE;
                    // error message is set in setSource()
                    $this->setScannerHasError(TRUE);
                    return NULL;
                }
            } else {
                $this->to_analyse = FALSE;
                $this->setScannerHasError(TRUE);
                return NULL;
            }
        } else {
            $this->to_analyse = FALSE;
            $this->setScannerHasError(TRUE); /* redundant */
            return NULL;
        }


        /**
         * If the URL was valid but the source code is empty there is nothing to
         * analyse.
         */
        if (empty($this->source)) {
            $this->setScannerErrorMessage(16, array('domain' => $this->url));
            $this->to_analyse = FALSE;
            $this->setScannerHasError(TRUE);
            return NULL;
        }
    }

    /**
     * Function to set the scanners error message.
     */
    public function setScannerErrorMessage($id, $values) {
        if (is_int($id)) {
            $placeholder = $this->messages->getNameById($id);

            $this->scannerErrorMessage = array("placeholder" => (string)$placeholder[0],
                                               "values" => $values);
        }
    }

    /**
     * Function to check if the scanner had an error.
     */
    public function getScannerErrorMessage() {
        return $this->scannerErrorMessage;
    }


    /**
     * Function to indicate that the scanner had an error.
     */
    public function setScannerHasError($hasError=FALSE) {
        if (is_bool($hasError)) {
            $this->scannerHasError = $hasError;
        }
    }

    /**
     * Function to check if the scanner had an error.
     */
    public function getScannerHasError() {
        return $this->scannerHasError;
    }

    /**
     * Function to set dangerLevel
     * NOTE: dangerLevel is not used for now.
     */
    public function setDangerLevel($dangerlevel) {
        if (is_int($dangerlevel)) {
            $this->dangerLevel = $dangerlevel;
        }
    }

    /**
     * Function to set callbackurls
     */
    public function setCallbackurls($callbackurls) {
        $this->callbackurls = $callbackurls;
    }

    /**
     * Function to access dangerLevel
     * NOTE: dangerLevel is not used for now.
     */
    public function getDangerLevel() {
        return $this->dangerLevel;
    }

    /**
     * Function to access callbackurls
     */
    public function getCallbackurls() {
        return $this->callbackurls;
    }

    /**
     * Function to access the private variable $url
     */
    public function getURL() {
        return $this->url;
    }

    /**
     * Function to access the private variable $source
     */
    public function getSource() {
        return $this->source;
    }

    /**
     * Function to access the private variable $userAgent
     */
    public function getUserAgent() {
        return $this->userAgent;
    }

    /**
     * Get curl error code and set errorMessage placeholder
     */
    private function curlError($errno) {
        switch ($errno) {
        case 28:
            $this->setScannerErrorMessage(36, array('domain' => $this->url,
                                                    'timeoutvalue' => 10));
            $this->setScannerHasError(TRUE);
            break;
        case 1:
            $this->setScannerErrorMessage(24, array('domain' => $this->url));
            $this->setScannerHasError(TRUE);
            break;
        case 2:
            $this->setScannerErrorMessage(25, array('domain' => $this->url));
            $this->setScannerHasError(TRUE);
            break;
        case 3:
            $this->setScannerErrorMessage(26, array('domain' => $this->url));
            $this->setScannerHasError(TRUE);
            break;
        case 6:
            $this->setScannerErrorMessage(27, array('domain' => $this->url));
            $this->setScannerHasError(TRUE);
            break;
        case 7:
            $this->setScannerErrorMessage(28, array('domain' => $this->url));
            $this->setScannerHasError(TRUE);
            break;
        case 9:
            $this->setScannerErrorMessage(29, array('domain' => $this->url));
            $this->setScannerHasError(TRUE);
            break;
        case 16:
            $this->setScannerErrorMessage(30, array('domain' => $this->url));
            $this->setScannerHasError(TRUE);
            break;
        case 47:
            $this->setScannerErrorMessage(31, array('domain' => $this->url));
            $this->setScannerHasError(TRUE);
            break;
        case 55:
            $this->setScannerErrorMessage(32, array('domain' => $this->url));
            $this->setScannerHasError(TRUE);
            break;
        case 61:
            $this->setScannerErrorMessage(34, array('domain' => $this->url));
            $this->setScannerHasError(TRUE);
            break;
        case 66:
            $this->setScannerErrorMessage(35, array('domain' => $this->url));
            $this->setScannerHasError(TRUE);
            break;
        case 75:
            $this->setScannerErrorMessage(37, array('domain' => $this->url));
            $this->setScannerHasError(TRUE);
            break;
        case 78:
            $this->setScannerErrorMessage(38, array('domain' => $this->url));
            $this->setScannerHasError(TRUE);
            break;
        case 92:
            $this->setScannerErrorMessage(39, array('domain' => $this->url));
            $this->setScannerHasError(TRUE);
            break;
        default:
            $this->setScannerErrorMessage(19, array('domain' => $this->url));
            $this->setScannerHasError(TRUE);
            break;
        }
    }

    /**
     * @short Get source code of given URL.
     * @var options Defines settings for the cURL connection
     * @var con The cURL connection
     * @algorithm Connects to the global variable $url. Gets content of the
     * * website. Saves content to the global variable $source.
     * @return 0
     */
    private function setSource() {
        $con = curl_init($this->punycode_url);
        
        $options = array(
            CURLOPT_HEADER          => false,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_AUTOREFERER     => true,
            CURLOPT_FAILONERROR     => true,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_USERAGENT       => $this->userAgent,
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_TIMEOUT         => 10
        );

        /* Use settings defined in $options for the connection */
        curl_setopt_array($con, $options);
        /* Save content */
        $this->source = curl_exec($con);

        $curl_errno = curl_errno($con);
        if ($curl_errno > 0) {
            $this->curlError($curl_errno);

            curl_close($con);
            return $curl_errno;
        }

        curl_close($con);

        return 0;
    }

    /**
     * @short Do not allow redirects to foreign hosts.
     * @var url_host Defines the host
     * @var redir_host Holds destination host of the redirect
     * @algorithm Check whether there is a redirect. If there is a redirect,
     * * check its destination. Do only allow destinations which point to the same
     * * host.
     * @return boolean
     */
    private function checkRedir() {
        $data = $this->header[0];
        $info = $this->header[1];

        $header = $data;
        if ($info>=300 && $info<=308) {
            $key = array_values(preg_grep("!(?:Location|URI): *(.*?) *!", $header));
            $key = $key[0];
            $redir = substr($key, strpos($key, ':')+2, strlen($key));

            if (!empty($redir)) {
                $tmp = $this->checkURL($redir);

                if (empty($tmp)) {
                    return TRUE;
                } else if ($tmp !== FALSE) {
                    $redir_host = parse_url($this->checkURL($redir));
                } else {
                    $this->setScannerErrorMessage(23, array('domain' => $redir));
                    $this->setScannerHasError(TRUE);
                    return FALSE;
                }

                //$redir_host = parse_url($redir);
                $url_host = parse_url($this->punycode_url);

                if (empty($redir_host['host']) || empty($url_host['host']))
                    return TRUE;

                if ($url_host['host'] === $redir_host['host']) {
                    return TRUE;
                } else {
                    $this->setScannerHasError(TRUE);
                    $this->setScannerErrorMessage(23, array('domain' => $redir));
                    return FALSE;
                }
            } else {
                return TRUE;
            }
        } else {
            return TRUE;
        }
    }

    /**
     * @short Returns header fields.
     * @var result Contains header fields
     * @var con The cURL connection
     * @var options Defines settings for the cURL connection
     * @return string
     */
    private function setHeader($url) {
        $con = curl_init($this->punycode_url);

        $options = array(
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_FAILONERROR    => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => $this->userAgent,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 10
        );
        
        curl_setopt_array($con, $options);
        $data = curl_exec($con);
        $info = curl_getinfo($con);

        $curl_errno = curl_errno($con);
        if ($curl_errno > 0) {
            $this->curlError($curl_errno);

            curl_close($con);
            return $curl_errno;
        }

        $result = array(
            '0' => $data,
            '1' => $info
        );
        $this->header = $result;

        curl_close($con);
        return $result;
    }

    /**
     * @short: Add HTTP scheme to the URL.
     * @var url: The URL which will get the scheme added
     * @algorithm: Is the scheme specified? If not add it, else leave it as it
     * * is.
     * @return string
     */
    private function addHTTP($url, $scheme = 'http://') {
        return parse_url($url, PHP_URL_SCHEME) === null ? $scheme . $url : $url;
    }

    /*
     * @short: Validate the given URL.
     * @var url: The URL which is going to be analyzed
     * @var url_head: Contains respone headers
     * @algorithm: Did the user specify the protocol?
     * * If not, do it with 'http://'.
     * * Are all characters within the URL valid?
     * * Does the URL exist? Does it respond?
     * * Check the HTTP status code - if it's 404 the given address
     * * probably does not exist -> exit.
     * * Is a local/localhost address given? If so, exit.
     * * Is a port other than 80 (HTTP) or 443 (HTTPS) specified? If so, exit.
     * * Do not allow any username/passwords within the given url.
     *
     * IMPORTANT: $url may be edited.
     * @return boolean
     */
    private function checkURL($url) {
        /* relative path for redirect */
        if (substr($url, 0, 1) === "/") {
            $url = filter_var($url, FILTER_SANITIZE_URL);
            return TRUE;
        }

        if (!empty($url)) {
            /* Does the URL have illegal characters? */
            $url = filter_var($url, FILTER_SANITIZE_URL);

            /* Protocol specified? */
            $url = $this->addHTTP($url);

            /* Is the URL valid? */
            if ((filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED) === FALSE)) {
                $this->setScannerErrorMessage(17, array('domain' => $url));
                $this->setScannerHasError(TRUE);
                return FALSE;
            } else {
                $url_tmp = parse_url($url);

                if (isset($url_tmp['host'])) {
                    if (($url_tmp['host'] === '127.0.0.1') || ($url_tmp['host'] === 'localhost')) {
                        $this->setScannerErrorMessage(18, array('domain' => $url));
                        $this->setScannerHasError(TRUE);
                        return FALSE;
                    }

                    $regex  = "/\b(([1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.)(([0-9]|";
                    $regex .= "[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.) {2}([0-9]|";
                    $regex .= "[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\b/";
                    $ip;
                    if (preg_match($regex, $url_tmp['host'], $ip) === 1) {
                        /*
                         * Broadcast and mask are hardcoded for now.
                         * With this check the program denies that an attacker
                         * is able to "scan" the local network of the
                         * server. We could get the $bcast and $smask out of
                         * ifconfig or ipconfig. We could also request it by the
                         * admin in a setup script or similar.
                         * --- TODO ---
                         */
                        $bcast = $this->bcast;
                        $smask = $this->smask;
                        if ($this->IP_isLocal($url_tmp['host'], $bcast, $smask) === TRUE) {
                            $this->setScannerErrorMessage(19, array('domain' => $url));
                            $this->setScannerHasError(TRUE);
                            return FALSE;
                        }
                    }
                }

                /* Only allow HTTP and HTTPS ports in the URL. */
                if (isset($url_tmp['port'])) {
                    if (($url_tmp['port'] != '80')
                        && ($url_tmp['port'] != '443')) {
                        $this->setScannerErrorMessage(20, array('domain' => $url));
                        $this->setScannerHasError(TRUE);
                        return FALSE;
                    }
                }

                if (isset($url_tmp['user']) || isset($url_tmp['pass'])) {
                    $this->setScannerErrorMessage(20, array('domain' => $url));
                    $this->setScannerHasError(TRUE);
                    return FALSE;
                } else {
                    /* URL seems legit. Check headers now. */
                    $headers = get_headers($url);
                    $status_code = substr($headers[0], 9, 3);
                    $this->header[0] = $headers;
                    $this->header[1] = $status_code;

                    if (empty($status_code)) {
                        //$this->setScannerErrorMessage(19, array('domain' => $url));
                        $this->setScannerHasError(TRUE);
                        return FALSE;
                    } else if ($status_code != '404') {
                        /* Everything seems fine! */
                        $this->punycode_url = $url;
                        return $url;
                    } else {
                        $this->setScannerErrorMessage(19, array('domain' => $url));
                        $this->setScannerHasError(TRUE);
                        return FALSE;
                    }
                }
            }
        } /* else: no URL given - nothing to do. */
    }

    /**
     * @short: Is the given IP local?
     * @var ip: IP to analyze
     * @var bcast: Broadcast address of server
     * @var smask: Mask address of server
     * @algorithm: Calculates whether $ip is in the local network.
     * * Actually it only calculates if it _could_ be in the local network with
     * * the given broadcast address and mask.
     * @return boolean
     */
    private function IP_isLocal($ip, $bcast, $smask) {
        if (empty($bcast) || empty($smask) || empty($ip))
            return NULL;

        $bcast = ip2long($bcast);
        $smask = ip2long($smask);
        $ip    = ip2long($ip);

        $nmask = $bcast & $smask;

        return (($ip & $smask) == ($nmask & $smask));
    }

    /**
     * Send scan results to defined callbackurls
     */
    public function send_to_callbackurls($result) {
        foreach($this->getCallbackurls() as $url) {
            $this->sendResult_POST(json_encode($result,
                                               JSON_PRETTY_PRINT |
                                               JSON_UNESCAPED_UNICODE |
                                               JSON_UNESCAPED_SLASHES),
                                   $url);
        }
    }

    /**
     * Send $result to $url per POST
     */
    public function sendResult_POST($result, $url) {
        $this->checkURL($url);

        $con = curl_init($url);

        $options = array(
            CURLOPT_HEADER          => false,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_CUSTOMREQUEST   => "POST",
            CURLOPT_POSTFIELDS      => $result,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_AUTOREFERER     => true,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_USERAGENT       => $this->userAgent,
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_TIMEOUT         => 10
        );

        /* Use settings defined in $options for the connection */
        curl_setopt_array($con, $options);
        curl_exec($con);
        curl_close($con);

        return 0;
    }

    /**
     * Set the user agent individually
     */
    public function setUserAgent($agent) {
        if (!empty($agent)) {
            $this->userAgent = $agent;   
        } else {
            /**
             * Default user agent
             */
            $agent  = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) ";
            $agent .= "AppleWebKit/537.36 (KHTML, like Gecko) ";
            $agent .= "Chrome/60.0.3112.113 Safari/537.36";

            $this->userAgent = $agent;   
        }
    }

    /**
     * Returns the Punycode encoded URL for a given URL.
     *
     * @param string $url URL to encode
     *
     * @return string Punycode-Encoded URL.
     * @author https://github.com/Lednerb
     */
    public function punycodeUrl($url) {
        $parsed_url = parse_url($url);
        $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'].'://' : '';
        $host = isset($parsed_url['host']) ? idn_to_ascii($parsed_url['host'], IDNA_NONTRANSITIONAL_TO_ASCII,INTL_IDNA_VARIANT_UTS46) : '';
        $port = isset($parsed_url['port']) ? ':'.$parsed_url['port'] : '';
        $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass = isset($parsed_url['pass']) ? ':'.$parsed_url['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query = isset($parsed_url['query']) ? '?'.$parsed_url['query'] : '';

        return "$scheme$user$pass$host$port$path$query";
    }
}
?>
