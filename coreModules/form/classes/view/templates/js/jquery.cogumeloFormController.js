/**
 *  Gestión de informacion en cliente
 */
var cogumelo = cogumelo || {};
cogumelo.formController = cogumelo.formController || {};
cogumelo.formController.formsInfo = cogumelo.formController.formsInfo || [];
cogumelo.formController.fileGroup = cogumelo.formController.fileGroup || [];

cogumelo.publicConf.session_lifetime = cogumelo.publicConf.session_lifetime || 900;
// Lanzamos formKeepAlive cuando pasa el 90% del tiempo session_lifetime
cogumelo.formController.keepAliveTimer = cogumelo.formController.keepAliveTimer || setInterval( formKeepAlive, 900*cogumelo.publicConf.session_lifetime );

cogumelo.formController.langForm = cogumelo.formController.langForm || false;





function getForms() {
  var result = [];

  for( var i = cogumelo.formController.formsInfo.length - 1; i >= 0; i-- ) {
    result.push( cogumelo.formController.formsInfo[i].idForm );
  }

  return result;
}


function getFormInfoIndex( idForm ) {
  var index = false;

  for( var i = cogumelo.formController.formsInfo.length - 1; i >= 0; i-- ) {
    if( cogumelo.formController.formsInfo[i].idForm === idForm ) {
      index = i;
      break;
    }
  }

  return index;
}


function setFormInfo( idForm, key, value ) {
  var index = getFormInfoIndex( idForm );
  if( index === false ) {
    index = cogumelo.formController.formsInfo.length;
    cogumelo.formController.formsInfo[ index ] = { idForm: idForm };
  }
  cogumelo.formController.formsInfo[ index ][ key ] = value;
}


function getFormInfo( idForm, key ) {
  var result = null;

  var index = getFormInfoIndex( idForm );

  if( index !== false ) {
    result = cogumelo.formController.formsInfo[ index ][ key ];
  }

  return result;
}








function formKeepAlive() {
  // console.log( 'keepAlive' );
  formIds = getForms();
  // console.log( 'keepAlive formIds --- ',formIds );
  $.each( formIds, function( i, idForm ){
    // console.log( 'keepAlive idForm --- ',idForm );
    formKeepAliveById( idForm );
  });
}


function formKeepAliveById( idForm ) {
  // console.log( 'formKeepAliveById '+idForm );

  var cgIntFrmId = $( '#' + idForm ).attr( 'data-token_id' );

  var formData = new FormData();
  formData.append( 'execute', 'keepAlive' );
  formData.append( 'idForm', idForm );
  formData.append( 'cgIntFrmId', cgIntFrmId );

  // console.log( 'formData --- ', formData );

  $.ajax({
    url: '/cgml-form-command', type: 'POST',
    data: formData, cache: false, contentType: false, processData: false,
    success: function successHandler( $jsonData, $textStatus, $jqXHR ) {
      // console.log( 'formKeepAliveById $jsonData --- ', $jsonData );
      var idForm = ($jsonData.moreInfo.idForm) ? $jsonData.moreInfo.idForm : false;
      if( $jsonData.result === 'ok' ) {
        // console.log( 'formKeepAliveById OK --- ',idForm );
      }
      else {
        // console.log( 'formKeepAliveById ERROR',$jsonData );
      }
    }
  });
}






function createFilesTitleField( idForm ) {
  // console.log( 'createFilesTitleField( '+idForm+' )' );

  var $inputFileFields = $( 'input:file[form="'+idForm+'"]' ).not( '[multiple]' );
  $inputFileFields.after( function() {
    // console.log( 'createFilesTitleField after ', this );

    var fileField = this;
    var langs = ( typeof( cogumelo.publicConf.langAvailableIds ) === 'object' ) ? cogumelo.publicConf.langAvailableIds : [''];
    var html = '<div class="cgmMForm-wrap cgmMForm-'+idForm+' cgmMForm-fileFields-'+idForm+
      ' cgmMForm-titleFileField cgmMForm-titleFileField_'+fileField.name+'" style="display:none">'+"\n";

    $.each( langs, function( i, lang ) {
      var name = ( lang !== '' ) ? fileField.name+'_'+lang : fileField.name;
      var filefielddata = ( lang !== '' ) ? 'fm_title_'+lang : 'fm_title';
      var classLang = ( lang !== '' ) ? ' js-tr js-tr-'+lang : '';
      var titleValue = ( $( fileField ).data( filefielddata ) ) ? $( fileField ).data( filefielddata ) : '';
      html += '<div class="cgmMForm-wrap cgmMForm-field-titleFileField_'+name+'">'+"\n"+
        '<label class="cgmMForm'+classLang+'">Alt-Title</label>'+"\n"+
        '<input name="titleFileField_'+name+'" value="'+titleValue+'" '+
        'data-ffid="'+idForm+'" data-ffname="'+fileField.name+'" data-ffdata="'+filefielddata+'" '+
        'class="noValidate cgmMForm-field cgmMForm-field-titleFileField'+classLang+'" type="text">'+"\n"+
        '</div>'+"\n";
      // console.log( 'createFilesTitleField each lang '+lang );
    });

    html += '</div>'+"\n";

    return html;
  });

  $( 'input.cgmMForm-field-titleFileField' ).on( 'change', function() {
    // console.log( 'titleFileField change en ', this );
    var $titleFileField = $( this );
    var $titleData = $titleFileField.data();
    var $fileField = $( 'input[form="'+$titleData.ffid+'"][name="'+$titleData.ffname+'"]' );
    $fileField.attr( 'data-'+$titleData.ffdata, $titleFileField.val() );
    $fileField.data( $titleData.ffdata, $titleFileField.val() );
    // Doble escritura para asegurar porque funcionan distinto
  });
}

function hideFileTitleField( idForm, fieldName ) {
  // console.log( 'hideFileTitleField( '+idForm+', '+fieldName+' )' );

  var $fileField = $( 'input[form="'+idForm+'"][name="'+fieldName+'"]' );
  // Clear data-fm_title
  var langs = ( typeof( cogumelo.publicConf.langAvailableIds ) === 'object' ) ? cogumelo.publicConf.langAvailableIds : [''];
  $.each( langs, function( i, lang ) {
    var filefielddata = ( lang !== '' ) ? 'fm_title_'+lang : 'fm_title';
    $fileField.attr( 'data-'+filefielddata, '' );
    $fileField.data( filefielddata, '' );
  });
  // Hide wrap
  var $wrap = $( '.cgmMForm-' + idForm+'.cgmMForm-titleFileField_'+fieldName );
  $wrap.hide();
  // Clear values
  $wrap.find( ' input' ).val('');
}


function bindForm( idForm ) {
  // console.log( 'bindForm( '+idForm+' )' );
  var $inputFileFields = $( 'input:file[form="'+idForm+'"]' );
  if( $inputFileFields.length ) {
    if( !window.File ) {
      // File - provides readonly information such as name, file size, mimetype
      alert( __('Your browser does not have HTML5 support for send files. Upgrade to recent versions...') );
    }
    $inputFileFields.on( 'change', inputFileFieldChange );
    $inputFileFields.each(
      function() {
        var fieldName = $( this ).attr( 'name' );
        createFileFieldDropZone( idForm, fieldName );

        if( $( this ).attr('multiple') ) {
          fileFieldGroupWidget( idForm, fieldName );
        }
      }
    );
  }

  $( '.addGroupElement[data-form_id="'+idForm+'"]' ).on( 'click', addGroupElement ).css( 'cursor', 'pointer' );
  $( '.removeGroupElement[data-form_id="'+idForm+'"]' ).on( 'click', removeGroupElement ).css( 'cursor', 'pointer' );
}


function unbindForm( idForm ) {
  // console.log( 'unbindForm( '+idForm+' )' );
  $( 'input:file[form="'+idForm+'"]' ).off( 'change' );
  $( '.addGroupElement[data-form_id="'+idForm+'"]' ).off( 'click' );
  $( '.removeGroupElement[data-form_id="'+idForm+'"]' ).off( 'click' );
}

/*
  Gestión de informacion en cliente (FIN)
*/

function setSubmitElement( evnt ) {
  //console.log( 'setSubmitElement: ', evnt );
  $elem = $( evnt.target );
  $( '#'+$elem.attr('form') ).attr('data-submit-element-name', $elem.attr('name') );
}

function unsetSubmitElement( evnt ) {
  //console.log( 'unsetSubmitElement: ', evnt );
  $elem = $( evnt.target );
  $( '#'+$elem.attr('form') ).removeAttr('data-submit-element-name');
}

function setValidateForm( idForm, rules, messages ) {
  $formSubmitFields = $( '[form="'+idForm+'"][type="submit"]' );
  $formSubmitFields.on({
    // 'mouseenter' : setSubmitElement,
    'focusin' : setSubmitElement,
    // 'mouseleave' : unsetSubmitElement,
    'focusout' : unsetSubmitElement
  });

  $.validator.setDefaults({
    errorPlacement: function( error, element ) {
      console.log( 'JQV errorPlacement:', error, element );
      var $msgContainer = $( '#JQVMC-'+$( error[0] ).attr('id')+', .JQVMC-'+$( error[0] ).attr('id') );
      if( $msgContainer.length > 0 ) {
        $msgContainer.append( error );
      }
      else {
        error.insertAfter( element );
      }
    },
    showErrors: function( errorMap, errorList ) {
      // console.log( 'JQV showErrors:', errorMap, errorList );

      // Lanzamos el metodo original
      this.defaultShowErrors();
    },
    invalidHandler: function( evnt, validator ) {
      console.log( 'JQV invalidHandler:', evnt, validator );
      if( validator.numberOfInvalids() ) {
        failFields = new Object({});
        jQuery.each( validator.errorList, function( index, value ) {
          failFields[index] = value.element;
        });

        var idForm = $( failFields[0] ).attr('form');
        reprocessFormErrors( idForm, failFields );
      }
    }
  });

  // Cargamos el fichero del idioma del entorno
  if( cogumelo.publicConf.C_LANG !== 'en' ) {
    basket.require( { url: '/vendor/bower/jquery-validation/src/localization/messages_'+cogumelo.publicConf.C_LANG+'.js' } );
  }

  // console.log( 'setValidateForm VALIDATE: ', $( '#'+idForm ) );
  var $validateForm = $( '#'+idForm ).validate({
    // debug: true,
    errorClass: 'formError',
    ignore: '.noValidate',
    lang: cogumelo.publicConf.C_LANG,
    rules: rules,
    messages: messages,
    submitHandler: function ( form, evnt ) {
      // Controlamos que el submit se realice desde un elemento de submit
      $form = $( form );
      var submitElementName = $form.attr('data-submit-element-name');
      $form.removeAttr('data-submit-element-name');
      // console.log( 'submitElementName: '+submitElementName );

      if( submitElementName ) {
        // Se ha pulsado en alguno de los elementos de submit
        $submitElement = $( '[form="'+idForm+'"][name="'+submitElementName+'"]' );
        if( $submitElement.attr('data-confirm-text') ) {
          // Se ha indicado que hay que solicitar confirmacion antes del envio.
          if( confirm( $submitElement.attr('data-confirm-text') ) ) {
            sendValidatedForm( form );
          }
        }
        else {
          sendValidatedForm( form );
        }
      }
      else {
        // Se ha lanzado sin pulsar en alguno de los elementos de submit
        console.log('Cogumelo Form: Not submit element');
      }

      return false; // required to block normal submit since you used ajax
    }
  });
  //
  // JQUERY VALIDATE HACK !!! (Start)
  //
  $validateForm.findByName = function( name ) {
    // console.log( 'JQV cgmlHACK findByName: ', name );
    var $form = $( this.currentForm );
    var $elem = $form.find( '[name="' + name + '"]' );
    if( $elem.length !== 1 ) {
      $elem = $( '[form="'+$form[0].id+'"][name="'+name+'"]' );
    }
    // console.log( 'JQV cgmlHACK findByName ret: ', $elem );
    return $elem;
  };
  $validateForm.idOrName = function( element ) {
    // console.log( 'JQV cgmlHACK idOrName: ', name );
    var resp = this.groups[ element.name ] || ( this.checkable( element ) ? element.name : element.id || element.name );
    // console.log( 'JQV cgmlHACK idOrName ret: ', resp );
    return resp;
  };
  // $validateForm.hideTheseReal = $validateForm.hideThese;
  // $validateForm.hideThese = function( errors ) {
  //   // console.log( 'JQV cgmlHACK hideThese: ', errors );
  //   // errors.not( this.containers ).text( "" );
  //   // this.addWrapper( errors ).hide();
  //   $validateForm.hideTheseReal( errors );
  // };
  //
  // JQUERY VALIDATE HACK !!! (End)
  //

  // console.log( 'VALIDATE PREPARADO: ', $validateForm );


  // Bind file fields and group actions...
  bindForm( idForm );

  // Save validate instance for this Form
  setFormInfo( idForm, 'validateForm', $validateForm );

  createFilesTitleField( idForm );

  // Si hay idiomas, buscamos campos multi-idioma en el form y los procesamos
  createSwitchFormLang( idForm );


  // Default marginTop
  setFormInfo( idForm, "marginTop", 150 );


  return $validateForm;
} // function setValidateForm( idForm, rules, messages )

function sendValidatedForm( form ) {
  console.log( 'Executando sendValidatedForm...' );

  $( form ).find( '[type="submit"]' ).attr('disabled', 'disabled');
  $( form ).find( '.submitRun' ).show();

  $.ajax( {
    contentType: 'application/json', processData: false,
    data: JSON.stringify( $( form ).serializeFormToObject() ),
    type: 'POST', url: $( form ).attr( 'data-form-action' ),
    dataType : 'json'
  } )
  .done( function ( response ) {
    // console.log( 'Executando validate.submitHandler.done...' );
    // console.log( response );
    if( response.result === 'ok' ) {
      // alert( 'Form Submit OK' );
      // console.log( 'Form Done: OK' );
      formDoneOk( form, response );
    }
    else {
      // console.log( 'Form Done: ERROR',response );
      formDoneError( form, response );
    }
    $( form ).find( '[type="submit"]' ).removeAttr('disabled');
    $( form ).find( '.submitRun' ).hide();
  } ); // /.done
}

function formDoneOk( form, response ) {
  // console.log( 'formDoneOk' );
  // console.log( response );

  // var $validateForm = getFormInfo( $( form ).attr( 'id' ), 'validateForm' );
  var idForm = $( form ).attr( 'id' );

  var successActions = response.success;
  if ( successActions.onSubmitOk ) {
    eval( successActions.onSubmitOk+'( idForm );' );
  }
  if ( successActions.jsEval ) {
    eval( successActions.jsEval );
  }
  if ( successActions.accept ) {
    alert( successActions.accept );
  }
  if ( successActions.redirect ) {
    // Usando replace no permite volver a la pagina del form
    window.location.replace( successActions.redirect );
  }
  if ( successActions.reload ) {
    window.location.reload();
  }
  if ( successActions.resetForm ) {
    $( form )[0].reset();
    console.log( 'IMPORTANTE: En resetForm falta borrar los campos FILE porque no lo hace el reset!!!' );
  }
  // alert( 'Form Submit OK' );
}


function formDoneError( form, response ) {
  console.log( 'formDoneError' );
  console.log( response );

  var idForm = $( form ).attr( 'id' );
  var $validateForm = getFormInfo( idForm, 'validateForm' );

  var successActions = response.success;
  if ( successActions.onSubmitError ) {
    eval( successActions.onSubmitError+'( idForm );' );
  }

  if( response.result === 'errorSession' ) {
    // No se ha podido recuperar el form en el servidor porque ha caducado
    // console.log( 'formDoneError: errorSession' );
    showErrorsValidateForm( $( form ), __('Form session expired. Reload'), 'formError' );
    if( confirm( __('Reload to get valid From?') ) ) {
      window.location.reload();
    }
  }

  for( var i in response.jvErrors ) {
    var errObj = response.jvErrors[i];
    // console.log( errObj );

    if( errObj.fieldName !== false ) {
      if( errObj.JVshowErrors[ errObj.fieldName ] === false ) {
        var $defMess = $validateForm.defaultMessage( errObj.fieldName, errObj.ruleName );
        if( typeof $defMess !== 'string' ) {
          $defMess = $defMess( errObj.ruleParams );
        }
        errObj.JVshowErrors[ errObj.fieldName ] = $defMess;
      }
      console.log( 'showErrors: ', errObj.JVshowErrors );
      $validateForm.showErrors( errObj.JVshowErrors );
    }
    else {
      console.log( errObj.JVshowErrors );
      showErrorsValidateForm( $( form ), errObj.JVshowErrors.msgText, errObj.JVshowErrors.msgClass );
    }
  } // for(var i in response.jvErrors)



  reprocessFormErrors( idForm );


  // if( response.formError !== '' ) $validateForm.showErrors( {'submit': response.formError} );
  console.log( 'formDoneError (FIN)' );
}







function reprocessFormErrors( idForm, failFields ) {
  console.log( 'reprocessFormErrors', idForm );
  var topErrScroll = 999999;
  var numErrors = 0;
  var formMarginTop = getFormInfo( idForm, 'marginTop' );

  if( typeof failFields === 'undefined' ) {
    failFields = $( '.formError[form="' + idForm + '"]' );
  }
  console.log( 'reprocessFormErrors failFields', failFields );

  // $( '.formError[form="' + idForm + '"]' ).each( function() {
  jQuery.each( failFields, function( index, value ) {
    numErrors++;
    $field = $( value );
    $wrap = $( '.cgmMForm-wrap.cgmMForm-field-'+$field.attr('name') );
    if( $wrap.length > 0 ) {
      topElem = $wrap.offset().top;
      console.log( 'reprocessFormErrors WRAP ', topElem, $field.attr('name') );
    }
    else {
      topElem = $field.offset().top;
      console.log( 'reprocessFormErrors FIELD ', topElem, $field.attr('name') );
    }

    if( topElem && topErrScroll > topElem ) {
      topErrScroll = topElem;
    }
  });


  if( topErrScroll != 999999 ) {
    if( formMarginTop !== null && formMarginTop !== undefined ) {
      topErrScroll -= formMarginTop;
    }
    console.log( 'JQV topErrScroll:', formMarginTop, topErrScroll );
    $( 'html, body' ).animate( { scrollTop: topErrScroll }, 500 );
  }

  notifyFormErrors( idForm, numErrors );
}


function notifyFormErrors( idForm, numErrors ) {
  if( typeof geozzy !== 'undefined' && typeof geozzy.clientMsg !== 'undefined' && typeof geozzy.clientMsg.notify !== 'undefined' ) {
    geozzy.clientMsg.notify(
      __('There are errors in the form') + ' ('+numErrors+')',
      { notifyType: 'warning', size: 'normal', 'title': __('Warning') }
    );
  }
}





function showErrorsValidateForm( $form, msgText, msgClass ) {
  // Solo se muestran los errores pero no se marcan los campos

  // Replantear!!!

  console.log( 'showErrorsValidateForm: '+msgClass+' , '+msgText );
  var msgLabel = '<label class="formError" form="'+$form.attr( 'id' )+'">'+msgText+'</label>';
  var $msgContainer = false;
  if( msgClass !== false ) {
    $msgContainer = $( '.JQVMC-'+msgClass );
  }
  if( $msgContainer !== false && $msgContainer.length > 0 ) {
    $msgContainer.append( msgLabel );
  }
  else {
    $form.append( msgLabel );
  }
}



/*
***  FICHEROS  ***
*/

// Evento de fichero en campo input
function inputFileFieldChange( evnt ) {
  // console.log('inputFileFieldChange:', evnt);
  $fileField = $( evnt.target );
  // processFilesInputFileField( evnt.target.files, evnt.target.form.id, evnt.target.name );
  processFilesInputFileField( evnt.target.files, $fileField.attr('form'), evnt.target.name );
} // function inputFileFieldChange( evnt )


function processFilesInputFileField( formFileObjs, idForm, fieldName ) {
  console.log( 'processFilesInputFileField(): ', formFileObjs, idForm, fieldName );

  var valid = checkInputFileField( formFileObjs, idForm, fieldName );

  if( valid ) {
    var cgIntFrmId = $( '#' + idForm ).attr( 'data-token_id' );
    for( var i = 0, formFileObj; (formFileObj = formFileObjs[i]); i++ ) {
      uploadFile( formFileObj, idForm, fieldName, cgIntFrmId );
    }
  }

  // var $fileField = $( 'input[name="' + fieldName + '"][form="' + idForm + '"]' );
  // $fileField.data( 'dropfiles', false );
} // function processFilesInputFileField( evnt )


function checkInputFileField( formFileObjs, idForm, fieldName ) {
  // console.log( 'checkInputFileField(): ' );
  // console.log( formFileObjs );
  // console.log( fieldName );
  var $validateForm = getFormInfo( idForm, 'validateForm' );

  var $fileField = $( 'input[name="' + fieldName + '"][form="' + idForm + '"]' );
  $( '#' + $fileField.attr('id') + '-error' ).remove();

  $fileField.data( 'validateFiles', formFileObjs );
  var valRes = $validateForm.element( 'input[name="' + fieldName + '"][form="' + idForm + '"]' );
  $fileField.data( 'validateFiles', false );

  return valRes;
} // function checkInputFileField( formFileObjs, idForm, fieldName )


function uploadFile( formFileObj, idForm, fieldName, cgIntFrmId ) {
  console.log( 'uploadFile(): ', formFileObj );

  var formData = new FormData();
  formData.append( 'idForm', idForm );
  formData.append( 'fieldName', fieldName );
  formData.append( 'cgIntFrmId', cgIntFrmId );

  var tnProfile = $( 'input[name="'+fieldName+'"][form="'+idForm+'"]' ).attr('data-tnProfile');
  if( typeof tnProfile === 'undefined' ) {
    formData.append( 'tnProfile', 'modFormTn' );
  }
  if( tnProfile ) {
    formData.append( 'tnProfile', tnProfile );
  }

  formData.append( 'ajaxFileUpload', formFileObj );

  $( '.'+fieldName+'-info[data-form_id="'+idForm+'"]' ).show();

  $.ajax({
    url: '/cgml-form-file-upload', type: 'POST',
    // Form data
    data: formData,
    //Options to tell jQuery not to process data or worry about content-type.
    cache: false, contentType: false, processData: false,
    // Custom XMLHttpRequest
    xhr: function() {
      var myXhr = $.ajaxSettings.xhr();
      if(myXhr.upload){ // Check if upload property exists for handling the progress of the upload
        myXhr.upload.addEventListener(
          'progress',
          function progressHandler( evnt ) {
            var percent = Math.round( (evnt.loaded / evnt.total) * 100 );

            // TODO: Poñer idForm e fieldName
            $( '.contact-file-info .wrap .progressBar' ).val( percent );
            $( '.contact-file-info .wrap .status' ).html( 'Cargando el fichero...' );

            //$( '#progressBar' ).val( percent );
            //$( '#status' ).html( percent + '% uploaded... please wait' );
            //$( '#loaded_n_total' ).html( 'Uploaded ' + evnt.loaded + ' bytes of ' + evnt.total );
          },
          false
        );
      }
      return myXhr;
    },
    /*
    beforeSend: function beforeSendHandler( $jqXHR, $settings ) {
      $( '#status' ).html( 'Upload Failed (' + $textStatus + ')' );
    },
    */
    success: function successHandler( $jsonData, $textStatus, $jqXHR ) {
      // console.log( 'Executando fileSendOk...' );
      // console.log( $jsonData );

      var idForm = $jsonData.moreInfo.idForm;
      var fieldName = $jsonData.moreInfo.fieldName;
      $( '.'+fieldName+'-info[data-form_id="'+idForm+'"] .wrap .progressBar' ).hide();

      if( $jsonData.result === 'ok' ) {

        fileSendOk( idForm, fieldName, formFileObj, $jsonData.moreInfo );

        var successActions = $jsonData.success;
        if( successActions.onFileUpload ) {
          eval( successActions.onFileUpload+'( idForm, fieldName );' );
        }
      }
      else {
        // console.log( 'uploadFile ERROR' );
        $( '.'+fieldName+'-info[data-form_id="'+idForm+'"] .wrap .status' ).html( __('Error loading file') );

        var $validateForm = getFormInfo( idForm, 'validateForm' );
        // console.log( 'uploadFile ERROR', $validateForm );

        for(var i in $jsonData.jvErrors) {
          var errObj = $jsonData.jvErrors[i];
          // console.log( 'uploadFile ERROR', errObj );

          if( errObj.fieldName !== false ) {
            if( errObj.JVshowErrors[ errObj.fieldName ] === false ) {
              var $defMess = $validateForm.defaultMessage( errObj.fieldName, errObj.ruleName );
              if( typeof $defMess !== 'string' ) {
                $defMess = $defMess( errObj.ruleParams );
              }
              errObj.JVshowErrors[ errObj.fieldName ] = $defMess;
            }
            // console.log( errObj.JVshowErrors );
            $validateForm.showErrors( errObj.JVshowErrors );
          }
          else {
            // console.log( errObj.JVshowErrors );
            showErrorsValidateForm( $( '#'+idForm ), errObj.JVshowErrors.msgText, errObj.JVshowErrors.msgClass );
          }
        }
        // if( $jsonData.formError !== '' ) $validateForm.showErrors( {'submit': $jsonData.formError} );
      }
    },
    error: function errorHandler( $jqXHR, $textStatus, $errorThrown ) { // textStatus: timeout, error, abort, or parsererror
      // console.log( 'uploadFile errorHandler', $jqXHR, $textStatus, $errorThrown );
      $( '.'+fieldName+'-info[data-form_id="'+idForm+'"] .status' ).html( 'Upload Failed (' + $textStatus + ')' );
    }
  });
} // function uploadFile( formFileObj, idForm, fieldName, cgIntFrmId )


function deleteFormFileEvent( evnt ) {
  // console.log( 'deleteFormFileEvent: ', evnt );
  var $fileField = $( evnt.target );
  var idForm = $fileField.attr( 'data-form_id' );
  var fieldName = $fileField.attr( 'data-fieldname' );
  var fileId = $fileField.attr( 'data-file_id' ) || false;
  var fileTempId = $fileField.attr( 'data-file_temp_id' ) || false;
  var cgIntFrmId = $( '#' + idForm ).attr( 'data-token_id' );

  deleteFormFile( cgIntFrmId, idForm, fieldName, fileId, fileTempId );
} // function deleteFormFileEvent( evnt )


function deleteFormFile( cgIntFrmId, idForm, fieldName, fileId, fileTempId ) {
  // console.log( 'deleteFormFile: ', cgIntFrmId, idForm, fieldName, fileId, fileTempId );
  var formData = new FormData();
  formData.append( 'execute', 'delete' );
  formData.append( 'cgIntFrmId', cgIntFrmId );
  formData.append( 'idForm', idForm );
  formData.append( 'fieldName', fieldName );
  formData.append( 'fileId', fileId );
  if( fileTempId !== false ) {
    formData.append( 'fileTempId', fileTempId );
  }

  $.ajax( {
    url: '/cgml-form-file-upload', type: 'POST',
    data: formData,
    //Options to tell jQuery not to process data or worry about content-type.
    cache: false, contentType: false, processData: false
  } )
  .done( function ( response ) {
    // console.log( 'Executando deleteFormFile.done...' );
    // console.log( response );
    if( response.result === 'ok' ) {

      fileDeleteOk( idForm, fieldName, fileId, fileTempId );
      // fileFieldToInput( idForm, fieldName );

      var successActions = response.success;
      if( successActions.onFileDelete ) {
        eval( successActions.onFileDelete+'( idForm, fieldName );' );
      }
    }
    else {
      console.log( 'deleteFormFile.done...ERROR', response );
      for(var i in response.jvErrors) {
        var errObj = response.jvErrors[i];
        // console.log( errObj );

        if( errObj.fieldName !== false ) {

          // TODO !!!

        }
        else {
          // console.log( errObj.JVshowErrors );
          showErrorsValidateForm( $( '#'+idForm ), errObj.JVshowErrors.msgText, errObj.JVshowErrors.msgClass );
        }

      } // for
    }
  } );
} // function deleteFormFile( idForm, fieldName, cgIntFrmId )


function fileFieldGroupAddElem( idForm, fieldName, fileInfo ) {
  console.log( 'fileFieldGroupAddElem: ', idForm, fieldName, fileInfo );
  var $fileField = $( 'input[name="' + fieldName + '"][form="'+idForm+'"]' );
  var groupId = $fileField.attr('data-fm_group_id');
  var groupFiles = [];

  // console.log( 'groupId antes: ',groupId );
  // console.log( 'groupFiles antes: ',groupFiles );

  if( groupId ) {
    groupFiles = cogumelo.formController.fileGroup[ groupId ];
  }
  else {
    groupId = idForm+'_'+fieldName;
    cogumelo.formController.fileGroup[ groupId ] = groupFiles;
    $fileField.attr( 'data-fm_group_id', groupId );
  }

  // console.log( 'groupId: ',groupId );
  // console.log( 'groupFiles: ',groupFiles );

  groupFiles.push( fileInfo );

  // console.log( 'groupFiles despois: ',groupFiles );
  cogumelo.formController.fileGroup[ groupId ] = groupFiles;

  fileFieldGroupWidget( idForm, fieldName );
}

function fileFieldGroupRemoveElem( idForm, fieldName, fileId, fileTempId ) {
  console.log( 'fileFieldGroupRemoveElem: ', idForm, fieldName, fileId, fileTempId );
  var $fileField = $( 'input[name="'+fieldName+'"][form="'+idForm+'"]' );
  var groupId = $fileField.attr('data-fm_group_id');
  var groupFiles = cogumelo.formController.fileGroup[ groupId ];

  var newGroupFiles = jQuery.grep( groupFiles, function( elem ) {
    // console.log('grep: ',elem);
    return (
      ( fileId !== false && elem.id != fileId ) ||
      ( fileTempId !== false && ( !elem.hasOwnProperty('tempId') || elem.tempId != fileTempId ) )
    );
  });
  cogumelo.formController.fileGroup[ groupId ] = newGroupFiles;

  fileFieldGroupWidget( idForm, fieldName );
}





function fileSendOk( idForm, fieldName, formFileObj, moreInfo ) {
  // console.log( 'fileSendOk: ',idForm,fieldName,formFileObj,moreInfo );
  var $fileField = $( 'input[name="' + fieldName + '"][form="'+idForm+'"]' );

  var fileInfo = {
    'id': false,
    'formFileObj': formFileObj,
    'tempId': moreInfo.tempId,
    'name': moreInfo.fileName,
    'type': moreInfo.fileType,
    'size': moreInfo.fileSize,
  };

  var tnProfile = $fileField.attr('data-tnProfile');
  if( tnProfile ) {
    fileInfo.tnProfile = tnProfile;
  }

  fileInfo.fileSrcTn = moreInfo.hasOwnProperty('fileSrcTn') ? moreInfo.fileSrcTn : false;

  if( $fileField.attr('multiple') ) {
    fileFieldGroupAddElem( idForm, fieldName, fileInfo );
  }
  else {
    fileFieldToOk( idForm, fieldName, fileInfo );
  }
}

function fileDeleteOk( idForm, fieldName, fileId, fileTempId ) {
  // console.log( 'fileDeleteOk: ', idForm, fieldName, fileId, fileTempId );
  var $fileField = $( 'input[name="' + fieldName + '"][form="'+idForm+'"]' );

  if( $fileField.attr('multiple') ) {
    fileFieldGroupRemoveElem( idForm, fieldName, fileId, fileTempId );
  }
  else {
    fileFieldToInput( idForm, fieldName );
  }
}



function fileFieldGroupWidget( idForm, fieldName ) {
  console.log( 'fileFieldGroupWidget: ', idForm, fieldName );
  var $fileField = $( 'input[name="' + fieldName + '"][form="'+idForm+'"]' );
  var groupId = $fileField.attr('data-fm_group_id');
  var groupFiles = [];

  // console.log( 'groupId antes: ',groupId );
  // console.log( 'groupFiles antes: ',groupFiles );

  if( groupId ) {
    groupFiles = cogumelo.formController.fileGroup[ groupId ];
  }
  else {
    groupId = idForm+'_'+fieldName;
    cogumelo.formController.fileGroup[ groupId ] = groupFiles;
    $fileField.attr( 'data-fm_group_id', groupId );
  }

  var $fileFieldWrap = $fileField.closest( '.cgmMForm-wrap.cgmMForm-field-' + fieldName );
  var $fileFieldDropZone = $fileFieldWrap.find( '.fileFieldDropZone' );
  var $filesWrap = $fileFieldWrap.find('.cgmMForm-fileBoxWrap');

  // TODO: temporal
  // $fileFieldDropZone.css('background-color','yellow');

  if( $filesWrap.length == 1 ) {
    $filesWrap = $( $filesWrap[0] );
    $filesWrap.find('*').remove();
    // console.log('Xa hai un filesWrap');
  }
  else {
    $filesWrap = $( '<div>' ).addClass( 'cgmMForm-fileBoxWrap clearfix' );
    $fileFieldDropZone.after( $filesWrap );
    // console.log('Creo un filesWrap');
  }

  $.each( groupFiles, function(){
    // console.log('Añadimos esto a fileBoxWrap;', this, $filesWrap);
    $filesWrap.append( fileBox( idForm, fieldName, this, deleteFormFileEvent )
     .css( {'float': 'left', 'width': '23%', 'margin': '1%' } ) );
  } );
}


function fileFieldToOk( idForm, fieldName, fileInfo ) {
  console.log( 'fileFieldToOk: ', idForm, fieldName, fileInfo );
  var $fileField = $( 'input[name="' + fieldName + '"][form="'+idForm+'"]' );
  var $fileFieldWrap = $fileField.closest( '.cgmMForm-wrap.cgmMForm-field-' + fieldName );

  $fileField.attr( 'readonly', 'readonly' ).prop( 'disabled', true ).hide();

  //$( '#'+fieldName+'-error[data-form_id="'+idForm+'"]' ).hide();
  $( '#' + $fileField.attr('id') + '-error' ).remove();

  // Show Title file field
  // $( '.cgmMForm-' + idForm+'.cgmMForm-titleFileField_'+fieldName ).show();
  $( '.cgmMForm-' + idForm+'.cgmMForm-titleFileField_'+fieldName ).removeAttr('display');

  if( !fileInfo.hasOwnProperty('tnProfile') && $fileField.attr('data-tnProfile') ) {
    fileInfo.tnProfile = $fileField.attr('data-tnProfile');
  }

  $fileFieldWrap.append( fileBox( idForm, fieldName, fileInfo, deleteFormFileEvent ) );

  removeFileFieldDropZone( idForm, fieldName );
}


function fileBox( idForm, fieldName, fileInfo, deleteFunc ) {
  // console.log( 'fileBox: ', idForm, fieldName, fileInfo );

  var $fileBoxElem = $( '<div>' ).addClass( 'cgmMForm-fileBoxElem fileFieldInfo fileUploadOK formFileDelete' )
    .attr( { 'data-form_id': idForm, 'data-fieldname': fieldName, 'data-file_id': fileInfo.id } );
  if( fileInfo.hasOwnProperty('tempId') ) {
    $fileBoxElem.attr( 'data-file_tempId', fileInfo.tempId );
  }

  // Element to send delete order
  $deleteButton = $( '<i>' ).addClass( 'formFileDelete fa fa-trash' )
    .attr( { 'data-fieldname': fieldName, 'data-form_id': idForm } )
    .on( 'click', deleteFunc );
  if( fileInfo.id !== false ) {
    $deleteButton.attr( 'data-file_id', fileInfo.id );
  }
  else {
    $deleteButton.attr( 'data-file_temp_id', fileInfo.tempId );
  }
  $fileBoxElem.append( $deleteButton );

  // Element to download
  if( fileInfo.id !== false ) {
    $fileBoxElem.append( '<a class="formFileDownload" href="/cgmlformfilewd/'+fileInfo.id+'-a'+fileInfo.aKey+
      '/'+fileInfo.name+'" target="_blank"><i class="fa fa-download"></i></a>' );
  }

  var tnSrc = cogumelo.publicConf.media+'/module/form/img/file.png';

  if( fileInfo.fileSrcTn ) {
    tnSrc = fileInfo.fileSrcTn;
  }
  if( fileInfo.id !== false && fileInfo.type && fileInfo.type.indexOf( 'image' ) === 0 ) {
    var tnProfile = 'modFormTn';
    if( fileInfo.hasOwnProperty('tnProfile') ) {
      tnProfile = fileInfo.tnProfile;
    }
    else {
      var inputTnProfile = $( 'input[name="'+fieldName+'"][form="'+idForm+'"]' ).attr('data-tnProfile');
      if( typeof inputTnProfile !== 'undefined' ) {
        tnProfile = inputTnProfile;
      }
    }

    if( tnProfile ) {
      tnSrc = '/cgmlImg/'+fileInfo.id+'-a'+fileInfo.aKey+'/'+tnProfile+'/'+fileInfo.name;
    }
  }

  var tnClass = 'tn-';
  tnClass += (fileInfo.id) ? fileInfo.id : 'N';
  tnClass += '-';
  tnClass += fileInfo.hasOwnProperty('tempId') ? fileInfo.tempId : 'N';
  tnClass += '-id';

  $fileBoxElem.append( '<img class="tnImage '+tnClass+'" data-tnClass="'+tnClass+'" '+
    'src="'+tnSrc+'" alt="'+fileInfo.name+'" title="'+fileInfo.name+'"></img>' );

  // loadImageTn( idForm, fieldName, fileInfo, $fileBoxElem );

  return $fileBoxElem;
}


/*
  function loadImageTn( idForm, fieldName, fileInfo, $fileBoxElem ) {
    console.log( 'loadImageTn(): ', idForm, fieldName, fileInfo, $fileBoxElem );

    var fileObj = fileInfo.hasOwnProperty('formFileObj') ? fileInfo.formFileObj : false;

    if( fileObj && fileObj.type.match('image.*') && fileObj.size < 2000000 ) {
      // console.log( 'loadImageTn: Preparo FileReader' );
      var imageReader = new FileReader();
      imageReader.onload = (
        function cargado( fileLoaded ) {
          // console.log( 'loadImageTn: cargado ', fileLoaded );
          return(
            function procesando( evnt ) {
              $tnImage = false;
              tnClass = $fileBoxElem.find('.tnImage').attr('data-tnClass');
              // console.log( 'tnClass: ', tnClass );

              if( tnClass ) {
                $newFileBoxElem = $('.cgmMForm-fileBoxElem[data-form_id="'+idForm+'"][data-fieldname="'+fieldName+'"]');
                // console.log( 'tnImage newFileBoxElem: ', $newFileBoxElem,idForm,fieldName );
                if( $newFileBoxElem.length ) {
                  $tnImage = $newFileBoxElem.find( 'img.tnImage.'+tnClass );
                  // console.log( 'tnImage ANTES: ', $tnImage );
                }
              }
              if( $tnImage ) {
                // console.log( 'tnImage CAMBIANDO SRC ', $tnImage.attr('src'), evnt.target.result );
                $tnImage.attr( 'src', evnt.target.result );
              }
            }
          );
        }
      )( fileObj );

      // Read in the image file as a data URL.
      console.log( 'loadImageTn: readAsDataURL ',fileObj );
      imageReader.readAsDataURL( fileObj );
    }
  } // function loadImageTn( fileObj, $fileBoxElem )
*/


function fileFieldToInput( idForm, fieldName ) {
  // console.log( 'fileFieldToInput(): ', idForm, fieldName );
  var $fileField = $( 'input[name="' + fieldName + '"][form="'+idForm+'"]' );
  var $fileFieldWrap = $fileField.closest( '.cgmMForm-wrap.cgmMForm-field-' + fieldName );

  // console.log( $fileField );

  $fileFieldWrap.find( '.fileUploadOK' ).remove();

  $fileField.removeAttr( 'readonly' );
  $fileField.prop( 'disabled', false ); //$fileField.removeProp( 'disabled' );
  $fileField.val( null );
  $fileField.show();

  // Hide and clear Title file field/value
  hideFileTitleField( idForm, fieldName );

  createFileFieldDropZone( idForm, fieldName );
}


function createFileFieldDropZone( idForm, fieldName ) {
  console.log( 'createFileFieldDropZone: ', idForm, fieldName );

  var $fileField = $( 'input[name="' + fieldName + '"][form="'+idForm+'"]' );
  var $fileFieldWrap = $fileField.closest( '.cgmMForm-wrap.cgmMForm-field-' + fieldName );
  var $fileDefLabel = $fileFieldWrap.find( 'label' );

  $buttonText = ( $fileDefLabel.length > 0 ) ? $fileDefLabel.html() : 'Upload file';

  // console.log( 'Preparando DropZone #fileFieldDropZone_' + idForm + '_' + fieldName );
  var $fileFieldDropZone = $( '<div>' ).addClass( 'fileFieldDropZone fileFieldDropZoneWait' )
    .attr( {
      'id': 'fileFieldDropZone_' + idForm + '_' + fieldName,
      // 'for': $fileField.attr( 'id' ),
      'data-fieldname': fieldName, 'data-form_id': idForm,
      'style': 'text-align:center; cursor:pointer;'
    });

  $fileFieldDropZone.append(
    '<i class="fa fa-cloud-upload" style="font-size:100px; color:#7fb1c7;"></i>'+
    '<br><span class="cgmMForm-button-js">' + $buttonText + '</span>'+
    // '<br><div id="list"><p>Avisos:</p></div>'+
    // '<br><input type="button" class="cgmMForm-field" value="' + $buttonText + '">'+
    '<style>'+
    '.cgmMForm-button-js { display: inline-block; '+
    ' background-color: transparent; background-image: none; border: 2px solid #7fb1c7; border-radius: 2px;'+
    ' color: #7fb1c7; cursor: pointer; font-size: 14px; font-weight: normal; line-height: 1.42857;'+
    ' margin-bottom: 5px; padding: 5px 15px; text-align: center; text-transform: uppercase;'+
    ' vertical-align: middle;'+ //  white-space: nowrap;
    '}'+
    '.cgmMForm-button-js:hover {'+
    ' background-color: #528ba4; border: 2px solid #5497b4; color: #ffffff; text-decoration: none;'+
    '}'+
    '</style>'
  );

  $fileFieldWrap.append( $fileFieldDropZone );

  // Pasamos el click en fileFieldDropZone al input file
  $fileFieldDropZone.on( 'click', function() {
    $( 'input[name="' + fieldName + '"][form="'+idForm+'"]' ).click();
  });

  $fileField.hide();
  // $fileDefLabel.hide();

  // Setup the fileFieldDropZone listeners.
  //$fileFieldDropZoneElem = $( '.fileFieldDropZone' );
  // console.log( 'fileFieldDropZoneElem: ', $fileFieldWrap.find( '.fileFieldDropZone' ) );

  var fileFieldDropZoneElem = document.getElementById( 'fileFieldDropZone_' + idForm + '_' + fieldName );
  fileFieldDropZoneElem.addEventListener( 'drop', fileFieldDropZoneDrop, false);
  fileFieldDropZoneElem.addEventListener( 'dragover', fileFieldDropZoneDragOver, false);
}

function removeFileFieldDropZone( idForm, fieldName ) {
  console.log( 'removeFileFieldDropZone: ', idForm, fieldName );

  var $fileField = $( 'input[name="' + fieldName + '"][form="'+idForm+'"]' );
  var $fileFieldWrap = $fileField.closest( '.cgmMForm-wrap.cgmMForm-field-' + fieldName );

  $fileFieldWrap.find( '.fileFieldDropZone' ).remove();
}

function fileFieldDropZoneDrop( evnt ) {
  console.log( 'fileFieldDropZoneDrop() ', evnt );

  evnt.stopPropagation();
  evnt.preventDefault();

  var files = evnt.dataTransfer.files; // FileList object.
  console.log( 'fileFieldDropZoneDrop files: ', files );

  var $fileFieldDropZone = $( evnt.target ).closest( '.fileFieldDropZone' );
  var idForm = $fileFieldDropZone.data( 'form_id' );
  var fieldName = $fileFieldDropZone.data( 'fieldname' );
  // console.log( 'fileFieldDropZoneDrop fileFieldDropZone: ', $fileFieldDropZone, idForm, fieldName );
  var $fileField = $( 'input[name="' + fieldName + '"][form="'+idForm+'"]' );
  // console.log( 'fileFieldDropZoneDrop fileField: ', $fileField );

  // $fileField.data( 'dropfiles', false );

  if( files.length === 1 || $fileField.attr('multiple') ) {
    // $fileField.data( 'dropfiles', files );
    processFilesInputFileField( files, idForm, fieldName );
  }
}

function fileFieldDropZoneDragOver( evnt ) {
  // console.log( 'fileFieldDropZoneDragOver event: ', evnt );

  evnt.stopPropagation();
  evnt.preventDefault();
  // evnt.originalEvent.dataTransfer.dropEffect = 'copy'; // Explicitly show this is a copy.
  evnt.dataTransfer.dropEffect = 'copy'; // Explicitly show this is a copy.
}




/*
***  Agrupaciones de campos  ***
*/

function addGroupElement( evnt ) {
  // console.log( 'addGroupElement:' );
  // console.log( evnt );

  var myForm = evnt.target.closest("form");
  var idForm = $( myForm ).attr('id');
  var cgIntFrmId = $( myForm ).attr('data-token_id');
  var groupName = $( evnt.target ).attr('groupName');


  var formData = new FormData();
  formData.append( 'execute', 'getGroupElement' );
  formData.append( 'idForm', idForm );
  formData.append( 'cgIntFrmId', cgIntFrmId );
  formData.append( 'groupName', groupName );

  // console.log( idForm );
  // console.log( cgIntFrmId );
  // console.log( groupName );

  // Desactivamos los bins del form durante el proceso
  unbindForm( idForm );

  $.ajax({
    url: '/cgml-form-group-element', type: 'POST',
    // Form data
    data: formData,
    //Options to tell jQuery not to process data or worry about content-type.
    cache: false, contentType: false, processData: false,
    // Custom XMLHttpRequest
    success: function successHandler( $jsonData, $textStatus, $jqXHR ) {

      // console.log( 'getGroupElement success:' );
      // console.log( $jsonData );

      var idForm = $jsonData.moreInfo.idForm;
      var groupName = $jsonData.moreInfo.groupName;

      $( '#' + idForm + ' .JQVMC-group-' + groupName + ' .formError' ).remove();

      if( $jsonData.result === 'ok' ) {
        // console.log( 'getGroupElement OK' );
        // console.log( 'idForm: ' + idForm + ' groupName: ' + groupName );

        $( $jsonData.moreInfo.htmlGroupElement ).insertBefore(
          '#' + idForm + ' .cgmMForm-group-' + groupName + ' .addGroupElement'
        );

        $.each( $jsonData.moreInfo.validationRules, function( fieldName, fieldRules ) {
          // console.log( 'fieldName: ' + fieldName + ' fieldRules: ', fieldRules );
          // console.log( 'ELEM: #' + idForm + ' .cgmMForm-field.cgmMForm-field-' + fieldName );
          $( '#' + idForm + ' .cgmMForm-field.cgmMForm-field-' + fieldName ).rules( 'add', fieldRules );
        });

        // console.log( 'getGroupElement OK Fin' );
      }
      else {
        // console.log( 'getGroupElement ERROR' );
        var $validateForm = getFormInfo( idForm, 'validateForm' );
        // console.log( $validateForm );
        var errObj = $jsonData.jvErrors[0];
        // console.log( errObj.JVshowErrors );
        showErrorsValidateForm( $( '#'+idForm ), errObj.JVshowErrors[0], 'group-' + groupName );
      }

      // Activamos los bins del form despues del proceso
      bindForm( idForm );

      // console.log( 'getGroupElement success: Fin' );
    },
    error: function errorHandler( $jqXHR, $textStatus, $errorThrown ) { // textStatus: timeout, error, abort, or parsererror
      // console.log( 'uploadFile errorHandler', $jqXHR, $textStatus, $errorThrown );
      $( '#status' ).html( 'ERROR: (' + $textStatus + ')' );

      // Activamos los bins del form despues del proceso
      bindForm( idForm );
    }
  });
} // function addGroupElement( evnt )


function removeGroupElement( evnt ) {
  // console.log( 'removeGroupElement:' );
  // console.log( evnt );

  var myForm = evnt.target.closest("form");
  var idForm = $( myForm ).attr('id');
  var cgIntFrmId = $( myForm ).attr('data-token_id');
  var groupName = $( evnt.target ).attr('groupName');
  var groupIdElem = $( evnt.target ).attr('groupIdElem');
  // console.log( idForm );
  // console.log( cgIntFrmId );
  // console.log( groupName );
  // console.log( groupIdElem );

  var formData = new FormData();
  formData.append( 'execute', 'removeGroupElement' );
  formData.append( 'idForm', idForm );
  formData.append( 'cgIntFrmId', cgIntFrmId );
  formData.append( 'groupName', groupName );
  formData.append( 'groupIdElem', groupIdElem );

  // Desactivamos los bins del form durante el proceso
  unbindForm( idForm );

  $.ajax({
    url: '/cgml-form-group-element', type: 'POST',
    // Form data
    data: formData,
    //Options to tell jQuery not to process data or worry about content-type.
    cache: false, contentType: false, processData: false,
    // Custom XMLHttpRequest
    success: function successHandler( $jsonData, $textStatus, $jqXHR ) {

      // console.log( 'removeGroupElement success:' );
      // console.log( $jsonData );

      var idForm = $jsonData.moreInfo.idForm;
      var groupName = $jsonData.moreInfo.groupName;

      $( '#' + idForm + ' .JQVMC-group-' + groupName + ' .formError' ).remove();

      if( $jsonData.result === 'ok' ) {
        // console.log( 'removeGroupElement OK' );
        // console.log( idForm, groupName, $jsonData.moreInfo.groupIdElem );
        // console.log( '#' + idForm + ' .cgmMForm-groupElem_C_' + $jsonData.moreInfo.groupIdElem );
        $( '#' + idForm + ' .cgmMForm-groupElem_C_' + $jsonData.moreInfo.groupIdElem ).remove();
      }
      else {
        // console.log( 'removeGroupElement ERROR' );
        var $validateForm = getFormInfo( idForm, 'validateForm' );
        // console.log( $validateForm );
        var errObj = $jsonData.jvErrors[0];
        // console.log( errObj.JVshowErrors );
        showErrorsValidateForm( $( '#'+idForm ), errObj.JVshowErrors[0], 'group-' + groupName );
      }

      // Activamos los bins del form despues del proceso
      bindForm( idForm );

      // console.log( 'removeGroupElement success: Fin' );
    },
    error: function errorHandler( $jqXHR, $textStatus, $errorThrown ) { // textStatus: timeout, error, abort, or parsererror
      // console.log( 'uploadFile errorHandler', $jqXHR, $textStatus, $errorThrown );
      $( '#status' ).html( 'ERROR: (' + $textStatus + ')' );

      // Activamos los bins del form despues del proceso
      bindForm( idForm );
    }
  });
} // function removeGroupElement( evnt )



function activateHtmlEditor( idForm ) {
  // console.log( 'activateHtmlEditor: ' + idForm );
  // console.log( idForm );

  $( 'textarea.cgmMForm-htmlEditor[form="'+idForm+'"]' ).each(
    function( index ) {
      var textarea = this;
      var CKcontent = CKEDITOR.replace( textarea, {
        customConfig: '/cgml-form-htmleditor-config.js'
      } );
      CKcontent.on( 'change', function ( ev ) { $( textarea ).html(CKcontent.getData()); });
    }
  );
}



function switchFormLang( idForm, lang ) {
  // console.log( 'switchFormLang: '+lang );
  cogumelo.formController.langForm = lang;
  $( '[form="'+idForm+'"].js-tr, [data-form_id="'+idForm+'"].js-tr, '+
    ' .cgmMForm-fileFields-'+idForm+' input.js-tr' )
    .parent().hide();
  $( '[form="'+idForm+'"].js-tr.js-tr-'+lang+', [data-form_id="'+idForm+'"].js-tr.js-tr-'+lang+', '+
    ' .cgmMForm-fileFields-'+idForm+' input.js-tr.js-tr-'+lang )
    .parent().show(); //.removeAttr('display');
  $( 'ul[data-form_id="'+idForm+'"].langSwitch li' ).removeClass( 'langActive' );
  $( 'ul[data-form_id="'+idForm+'"].langSwitch li.langSwitch-'+lang ).addClass( 'langActive' );
}

function createSwitchFormLang( idForm ) {
  // console.log( 'createSwitchFormLang' );

  if( typeof cogumelo.publicConf.langAvailableIds === 'object' && cogumelo.publicConf.langAvailableIds.length > 1 ) {
    var htmlLangSwitch = '';
    htmlLangSwitch += '<div class="langSwitch-wrap">';
    htmlLangSwitch += '<ul class="langSwitch" data-form_id="'+idForm+'">';
    $.each( cogumelo.publicConf.langAvailableIds, function( index, lang ) {
      htmlLangSwitch += '<li class="langSwitch-'+lang+'" data-lang="'+lang+'">'+lang;
    });
    htmlLangSwitch += '</ul>';
    htmlLangSwitch += '<span class="langSwitchIcon"><i class="fa fa-globe fa-fw"></i></span>';
    htmlLangSwitch += '</div>';

    $langSwitch = $( htmlLangSwitch );
    $( '[form="'+idForm+'"].cgmMForm-field.js-tr.js-tr-' + cogumelo.publicConf.langDefault + ':not("input:file")' ).parent().before( $langSwitch );
    $( '.cgmMForm-fileFields-'+idForm+' .cgmMForm-field.js-tr.js-tr-' + cogumelo.publicConf.langDefault + ':not("input:file")' ).parent().before( $langSwitch );

    $langSwitch = $( htmlLangSwitch ).addClass('langSwitch-file');
    $( '[type=file][form="'+idForm+'"].cgmMForm-field.js-tr.js-tr-' + cogumelo.publicConf.langDefault ).parent().before( $langSwitch );
    $( '[type=file].cgmMForm-fileFields-'+idForm+' .cgmMForm-field.js-tr.js-tr-' + cogumelo.publicConf.langDefault ).parent().before( $langSwitch );

    switchFormLang( idForm, cogumelo.publicConf.langDefault );

    $( 'ul[data-form_id="'+idForm+'"].langSwitch li' ).on( 'click', function() {
      var newLang = $( this ).data( 'lang' );
      if( newLang !== cogumelo.formController.langForm ) {
        switchFormLang( idForm, newLang );
      }
    });
  }
}


/*** Form lang select - End ***/
