<?php

Cogumelo::load('coreModel/mysql/MysqlDAO.php');

//
//  Mysql MysqlDevelDBDAO DAO
//

class MysqlDevelDBDAO extends MysqlDAO
{

  static $conversion_types = array(
    'INT' => 'INT',
    'BIGINT' => 'BIGINT',
    'FLOAT' => 'FLOAT',
    'DATETIME' => 'DATETIME',
    'TIMESTAMP' => 'TIMESTAMP',
    'BOOLEAN' => 'BIT',
    'CHAR' => 'CHAR',
    'VARCHAR' => 'VARCHAR',
    'TEXT' => 'TEXT',
    'LONGTEXT' => 'LONGTEXT',

    // GIS DATA
    'GEOMETRY' => 'GEOMETRY',
    'POINT' => 'POINT',
    'LINESTRING' => 'LINESTRING',
    'POLYGON' => 'POLYGON',
    'MULTIPOINT' => 'MULTIPOINT',
    'MULTILINESTRING' => 'MULTILINESTRING',
    'MULTIPOLYGON' => 'MULTIPOLYGON',
    'GEOMETRYCOLLECTION' => 'GEOMETRYCOLLECTION'
  );

  function createSchemaDB($connection){

    $resultado =  array();

    $strSQL0 = "DROP DATABASE IF EXISTS ". DB_NAME ;
    $strSQL1 = "CREATE DATABASE ". DB_NAME ;
    $strSQL2 = "GRANT ".
            "SELECT, ".
            "INSERT, ".
            "UPDATE, ".
            "DELETE, ".
            "INDEX, ".
            "LOCK TABLES, ".
            "CREATE VIEW, ".
            "CREATE, ".
            "DROP, ".
            "SHOW VIEW ".
          "ON ". DB_NAME .".* ".
          "TO '". DB_USER ."'@'localhost' IDENTIFIED BY '". DB_PASSWORD ."' ";

    $resultado[] = $this->execSQL($connection, $strSQL0, array() );
    $resultado[] = $this->execSQL($connection, $strSQL1, array() );
    $resultado[] = $this->execSQL($connection, $strSQL2, array() );

    return $resultado;
  }



  function dropTable($connection, $vo_name) {
    $this->execSQL($connection, $this->getDropSQL($connection, $vo_name) , array() );
  }

  function createTable($connection, $vo_name) {
    $strSQL = $this->getTableSQL($connection, $vo_name);
    $this->execSQL($connection, $strSQL, array() );
  }
  function insertTableValues($connection, $vo_name){
    $res = $this->getInsertTableSQL($connection, $vo_name);
    if(!empty($res)) {
      foreach ($res as $resKey => $resValue) {
        $this->execSQL($connection, $resValue['strSQL'], $resValue['valuesSQL']);
      }
    }
  }


  // Sql generation methods

  function getDropSQL($connection, $vo_name, $vo_route = false ) {
    $vo= new $vo_name();

    $strSQL = $this->getTableSQL($connection, $vo_name, $vo_route);
    return "DROP TABLE IF EXISTS  ".$vo::$tableName.";";

  }

  function getTableSQL($connection, $vo_name, $vo_route = false){
    $VO = new $vo_name();

    $primarykeys = array();
    $uniques = array();
    $lines = array();

    foreach($VO::$cols as $colkey => $col) {

      if( isset( $col['multilang'] ) && $col['multilang'] == true &&  $col['type'] != 'FOREIGN'  ) {

        foreach ( explode(',', LANG_AVAILABLE) as $langKey) {

          $retMLC = $this->multilangCols( $colkey.'_'.$langKey, $col,  $primarykeys, $uniques, $lines );
          $primarykeys = $retMLC['primarykeys'];
          $uniques = $retMLC['uniques'];
          $lines = $retMLC['lines'];

        }
      }
      else {
        $retMLC = $this->multilangCols( $colkey, $col,  $primarykeys, $uniques, $lines );

        $primarykeys = $retMLC['primarykeys'];
        $uniques = $retMLC['uniques'];
        $lines = $retMLC['lines'];
      }

    }



    $uniques_str = (sizeof($uniques)>0)? ', UNIQUE (`'.implode(',',$uniques).'`)' : '';
    $primarykeys_str = (sizeof($primarykeys)>0)? ', PRIMARY KEY  USING BTREE (`'.implode(',',$primarykeys).'`)' : '';
    $strSQL = "CREATE TABLE ".$VO::$tableName." (\n".implode(" ,\n", $lines).' '.$uniques_str.$primarykeys_str.')'." ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Generated by Cogumelo devel, ref:".$vo_route."';";

    return $strSQL;
  }



  function multilangCols( $colkey, $col,  $primarykeys, $uniques, $lines) {

      $extrapkey = "";
      $type = "";
      $size = "";

      // is primary key
      if( array_key_exists('primarykey', $col )){
        $primarykeys[] = $colkey;
      }

      // is autoincrement
      if(  array_key_exists('autoincrement', $col ) ){
        $extrapkey=' NOT NULL auto_increment ';
      }

      // is unique
      if(  array_key_exists('unique', $col ) ){
        $uniques[] = $colkey;
      }


      if( $col['type'] == "FOREIGN" ) { // is a foreign key
        eval( '$foreign_col = '.$col['vo'].'::$cols[\''.$col['key'].'\'];' );
        $type = $this::$conversion_types[$foreign_col['type']].$size;
      }
      else {
        $size = (array_key_exists('size', $col))? '('.$col['size'].') ': '';
        $type = $this::$conversion_types[$col['type']].$size;
      }

      $lines[] = '`'.$colkey.'` '.$type.$extrapkey;



    return array(
        'primarykeys' => $primarykeys,
        'uniques' => $uniques,
        'lines' => $lines
      );
  }




  function getInsertTableSQL($connection, $vo_name, $vo_route = false ) {
    $VO = new $vo_name();
    $primarykey = $VO->getFirstPrimarykeyId();
    $valuesSQL = array();
    $res = array();

    if(isset($VO::$insertValues)){

      foreach ($VO::$insertValues as $insertKey => $insertValue) {
        if(array_key_exists($primarykey, $insertValue)) {
          $insertArrayValues = $insertValue;
          unset($insertArrayValues[$primarykey]);
          $insertStringValues = implode(',', array_keys($insertArrayValues));
          $valuesSQL = array_values($insertArrayValues);
          $infoSQLValues = implode(',', array_values($insertArrayValues));

          $strSQL = "INSERT INTO ".$VO::$tableName." (".$insertStringValues. ") VALUES (".$this->getQuestionMarks($insertArrayValues)."); ";
          $infoSQL = "INSERT INTO ".$VO::$tableName." (".$insertStringValues. ") VALUES (".$infoSQLValues."); ";

          array_push($res, array('strSQL' => $strSQL, 'valuesSQL' => $valuesSQL, 'infoSQL' => $infoSQL ));
        }
        else {
          $valuesSQL = array_values($insertValue);
          $infoSQLValues = implode(',', array_values($insertValue));
          $strSQL = "INSERT INTO ".$VO::$tableName." (".implode(',', array_keys($insertValue)). ") VALUES (".$this->getQuestionMarks($insertValue)."); ";
          $infoSQL = "INSERT INTO ".$VO::$tableName." (".implode(',', array_keys($insertValue)). ") VALUES (".$infoSQLValues."); ";
          array_push($res, array('strSQL' => $strSQL, 'valuesSQL' => $valuesSQL, 'infoSQL' => $infoSQL ));
        }
      }
    }
    return $res;
  }

}