<?php


Cogumelo::load('coreController/Module.php');

class mediaserver extends Module {

  public $name = 'mediaserver';
  public $version = 1.0;

  public $dependences = [
    // COMPOSER
    array(
      'id' => 'jsmin',
      'params' => array('linkorb/jsmin-php', '1.0.0'),
      'installer' => 'composer',
      'includes' => array('src/jsmin-1.1.1.php')
    ),
    array(
      'id' => 'cssmin',
      'params' => array('natxet/cssmin', '3.0.2'),
      'installer' => 'composer',
      'includes' => array('src/CssMin.php')
    ),
    'lessmin' => [
      'id' => 'lessmin',
      // Cambiamos la dependencia en __construct() segun la version de PHP
      // 'params' => [ 'oyejorge/less.php', '1.7.0.13' ], // No sirve para PHP 7.4
      'params' => [ 'wikimedia/less.php', '3.1.0' ], // PHP >= 7.2.9
      'installer' => 'composer',
      'includes' => [ 'lessc.inc.php' ]
    ]
  ];

  public $includesCommon = array(
    'controller/MediaserverController.php',
    'controller/CacheUtilsController.php',
    'controller/LessController.php'
  );


  public function __construct() {

    // Cambiamos la dependencia segun la version de PHP
    if( PHP_VERSION_ID < 70209 ) {
      $this->dependences['lessmin']['params'] = [ 'oyejorge/less.php', '1.7.0.13' ]; // No sirve para PHP 7.4
    }

    $this->addUrlPatterns( '#^'.Cogumelo::getSetupValue( 'mod:mediaserver:cachePath' ).'/jsConfConstants.js#', 'view:ConfConstantsView::javascript' );
    $this->addUrlPatterns( '#^'.Cogumelo::getSetupValue( 'mod:mediaserver:path' ).'/jsConfConstants.js#', 'view:ConfConstantsView::javascript' );
    $this->addUrlPatterns( '#^'.Cogumelo::getSetupValue( 'mod:mediaserver:path' ).'/jsLog.js#', 'view:ConfConstantsView::jslog' );
    $this->addUrlPatterns( '#^'.Cogumelo::getSetupValue( 'mod:mediaserver:path' ).'/lessConfConstants.less#', 'view:ConfConstantsView::less' );
    $this->addUrlPatterns( '#^'.Cogumelo::getSetupValue( 'mod:mediaserver:path' ).'/module(.*)#', 'view:MediaserverView::module' );
    $this->addUrlPatterns( '#^'.Cogumelo::getSetupValue( 'mod:mediaserver:path' ).'(/.*)#', 'view:MediaserverView::application' );
    $this->addUrlPatterns( '#(.+\/)?classes/view/templates/(.+)\.less$#', 'view:MediaserverView::onClientLess');

  }
}
