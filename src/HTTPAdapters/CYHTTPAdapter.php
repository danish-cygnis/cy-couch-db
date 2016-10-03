<?php


namespace CYCouchDB\HTTPAdapters;
use CYCouchDB\CYException;

abstract class CYHTTPAdapter {
  public $decodeResp = true;

  protected $host;
  protected $port;

  protected $proto = 'http'; //http or https
  protected $sslCertPath;

  protected $socketOpenTimeout;                 //The seconds until socket connection timeout
  protected $socketRWTimeoutSeconds;            //The seconds for socket I/O timeout
  protected $socketRWTimeoutMicroseconds;       //The microseconds for socket I/O timeout

  public function __construct($host = "127.0.0.1", $port = "5984") {
    $this->host = $host;
    $this->port = $port;
  }


  protected function makeResult($response, $method) {
    //Make sure we got the complete response.
    if(
      $method != 'HEAD' &&
      isset($response->headers->{'content-length'}) &&
      strlen($response->body) != $response->headers->{'content-length'}
    ) {
      throw new CYException('Unexpected end of packet.');
    }

    if($method == 'HEAD') {
      if($response->status >= 400) {
        throw new CYCouchException('HTTP/CouchDB error without mesCYe body', $response->headers->_HTTP->status);
      }

      return $response;
    }

    if(
      !empty($response->headers->{'content-type'}) &&
      $response->headers->{'content-type'} == 'application/json'
    ) {
      $json = json_decode($response->body);

      if(isset($json)) {
        if(!empty($json->error)) {
          throw new CYCouchException("{$json->error} ({$json->reason})", $response->headers->_HTTP->status);
        }

        if($this->decodeResp) {
          $response->body = $json;
        }
      }
    }

    return $response;
  }

  protected function parseCookieString($cookieStr) {
    $cookies = new \StdClass();

    foreach(explode('; ', $cookieStr) as $cookie) {
      $crumbs = explode('=', $cookie);
      if(!isset($crumbs[1])) {
        $crumbs[1] = '';
      }
      $cookies->{trim($crumbs[0])} = trim($crumbs[1]);
    }

    return $cookies;
  }


  abstract public function procPacket($method, $url, $data = null, $reqHeaders = array(), $specialHost = null, $specialPort = null);



  public function useSSL($use) {
    $this->proto = 'http' . (($use) ? 's' : '');
  }

  public function setSSLCert($path) {
    $this->sslCertPath = $path;
  }


  public function usingSSL() {
    return $this->proto === 'https';
  }


  public function setOpenTimeout($seconds) {
    if(!is_int($seconds) || $seconds < 1) {
      throw new CYException('setOpenTimeout() expects a positive integer.');
    }

    $this->socketOpenTimeout = $seconds;
  }

  public function setRWTimeout($seconds, $microseconds) {
    if(!is_int($microseconds) || $microseconds < 0) {
      throw new CYException('setRWTimeout() expects $microseconds to be an integer >= 0.');
    }

    if(
      !is_int($seconds) ||
      (
        (!$microseconds && $seconds < 1) ||
        ($microseconds && $seconds < 0)
      )
    ) {
      throw new CYException('setRWTimeout() expects $seconds to be a positive integer.');
    }

    $this->socketRWTimeoutSeconds = $seconds;
    $this->socketRWTimeoutMicroseconds = $microseconds;
  }


  public function getTimeouts() {
    return array(
      'open' => $this->socketOpenTimeout,
      'rwSeconds' => $this->socketRWTimeoutSeconds,
      'rwMicroseconds' => $this->socketRWTimeoutMicroseconds
    );
  }

  public function setTimeoutsFromArray($arr) {

    if(!is_array($arr)) {
      throw CYException('Expected an array and got something else.');
    }

    if(is_int($arr['open'])) {
      $this->setOpenTimeout($arr['open']);
    }

    if(is_int($arr['rwSeconds'])) {
      if(is_int($arr['rwMicroseconds'])) {
        $this->setRWTimeout($arr['rwSeconds'], $arr['rwMicroseconds']);
      }
      else {
        $this->setRWTimeout($arr['rwSeconds']);
      }
    }
  }
}
