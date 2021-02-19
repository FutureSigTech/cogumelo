<?php
// common::autoIncludes();
// form::autoIncludes();


/**
 * Gestión de ficheros en formularios. Subir o borrar ficheros en campos de formulario.
 *
 * @package Module Form
 **/
class FormConnectorFiles {

  public function uploadFormFile( $post, $phpFiles ) {
    Cogumelo::log(__METHOD__, 'Form');
    // error_log(__METHOD__);

    // error_log('FormConnector: FILES:' ); error_log( print_r( $phpFiles, true ) );
    // error_log('FormConnector: POST:' ); error_log( print_r( $post, true ) );

    $form = new FormController();

    $idForm = isset( $post['idForm'] ) ? $post['idForm'] : false;
    $fieldName = isset( $post['fieldName'] ) ? $post['fieldName'] : false;
    $tnProfile = isset( $post['tnProfile'] ) ? $post['tnProfile'] : false;
    $moreInfo = [ 'idForm' => $idForm ];

    if( isset( $post['cgIntFrmId'], $post['fieldName'], $phpFiles['ajaxFileUpload'] ) ) {
      $cgIntFrmId = $post['cgIntFrmId'];
      $moreInfo['cgIntFrmId'] = $cgIntFrmId;
      $moreInfo['fieldName'] = $fieldName;

      Cogumelo::log(__METHOD__.' FILES:'.$phpFiles['ajaxFileUpload']['name'], 'Form');
      // error_log(__METHOD__.': FILES:'.$phpFiles['ajaxFileUpload']['name'] );
      $fich = [
        'tmpLoc'  => $phpFiles['ajaxFileUpload']['tmp_name'], // File in the PHP tmp folder
        'name'    => $phpFiles['ajaxFileUpload']['name'],     // The file name
        'type'    => $phpFiles['ajaxFileUpload']['type'],     // The type of file it is
        'size'    => $phpFiles['ajaxFileUpload']['size'],     // File size in bytes
        'errorId' => $phpFiles['ajaxFileUpload']['error'],    // UPLOAD_ERR_OK o errores
      ];

      $this->uploadFormFilePhpValidate( $form, $fieldName, $fich );

      if( !$form->existErrors() ) {
        // Recuperamos formObj, validamos y guardamos el fichero
        $newFileFieldValue = $this->uploadFormFileProcess( $form, $fieldName, $fich, $cgIntFrmId );
      }
    }
    else { // no parece haber fichero
      if( !empty( $fieldName ) ) {
        $msg = 'La subida del fichero ha fallado. (IS)';
        $form->addFieldRuleError( $fieldName, 'cogumelo', $msg );
        Cogumelo::log(__METHOD__.' Falta cgIntFrmId ou ajaxFileUpload. '.$msg, 'Form');
      }
      else {
        $msg = 'La subida del fichero ha fallado. (IS2)';
        $form->addFormError( $msg, 'formError' );
        Cogumelo::log(__METHOD__.' Falta fieldname. '.$msg, 'Form');
      }
    }


    if( !$form->existErrors() ) {
      $newVal = $newFileFieldValue['temp'];
      $moreInfo['fileName'] = $newVal['name'];
      $moreInfo['fileSize'] = $newVal['size'];
      $moreInfo['fileType'] = $newVal['type'];
      $moreInfo['tempId'] = isset( $newVal['tempId'] ) ? $newVal['tempId'] : false;

      if( !empty( $tnProfile ) ) {
        $moreInfo['fileSrcTn'] = $this->uploadFormFileThumbnail( $newVal['absLocation'], $tnProfile );
      }
    }


    // Notificamos el resultado al UI
    $form->sendJsonResponse( $moreInfo );
  } // function uploadFormFile() {

  public function uploadFormFilePhpValidate( $form, $fieldName, $fich ) {
    Cogumelo::log(__METHOD__, 'Form');
    // error_log(__METHOD__);

    // Aviso de error PHP
    if( $fich['errorId'] !== UPLOAD_ERR_OK ) {
      $msg = 'La subida del fichero ha fallado. (SF-'.$fich['errorId'].')';
      $form->addFieldRuleError( $fieldName, 'cogumelo', $msg );
      Cogumelo::log(__METHOD__.' ERROR: PHP UPLOAD_ERR. '.$msg, 'Form');
    }

    // Verificando la existencia y tamaño del fichero intermedio
    if( !$form->existErrors() ) {
      if( $fich['size'] < 1 ) {
        $msg = 'La subida del fichero ha fallado. (T0)';
        $form->addFieldRuleError( $fieldName, 'cogumelo', $msg );
        Cogumelo::log(__METHOD__.' ERROR: Tamaño 0. '.$msg, 'Form');
      }
      elseif( !is_uploaded_file( $fich['tmpLoc'] ) ) {
        $msg = 'La subida del fichero ha fallado. (T1)';
        $form->addFieldRuleError( $fieldName, 'cogumelo', $msg );
        Cogumelo::log(__METHOD__.' ERROR: Falta el temporal. '.$msg, 'Form');
      }
      elseif( filesize( $fich['tmpLoc'] ) !== $fich['size'] ) {
        $msg = 'La subida del fichero ha fallado. (T2)';
        $form->addFieldRuleError( $fieldName, 'cogumelo', $msg );
        Cogumelo::log(__METHOD__.' ERROR: Tamaño incorrecto. '.$msg, 'Form');
      }
    }

    // Verificando el MIME_TYPE del fichero intermedio
    if( !$form->existErrors() ) {
      $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
      $fileTypePhp = finfo_file( $finfo, $fich['tmpLoc'] );
      if( $fileTypePhp !== false ) {
        if( $fich['type'] !== $fileTypePhp ) {
          $fich['type'] = $fileTypePhp;

          $msg = ' ALERTA: MIME_TYPE de navegador y PHP difieren: '.$fich['type'].' != '.$fileTypePhp.' Usamos PHP.';
          error_log(__METHOD__.$msg );
          Cogumelo::log(__METHOD__.$msg, 'Form');
        }
      }
      else {
        $msg = ' ALERTA: MIME_TYPE PHP del fichero desconocido. Usamos el de Navegador: '.$fich['type'];
        error_log(__METHOD__.$msg );
        Cogumelo::log(__METHOD__.$msg, 'Form');
      }
    }
  } // uploadFormFilePhpValidate( $form, $fieldName, $fich )

  public function uploadFormFileProcess( $form, $fieldName, $fich, $cgIntFrmId ) {
    Cogumelo::log(__METHOD__, 'Form');
    // error_log(__METHOD__);

    $newFileFieldValue = false;

    if( $form->loadFromSession( $cgIntFrmId ) && $form->getFieldType( $fieldName ) === 'file' ) {
      // error_log(__METHOD__.' FORM CARGADO');

      // Guardamos los datos previos del campo
      $fileFieldValuePrev = $form->getFieldValue( $fieldName );
      // error_log('FormConnector: LEEMOS File Field: '.print_r($fileFieldValuePrev,true) );

      // Almacenamos datos temporales en el formObj para validarlos
      $form->setFieldValue( $fieldName, [
        'status' => 'LOAD',
        'validate' => [ 'partial' => true, 'name' => $fich['name'], 'originalName' => $fich['name'],
          'absLocation' => $fich['tmpLoc'], 'type' => $fich['type'], 'size' => $fich['size'] ]
      ] );
      $form->validateField( $fieldName );

      if( !$form->existErrors() ) {
        // El fichero ha superado las validaciones. Ajustamos sus valores finales y los almacenamos.
        $newFileFieldValue = $this->uploadFormFileSave( $form, $fieldName, $fich, $fileFieldValuePrev );
      }
      else {
        Cogumelo::log(__METHOD__.' NON Valida o ficheiro subido...', 'Form');
      }
    }
    else {
      $msg = 'La subida del fichero ha fallado. (FO)';
      $form->addFieldRuleError( $fieldName, 'cogumelo', $msg );
      Cogumelo::log(__METHOD__.' Form o tipo de campo errorneo. '.$msg, 'Form');
    }

    return $newFileFieldValue;
  } // uploadFormFileProcess( $form, $fieldName, $fich, $cgIntFrmId )

  public function uploadFormFileSave( $form, $fieldName, $fich, $fileFieldValuePrev ) {
    Cogumelo::log(__METHOD__, 'Form');
    // error_log(__METHOD__);

    $newFileFieldValue = false;

    Cogumelo::log(__METHOD__.' Validado. Vamos a moverlo...', 'Form');
    $tmpCgmlFileLocation = $form->tmpPhpFile2tmpFormFile( $fich['tmpLoc'], $fich['name'], $fieldName );

    if( $tmpCgmlFileLocation === false ) {
      $msg = 'La subida del fichero ha fallado. (MU)';
      $form->addFieldRuleError( $fieldName, 'cogumelo', $msg );
      Cogumelo::log(__METHOD__.' Fallo move_uploaded_file movendo '.$fieldName.': ('.$fich['tmpLoc'].') '.$msg, 'Form');
    }
    else {
      // El fichero subido ha pasado todos los controles. Vamos a registrarlo según proceda
      Cogumelo::log(__METHOD__.' Validado y movido. Paso final...', 'Form');

      $newFileFieldValue = [
        'status' => 'LOAD',
        'temp' => [
          'name' => $fich['name'],
          'originalName' => $fich['name'],
          'absLocation' => $tmpCgmlFileLocation,
          'type' => $fich['type'],
          'size' => $fich['size']
        ]
      ];

      if( !$form->getFieldParam( $fieldName, 'multiple' ) ) {
        // Basic: only one file
        if( isset( $fileFieldValuePrev['status'] ) && $fileFieldValuePrev['status'] !== false ) {
          if( $fileFieldValuePrev['status'] === 'DELETE' ) {
            Cogumelo::log(__METHOD__.' Todo OK. Estado REPLACE...', 'Form');

            $newFileFieldValue['status'] = 'REPLACE';
            $fileFieldValuePrev = $newFileFieldValue;
          }
          else {
            $msg = 'La subida del fichero ha fallado. (FE)';
            $form->addFieldRuleError( $fieldName, 'cogumelo', $msg );
            Cogumelo::log(__METHOD__.' Validado pero status erroneo: '.$fileFieldValuePrev['status'].' '.$msg, 'Form');
          }
        }
        else {
          Cogumelo::log(__METHOD__.' Todo OK. Estado LOAD...', 'Form');
          $fileFieldValuePrev = $newFileFieldValue;
        }
      }
      else {
        // Multiple: add files
        Cogumelo::log(__METHOD__.' Todo OK. Multifile LOAD...', 'Form');
        if( !isset( $fileFieldValuePrev['multiple'] ) ) {
          $fileFieldValuePrev['multiple'] = [];
          if( isset( $fileFieldValuePrev['status'] ) ) {
            $fileFieldValuePrev['multiple'] = [ $fileFieldValuePrev ];
          }
        }
        $preKeys = array_keys( $fileFieldValuePrev['multiple'] );
        $fileFieldValuePrev['multiple'][] = $newFileFieldValue;
        $newKeys = array_diff( array_keys( $fileFieldValuePrev['multiple'] ), $preKeys );
        $newKey = array_shift( $newKeys );
        $newFileFieldValue['temp']['tempId'] = $newKey;
        $fileFieldValuePrev['multiple'][ $newKey ]['temp']['tempId'] = $newKey;
      }

      if( !$form->existErrors() ) {
        Cogumelo::log(__METHOD__.' OK con el ficheiro subido... Se persiste...', 'Form');
        // error_log(__METHOD__.' OK con el ficheiro subido... Se persiste...');
        // error_log('FormConnector: GUARDAMOS File Field: '.print_r($fileFieldValuePrev,true) );
        $form->setFieldValue( $fieldName, $fileFieldValuePrev );
        // Persistimos formObj para cuando se envíe el formulario completo
        $form->saveToSession();
      }
      else {
        $msg = ' ERROR: Como ha fallado, eliminamos: '.$tmpCgmlFileLocation;
        Cogumelo::log(__METHOD__.$msg, 'Form');
        error_log(__METHOD__.$msg);
        unlink( $tmpCgmlFileLocation );
      }
    } // else - if( !$tmpCgmlFileLocation )

    return $newFileFieldValue;
  } // uploadFormFileSave( $form, $fieldName, $fich )

  public function uploadFormFileThumbnail( $fileLocation, $tnProfile ) {
    filedata::load('controller/FiledataImagesController.php');
    $iCtrl = new FiledataImagesController();
    $iCtrl->setProfile( $tnProfile );

    $fileSrcTn = $iCtrl->createImageProfile( $fileLocation, false, true );

    return $fileSrcTn;
  } // uploadFormFileThumbnail( $fileLocation, $tnProfile )




  public function deleteFormFile( $post ) {
    Cogumelo::log(__METHOD__, 'Form');
    // error_log(__METHOD__);

    // error_log('FormConnector: POST:' );
    // error_log('FormConnector: '. print_r( $post, true ) );

    $form = new FormController();

    $idForm = isset( $post['idForm'] ) ? $post['idForm'] : false;
    $fieldName = isset( $post['fieldName'] ) ? $post['fieldName'] : false;
    $moreInfo = [ 'idForm' => $idForm ];

    if( isset( $post['cgIntFrmId'], $post['fieldName'] ) ) {
      $cgIntFrmId = $post['cgIntFrmId'];
      $moreInfo['cgIntFrmId'] = $cgIntFrmId;
      $moreInfo['fieldName'] = $fieldName;

      $fich['fileTempId'] = isset( $post['fileTempId'] ) ? $post['fileTempId'] : false;
      $fich['fileId'] = isset( $post['fileId'] ) ? $post['fileId'] : false;

      $this->deleteFormFileProcess( $form, $fieldName, $fich, $cgIntFrmId );
    }
    else { // no parece haber fichero
      if( !empty( $fieldName ) ) {
        $msg = 'No han llegado los datos o lo ha hecho con errores. (ISE)';
        $form->addFieldRuleError( $fieldName, 'cogumelo', $msg );
        Cogumelo::log(__METHOD__.' Falta cgIntFrmId. '.$msg, 'Form');
      }
      else {
        $msg = 'No han llegado los datos o lo ha hecho con errores. (ISE2)';
        $form->addFormError( $msg, 'formError' );
        Cogumelo::log(__METHOD__.' Falta fieldName. '.$msg, 'Form');
      }
    }

    // Notificamos el resultado al UI
    $form->sendJsonResponse( $moreInfo );
  } // function deleteFormFile() {

  public function deleteFormFileProcess( $form, $fieldName, $fich, $cgIntFrmId ) {
    Cogumelo::log(__METHOD__, 'Form');
    // error_log(__METHOD__);

    // Recuperamos formObj y validamos el fichero temporal
    if( $form->loadFromSession( $cgIntFrmId ) && $form->getFieldType( $fieldName ) === 'file' ) {
      // Cargamos los datos previos del campo
      $fieldPrev = $form->getFieldValue( $fieldName );

      $fileGroup = false;
      $multipleFileField = false;
      $multipleIndex = false;
      if( $fieldPrev['status'] === 'GROUP' ) {
        // Necesitamos informacion extra porque es un grupo de ficheros
        $multipleFileField = true;

        if( isset($fieldPrev['idGroup']) ) {
          $fileGroup = $fieldPrev['idGroup'];
        }

        // error_log(__METHOD__ .' fileTempId '. json_encode($fich['fileTempId']) );

        // if( !empty( $fich['fileTempId'] ) ) {
        // if( isset( $fich['fileTempId'] ) && $fich['fileTempId'] !== false ) {
        if( isset( $fich['fileTempId'] ) && $fich['fileTempId'] !== false && $fich['fileTempId'] !== '' ) {
          $multipleIndex = $fich['fileTempId'];
        }
        else {
          $multipleIndex = 'FID_'.$fich['fileId'];
        }

        // error_log(__METHOD__ .' MULTIPLE '. $multipleIndex );
        // error_log(__METHOD__ .' MULTIPLE '. print_r($fieldPrev['multiple'],true) );

        if( isset( $fieldPrev['multiple'][ $multipleIndex ] ) ) {
          $fieldPrev = $fieldPrev['multiple'][ $multipleIndex ];
        }
        else {
          $fieldPrev = false;
        }
      }


      Cogumelo::log(__METHOD__.' LEEMOS File Field para BORRAR: '.json_encode( $fieldPrev ), 'Form');


      if( isset( $fieldPrev['status'] ) && $fieldPrev['status'] !== false ) {
        // error_log('FormConnector: FDelete: STATUS: ' . $fieldPrev['status'] );

        switch( $fieldPrev['status'] ) {
          case 'LOAD':
            // error_log('FormConnector: FDelete: LOAD - Borramos: '.$fieldPrev['temp']['absLocation'] );
            // unlink( $fieldPrev['temp']['absLocation'] ); // Garbage collector
            $fieldPrev = null;
            break;
          case 'EXIST':
            // error_log('FormConnector: FDelete: EXIST - Marcamos para borrar: '.$fieldPrev['prev']['absLocation'] );
            $fieldPrev['status'] = 'DELETE';
            break;
          case 'REPLACE':
            // error_log('FormConnector: FDelete: REPLACE - Borramos: '.$fieldPrev['temp']['absLocation'] );
            $fieldPrev['status'] = 'DELETE';
            // unlink( $fieldPrev['temp']['absLocation'] ); // Garbage collector
            $fieldPrev['temp'] = null;
            break;
          default:
            $msg = 'Intento de borrado erroneo (STB)';
            $form->addFieldRuleError( $fieldName, 'cogumelo', $msg );
            error_log(__METHOD__.' ERROR: Campo '.$fieldName.' con estado '.$fieldPrev['status'].' erroneo. '.$msg );
            Cogumelo::log(__METHOD__.' ERROR: Campo '.$fieldName.' con estado '.$fieldPrev['status'].' erroneo. '.$msg, 'Form' );
            break;
        }
      }
      else {
        $msg = 'Intento de borrado erroneo (STN)';
        $form->addFieldRuleError( $fieldName, 'cogumelo', $msg );
        error_log(__METHOD__.' ERROR: Campo '.$fieldName.' sin estado. '.$msg );
        Cogumelo::log(__METHOD__.' ERROR: Campo '.$fieldName.' sin estado. '.$msg, 'Form' );
      }

      if( !$form->existErrors() ) {
        // error_log('FormConnector: FDelete: OK. Guardando el nuevo estado... Se persiste...' . $fieldPrev['status'] );
        if( $multipleFileField ) {
          $fieldNew = $fieldPrev;
          $fieldPrev = $form->getFieldValue( $fieldName );
          if( $fieldNew !== null ) {
            $fieldPrev['multiple'][ $multipleIndex ] = $fieldNew;
          }
          else {
            unset( $fieldPrev['multiple'][ $multipleIndex ] );
          }
        }

        Cogumelo::log(__METHOD__.' GUARDAMOS File Field: '.$fieldName, 'Form');

        $form->setFieldValue( $fieldName, $fieldPrev );
        // Persistimos formObj para cuando se envíe el formulario completo
        $form->saveToSession();
      }
      else {
        $msg = ' ERROR: El borrado ha fallado. Se mantiene el estado: '.$fieldName;
        Cogumelo::log(__METHOD__.$msg, 'Form');
        error_log(__METHOD__.$msg );
      }
    } // if( $form->loadFromSession( $cgIntFrmId ) && $form->getFieldType( $fieldName ) === 'file' )
    else {
      $msg = 'Intento de borrado erroneo. (FRM)';
      $form->addFieldRuleError( $fieldName, 'cogumelo', $msg );
      Cogumelo::log(__METHOD__.' ERROR: Falta cgIntFrmId o '.$fieldName.' no es file. '.$msg, 'Form' );
      error_log(__METHOD__.' ERROR: Falta cgIntFrmId o '.$fieldName.' no es file. '.$msg );
    }
  }
}
