<?php


class FormController implements Serializable {

  private $name = false;
  private $id = false;
  private $cgIntFrmId = false;
  private $action = false;
  private $method = 'post';
  private $enctype = 'multipart/form-data';
  private $fields = array();
  //private $validation = array( 'rules' => array(), 'messages' => array() );
  private $rules = array();
  private $messages = array();

  // POST submit
  private $postValues = false;
  //private $evaluateRuleMethod = false;
  private $validationObj = null;
  private $rulesErrors = array();



  function __construct( $name = false, $action = false, $cgIntFrmId = false, $formPost = false ) {
    if( $cgIntFrmId ) {
      $this->loadFromSession( $cgIntFrmId );
      if( $formPost ) {
        $this->loadPostValues( $formPost );
      }
    }
    else {
      $this->cgIntFrmId = crypt( uniqid().'---'.session_id(), 'cf' );
      $this->name = $name;
      $this->id = $name;
      if( $action ) {
        $this->action = $action;
      }
      $this->setField( 'cgIntFrmId', array( 'type' => 'text', 'value' => $this->cgIntFrmId ) );
    }
  }

  public function serialize() {
    $data = array();

    $data[] = $this->name;
    $data[] = $this->id;
    $data[] = $this->cgIntFrmId;
    $data[] = $this->action;
    $data[] = $this->method;
    $data[] = $this->enctype;
    $data[] = $this->fields;
    $data[] = $this->rules;
    $data[] = $this->messages;
    $data[] = $this->postValues;

    return serialize( $data );
  }

  public function unserialize( $dataSerialized ) {
    $data = unserialize( $dataSerialized );

    $this->name = array_shift( $data );
    $this->id = array_shift( $data );
    $this->cgIntFrmId = array_shift( $data );
    $this->action = array_shift( $data );
    $this->method = array_shift( $data );
    $this->enctype = array_shift( $data );
    $this->fields = array_shift( $data );
    $this->rules = array_shift( $data );
    $this->messages = array_shift( $data );
    $this->postValues = array_shift( $data );
  }

  public function saveToSession() {
    $formSessionId = 'CGFSI_'.$this->getIntFrmId();
    $_SESSION[ $formSessionId ] = $this->serialize();
    //error_log( $_SESSION[ $formSessionId ] );

    return $formSessionId;
  }

  public function loadFromSession( $cgIntFrmId ) {
    $formSessionId = 'CGFSI_'.$cgIntFrmId;
    $this->unserialize( $_SESSION[ $formSessionId ] );
    //error_log( $_SESSION[ $formSessionId ] );
  }

  public function setField( $fieldName, $params = false ) {

    if( !isset( $this->fields[$fieldName]['id'] ) ||
      ( isset( $this->fields[$fieldName]['name'] ) && $this->fields[$fieldName]['name'] === $this->fields[$fieldName]['id'] ) )
    {
      $this->fields[$fieldName]['id'] = $fieldName;
    }

    $this->fields[$fieldName]['name'] = $fieldName;
    if( $params ) {
      foreach( $params as $key => $value ) {
        $this->fields[$fieldName][$key] = $value;
      }
    }
    if( !isset( $this->fields[$fieldName]['type'] ) ) {
      $this->fields[$fieldName]['type'] = 'text';
    }
  } // function setField

  public function setValidationRule( $fieldName, $ruleName, $ruleParams = true ) {
    $this->rules[$fieldName][$ruleName] = $ruleParams;
  }

  public function setValidationMsg( $fieldName, $msg ) {
    $this->messages[$fieldName] = $msg;
  }


  public function loadPostValues( $formPost ) {
    $this->postValues = $formPost;
  }

  public function getIntFrmId() {
    return $this->cgIntFrmId;
  }

  public function getHtmlForm() {
    $html='';

    $html .= $this->getHtmpOpen()."\n";
    $html .= $this->getHtmlFields()."\n";
    $html .= $this->getHtmlClose()."\n";

    $html .= $this->getJqueryValidationJS()."\n";

    return $html;
  }

  public function getHtmpOpen() {
    $html='';

    $html .= '<form name="'.$this->name.'" id="'.$this->id.'" sg="'.$this->cgIntFrmId.'"';
    if( $this->action ) {
      $html .= ' action="'.$this->action.'"';
    }
    $html .= ' method="'.$this->method.'">';

    return $html;
  }

  public function getHtmlFields() {
    $html = '';
    foreach( $this->fields as $fieldName => $fieldParams ) {
      $html .= '<div>'.$this->getHtmlField($fieldName)."</div>\n";
    }
    return $html;
  }

  public function getHtmlFieldsArray() {
    $html = array();
    foreach( $this->fields as $fieldName => $fieldParams ) {
      $html[] = '<div class="ffn-'.$fieldName.'">'.$this->getHtmlField($fieldName)."</div>\n";
    }
    return $html;
  }


  public function getHtmlField( $fieldName ) {
    $html = '';

    $htmlFieldArray = $this->getHtmlFieldArray( $fieldName );

    if( isset( $htmlFieldArray['label'] ) ) {
      $html .= $htmlFieldArray['label']."<br>\n";
    }
    switch( $htmlFieldArray['fieldType'] ) {
      case 'select':
        $html .= $htmlFieldArray['inputOpen']."\n";
        foreach( $htmlFieldArray['options'] as $optionAndText ) {
          $html .= $optionAndText['input']."\n";
        }
        $html .= $htmlFieldArray['inputClose'];
        break;
      case 'checkbox':
      case 'radio':
        foreach( $htmlFieldArray['options'] as $inputAndText ) {
          $html .= $inputAndText['input'] . $inputAndText['text'];
        }
        break;
      case 'textarea':
        $html .= $htmlFieldArray['inputOpen'] . $htmlFieldArray['value'] . $htmlFieldArray['inputClose'];
        break;
      default:
        $html .= $htmlFieldArray['input'];
        break;
    }

    return $html;
  } // function getHtmlField





  public function getHtmlFieldArray( $fieldName ) {
    $html = array();

    $field = $this->fields[$fieldName];

    $html['fieldType'] = $field['type'];

    if( isset( $field['label'] ) && isset( $field['id'] ) ) {
      $html['label'] = '<label for="'.$field['id'].'">'.$field['label'].'</label>';
    }
    switch( $field['type'] ) {

      case 'select':
        $html['inputOpen'] = '<select name="'.$field['name'].'" id="'.$field['id'].'"';
        if( isset( $field['size'] ) ) { $html['inputOpen'] .= ' size="'.$field['size'].'"'; }
        if( isset( $field['disabled'] ) ) { $html['inputOpen'] .= ' disabled="disabled"'; }
        if( isset( $field['readonly'] ) ) { $html['inputOpen'] .= ' readonly="readonly"'; }
        if( isset( $field['multiple'] ) ) { $html['inputOpen'] .= ' multiple="multiple"'; }
        $html['inputOpen'] .= '>';

        $html['options'] = array();
        foreach( $field['options'] as $val => $text ) {
          $html['options'][$val] = array(
            'input' => '<option value="'.$val.'">'.$text.'</option>',
            'text' => $text
            );
        }

        // Colocamos los selected
        if( isset( $field['value'] ) || is_array( $field['value'] ) ) {
          $values = is_array( $field['value'] ) ? $field['value'] : array( $field['value'] );
          foreach( $values as $val ) {
            $html['options'][$val]['input'] = str_replace( 'option value="'.$val.'"',
              'option value="'.$val.'" selected="selected"', $html['options'][$val]['input'] );
            if( !isset( $field['multiple'] ) ) {
              break; // Si no es multiple, solo puede tener 1 valor
            }
          }
        }

        $html['inputClose'] = '</select><!-- select '.$field['name'].' -->';

        // Creamos ya la regla que controla el contenido
        $this->setValidationRule( $field['name'], 'inArray', array_keys( $field['options'] ) );
        break;

      case 'checkbox':
      case 'radio':
        $html['options'] = array();
        foreach( $field['options'] as $val => $text ) {
          $html['options'][$val] = array();
          $html['options'][$val]['input'] = '<input type="'.$field['type'].'" name="'.$field['name'].'" value="'.$val.'"';
          $html['options'][$val]['input'] .= isset( $field['id'] ) ? ' id="'.$field['id'].'"' : '';
          if( isset( $field['placeholder'] ) ) { $html['options'][$val]['input'] .= ' placeholder="'.$field['placeholder'].'"'; }
          if( isset( $field['maxlength'] ) ) { $html['options'][$val]['input'] .= ' maxlength="'.$field['maxlength'].'"'; }
          if( isset( $field['disabled'] ) ) { $html['options'][$val]['input'] .= ' disabled="disabled"'; }
          if( isset( $field['readonly'] ) ) { $html['options'][$val]['input'] .= ' readonly="readonly"'; }
          $html['options'][$val]['input'] .= '>';
          $html['options'][$val]['text'] = $text;
        }

        // Colocamos los checked
        if( isset( $field['value'] ) || is_array( $field['value'] ) ) {
          $values = is_array( $field['value'] ) ? $field['value'] : array( $field['value'] );
          foreach( $values as $val ) {
            $html['options'][$val]['input'] = str_replace( 'name="'.$field['name'].'" value="'.$val.'"',
              'name="'.$field['name'].'" value="'.$val.'" checked="checked"', $html['options'][$val]['input'] );
            if( $field['type']=='radio' ) {
              break; // Radio solo puede tener 1 valor
            }
          }
        }
        break;

      case 'textarea':
        $html['inputOpen'] = '<textarea name="'.$field['name'].'" id="'.$field['id'].'"';
        if( isset( $field['placeholder'] ) ) { $html['inputOpen'] .= ' placeholder="'.$field['placeholder'].'"'; }
        if( isset( $field['disabled'] ) ) { $html['inputOpen'] .= ' disabled="disabled"'; }
        if( isset( $field['readonly'] ) ) { $html['inputOpen'] .= ' readonly="readonly"'; }
        if( isset( $field['cols'] ) ) { $html['inputOpen'] .= ' cols="'.$field['cols'].'"'; }
        if( isset( $field['rows'] ) ) { $html['inputOpen'] .= ' rows="'.$field['rows'].'"'; }
        $html['inputOpen'] .= '>';
        $html['value'] = isset( $field['value'] ) ? $field['value'] : '';
        $html['inputClose'] = '</textarea>';
        break;

      //case 'file':
      //  break;

      default:
        // button, file, hidden, password, range, text
        // color, date, datetime, datetime-local, email, image, month, number, search, tel, time, url, week
        $html['input'] = '<input name="'.$field['name'].'" id="'.$field['id'].'"';
        if( isset( $field['value'] ) ) { $html['input'] .= ' value="'.$field['value'].'"'; }
        if( isset( $field['placeholder'] ) ) { $html['input'] .= ' placeholder="'.$field['placeholder'].'"'; }
        if( isset( $field['maxlength'] ) ) { $html['input'] .= ' maxlength="'.$field['maxlength'].'"'; }
        if( isset( $field['disabled'] ) ) { $html['input'] .= ' disabled="disabled"'; }
        if( isset( $field['readonly'] ) ) { $html['input'] .= ' readonly="readonly"'; }
        $html['input'] .= ' type="'.$field['type'].'">';
        break;
    }

    return $html;
  } // function getHtmlFieldArray






  public function getHtmlPartialField( $fieldName, $paramValue ) {
    $html = '';
    $field = $this->fields[$fieldName];

    switch( $field['type'] ) {

      case 'checkbox':
      case 'radio':
        if( isset( $field['options'][$paramValue] ) ) {
          $val = $paramValue;
          $text = $field['options'][$val];
          $html .= '<input type="'.$field['type'].'" name="'.$field['name'].'" value="'.$val.'"';
          if( isset( $field['id'] ) ) { $html .= ' id="'.$field['id'].'"'; }
          if( isset( $field['placeholder'] ) ) { $html .= ' placeholder="'.$field['placeholder'].'"'; }
          if( isset( $field['maxlength'] ) ) { $html .= ' maxlength="'.$field['maxlength'].'"'; }
          if( isset( $field['disabled'] ) ) { $html .= ' disabled="disabled"'; }
          if( isset( $field['readonly'] ) ) { $html .= ' readonly="readonly"'; }
          $html .= '>'.$text;
        }
        break;
    }

    return $html;
  } // function getHtmlPartialField

  public function getHtmlClose() {
    $html = '</form><!-- '.$this->name.' -->';
    return $html;
  }


  public function getJqueryValidationJS() {
    $html = '';

    $separador = '';

    $html .= '<!-- Validate form '.$this->name.' -->'."\n";
    $html .= '<script>'."\n";

    $html .= '$().ready(function() {'."\n";

    $html .= '  $validateForm_'.$this->id.' = setValidateForm( "'.$this->id.'", ';
    $html .= ( count( $this->rules ) > 0 ) ? json_encode( $this->rules ) : 'false';
    $html .= ', ';
    $html .= ( count( $this->messages ) > 0 ) ? json_encode( $this->messages ) : 'false';
    $html .= ' );'."\n";

    /*
    if( count( $this->messages ) > 0 ) {
      $html .= $separador.'    messages: '.json_encode( $this->messages )."\n";
      $separador = '    ,'."\n";
    }
    */
    $html .= '  console.log( $validateForm_'.$this->id.' );'."\n";

    $html .= '});'."\n";
    $html .= '</script>'."\n";

    $html .= '<!-- Validate form '.$this->name.' - END -->'."\n";

    return $html;
  } // function getJqueryValidationJS







/**
  ***********************************************************
  VALIDATION
  ***********************************************************
**/

  /*
  public function setEvaluateRuleMethod( $evaluateRuleMethod ) {
    error_log( 'setEvaluateRuleMethod' );
    error_log( print_r( $evaluateRuleMethod, true ) );
    $this->evaluateRuleMethod = $evaluateRuleMethod;
  }
  */

  public function setValidationObj( $validationObj ) {
    $this->validationObj = $validationObj;
  }

  public function getFieldValue( $fieldName ) {
    $value = isset( $this->postValues[ $fieldName ] ) ? $this->postValues[ $fieldName ] : null;
    return $value;
  }

  public function isRequiredField( $fieldName ) {
    return isset( $this->rules[ $fieldName ][ 'required' ] );
  }


  /**
   * Metodo de validacion global segun las reglas cargadas
   *
   * @param mixed $param (optinal)
   * @return bool $validate
   **/
  public function validateForm( $formPost = false ) {
    error_log( 'validateForm:' );
    //error_log( print_r( $this->rules, true ) );
    //error_log( print_r( $this->validationObj, true ) );

    $validate = true;

    if( $formPost ) {
      $this->loadPostValues( $formPost );
    }

    // Tienen que existir los validadores y los valores del form
    if( is_object( $this->validationObj ) && $this->postValues!==false ) {
      foreach( $this->rules as $fieldName => $fieldRules ) {
        $fieldValidate = false;
        $value = $this->getFieldValue( $fieldName );
        error_log( 'validando '.$fieldName.' = '.$value );
        if( $value === '' && !isRequiredField( $fieldName ) ) {
          $fieldValidate = true;
        }
        else {
          $fieldValidate = true;
          foreach( $fieldRules as $ruleName => $ruleParams ) {
            error_log( 'evaluateRule( '.$ruleName.', '.$value.', '.$fieldName.', '.$ruleParams.' )' );

            $fieldRuleValidate = $this->validationObj->evaluateRule( $ruleName, $value, $fieldName, $ruleParams );
            if( $ruleName === 'equalTo' ) {
              $fieldRuleValidate = ( $value === $this->getFieldValue( $ruleParams ) );
             }
            error_log( print_r( $fieldRuleValidate, true ) );

            $this->rulesErrors[ $fieldName ][ $ruleName ] = $fieldRuleValidate;
            if( !$fieldRuleValidate ) {
              $fieldValidate = false;
              break;
            }
          }
        }

        if( !$fieldValidate ) {
          $validate = false;
        }

      } // foreach( $this->rules as $fieldName => $fieldRules )
    } // if( is_object( $this->validationObj ) && $this->postValues!==false )
    else {
      $validate = false;
      error_log( 'FALTA CARGAR EL POST DEL FORM O LOS VALIDADORES' );
    }

    return $validate;
  } // function validateForm( $formPost = false ) {

  public function getJVErrors() {
    $errors = array();

    foreach( $this->rules as $fieldName => $fieldRules ) {
      foreach( $fieldRules as $ruleName => $ruleParams ) {
        $msgRule = '';
        if( isset( $this->rulesErrors[ $fieldName ][ $ruleName ] ) &&
          $this->rulesErrors[ $fieldName ][ $ruleName ] === false )
        {
          $errors[] = array( 'fieldName' => $fieldName, 'msgRule' => $ruleName, 'ruleParams' => $ruleParams, 'JVshowErrors' => array( $fieldName => $msgRule ) );
        }
      }
    }

    return $errors;
  }

} // class FormController implements Serializable {
