<?php

// Project location
define( 'WEB_BASE_PATH', getcwd() );
define( 'APP_BASE_PATH', getcwd().'/../app' );
define( 'SITE_PATH', APP_BASE_PATH.'/' );

// Include cogumelo core Location
set_include_path( '.:'.SITE_PATH.':'.get_include_path() );

require_once( SITE_PATH.'/conf/setup.php' );

require_once( COGUMELO_LOCATION.'/coreClasses/CogumeloClass.php' );
require_once( COGUMELO_LOCATION.'/coreClasses/coreController/DependencesController.php' );

require_once( SITE_PATH.'/Cogumelo.php' );

// resolving vendor includes
$dependencesControl = new DependencesController();
$dependencesControl->loadCogumeloIncludes();

// error & warning handlers
set_error_handler( 'Cogumelo::warningHandler' );
register_shutdown_function( 'Cogumelo::errorHandler' );
if( !ERRORS ) {
  ini_set( 'display_errors', 0 );
}


global $_C;
$_C = Cogumelo::get();
$_C->exec();

