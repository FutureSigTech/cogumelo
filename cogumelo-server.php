<?php
/**
 * Este script se lanza desde un php en el servidor web que prepara el entorno con la infomacion necesaria
 */

// We check that the conexion comes from localhost
if( !empty( $_SERVER['REMOTE_ADDR'] ) && isLocalAccess( $_SERVER['REMOTE_ADDR'] ) ) {

  $command = empty( $_GET['q'] ) ? '' : $_GET['q'];

  echo 'Processing cogumelo-server '.$command."\n";

  // Cargamos Cogumelo
  require_once( COGUMELO_LOCATION.'/coreClasses/CogumeloClass.php' );
  require_once( COGUMELO_LOCATION.'/coreClasses/coreController/DependencesController.php' );
  require_once( COGUMELO_LOCATION.'/coreClasses/coreController/Cache.php' );
  require_once( APP_BASE_PATH.'/Cogumelo.php' );

  switch( $command ) {
    case 'flush':
      if( function_exists('opcache_reset') ) {
        $opcacheReset = opcache_reset();
        echo ' - Cogumelo PHP cache flush: '.( $opcacheReset ? 'DONE' : 'FAIL')."\n";
      }
      break;

    default:
      header( 'HTTP/1.0 403 Forbidden' );
      echo( "You are forbidden!\n\nUnusual access to cogumelo-server\n" );
      error_log('ERROR: cogumelo-server.php - Unknown command ('.$command.')');
      break;

  } // switch
}
else {
  header( 'HTTP/1.0 403 Forbidden' );
  echo( "You are forbidden!\n\nUnusual access to cogumelo-server\n" );
  error_log('ERROR: cogumelo-server.php - Access forbidden ('.$command.')');
}




function isLocalAccess( $remoteAddr ) {
  $isLocal = false;

  $isLocal = $isLocal || ( $remoteAddr === 'local_shell' );
  $isLocal = $isLocal || ( strpos( $remoteAddr, '127.' ) === 0 );
  $isLocal = $isLocal || !filter_var( $remoteAddr, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );

  return $isLocal;
}
