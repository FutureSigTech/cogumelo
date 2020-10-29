<?php

Cogumelo::load('coreController/Module.php');

class common extends Module {

  public $name = 'common';
  public $version = 1.0;
  public $autoIncludeAlways = true;

  public $dependences = [
    [
      'id' => 'less',
      'params' => [ 'less#v2.7.3' ],
      'installer' => 'bower',
      'includes' => []
    ],
    [
      'id' =>'lobibox',
      'params' => [ 'lobibox' ],
      'installer' => 'bower',
      'includes' => [ 'dist/css/lobibox.min.css', 'dist/js/lobibox.min.js' ]
    ]
  ];

  public $includesCommon = [
    'js/clientMsg.js',
    'js/ckAcepto.js',
    //'styles/common.less',
  ];

  public function __construct() {}
}
