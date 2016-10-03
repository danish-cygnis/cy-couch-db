<?php

namespace CYCouchDB;

class SagException extends \Exception {
  public function __construct($msg = "", $code = 0) {
    parent::__construct("CY Error: $msg", $code);
  }
}
