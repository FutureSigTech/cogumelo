<?php


Cogumelo::load('c_view/Template');


abstract class View {
  var $first_execution = true;
  var $template;

  function __construct($teplates_dir) {
    if($this->first_execution) {

      $first_execution = false;

      $this->template = new Template($teplates_dir);

      if(!$this->accessCheck()){
        Cogumelo::error('Acess error on view '. get_called_class() );
        exit;
      }
      else {
        Cogumelo::debug('accessCheck OK '. get_called_class() );
      }
    }
  }

  /**
  * Evaluar las condiciones de acceso y reportar si se puede continuar
  * @return bool : true -> Access allowed
  */
  function accessCheck() {

    Cogumelo::error('Es necesario definir el método "accessCheck" en el View con los controles de'.
      ' restricción de acceso.');

    return false;
  }

}

