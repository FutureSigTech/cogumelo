<?php
/**
 * CacheRedis Class
 *
 * This class encapsulates the Redis library
 *
 * @author: jmpmato, pablinhob
 */
class CacheRedis {

  private $cacheCtrl = null;
  private $cacheSetup = false;
  private $keyPrefix = 'CGMLPHPCACHE';
  private $expirationTime = 0;


  public function __construct( $setup ) {
    $status = false;

    $this->cacheSetup = $setup;

    if( !empty( $this->cacheSetup['host'] ) && class_exists('Redis') ) {
      $status = $this->prepareCotroller();
    }

    if( $status ) {
      $this->prepareVars();
    }
    else {
      unset( $this->cacheCtrl );
      $this->cacheCtrl = null;
    }
  }

  private function prepareCotroller() {
    $status = false;

    if( empty( $this->cacheSetup['port'] ) ) {
      $this->cacheSetup['port'] = 6379; // port 6379 by default - same connection like before.
    }

    $this->cacheCtrl = new Redis();
    $status = $this->cacheCtrl->pconnect( $this->cacheSetup['host'], $this->cacheSetup['port'] );

    if( $status && !empty( $this->cacheSetup['database'] ) ) {
      $status = $this->cacheCtrl->select( $this->cacheSetup['database'] );  // switch to DB n
    }

    if( $status && !empty( $this->cacheSetup['auth'] ) ) {
      $status = $this->cacheCtrl->auth( $this->cacheSetup['auth'] );
    }

    return $status;
  }

  private function prepareVars() {
    if( !empty( $this->cacheSetup['subPrefix'] ) ) {
      $this->keyPrefix .= '_'.$this->cacheSetup['subPrefix'];
    }
    elseif( $prjIdName=Cogumelo::getSetupValue('project:idName') ) {
      $this->keyPrefix .= '_'.$prjIdName;
    }
    elseif( $dbName=Cogumelo::getSetupValue('db:name') ) {
      $this->keyPrefix .= '_'.$dbName;
    }

    if( isset( $this->cacheSetup['expirationTime'] ) ) {
      $this->expirationTime = intval( $this->cacheSetup['expirationTime'] );
    }
  }


  public function __toString() {
    return json_encode($this->getInfo());
  }


  public function getInfo() {
    return([
      'type'=>'Redis',
      'status'=>$this->isValid(),
      'keyPrefix'=>$this->keyPrefix,
      'defExpirationTime'=>$this->expirationTime,
      'cacheSetup'=>$this->cacheSetup,
    ]);
  }


  public function isValid() {
    return( is_object( $this->cacheCtrl ) );
  }


  /**
   * Recupera un contenido
   *
   * @param string $key Identifies the data to be saved
   * @param mixed $data Content to save
   * @param mixed $expirationTime Expiration time. (default or fail: use setup value)
   */
  public function setCache( $key, $data, $expirationTime = false ) {
    $result = null;

    if( $this->cacheCtrl ) {
      $key = $this->keyPrefix .':'. $key;

      if( empty( $expirationTime ) || !is_numeric( $expirationTime ) ) {
        $expirationTime = $this->expirationTime;
      }
      else {
        $expirationTime = intval( $expirationTime );
      }

      Cogumelo::log( __METHOD__.' - key: '.$key.' exp: '.$expirationTime, 'cache' );

      if( $expirationTime !== 0 && $this->cacheCtrl->setEx( $key, $expirationTime, serialize( $data ) ) ) {
        $result = true;
      }
    }

    return $result;
  }


  /**
   * Recupera un contenido
   *
   * @param string $key Identifies the requested data
   */
  public function getCache( $key ) {
    $result = null;

    if( $this->cacheCtrl ) {
      $key = $this->keyPrefix .':'. $key;

      $result = $this->cacheCtrl->get( $key );
      if( $result === false ) {
        $result = null;
        Cogumelo::log( __METHOD__.' - key: '.$key.' FAIL!!!', 'cache' );
      }
      else {
        $result = unserialize( $result );
        Cogumelo::log( __METHOD__.' - key: '.$key.' Atopado :)', 'cache' );
      }
    }

    return $result;
  }


  /**
   * Borra nuestros contenidos cache
   */
  public function flush() {
    Cogumelo::log(__METHOD__, 'cache');
    $result = null;

    if( $this->cacheCtrl ) {
      $cacheKeys = $this->cacheCtrl->keys( $this->keyPrefix .':*' );
      Cogumelo::log(__METHOD__.' - cacheKeys: '.json_encode( $cacheKeys ), 'cache');
      if( $this->cacheCtrl->del( $cacheKeys ) ) {
        $result = true;
      }
    }

    return $result;
  }
}
