<?php

Cogumelo::load('coreController/Module.php');

class common extends Module {

  public $name = 'common';
  public $version = 1.0;
  public $autoIncludeAlways = true;

  public $dependences = [
    [
      'id' =>'lobibox',
      'params' => [ 'lobibox' ],
      'installer' => 'yarn',
      'includes' => [ 'dist/css/lobibox.min.css', 'dist/js/lobibox.min.js' ]
    ]
  ];

  public $includesCommon = [
    'js/clientMsg.js',
    'js/ckAcepto.js',
  ];

  public function __construct() {}
}
