<?php

Cogumelo::load('c_view/View');
devel::load('controller/LogReaderController');
devel::load('controller/DevelDBController');


class DevelView extends View
{

  function __construct($base_dir){
    parent::__construct($base_dir);
  }

  function accessCheck() {
    global $DEVEL_ALLOWED_HOSTS;
    if(!in_array($_SERVER["REMOTE_ADDR"], $DEVEL_ALLOWED_HOSTS)){
      Cogumelo::error("Must be developer machine to enter on this site");
      RequestController::redirect(SITE_URL_CURRENT.'');
    }
    else{
      if ($_SERVER['PHP_AUTH_PW']!= DEVEL_PASSWORD) {
          header('WWW-Authenticate: Basic realm="Cogumelo Devel Confirm"');
          header('HTTP/1.0 401 Unauthorized');
          echo 'Acceso Denegado.';
          exit;
      } else {
          return true;
      }
    }
  }

  function main($url_path=''){
    $this->template->setTpl("develpage.tpl", "devel");
    $this->template->addJs('js/devel.js', 'devel');
    $this->template->addCss('css/devel.css', 'devel');
    $this->logs();       
    $this->infosetup();
    $this->DBSQL();
        
    

    $this->template->exec();

  }
    

    //
    // actions Logs
    //
    function logs( ){
        $list_file_logs_path = glob(SITE_PATH."log/*.log");
        $list_file_logs = str_replace(SITE_PATH."log/", "", $list_file_logs_path);
        $list_file_logs = str_replace(".log", "", $list_file_logs);
        $this->template->assign("list_file_logs" , $list_file_logs);
    }
    function read_logs(){ //LLamada a Ajax para buscar mas lineas

      $readerlogcontrol = new LogReaderController();
      $content_logs = $readerlogcontrol->read_logs();
      header("Content-Type: application/json"); //return only JSON data
      echo json_encode($content_logs);
        
    }
    function debugs( ){

    }

    function infosetup(){
      print ".";
      //print_r(SITE_PATH);
      //print_r(SITE_URL_CURRENT);
    }

    function DBSQL(){
     $data_sql = $this->get_sql_tables();
     $this->template->assign("data_sql" , $data_sql);
    }






    //
    // Actions base de datos
    //
    function create_db_scheme(){      
      $fvotdbcontrol = new DevelDBController($_POST['u'], $_POST['p']);
      header("Content-Type: application/json"); //return only JSON data
      echo json_encode(array('response' => $fvotdbcontrol->createSchemaDB() ));
    }


    function create_db_tables(){
      $fvotdbcontrol = new DevelDBController();
      header("Content-Type: application/json"); //return only JSON data
      echo json_encode(array('response' => $fvotdbcontrol->createTables() ));
    }
    
    function get_sql_tables(){
      $fvotdbcontrol = new DevelDBController();
      return ($fvotdbcontrol->getTablesSQL() );
    }

}


