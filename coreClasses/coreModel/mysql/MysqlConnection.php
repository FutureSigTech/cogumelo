<?php


Cogumelo::load('coreModel/Connection.php');


/**
 * Mysql connection class
 *
 * @package Cogumelo Model
 */
class MysqlConnection extends Connection {
  var $db = false;
  var $stmt = false;

  var $DB_HOST='127.0.0.1';
  var $DB_PORT=3306;
  var $DB_USER;
  var $DB_PASSWORD;
  var $DB_NAME;


  /**
   * Fetch just one result
   *
   * @param array $dbDevelAuth in case of cogumelo Script process
   *
   * @return object
   */
  public function __construct( $dbDevelAuth = false ) {
    $confDB = $this->getDBConfiguration();

    $this->DB_HOST = $confDB['hostname'];
    $this->DB_PORT = $confDB['port'];
    $this->DB_USER = $confDB['user'];
    $this->DB_PASSWORD = $confDB['password'];
    $this->DB_NAME = $confDB['name'];

    if( !empty($dbDevelAuth) ) {
      $this->DB_USER = $dbDevelAuth['DB_USER'];
      $this->DB_PASSWORD = $dbDevelAuth['DB_PASSWORD'];
      $this->DB_NAME = $dbDevelAuth['DB_NAME'];
    }

    $this->connect();
  }

  private function getDBConfiguration() {
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
   * Connect Mysql: Only when dbinstance doesn't exist
   *
   * @return void
   */
  public function connect() {

    if( empty( $this->db ) ) {
      $this->db = new mysqli( $this->DB_HOST, $this->DB_USER, $this->DB_PASSWORD, $this->DB_NAME, $this->DB_PORT );

      if( $this->db->connect_error ) {
        Cogumelo::debug( mysqli_connect_error() );
        // die('ERROR conectando coa BBDD');
      }
      else {
        Cogumelo::debug( "MYSQLI: Connection Stablished to ".$this->DB_HOST );
        $this->db->set_charset("utf8");
      }
    }
  }


  /**
   * Start transaction
   *
   * @return void
   */
  public function transactionStart() {
    Cogumelo::debug("DB TRANSACTION START");
    mysqli_query($this->db ,"START TRANSACTION;");
    mysqli_query($this->db ,"BEGIN;");
  }


  /**
   * Commit transaction
   *
   * @return void
   */
  public function transactionCommit() {
    Cogumelo::debug("DB TRANSACTION COMMIT");
    mysqli_query($this->db ,"COMMIT;");
  }


  /**
   * Rollback transaction
   *
   * @return void
   */
  public function transactionRollback() {
    Cogumelo::debug("DB TRANSACTION ROLLBACK");
    mysqli_query($this->db ,"ROLLBACK;");
  }
}
