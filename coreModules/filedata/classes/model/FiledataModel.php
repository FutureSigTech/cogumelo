<?php
Cogumelo::load('coreModel/VO.php');
Cogumelo::load('coreModel/Model.php');

class FiledataModel extends Model {
  static $tableName = 'filedata_filedata';

  static $cols = array(
    'id' => array(
      'type' => 'INT',
      'primarykey' => true,
      'autoincrement' => true
    ),
    'name' => array(
      'type' => 'VARCHAR',
      'size' => 250
    ),
    'originalName'=> array(
      'type' => 'VARCHAR',
      'size' => 250
    ),
    'absLocation'=> array(
      'type' => 'VARCHAR',
      'size' => 2000
    ),
    'type'=> array(
      'type' => 'VARCHAR',
      'size' => 250
    ),
    'size'=> array(
      'type' => 'BIGINT'
    ),
    'title' => array(
      'type' => 'VARCHAR',
      'size' => 150,
      'multilang' => true
    ),
    'user' => [
      'type'=>'FOREIGN',
      'vo' => 'UserModel',
      'key'=> 'id'
    ],
    'privateMode' => [
      'type' => 'TINYINT',
      'default' => null
    ],
    'aKey' => [
      'type' => 'VARCHAR',
      'size' => 16,
      'default' => 'z'
    ]
  );


  static $extraFilters = array(
    'notInId' => ' filedata_filedata.id NOT IN (?) ',
    'idIn' => ' filedata_filedata.id IN (?) '
  );


  var $deploySQL = array(
    array(
      'version' => 'filedata#2',
      'sql'=> '
        ALTER TABLE filedata_filedata
        MODIFY COLUMN `type` VARCHAR(250) NULL DEFAULT NULL;
      '
    ),
    array(
      'version' => 'filedata#1.11',
      'sql'=> '
        ALTER TABLE filedata_filedata
        ADD COLUMN aKey VARCHAR(16) DEFAULT "z";
        update filedata_filedata SET aKey = CONCAT(CHAR(FLOOR(97+(RAND()*25))),CHAR(FLOOR(97+(RAND()*25))),
          CHAR(FLOOR(97+(RAND()*25))),CHAR(FLOOR(97+(RAND()*25))),CHAR(FLOOR(97+(RAND()*25))),CHAR(FLOOR(97+(RAND()*25))));
      '
    ),
    array(
      'version' => 'filedata#1.10',
      'sql'=> '
        ALTER TABLE filedata_filedata
        ADD COLUMN user INT DEFAULT NULL,
        ADD COLUMN privateMode TINYINT DEFAULT NULL
      '
    )
  );


  public function __construct( $datarray = array(), $otherRelObj = false ) {
    parent::__construct( $datarray, $otherRelObj );
  }

  public function garbageCollector() {
    Cogumelo::debug( __METHOD__ );

    Cogumelo::load( 'coreModel/VOUtils.php' );

    $idsInUse = VOUtils::getIdsInUse( get_class($this) );

    $listModel = $this->listItems( array( 'filters' => array( 'notInId' => $idsInUse ) ) );
    while( $objElem = $listModel->fetch() ) {
      echo "Borramos obj. id:".$objElem->getter('id')."\n";
      $objElem->delete();
    }
  }


  /**
   * Delete item (This method is a mod from Model::delete)
   *
   * @param array $parameters array of filters
   *
   * @return boolean
   */
  public function delete( array $parameters = array() ) {
    Cogumelo::debug( __METHOD__ );

    // Eliminamos ficheros en disco
    filedata::load('controller/FiledataController.php');
    $filedataCtrl = new FiledataController();
    $filedataCtrl->removeServerFiles( $this );

    // Eliminamos el objeto con el delete original
    Cogumelo::debug( __METHOD__.' - Called custom delete on '.get_called_class().' with "'.
      $this->getFirstPrimarykeyId().'" = '. $this->getter( $this->getFirstPrimarykeyId() ) );
    $result = $this->dataFacade->deleteFromKey( $this->getFirstPrimarykeyId(), $this->getter( $this->getFirstPrimarykeyId() ) );

    return( $result === true );
  }
}
