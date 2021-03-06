<?php

global $COGUMELO_RELATIONSHIP_MODEL;
$COGUMELO_RELATIONSHIP_MODEL = array();

/**
* Utils for VO objects and relationship
*
* @package Cogumelo Model
*/
class VOUtils {


  /**
  * List and include all Models and VOs from project
  *
  * @return array
  */
  public static function listVOs() {

    // VOs into APP
    $voarray = self::listVOsByModule('app');

    global $C_ENABLED_MODULES;
    foreach( $C_ENABLED_MODULES as $modulename ) {
      // modules into APP
      $voarray = array_merge( $voarray, self::listVOsByModule($modulename) );
    }

    return $voarray;
  }

  public static function listVOsByModule( $modulename ) {
    $voarray = [];

    if( $modulename === 'app') {
      $voarray = self::mergeVOs( [], APP_BASE_PATH.'/classes/model/' ); // scan app model dir
    }
    else {
      $voarray = self::mergeVOs($voarray, APP_BASE_PATH.'/modules/'.$modulename.'/classes/model/', $modulename );

      if( defined('COGUMELO_DIST_LOCATION') && COGUMELO_DIST_LOCATION !== false ) {
        // modules into DIST
        $voarray = self::mergeVOs($voarray, COGUMELO_DIST_LOCATION.'/distModules/'.$modulename.'/classes/model/', $modulename );
      }

      // modules into COGUMELO
      $voarray = self::mergeVOs($voarray, COGUMELO_LOCATION.'/coreModules/'.$modulename.'/classes/model/', $modulename );
    }

    return $voarray;
  }


  /**
  * Alias for listVOs method
  *
  * @return array
  */
  public static function includeVOs() {
    return self::listVOs();
  }


  /**
  * Merge into original array the new (VOs or Models) that find the directory passed and returns it merged
  *
  * @param array $voarray original array
  * @param string $dir path to search new Models or VOs to merge with original array
  * @param string $modulename name of module to search (default is the appplication)
  *
  * @return array
  */
  public static function mergeVOs( $voarray, $dir, $modulename = 'app' ) {
    $vos = array();

    // VO's from APP
    if ( is_dir($dir) && $handle = opendir( $dir )) {

      while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != "..") {

          if(mb_substr($file, -9) == 'Model.php' || mb_substr($file, -6) == 'VO.php'){
            $classVoName = mb_substr($file, 0,-4);

            // prevent reload an existing vo in other place
            if (!array_key_exists( $classVoName, $voarray )) {
              require_once($dir.$file);
              $vos[ $classVoName ] = array('path' => $dir, 'module' => $modulename );
            }
          }
        }
      }
      closedir($handle);
    }

    return array_merge( $voarray , $vos );
  }



  /**
  * Get VO or Model Cols
  *
  * @param string $voName
  *
  * @return array
  */
  public static function getVOCols( $voName ) {
    $retCols = array();

    $vo = new $voName();

    foreach( $vo->getCols(true) as $colK => $col ) {
      $retCols[] = $colK;
    }

    return $retCols;
  }



  /**
  * Get basic VO or Model relationship with other VOs or Models
  *
  * @param object $VOInstance
  * @param boolean $includeKeys
  *
  * @return array
  */
  public static function getVOConnections( $VOInstance, $includeKeys = false ) {
    $relationships = array();

    $cols = $VOInstance->getCols(true);
    if( count( $cols ) > 0 ) {
      foreach( $cols as $attrKey => $attr ) {
        if( array_key_exists( 'type', $attr ) && $attr['type'] == 'FOREIGN' ){
          if( !$includeKeys ) {
            $relationships[] =  $attr['vo'];
          }
          else {
            $relationships[] =  array( 'vo' => $attr['vo'] , 'key' => $attrKey, 'related'=>$attr['key'] );
          }
        }
      }
    }

    return $relationships;
  }



  /**
  * Get relationship scheme from all VOs and Models
  *
  * @return array
  */
  public static function getAllRelScheme() {
    $ret = array();

    foreach( self::listVOs() as $voName => $voDef ) {
      $vo = new $voName();
      $ret[ $voName ] = array(
        'name' => $voName,
        'relationship' => self::getVOConnections( $vo ),
        'extendedRelationship' => self::getVOConnections( $vo, true ),
        'elements' => count( $vo->getCols(true) ),
        'module' => $voDef['module']
      );
    }

    return $ret;
  }



  /**
  * Get the IDs in use of a model in all models
  *
  * @return array
  */
  public static function getIdsInUse( $modelRelName ) {
    // echo "\n getIdsInUse( $modelRelName )\n\n";
    $modelRelIds = array();

    // Usado en Filedata

    $voNames = array_keys( self::listVOs() );
    foreach( $voNames as $voName ) {
      $vo = new $voName();
      $extendedRelationship = self::getVOConnections( $vo, true );
      if( is_array( $extendedRelationship ) ) {
        // Buscamos relaciones de este modelo con el modelo indicado y anotamos los campos.
        $depModelKeys = array();
        foreach( $extendedRelationship as $relationship ) {
          if( $relationship['vo'] === $modelRelName ) {
            $depModelKeys[] = $relationship['key'];
          }
        }
        // Si hay campos que relacionen los dos modelos, recuperamos los IDs en uso en este.
        if( count( $depModelKeys ) > 0 ) {
          $listModel = $vo->listItems( array( 'fields' => $depModelKeys ) );
          while( $objElem = $listModel->fetch() ) {
            foreach( $depModelKeys as $fieldName ) {
              $fieldValue = $objElem->getter( $fieldName );
              if( $fieldValue && is_numeric( $fieldValue ) ) {
                $modelRelIds[ $fieldValue ] = true;
              }
            }
          }
        }
      }
    }
    $modelRelIds = array_keys( $modelRelIds ); // Nos quedamos con los keys que son los IDs

    return( count($modelRelIds) ? $modelRelIds : false );
  }



  /**
  * Get relationship scheme from VO or Models, resolving son VOs and Models
  *
  * @param string $voName Name of VO or Model
  * @param array $parentInfo parent VO info
  *
  * @return array
  */
  public static function getVORelationship( $voName, $parentInfo = array( 'parentVO' => false, 'parentTable'=>false, 'parentId'=>false, 'relatedWithId'=>false, 'preventCircle'=>array() ), $deep = 1 ) {

    $vo = new $voName();
    $relArray = array(
      'vo' => $voName,
      'table' => $vo::$tableName
    );
    $relArray = array_merge( $relArray, $parentInfo);

    $relArray['cols'] = self::getVOCols( $voName );
    $relArray['relationship'] = array();


    if( $deep < 4 ) {
      $allVOsRel = self::getAllRelScheme();


      if( count( $allVOsRel ) > 0) {
        foreach( $allVOsRel as $roRel ) {
          if(
            (
              in_array( $roRel['name'], $allVOsRel[$voName]['relationship']) ||   // relation from this to other VO
              in_array( $voName, $roRel['relationship'] )                         // relation fron other to this VO
            ) &&
            ( !in_array( $roRel['name'], $parentInfo['preventCircle']) )
          ) {
            // prevent circle relationships array
            if( count( $parentInfo['preventCircle'] ) === 0 ) {
              $preventCircle = array($voName);
            }
            else {
              array_push( $parentInfo['preventCircle'], $voName);
              $preventCircle = $parentInfo['preventCircle'];
            }


            $sonParentArray = array(
              'parentVO' => $voName,
              'parentTable'=> $vo::$tableName,
              'parentId'=> 'NO',
              'relatedWithId'=> 'NO',
              'preventCircle' => $preventCircle
            );

            $relArray['relationship'] = array_merge( $relArray['relationship'],
              self::findRelFromIn( $allVOsRel[$voName]['extendedRelationship'],
                $roRel['name'], $voName, $sonParentArray, $deep++ ) );
            $relArray['relationship'] = array_merge( $relArray['relationship'],
              self::findRelFromOut( $roRel['extendedRelationship'], $voName, $roRel['name'],
                $sonParentArray, $deep++ ) );
          }
        }
      }
    }

    return $relArray;
  }


  public static function findRelFromOut( $rel, $voSearch, $voToRegister, $sonParentArray, $deep ) {
    $retArray = array();
    foreach( $rel as $r ) {
      if( $r['vo'] == $voSearch ) {
        $sonParentArray['parentId'] = $r['related'];
        $sonParentArray['relatedWithId'] = $r['key'];
        $retArray[ $r['related'].'.'.$voToRegister ] = self::getVORelationship( $voToRegister, $sonParentArray, $deep );
      }
    }

    return $retArray;
  }


  public static function findRelFromIn( $rel, $voSearch, $voToRegister, $sonParentArray, $deep ) {
    $retArray = array();
    foreach( $rel as $r ) {
      if( $r['vo'] == $voSearch ) {
        $sonParentArray['parentId'] = $r['key'];
        $sonParentArray['relatedWithId'] = $r['related'];
        $retArray[ $r['key'].'.'.$voSearch ] = self::getVORelationship( $voSearch, $sonParentArray, $deep );
      }
    }

    return $retArray;
  }



  /**
  * Generate index for rel Object
  *
  * @param object $voRel
  *
  * @return array
  */
  public static function relIndex( $voRel, $parentArrayKey = false, $relsArray = array() ) {


    $currentArrayKey = count($relsArray);
    $relsArray[] = array( 'voName' => $voRel['vo'], 'parentKey' => $parentArrayKey );


    if( array_key_exists('relationship', $voRel) && count( $voRel['relationship'] ) > 0  ) {
      foreach( $voRel['relationship'] as $relVO ){
          $relsArray = self::relIndex( $relVO, $currentArrayKey, $relsArray );
      }
    }

    return $relsArray;
  }


  /**
  * Generate temporal json files with relationship descriptions
  *
  * @return void
  */
  public static function createModelRelTreeFiles() {
    $dbEngine = Cogumelo::getSetupValue( 'db:engine' );

    if( !empty( $dbEngine ) ) {
      Cogumelo::load('coreModel/'.$dbEngine.'/'.ucfirst( $dbEngine ).'DAORelationship.php');

      eval( '$mrel = new '.ucfirst( $dbEngine ).'DAORelationship();' );

      $setupDir = Cogumelo::getSetupValue('cogumelo:modelRelationshipPath');
      $dirRelationship = empty( $setupDir ) ? APP_TMP_PATH.'/modelRelationship' : $setupDir;

      if( !is_dir( $dirRelationship ) ) {
        if( !mkdir( $dirRelationship, 0750, true ) ) {
          echo 'ERROR: Imposible crear el directorio: '.$dirRelationship."\n";
        }
      }

      foreach( self::listVOs() as $voName => $vo ) {
        $relVO = self::getVORelationship( $voName );
        //var_dump($relVO);
        $relVO['index'] = self::relIndex( $relVO );
        file_put_contents( $dirRelationship.'/'.$voName.'.json', json_encode( $relVO ) );
      }
    }
  }



  /**
  * Get relationship keys from VO or Model name
  *
  * @param string $nameVO name of VO or Model
  *
  * @return array
  */
  public static function getRelkeys( $nameVO, $tableAsKey = false, $resolveDependences = false ) {
    return self::getRelKeysByRelObj( self::getRelObj( $nameVO, $resolveDependences ), $tableAsKey );
  }



  /**
  * Get relationship keys from relationship object
  *
  * @param object $voRel relationship object (readed from temporal json files)
  * @param boolean tableAsKey table name as array key when true, else return VO name as keys
  *
  * @return array
  */
  public static function getRelKeysByRelObj( $voRel, $tableAsKey = false ) {
    $relKeys = false;

    if($voRel) {
      $relKeys = array();

      $relationship = (array) $voRel->relationship;
      if( count( $relationship ) > 0 ) {
        foreach( $relationship as $voName => $rel ) {
          if( $tableAsKey ){
            $relKeys[$rel->parentId."_".$rel->table] =  $rel->vo ;
          }
          else {
            $relKeys[$rel->parentId."_".$rel->table] = $rel->parentId."_".$rel->table."_serialized.".$rel->table." AS ".$rel->parentId."_".$rel->table;
            //$relKeys[$rel->vo] = $rel->table."_serialized.".$rel->table;
          }
        }
      }
    }

    return $relKeys;
  }



  /**
  * Gets Relationship object from global array. If not exist this global array reads it from temporal .json
  *
  * @param string $nameVO VO or Model name
  *
  * @return object
  */
  public static function getRelObj( $nameVO, $resolveDependences = true ) {


    ini_set('memory_limit','1024M'); // SET BIT LIMIT WHEN GENERATING MODEL
    global $COGUMELO_RELATIONSHIP_MODEL;

    $ret = false;


    if( isset($COGUMELO_RELATIONSHIP_MODEL[ $nameVO ] )) {
      $ret = clone $COGUMELO_RELATIONSHIP_MODEL[ $nameVO ];
    }
    else {
      $setupDir = Cogumelo::getSetupValue('cogumelo:modelRelationshipPath');
      $dirRelationship = empty( $setupDir ) ? APP_TMP_PATH.'/modelRelationship' : $setupDir;

      if( file_exists( $dirRelationship.'/'.$nameVO.'.json' ) ) {
        $COGUMELO_RELATIONSHIP_MODEL[ $nameVO ] = json_decode(
          file_get_contents( $dirRelationship.'/'.$nameVO.'.json' )
        );
        $ret = clone $COGUMELO_RELATIONSHIP_MODEL[ $nameVO ];
      }
    }

    return self::limitRelObj( $ret, $resolveDependences );
  }


  /**
  *  Limit the relObj acording list of VO names
  *
  * @param object $relObj
  * @param mixed $resolveDependences
  *
  * @return object
  */
  public static function limitRelObj( $relObj, $resolveDependences ) {



    $currentRel = empty($relObj->relationship) ? [] : (array) $relObj->relationship;

    if( is_array( $resolveDependences ) && count( $currentRel ) > 0 ) {

      $relationshipArray = array();
      foreach( $currentRel as $rok => $ro ) {
        if( in_array( preg_replace('/(.*)\./' ,'', $rok ), $resolveDependences ) ) {
          $relationshipArray[$rok] = self::limitRelObj( $ro, $resolveDependences );
        }
      }

      $relObj->relationship = $relationshipArray;
    }

    return $relObj;
  }





  public static function completeRelObject( $originalRelArray, $newRelArray, $listToResolve ) {
    $relList = $newRelArray;

    $voName = array_shift( $listToResolve );

    foreach( $originalRelArray as $rel ) {
      if( $rel->vo == $voName ) {
        // mirar se está dentro de reList para non machacalo
        $nRelationship = array();
        if( count( $relList) > 0 ){
          foreach( $relList as $rel2 ) {
            if( $rel2->vo == $voName ) {
              $nRelationship[] = $rel2;
            }
          }
        }

        $relList = array_merge($relList, self::completeRelObject($rel, $nRelationship  , $listToResolve)  );
      }
    }

    return $relList;
  }



  /**
  * Get all voNames of index array from the selected element folowing indexes
  *
  * @param array $retIndex index list
  * @param mixed $ref voName or index parentkey
  *
  * @return array
  */
  public static function limitRelIndex( $relIndex, $ref ) {
    $retArray = array();

    if( count( $relIndex ) > 0 ) {
      while( $rel = array_pop($relIndex) ) {

        // is an VO
        if( is_string( $ref ) && $ref == $rel->voName ) {
          $retArray[] = array_merge( self::limitRelIndex( $relIndex, $rel->parentKey ), array($rel->voName) );
        }
        // is a parent key
        else
        if( is_numeric( $ref ) && (count( $relIndex )) == $ref && $ref != 0) {
          $retArray = array_merge( self::limitRelIndex( $relIndex, $rel->parentKey ), array($rel->voName) );
        }

      }
    }

    return $retArray;
  }


  /**
  * Look if exist VO or Model name into relationship object
  *
  * @param string $voName VO or Model name
  * @param object $relObj relationship Object (readed from tmp .json relationship file)
  *
  * @return object
  */
  public static function searchVOinRelObj( $voName, $dataKey, $relObj ) {
    $relObjSon = -1;

    $relationship = empty($relObj->relationship) ? [] : (array) $relObj->relationship;

    if( count( $relationship ) > 0 ) {
      foreach( $relationship as $candidate ) {
        if( $candidate->vo == $voName ){
          if( $candidate->parentId.'_'.$candidate->table == $dataKey ) {
            $relObjSon = $candidate;
            break;
          }
        }
      }
    }

    return $relObjSon;
  }

}
