<?php


Class DependencesController {


  //
  //  Vendor lib resolution
  //
  var $allDependencesComposer = array();
  var $allDependencesBower = array();
  var $allDependencesManual = array();
  var $allDependencesYarn= array();

  public function __construct() {
    Cogumelo::load('coreController/ModuleController.php');
  }

  public function prepareDependences() {
    $this->loadDependences();
    $this->prepareDependencesYarn($this->allDependencesYarn);
    $this->prepareDependencesComposer($this->allDependencesComposer);
  }

  public function installDependences() {
    $this->loadDependences();

    $this->installDependencesYarn($this->allDependencesYarn);
    $this->installDependencesBower($this->allDependencesBower);
    $this->installDependencesComposer($this->allDependencesComposer);
    $this->installDependencesManual($this->allDependencesManual);
  }


  public function loadDependences() {

    $this->allDependencesComposer = array();
    $this->allDependencesBower = array();
    $this->allDependencesManual = array();
    $this->allDependencesYarn= array();

    $moduleControl = new ModuleController(false, true);
    //Cargamos las dependencias de los modulos
    global $C_ENABLED_MODULES;
    foreach ( $C_ENABLED_MODULES as $mod ){
      $modUrl = ModuleController::getRealFilePath( $mod.".php" , $mod );
      require_once($modUrl);
      if(! class_exists('extClass'. $mod)){
        eval('class extClass'. $mod .' extends '.$mod. '{}');
      }
      eval('$objMod'.$mod.' = new extClass'.$mod.'();');
      eval('$dependences = $objMod'.$mod.'->dependences;');

      $this->pushDependences($dependences);
    }

    //Cargamos dependencias de Cogumelo class
    $this->pushDependences(Cogumelo::$mainDependences);

    //Cargamos las dependencias de Base App (externas a los modulos).
    global $_C;
    $this->pushDependences($_C->dependences);
  }

  public function pushDependences( $dependences ) {
    //Hacemos una lista de las dependecias de todos los modulos
    foreach ( $dependences as $dependence ){
      //Diferenciamos entre instaladores
      switch($dependence['installer']){
        case "composer":
          $this->pushDependencesComposer ($dependence);
        break;
        case "yarn":
          $this->pushDependencesYarn ($dependence);
        break;
        case "bower":
          $this->pushDependencesBower ($dependence);
        break;
        case "manual":
          $this->pushDependencesManual ($dependence);
        break;
      }
    }   // end foreach
  }

  public function pushDependencesComposer( $dependence ) {

    if(!array_key_exists($dependence['id'], $this->allDependencesComposer)){
      $this->allDependencesComposer[$dependence['id']] = array($dependence['params']);
    }
    else{
      $diffAllDepend = array_diff($dependence['params'] , $this->allDependencesComposer[$dependence['id']][0]);

      if(!empty($diffAllDepend)){
        array_push($this->allDependencesComposer[$dependence['id']], array_diff($dependence['params'] , $this->allDependencesComposer[$dependence['id']][0])  );
      }
    }
  }

  public function pushDependencesBower( $dependence ) {
    if(!array_key_exists($dependence['id'], $this->allDependencesBower)){
      $this->allDependencesBower[$dependence['id']] = array($dependence['params']);
    }
    else{
      $diffAllDepend = array_diff($dependence['params'] , $this->allDependencesBower[$dependence['id']][0]);

      if(!empty($diffAllDepend)){
        array_push($this->allDependencesBower[$dependence['id']], array_diff($dependence['params'] , $this->allDependencesBower[$dependence['id']][0])  );
      }
    }
  }

  public function pushDependencesYarn( $dependence ) {
    if(!array_key_exists($dependence['id'], $this->allDependencesYarn)){
      $this->allDependencesYarn[$dependence['id']] = array($dependence['params']);
    }
    else{
      $diffAllDepend = array_diff($dependence['params'] , $this->allDependencesYarn[$dependence['id']][0]);

      if(!empty($diffAllDepend)){
        array_push($this->allDependencesYarn[$dependence['id']], array_diff($dependence['params'] , $this->allDependencesYarn[$dependence['id']][0])  );
      }
    }
  }

  public function pushDependencesManual( $dependence ) {
    if(!array_key_exists($dependence['id'], $this->allDependencesManual)){
      $this->allDependencesManual[$dependence['id']] = array($dependence['params']);
    }
    else{
      $diffAllDepend = array_diff($dependence['params'] , $this->allDependencesManual[$dependence['id']][0]);

      if(!empty($diffAllDepend)){
        array_push($this->allDependencesManual[$dependence['id']], array_diff($dependence['params'] , $this->allDependencesManual[$dependence['id']][0])  );
      }
    }
  }

  public function prepareDependencesComposer( $dependences ) {

    echo "\n === Composer dependences ===\n\n";

    $composerPath = Cogumelo::getSetupValue( 'dependences:composerPath' );

    if( !is_dir( $composerPath ) ) {
      if( !mkdir( $composerPath, 0755, true ) ) {
        echo "The destination folder does not exist and have permission to create \n";
      }
    }

    $finalArrayDep = array( "require" => array(), "config" => array( "vendor-dir" => ltrim(str_replace( PRJ_BASE_PATH, '', $composerPath), '/')));
    foreach( $dependences as $depKey => $dep ){
      foreach( $dep as $params ){
        $finalArrayDep['require'][$params[0]] = $params[1];
        echo "\n === Composer add dependence: ".$params[0].$params[1]." \n\n";
      }
    }

    $jsonencoded = json_encode( $finalArrayDep );
    $fh = fopen( PRJ_BASE_PATH . '/composer.json', 'w' );
      fwrite( $fh, $jsonencoded );
    fclose( $fh );

    echo "\n === Composer dependences: Done ===\n\n";
  }
  public function prepareDependencesYarn( $dependences ) {
    if(!empty($dependences) && !empty(Cogumelo::getSetupValue( 'dependences:yarnPath' ))){

      echo "\n === Yarn dependences ===\n\n";
      $yarnPath = Cogumelo::getSetupValue( 'dependences:yarnPath' );

      if( !is_dir( $yarnPath ) ) {
        if( !mkdir( $yarnPath, 0755, true ) ) {
          echo "The destination folder does not exist and have permission to create \n";
        }
      }

      $jsonYarnRC = "--production --no-lockfile --modules-folder httpdocs/vendor/yarn/ \n";
      $fh = fopen( PRJ_BASE_PATH . '/.yarnrc', 'w' );
        fwrite( $fh, $jsonYarnRC );
      fclose( $fh );


      $jsonYarn = '{ "name": "cogumelo", "version": "1.0.0", '.
        ' "repository": "https://github.com/Innoto/cogumelo", "license": "Apache-2.0", "dependencies": {}, "author": "Innoto" }';
      $fh = fopen( PRJ_BASE_PATH . '/package.json', 'w' );
        fwrite( $fh, $jsonYarn );
      fclose( $fh );

      foreach( $dependences as $depKey => $dep ){
        foreach( $dep as $params ) {
          if( count($params) > 1 ) {
            $allparam = "";
            foreach( $params as $p ) {
              $allparam = $allparam." ".$p;
            }
          }
          else {
            $allparam = $params[0];
          }
          echo( "Exec... yarn add ".$allparam."   \n" );
          exec( 'cd '.PRJ_BASE_PATH.' ; yarn add '.$allparam.'' );
        } // end foreach
      } // end foreach

      echo "\n === Yarn dependences: Done ===\n\n";

    }
  }

  public function installDependencesBower( $dependences ) {
    echo "\n === Bower dependences ===\n\n";

    $bowerPath = Cogumelo::getSetupValue( 'dependences:bowerPath' );

    if( !is_dir( $bowerPath ) ) {
      if( !mkdir( $bowerPath, 0755, true ) ) {
        echo "The destination folder does not exist and have permission to create \n";
      }
    }

    $jsonBowerRC = '{ "directory": "'.ltrim(str_replace( PRJ_BASE_PATH, '', $bowerPath), '/').'", '.
      ' "json": "'. PRJ_BASE_PATH . '/bower.json" }';
    $fh = fopen( PRJ_BASE_PATH . '/.bowerrc', 'w' );
      fwrite( $fh, $jsonBowerRC );
    fclose( $fh );

    $jsonBower = '{ "name": "cogumelo", "version": "1.0a", '.
      ' "homepage": "https://github.com/Innoto/cogumelo", "license": "GPLv2", "dependencies": {} }';
    $fh = fopen( PRJ_BASE_PATH . '/bower.json', 'w' );
      fwrite( $fh, $jsonBower );
    fclose( $fh );

    foreach( $dependences as $depKey => $dep ){
      foreach( $dep as $params ) {
        if( count($params) > 1 ) {
          $allparam = "";
          foreach( $params as $p ) {
            $allparam = $allparam." ".$p;
          }
        }
        else {
          $allparam = $params[0];
        }
        echo( "Exec... bower install ".$depKey."=".$allparam." --quiet\n" );
        exec( 'cd '.PRJ_BASE_PATH.' ; bower install '.$depKey.'='.$allparam.' --quiet --allow-root' );
      } // end foreach
    } // end foreach

    echo "\n === Bower dependences: Done ===\n\n";
  }

  public function installDependencesYarn( $dependences ) {
    if(!empty($dependences) && !empty(Cogumelo::getSetupValue( 'dependences:yarnPath' ))){
      echo "\n === Yarn dependences ===\n\n";
      echo("Exec... yarn install \n");
      echo exec('cd '.PRJ_BASE_PATH.' ; yarn install');
      echo "\n === Yarn dependences: Done ===\n\n";
    }
  }

  public function installDependencesComposer( $dependences ) {
    echo "\n === Composer dependences ===\n\n";
    echo("Exec... php composer.phar update\n\n");
    exec('cd '.PRJ_BASE_PATH.' ; php composer.phar update');
    echo("If the folder does not appear vendorServer dependencies run 'php composer.phar update' or 'composer update' and resolves conflicts.\n");

    echo "\n === Composer dependences: Done ===\n\n";
  }

  public function installDependencesManual( $dependences ) {
    echo "\n === Manual dependences ===\n\n";

    $manualPath = Cogumelo::getSetupValue( 'dependences:manualPath' );

    if( !is_dir( $manualPath ) ) {
      if( !mkdir( $manualPath, 0755, true ) ) {
        echo "The destination folder does not exist and have permission to create \n";
      }
    }

    foreach( $dependences as $depKey => $dep ){
      foreach( $dep as $params ) {
        echo "Installing ".$params[0]."\n";
        $manualCmd = 'cp -r '.Cogumelo::getSetupValue( 'dependences:manualRepositoryPath' ).'/'.$params[0].' '.$manualPath.'/';
        exec( $manualCmd );
      }
    }

    echo "\n === Manual dependences: Done ===\n\n";
  }

  //
  //  Includes
  //
  public function loadModuleIncludes( $moduleName ) {
    Cogumelo::load('coreController/ModuleController.php');
    ModuleController::getRealFilePath( $moduleName.'.php', $moduleName );
    //$this->loadCogumeloIncludes();
    $moduleInstance = new $moduleName();
    //$this->addVendorIncludeList( $moduleInstance->dependences );
    $dependences = array_filter( $moduleInstance->dependences,
      function( $dep ) {
        return( !isset( $dep['autoinclude'] ) || $dep['autoinclude'] !== false );
      }
    );
    //error_log( 'DependencesController::loadModuleIncludes : ' . print_r( $dependences, true ) );
    $this->addVendorIncludeList( $dependences );

    $this->addIncludeList( $moduleInstance->includesCommon, $moduleName );
  }

  public function loadModuleDependence( $moduleName, $idDependence, $installer = false ) {
    Cogumelo::load('coreController/ModuleController.php');
    ModuleController::getRealFilePath( $moduleName.'.php', $moduleName );

    $moduleInstance = new $moduleName();

    $dependences = array_filter( $moduleInstance->dependences,
      function( $dep ) use ( $idDependence, $installer ) {
        return( $dep['id'] === $idDependence && ( $installer === false || $dep['installer'] === $installer ) );
      }
    );
    //error_log( 'DependencesController::loadModuleDependence' . print_r( $dependences, true ) );
    $this->addVendorIncludeList( $dependences );
  }

  public function loadAppIncludes() {
    global $_C;
    //$this->loadCogumeloIncludes();
    $this->addVendorIncludeList( $_C->dependences );
    $this->addIncludeList( $_C->includesCommon );
  }

  public function loadCogumeloIncludes() {
    $this->addVendorIncludeList(CogumeloClass::$mainDependences);
  }

  public function addVendorIncludeList( $includes ) {
    if( count( $includes ) > 0) {

      foreach( $includes as $includeElement ) {

        $include_folder = '';

        if( $includeElement['installer'] == 'bower' ) {
          $installer = 'bower';
          $include_folder = $includeElement['id'];
        }
        else if( $includeElement['installer'] == 'yarn' ) {
          $installer = 'yarn';
          $paramYarn = explode('@', $includeElement['params'][0]);
          $include_folder = $paramYarn[0];
        }
        else if( $includeElement['installer'] == 'composer' ) {
          $installer = 'composer';
          $include_folder = $includeElement['params'][0];
        }
        else if( $includeElement['installer'] == 'manual' ) {
          $installer = 'manual';
          $include_folder = $includeElement['params'][0];
        }

        if( isset( $includeElement['includes'] ) && count( $includeElement['includes'] ) > 0 ) {
          foreach( $includeElement['includes'] as $includeFile ) {

            switch ($this->typeIncludeFile( $includeFile )) {
              case 'serverScript':
                //Cogumelo::debug( 'Including vendor:'.WEB_BASE_PATH.'/vendorServer/'.$include_folder.'/'.$includeFile );
                if($installer == 'manual') {
                  require_once( Cogumelo::getSetupValue( 'dependences:manualPath' ).'/'.$include_folder.'/'.$includeFile );
                }
                /*else {
                  require_once( Cogumelo::getSetupValue( 'dependences:composerPath' ).'/'.$include_folder.'/'.$includeFile );
                }*/


                break;
              case 'clientScript':
                $this->addIncludeJS( $include_folder.'/'.$includeFile, 'vendor/'.$installer );
                break;
              case 'styles':
                $this->addIncludeCSS( $include_folder.'/'.$includeFile, 'vendor/'.$installer );
                break;
            }
          }
        }
      }

      $composerAutoloadPath = Cogumelo::getSetupValue( 'dependences:composerPath' ).'/autoload.php';
      if(file_exists($composerAutoloadPath)){
        require_once( $composerAutoloadPath );
      }
    }
  }

  public function addIncludeList( $includes, $module = false ) {

    if( count( $includes ) > 0) {
      foreach( $includes as $includeFile ) {

        switch($this->typeIncludeFile( $includeFile ) ) {
          case 'serverScript':
            if($module == false) {
              Cogumelo::load($includeFile);
            }
            else {
              eval($module.'::load("'. $includeFile .'");');
            }
            break;
          case 'clientScript':
            $this->addIncludeJS( $includeFile, $module );
            break;
          case 'styles':
            $this->addIncludeCSS( $includeFile, $module );
            break;
        }
      }
    }
  }

  public function typeIncludeFile( $includeFile ) {

    $type = false;

    if( $includeFile != '' ) {
      // css or less file
      if( mb_substr($includeFile, -4) == '.css' || mb_substr($includeFile, -5) == '.less') {
        $type = 'styles';
      }
      // javascript file
      else if( mb_substr($includeFile, -3) == '.js' ) {
        $type = 'clientScript';
      }
      // php include
      else if( mb_substr($includeFile, -4) == '.php' || mb_substr($includeFile, -4) == '.inc')  {
        $type = 'serverScript';
      }
    }

    return $type;
  }

  public function addIncludeCSS( $includeFile, $module = false ) {
    global $cogumeloIncludesCSS;

    if( !isset( $cogumeloIncludesCSS ) ) {
      $cogumeloIncludesCSS = array();
    }

    if( !$this->isInIncludesArray($includeFile, $cogumeloIncludesCSS, $module) ) {
      array_push($cogumeloIncludesCSS, array('src'=>$includeFile, 'module'=>$module ) );
    }
  }

  public function addIncludeJS( $includeFile, $module = false ) {
    global $cogumeloIncludesJS;

    if( !isset( $cogumeloIncludesJS ) ) {
      $cogumeloIncludesJS = array();
    }

    if( !$this->isInIncludesArray($includeFile, $cogumeloIncludesJS, $module) ) {
      array_push($cogumeloIncludesJS, array('src'=>$includeFile, 'module'=>$module ) );
    }
  }

  public function isInIncludesArray( $file, $includesArray, $module ) {
    $ret = false;
    if( count( $includesArray ) > 0 ) {
      foreach( $includesArray as $includedFile ) {
        if($includedFile['src'] == $file  && $includedFile['module'] == $module  ) {
          $ret = true;
        }
      }
    }
    return $ret;
  }

}
