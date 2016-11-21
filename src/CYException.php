<?php

namespace CYCouchDB;

class CYException extends \Exception {
  public function __construct($msg = "", $code = 0) {
    parent::__construct("CY Error: $msg", $code);
  }
}
