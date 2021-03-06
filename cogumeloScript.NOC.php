<?php

// echo "\n\nScript  user: ".get_current_user();
// echo "\n\nPHP run user: ".(posix_getpwuid(posix_geteuid()))['name']."\n\n";

require_once( COGUMELO_LOCATION.'/coreClasses/CogumeloClass.php' );
require_once( APP_BASE_PATH.'/Cogumelo.php' );

global $COGUMELO_IS_EXECUTING_FROM_SCRIPT;
$COGUMELO_IS_EXECUTING_FROM_SCRIPT=true;

global $_C;
$_C = Cogumelo::get();

//
// Load the necessary modules
//
require_once( ModuleController::getRealFilePath('devel.php', 'devel') );
require_once( ModuleController::getRealFilePath('classes/controller/DevelDBController.php', 'devel') );
require_once( ModuleController::getRealFilePath('classes/controller/CacheUtilsController.php', 'mediaserver') );
Cogumelo::load('coreController/ModuleController.php');


if( !defined('DOCKER_ENV') ) {
  define( 'DOCKER_ENV', false );
}


if( empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
  $webBasePath = Cogumelo::getSetupValue( 'setup:webBasePath' );
  $_SERVER['DOCUMENT_ROOT'] = !empty($webBasePath) ? $webBasePath : ( getcwd().'/httpdocs' );
  echo( "\n".'Forzando SERVER[DOCUMENT_ROOT] = '.$_SERVER['DOCUMENT_ROOT']."\n" );
}


if( $argc > 1 ) {
  //parameters handler
  switch( $argv[1] ) {
    case 'setPermissions': // set the files/folders permission
      setPermissions();
      break;

    case 'setPermissionsDevel': // set the files/folders permission
      setPermissionsDevel();
      break;

    case 'makeAppPaths': // Prepare folders
      makeAppPaths();
      break;


    case 'createDB': // create database
      if( Cogumelo::getSetupValue('db:name') ) {
        ( IS_DEVEL_ENV ) ? setPermissionsDevel() : setPermissions();
        backupDB();
        createDB();
      }
      else {
        echo "\n\nDDBB not defined !!!\n - - -  EXIT  - - - \n\n\n";
      }
      break;

    case 'generateModel':
      if( Cogumelo::getSetupValue('db:name') ) {
        ( IS_DEVEL_ENV ) ? setPermissionsDevel() : setPermissions();
        backupDB();
        createRelSchemes();
        generateModel();
        flushAll();
      }
      else {
        echo "\n\nDDBB not defined !!!\n - - -  EXIT  - - - \n\n\n";
      }
      break;

    case 'deploy':
      if( Cogumelo::getSetupValue('db:name') ) {
        ( IS_DEVEL_ENV ) ? setPermissionsDevel() : setPermissions();
        backupDB();
        createRelSchemes();
        deploy();
        flushAll();
      }
      else {
        echo "\n\nDDBB not defined !!!\n - - -  EXIT  - - - \n\n\n";
      }
      break;

    case 'simulateDeploy':
      simulateDeploy();
      break;

    case 'createRelSchemes':
      if( Cogumelo::getSetupValue('db:name') ) {
        ( IS_DEVEL_ENV ) ? setPermissionsDevel() : setPermissions();
        createRelSchemes();
        ( IS_DEVEL_ENV ) ? setPermissionsDevel() : setPermissions();
      }
      else {
        echo "\n\nDDBB not defined !!!\n - - -  EXIT  - - - \n\n\n";
      }
      break;

    case 'bckDB': // do the backup of the db
    case 'backupDB': // do the backup of the db
      if( Cogumelo::getSetupValue('db:name') ) {
        ( IS_DEVEL_ENV ) ? setPermissionsDevel() : setPermissions();
        $file = ( $argc > 2 ) ? $argv[2].'.sql' : false;
        backupDB( $file );
      }
      else {
        echo "\n\nDDBB not defined !!!\n - - -  EXIT  - - - \n\n\n";
      }
      break;

    case 'restoreDB': // restore the backup of a given db
      if( Cogumelo::getSetupValue('db:name') ) {
        if( $argc > 2 ) {
          $file = $argv[2]; //name of the backup file
          restoreDB( $file );
          createRelSchemes();
          flushAll();
        }
        else {
          echo "You must specify the file to restore\n";
        }
      }
      else {
        echo "\n\nDDBB not defined !!!\n - - -  EXIT  - - - \n\n\n";
      }
      break;

    case 'prepareDependences':
      Cogumelo::load('coreController/DependencesController.php');
      $dependencesControl = new DependencesController();
      $dependencesControl->prepareDependences();
      break;

    case 'updateDependences':
      Cogumelo::load('coreController/DependencesController.php');
      $dependencesControl = new DependencesController();
      $dependencesControl->installDependences();
      break;

    case 'installDependences':
      Cogumelo::load('coreController/DependencesController.php');
      $dependencesControl = new DependencesController();
      $dependencesControl->prepareDependences();

      Cogumelo::load('coreController/DependencesController.php');
      $dependencesControl = new DependencesController();
      $dependencesControl->installDependences();
      break;

    case 'generateFrameworkTranslations':
      Cogumelo::load('coreController/i18nScriptController.php');
      $i18nscriptController = new i18nScriptController();
      $i18nscriptController->setEnviroment();
      $i18nscriptController->c_i18n_getSystemTranslations();
      echo "The files.po are ready to be edited!\n";
      break;

    case 'generateAppTranslations':
      Cogumelo::load('coreController/i18nScriptController.php');
      $i18nscriptController = new i18nScriptController();
      $i18nscriptController->setEnviroment();
      $i18nscriptController->c_i18n_getAppTranslations();
      echo "The files.po are ready to be edited!\n";
      break;

    case 'removeAllTranslations':
      Cogumelo::load('coreController/i18nScriptController.php');
      $i18nscriptController = new i18nScriptController();
      $i18nscriptController->c_i18n_removeTranslations();
      break;

    case 'precompileTranslations':
      actionPrecompileTranslations();
      break;

    case 'compileTranslations':
      actionCompileTranslations();
      break;

    case 'jsonTranslations':
      Cogumelo::load('coreController/i18nScriptController.php');
      $i18nscriptController = new i18nScriptController();
      $i18nscriptController->c_i18n_json();
      echo "The files.json are ready to be used!\n";
      break;

    /* We execute this two actions from web as we need to operate with the apache permissions*/
    case 'flush': // delete temporary files
      flushAll();
      echo "\n --- Flush DONE\n";
      break;

    // case 'rotateLogs':
    //   actionRotateLogs();
    //   break;

    case 'generateClientCaches':
      ( IS_DEVEL_ENV ) ? setPermissionsDevel() : setPermissions();
      actionGenerateClientCaches();
      ( IS_DEVEL_ENV ) ? setPermissionsDevel() : setPermissions();
      break;

    case 'garbageCollection':
      garbageCollection();
      break;

    default:
      echo "Invalid parameter;try:";
      printOptions();
      break;

  }//end switch
}//end parameters handler
else{
  echo "You have to write an option:";
  printOptions();
}


function printOptions(){
  echo "\n
  + Permissions and dependences
    * flush                   Remove temporary files
      * setPermissions(Devel) Set the files/folders permission
        * makeAppPaths        Prepare folders
      * generateClientCaches  Cache all js, css, compiled less and other client files
    * installDependences      Exec prepareDependences and then exec updateDependences
      * prepareDependences    Generate JSON's dependences
      * updateDependences     Install all modules dependencies


  + Database
    * createDB                Create a database

    * generateModel           Initialize database

    * deploy                  Deploy
      * createRelSchemes      Create JSON Model Rel Schemes
    * simulateDeploy          Simulate deploy SQL codes

    * resetModules
      - resetModuleVersions

    * backupDB                Do a DB backup (optional arg: filename)
    * restoreDB               Restore a database

  + Internationalization
    * generateFrameworkTranslations  Update text to translate in cogumelo and geozzy modules
    * generateAppTranslations        Get text to translate in the app
    * precompileTranslations         Generate the intermediate POs(geozzy, cogumelo and app)
    * compileTranslations            Mix geozzy, cogumelo and app POS in one and compile it to get the translations ready
  \n\n";
}

function callCogumeloServer( $param ) {
  $result = 'FAIL';

  echo "\n".'callCogumeloServer '.$param.' ...'."\n";

  $scriptCogumeloServerUrl = Cogumelo::getSetupValue( 'script:cogumeloServerUrl' );
  if( !empty( $scriptCogumeloServerUrl ) ) {
    // EVITAMOS CONTROLES HTTPS
    $contextOptions = stream_context_create( [
      "ssl" => [
        "verify_peer" => false,
        "verify_peer_name" => false,
      ],
    ] );
    $result = file_get_contents( $scriptCogumeloServerUrl . '?q='.$param, false, $contextOptions );
  }

  echo 'callCogumeloServer '.$param.': '.$result."\n";
  return $result;
}

function actionPrecompileTranslations() {
  Cogumelo::load('coreController/i18nScriptController.php');
  $i18nscriptController = new i18nScriptController();
  $i18nscriptController->c_i18n_precompile();
  echo "\nThe intermediate .po are ready\n\n";
}

function actionCompileTranslations() {
  Cogumelo::load('coreController/i18nScriptController.php');
  $i18nscriptController = new i18nScriptController();
  /*$i18nscriptController->setEnviroment();*/
  $i18nscriptController->c_i18n_compile();
  /* generate json for js */
  $i18nscriptController->c_i18n_json();
  echo "\nThe files.mo are ready to be used!\n\n";
}

function generateModel() {
  $develdbcontrol = new DevelDBController();
  $develdbcontrol->scriptGenerateModel();
}

function deploy() {
  $develdbcontrol = new DevelDBController();
  $develdbcontrol->scriptDeploy();
}

function simulateDeploy() {
  ob_start(); // Start output buffering
  $fvotdbcontrol = new DevelDBController();
  $fvotdbcontrol->deploy();
  $ret= ob_get_contents(); // Store buffer in variable
  ob_end_clean(); // End buffering and clean up
  var_dump( [$ret] );
}

function createRelSchemes() {
  echo "\nCreating relationship schemes\n";

  global $C_ENABLED_MODULES;

  foreach( $C_ENABLED_MODULES as $moduleName ) {
    require_once( ModuleController::getRealFilePath( $moduleName.'.php' , $moduleName) );
  }

  Cogumelo::load('coreModel/VOUtils.php');
  VOUtils::createModelRelTreeFiles();
}

function flushAll() {
  echo "\n --- setPermissions:\n";
  ( IS_DEVEL_ENV ) ? setPermissionsDevel() : setPermissions();

  echo "\n --- actionFlush:\n";
  actionFlush();

  // echo "\n --- actionCompileTranslations:\n";
  // actionCompileTranslations();

  echo "\n --- actionGenerateClientCaches:\n";
  if( Cogumelo::getSetupValue( 'mod:mediaserver:productionMode' ) ) {
    actionGenerateClientCaches();
  }
  else {
    echo "\nPasamos porque no estamos en PRODUCTION MODE\n";
  }

  echo "\n --- setPermissions:\n";
  ( IS_DEVEL_ENV ) ? setPermissionsDevel() : setPermissions();
}

function actionFlush() {

  // Def: app/tmp/templates_c
  rmdirRec( Cogumelo::getSetupValue('smarty:compilePath'), false );
  // Def: httpdocs/cgmlImg
  rmdirRec( Cogumelo::getSetupValue('mod:filedata:cachePath'), false );
  // Def: httpdocs/mediaCache
  rmdirRec( Cogumelo::getSetupValue('mod:mediaserver:tmpCachePath'), false );
  echo ' - Cogumelo File cache flush DONE'."\n";


  require_once( COGUMELO_LOCATION.'/coreClasses/coreController/Cache.php' );
  $cacheCtrl = new Cache();
  $cacheCtrl->flush();
  echo ' - Cogumelo Memory Cache flush DONE'."\n";


  $scriptCogumeloServerUrl = Cogumelo::getSetupValue( 'script:cogumeloServerUrl' );
  if( !empty( $scriptCogumeloServerUrl ) ) {
    echo ' - Cogumelo PHP cache flush...'."\n";
    callCogumeloServer('flush');
  }
  else {
    echo ' - Cogumelo PHP cache flush DESCARTADO.'."\n";
  }

  echo "\nCogumelo caches deleted!\n\n";
}

function actionGenerateClientCaches() {
  require_once( ModuleController::getRealFilePath( 'mediaserver.php', 'mediaserver' ) );
  mediaserver::autoIncludes();
  CacheUtilsController::generateAllCaches();

  // update timestamp file
  $cache_timestamp = new DateTime();
  file_put_contents( Cogumelo::getSetupValue( 'setup:appTmpPath' ).'/CACHE_FLUSH_TIMESTAMP.php', '<?php define("CACHE_FLUSH_TIMESTAMP", '.$cache_timestamp->getTimestamp().' );');

  echo "\nClient caches generated\n\n";
}

function createDB(){
  echo "\nDatabase configuration\n";

  $user = readStdin( "Enter an user with privileges:\n" );
  fwrite( STDOUT, "Enter the password:\n" );
  $passwd = getPassword( true );
  fwrite( STDOUT, "\n--\n" );

  $develdbcontrol = new DevelDBController( $user, $passwd );
  $develdbcontrol->createSchemaDB();

  echo "\nDatase created!\n";
}

function makeAppPaths() {
  echo "makeAppPaths\n";

  $prepareDirs = array( APP_TMP_PATH,
    Cogumelo::getSetupValue( 'smarty:configPath' ), Cogumelo::getSetupValue( 'smarty:compilePath' ),
    Cogumelo::getSetupValue( 'smarty:cachePath' ), Cogumelo::getSetupValue( 'smarty:tmpPath' ),
    Cogumelo::getSetupValue( 'mod:mediaserver:tmpCachePath' ),
    Cogumelo::getSetupValue( 'setup:webBasePath' ).'/'.Cogumelo::getSetupValue( 'mod:mediaserver:cachePath' ),

    // Dependences
    Cogumelo::getSetupValue( 'setup:webBasePath' ).'/vendor',
    Cogumelo::getSetupValue( 'dependences:bowerPath' ), Cogumelo::getSetupValue( 'dependences:yarnPath' ),
    Cogumelo::getSetupValue( 'dependences:composerPath' ), Cogumelo::getSetupValue( 'dependences:manualPath' ),

    Cogumelo::getSetupValue( 'logs:path' ),
    Cogumelo::getSetupValue( 'session:savePath' ),
    Cogumelo::getSetupValue( 'mod:form:tmpPath' ),
    Cogumelo::getSetupValue( 'mod:filedata:filePath' ),
    Cogumelo::getSetupValue( 'mod:filedata:cachePath' ),
    Cogumelo::getSetupValue( 'script:backupPath' ),
    Cogumelo::getSetupValue( 'i18n:path' ), Cogumelo::getSetupValue( 'i18n:localePath' )
  );

  foreach( Cogumelo::getSetupValue( 'lang:available' ) as $lang ) {
    $prepareDirs[] = Cogumelo::getSetupValue( 'i18n:localePath' ).'/'.$lang['i18n'].'/LC_MESSAGES';
  }

  $sessionSavePath = Cogumelo::getSetupValue('session:savePath');
  if( !empty( $sessionSavePath ) ) {
    $prepareDirs[] = $sessionSavePath;
  }

  foreach( $prepareDirs as $dir ) {
    if( $dir && $dir !== '' && !is_dir( $dir ) ) {
      if( !mkdir( $dir, 0750, true ) ) {
        echo 'ERROR: Imposible crear el dirirectorio: '.$dir."\n";
      }
    }
  }
  echo "makeAppPaths DONE.\n";
}

function setPermissions( $devel = false ) {
  makeAppPaths();

  $extPerms = $devel ? ',ugo+rX' : '';
  $sudo = 'sudo ';

  $sudoAllowed = true;
  if( Cogumelo::issetSetupValue('script:sudoAllowed') ) {
    $sudoAllowed = Cogumelo::getSetupValue('script:sudoAllowed');
  }

  $prjLivePath = Cogumelo::getSetupValue('setup:prjLivePath');

  if( DOCKER_ENV ) {
    $sudo = ''; // usase como root
    $sudoAllowed = true;
  }

  echo( "setPermissions ".($devel ? 'Devel' : '')."\n" );

  if( IS_DEVEL_ENV || $sudoAllowed ) {
    $dirsString =
      WEB_BASE_PATH.' '.APP_BASE_PATH.' '.APP_TMP_PATH.' '.
      Cogumelo::getSetupValue( 'smarty:configPath' ).' '.Cogumelo::getSetupValue( 'smarty:compilePath' ).' '.
      Cogumelo::getSetupValue( 'smarty:cachePath' ).' '.Cogumelo::getSetupValue( 'smarty:tmpPath' ).' '.
      Cogumelo::getSetupValue( 'mod:mediaserver:tmpCachePath' ).' '.
      WEB_BASE_PATH.'/'.Cogumelo::getSetupValue( 'mod:mediaserver:cachePath' ).' '.
      Cogumelo::getSetupValue( 'logs:path' ).' '.
      Cogumelo::getSetupValue( 'mod:form:tmpPath' ).' '.
      Cogumelo::getSetupValue( 'mod:filedata:filePath' ).' '.
      Cogumelo::getSetupValue( 'i18n:path' ).' '.Cogumelo::getSetupValue( 'i18n:localePath' )
    ;

    echo( " - Executamos chgrp general \n" );
    if( $prjLivePath ) {
      exec( $sudo.' chgrp www-data '.$prjLivePath );
    }
    $fai = 'chgrp -R www-data '.$dirsString;
    exec( $sudo.$fai );
  }
  else {
    echo( " - NON se executa chgrp general \n" );
  }

  if( IS_DEVEL_ENV || $sudoAllowed ) {
    $fai = 'chmod -R go-rwx,g+rX'.$extPerms.' '.WEB_BASE_PATH.' '.APP_BASE_PATH;
    echo( " - Executamos chmod WEB_BASE_PATH APP_BASE_PATH\n" );
    exec( $sudo.$fai );
  }
  else {
    echo( " - NON se executa chmod WEB_BASE_PATH APP_BASE_PATH\n" );
  }

  if( IS_DEVEL_ENV || $sudoAllowed ) {
    // Path que necesitan escritura Apache
    $fai = 'chmod -R ug+rwX'.$extPerms.' '.APP_TMP_PATH.' '.
      // Smarty
      Cogumelo::getSetupValue( 'smarty:configPath' ).' '.Cogumelo::getSetupValue( 'smarty:compilePath' ).' '.
      Cogumelo::getSetupValue( 'smarty:cachePath' ).' '.Cogumelo::getSetupValue( 'smarty:tmpPath' ).' '.

      // Cogumelo mediaserver
      Cogumelo::getSetupValue( 'mod:mediaserver:tmpCachePath' ).' '.
      WEB_BASE_PATH.'/'.Cogumelo::getSetupValue( 'mod:mediaserver:cachePath' ).' '.

      // Form y Filedata
      Cogumelo::getSetupValue( 'mod:filedata:cachePath' ).' '. // cgmlImg
      Cogumelo::getSetupValue( 'mod:filedata:filePath' ).' '. // formFiles
      Cogumelo::getSetupValue( 'mod:form:tmpPath' ).' '. // tmp formFiles

      // Varios
      Cogumelo::getSetupValue( 'logs:path' ).' '.
      // Cogumelo::getSetupValue( 'session:savePath' ).' '.
      // Cogumelo::getSetupValue( 'i18n:path' ).' '.Cogumelo::getSetupValue( 'i18n:localePath' ).' '.
      ''
    ;

    echo( " - Executamos chmod APP_TMP_PATH\n" );
    if( $prjLivePath ) {
      exec( $sudo.' chmod ug+rwX'.$extPerms.' '.$prjLivePath );
    }
    exec( $sudo.$fai );
  }
  else {
    echo( " - NON se executa ${sudo}chmod ug+rwX$extPerms dirsWriteString\n" );
  }

  if( IS_DEVEL_ENV || $sudoAllowed ) {
    echo( " - Preparando [session:savePath] e [script:backupPath]\n" );
    // session:savePath tiene que mantener el usuario y grupo
    $sessionSavePath = Cogumelo::getSetupValue( 'session:savePath' );
    if( !empty($sessionSavePath) ) {
      $fai = 'chgrp -R www-data '.$sessionSavePath;
      echo( " - Executamos ${sudo}$fai\n" );
      exec( $sudo.$fai );
      $fai = 'chmod -R ug+rwX'.$extPerms.' '.$sessionSavePath;
      echo( " - Executamos ${sudo}$fai\n" );
      exec( $sudo.$fai );
    }

    // Solo usuario administrador
    $backupPath = Cogumelo::getSetupValue( 'script:backupPath' );
    if( !empty($backupPath) ) {
      $fai = 'chmod -R go-rwx '.$backupPath;
      echo( " - Executamos ${sudo}$fai\n" );
      exec( $sudo.$fai );
    }
  }
  else {
    echo( " - NON se preparan [session:savePath] e [script:backupPath]\n" );
  }

  echo( "setPermissions ".($devel ? 'DEVEL' : '')." DONE.\n" );
}

function setPermissionsDevel() {
  setPermissions( true );
}

function backupDB( $file = false ) {

  $confDB = getDBConfiguration();

  if( empty( $file ) ) {
    $file = date('Ymd-His').'-'.$confDB['name'].'.sql';
  }

  $dir = Cogumelo::getSetupValue('script:backupPath');

  $params = '-h '.$confDB['hostname'].' -P '.$confDB['port'].' ';
  $params .= '-f ';
  $params .= '--hex-blob ';
  $params .= '--no-tablespaces ';
  // $params .= '--complete-insert --skip-extended-insert ';
  $params .= '-u '.$confDB['user'].' -p'.$confDB['password'].' ';
  $cmdBackup = 'mysqldump '.$params.' '.$confDB['name'].' --result-file='.$dir.'/'.$file;
  // echo "\n\n$cmdBackup\n";

  popen( $cmdBackup, 'r' );
  exec( 'gzip ' . $dir . '/' . $file );
  exec( 'chmod go-rwx ' . $dir . '/' . $file . '*' );
  echo "\nYour db was successfully saved!\n";
}

function restoreDB( $file = false ) {

  $confDB = getDBConfiguration();

  backupDB();

  $dir = Cogumelo::getSetupValue( 'script:backupPath' );

  $fileExt = pathinfo( $dir.$file, PATHINFO_EXTENSION );

  $params = '-h '.$confDB['hostname'].' -P '.$confDB['port'].' ';
  $params .= '-u '.$confDB['user'].' -p'.$confDB['password'].' ';

  if( $fileExt === 'gz' ) {
    popen('gunzip -c '.$dir.$file.' | mysql '.$params.' '.$confDB['name'], 'r');
  }
  else {
    popen('mysql '.$params.' '.$confDB['name'].'<' .$dir.$file, 'r');
  }
  echo "\nYour db was successfully restored!\n";
}

function getDBConfiguration() {
  $confDB = Cogumelo::getSetupValue('db');

  if( !empty($confDB) ) {
    if( empty( $confDB['hostname'] ) || $confDB['hostname'] === 'localhost' ) {
      $confDB['hostname'] = '127.0.0.1';
    }
    if( empty( $confDB['port'] ) ) {
      $confDB['port'] = 3306;
    }
  }

  return $confDB;
}

/**
 * Get data from the shell.
 */
function readStdin( $prompt ) {
  $input = false;

  while( empty($input) ) {
    echo $prompt;
    $input = strtolower( trim( fgets( STDIN ) ) );
  }

  return $input;
}

/**
 * Get a password from the shell.
 */
function getPassword( $stars = false ) {
  // Get current style
  $oldStyle = shell_exec('stty -g');

  if ($stars === false) {
    shell_exec('stty -echo');
    $password = rtrim(fgets(STDIN), "\n");
  }
  else {
    shell_exec('stty -icanon -echo min 1 time 0');

    $password = '';

    while( true ) {
      $char = fgetc( STDIN );

      if( $char === "\n" ) {
        break;
      }
      elseif( ord($char) === 127 ) {
        if( strlen($password) > 0 ) {
          fwrite( STDOUT, "\x08 \x08" );
          $password = substr( $password, 0, -1 );
        }
      }
      else {
        fwrite( STDOUT, "*" );
        $password .= $char;
      }
    }
  }

  // Reset old style
  shell_exec('stty ' . $oldStyle);

  // Return the password
  return $password;
}

function rmdirRec( $dir, $removeContainer = true ) {
  // error_log( "rmdirRec( $dir )" );

  $dir = rtrim( $dir, '/' );
  if( !empty( $dir ) && strpos( $dir, Cogumelo::getSetupValue('setup:prjBasePath') ) === 0 && is_dir( $dir ) ) {
    $dirElements = scandir( $dir );
    if( !empty( $dirElements ) ) {
      foreach( $dirElements as $object ) {
        if( $object !== '.' && $object !== '..' ) {
          if( is_dir( $dir.'/'.$object ) ) {

            rmdirRec( $dir.'/'.$object );
          }
          else {

            unlink( $dir.'/'.$object );
          }
        }
      }
    }
    reset( $dirElements );
    if( $removeContainer ) {
      if( !is_link( $dir ) ) {
        rmdir( $dir );
      }
      else {
        unlink( $dir );
      }
    }
  }
}

// garbageCollection
function garbageCollection() {
  echo "\n\n**********************************\n";
  echo "**  Garbage Collection - Start  **\n";
  echo "**********************************\n\n";

  // $params = [
  //   'verbose' => true,
  //   'noAction' => true
  // ];

  require_once( ModuleController::getRealFilePath( 'GarbageCollection.php', 'GarbageCollection' ) );
  GarbageCollection::load( 'controller/GarbageCollectionController.php' );
  $garbageCollCtrl = new GarbageCollectionController();
  $garbageCollCtrl->garbageCollection();

  echo "\n\n*********************************\n";
  echo "**  Garbage Collection - Done  **\n";
  echo "*********************************\n\n";
}
