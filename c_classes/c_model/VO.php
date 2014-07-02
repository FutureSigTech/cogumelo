<?php


Class VO
{

  var $attributes = array();


  function __construct(array $datarray){

    // Common developer errors
    if(!isset($this::$tableName)){
      Cogumelo::error('all VO Must have declared an $this::$tableName');
      return false;
    }
    if(!isset($this::$cols)){
      Cogumelo::error($this::$tableName.'VO Must have an self::$cols array (See Cogumelo documentation)');
      return false;
    }
    if(!$this->getFirstPrimarykeyId()){
      Cogumelo::error($this::$tableName.'VO Must be declared at least one primary key in $this::$cools array (See Cogumelo documentation)');
      return false;
    }

    $this->setVarlist($datarray);
  }

  // set variable list (initializes entity)
  function setVarlist(array $datarray) 
  {
    // rest of variables
    foreach($datarray as $datakey=>$data) {
        $this->setter($datakey, $data, true);
    }
  }
  

  function getFirstPrimarykeyId() {

    foreach($this::$cols as $cid => $col) {
      if(array_key_exists('primarykey', $this::$cols[$cid])){
        if($this::$cols[$cid]['primarykey'] == true){
          return $cid;
        }
      }
    }

    return false;
  }
  
  function getTableName() {
    return $this->tableName;
  }

  // set a attribute. If exist a manual method use it
  function setter($setterkey, $value = false, $ignore_nonexistent_key = false)
  {
    if( in_array($setterkey, array_keys($this::$cols)) ){
      $this->attributes[$setterkey] = $value;
    }
    else{
      if(!$ignore_nonexistent_key)
        Cogumelo::error("key '". $setterkey ."' doesn't exist in VO::". $this::$tableName);
    }
  }

  // get a attribute. If exist a manual method use it
  function getter($setterkey)
  {
    if(array_key_exists( $setterkey, $this->attributes))
      return $this->attributes[$setterkey];
    else
      return null;
  }

  function keysToString($resolveDependences = false) {

    $keys = false;

    if( $resolveDependences ) {

    }
    else {
      $comma = ' ';
      foreach( array_keys($this->cols) as $k ) {
        $keys .= $comma.$k;
        $comma = ', ';
      }
    }

    return $keys;
    
  }

  function toString(){
    $str = "\n " . $keyId. ': ' .$this->getter($keyId);
    foreach(array_keys($this->cools) as $k) {
      $str .= "\n " . $this->cools[$k] . ': ' .$this->getter($k);
    }

    return $str;
  }




}



