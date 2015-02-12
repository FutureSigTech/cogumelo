<?php

Cogumelo::load('coreModel/VO.php');
Cogumelo::load('coreModel/Facade.php');


/**
 * Merge of VO and Data controller concepts
 *
 * @package Cogumelo Model
 */
Class Model extends VO {

  var $dataFacade;


  var $customFacade = false;
  var $customDAO = false;
  var $moduleDAO = false;
  var $filters = array();


  function __construct( $datarray= array(), $otherRelObj = false ) {
    $this->setData( $datarray, $otherRelObj );

    if($this->customFacade) {
      $this->dataFacade = new $customFacade();
    }
    if( $this->customDAO ) {
      $this->dataFacade = new Facade( false,  $this->customDAO, $this->moduleDAO);
    }
    else {
      $this->dataFacade = new Facade( $this );
    }

  }



  /**
  * List items from table
  *
  * @param array $parameters array of filters
  *
  * @return array VO array
  */
  function listItems( array $parameters = array() )
  {

    $p = array(
        'filters' => false,
        'range' => false,
        'order' => false,
        'fields' => false,
        'affectsDependences' => false,
        'cache' => false
      );
    $parameters =  array_merge($p, $parameters );

    Cogumelo::debug( 'Called listItems on '.get_called_class() );
    $data = $this->dataFacade->listItems(
                                          $parameters['filters'],
                                          $parameters['range'],
                                          $parameters['order'],
                                          $parameters['fields'],
                                          $parameters['affectsDependences'],
                                          $parameters['cache']
                                        );

    return $data;
  }


  /**
  * Count items from table
  *
  * @param array $parameters array of filters
  *
  * @return array VO array
  */
  function listCount( array $parameters= array() )
  {

    $p = array(
        'filters' => false,
        'cache' => false
      );
    $parameters =  array_merge($p, $parameters );

    Cogumelo::debug( 'Called listCount on '.get_called_class() );
    $data = $this->dataFacade->listCount( $parameters['filters']);

    return $data;
  }

  function getFilters(){
    $filters = array();
    // add automatic filters for (INT) and (CHAR or VARCHAR) values
    /*foreach( $this::$cols as $colK => $col ) {
      if( $col['type'] == 'INT') {
        $filters[ $colK ] = $colK." = ?";
      }
      else
      if( $col['type'] == 'CHAR' || $col['type'] == 'VARCHAR' ) {
        $filters[ $colK ] = $colK." = '?'";
      }
    }*/

    // then merge with other filters and return 
    return array_merge( $filters, $this->filters);
  }




  /**
  * save item
  *
  * @param array $parameters array of filters
  *
  * @return object  VO
  */
  function save(  array $parameters= array() )
  {

    $p = array(
        'affectsDependences' => false
      );
    $parameters =  array_merge($p, $parameters );


    // Save all dependences
    if($parameters['affectsDependences']) {
      $depsInOrder = $this->getDepInLinearArray();

      while( $selectDep = array_pop($depsInOrder) ) {

          Cogumelo::debug( 'Called save on '.get_called_class(). ' with "'.$selectDep['ref']->getFirstPrimarykeyId().'" = '. $this->getter( $selectDep['ref']->getFirstPrimarykeyId() ) );
          return $this->dataFacade->Update( $selectDep['ref'] );
      }
    }
    // Save only this Model
    else {
      Cogumelo::debug( 'Called save on '.get_called_class(). ' with "'.$this->getFirstPrimarykeyId().'" = '. $this->getter( $this->getFirstPrimarykeyId() ) );
      return $this->dataFacade->Update($this);
    }

  }

  /**
  * save item
  *
  * @param object $voObj voObject
  *
  * @return object  VO
  */
  private function saveOrUpdate( $voObj = false ){
    $retObj = false; 

    if(!$voObj) {
      $voObj = $this;
    }

    if( $this->exist($voObj) ) {
      $retObj = $this->dataFacade->Update( $voObj );
    }
    else {

    }

    return $retObj;
  }

  /**
  * if VO exist
  *
  * @param object $voObj voObject
  *
  * @return boolean
  */
  function exist($voObj = false) {
    $ret = false;

    if(!$voObj) {
      $voObj = $this;
    }
    
    if($filters = $voObj->data) {

      if( $this->listCount( array('filters'=>$filters) ) ) {
        $ret = true;
      }
    }

    return $ret;
  }


  /**
  * delete item
  *
  * @param array $parameters array of filters
  *
  * @return object  VO
  */
  function delete( array $parameters = array() ) {

    $p = array(
        'affectsDependences' => false
      );
    $parameters =  array_merge($p, $parameters );


    // Delete all dependences
    if($parameters['affectsDependences']) {
      $depsInOrder = $this->getDepInLinearArray();

      while( $selectDep = array_pop($depsInOrder) ) {
          Cogumelo::debug( 'Called delete on '.get_called_class().' with "'.$selectDep['ref']->getFirstPrimarykeyId().'" = '. $selectDep['ref']->getter( $selectDep['ref']->getFirstPrimarykeyId() ) );
          $selectDep['ref']->dataFacade->deleteFromKey( $selectDep['ref']->getFirstPrimarykeyId(), $selectDep['ref']->getter( $selectDep['ref']->getFirstPrimarykeyId() )  );
      }
    }
    // Delete only this Model
    else {
      Cogumelo::debug( 'Called delete on '.get_called_class().' with "'.$this->getFirstPrimarykeyId().'" = '. $this->getter( $this->getFirstPrimarykeyId() ) );
      $this->dataFacade->deleteFromKey( $this->getFirstPrimarykeyId(), $this->getter( $this->getFirstPrimarykeyId() )  );
    }

    return true;
  }


}