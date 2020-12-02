$.fn.serializeFormToObject = function () {
  var formDataObj = {};

  var formDataArray = this.serializeArray();
  cogumelo.log( 'serializeFormToObject: formDataArray', formDataArray );

  var getDataInfo = function getDataInfo( elem ) {
    // PELIGRO: Los valores recuperados por .data() no son fiables!!!
    var dataInfo = false;

    var attrTmp = elem.attributes;
    $.each( attrTmp, function( i, attr ) {
      if( attr.name.indexOf('data-') === 0 ) {
        if( dataInfo === false ) {
          dataInfo = {};
        }
        dataInfo[ attr.name ] = attr.value;
      }
    } );

    return dataInfo;
  };

  $.each( formDataArray, function () {
    if( formDataObj[ this.name ] === undefined ) {
      formDataObj[ this.name ] = {};
      formDataObj[ this.name ].value = this.value || '';
    }
    else {
      if( !formDataObj[ this.name ].value.push ) {
        formDataObj[ this.name ].value = [ formDataObj[ this.name ].value ];
      }
      formDataObj[ this.name ].value.push( this.value || '' );
    }
  });


  $( ':input[form="' + this.attr( 'id' ) + '"]' ).each(
    // eslint-disable-next-line complexity
    function( i, elem ) {
      if( elem.name !== undefined && elem.name !== '' ) {
        var $elem = $( elem );

        // Set false value
        if( formDataObj[ elem.name ] === undefined ) {
          formDataObj[ elem.name ] = {};
          formDataObj[ elem.name ].value = false;
        }

        // Order select values
        if( elem.nodeName==='SELECT' && elem.multiple===true && $elem.hasClass('cgmMForm-order') && formDataObj[ elem.name ].value.push ) {
          cogumelo.log( 'Ordenando '+ elem.name, formDataObj[ elem.name ] );
          // Array de options
          formDataObj[ elem.name ].value = $elem.find( 'option' ).filter( ':selected').toArray()
            .sort( function( a, b ) { return( parseInt( $( a ).data( 'order' ) ) - parseInt( $( b ).data( 'order' ) ) ); } )
            .map( function( e ) { return( e.value ); } );
        }

        // Cargamos la informacion del los atributos "data-*"
        var dataInfo = getDataInfo( elem );
        if( dataInfo ) {
          formDataObj[ elem.name ].dataInfo = dataInfo;
        }

        // Cargamos la informacion del los atributos "data-*" en campos con opciones
        if( $elem.is('select') ) {
          var dataMultiInfo = {};
          $elem.find(':selected').each( function() {
            var dataInfo = getDataInfo( this );
            if( dataInfo ) {
              dataMultiInfo[ this.value ] = dataInfo;
            }
          } );
          cogumelo.log(dataMultiInfo.elements);
          if( !jQuery.isEmptyObject( dataMultiInfo ) ) {
            formDataObj[ elem.name ].dataMultiInfo = dataMultiInfo;
          }
        }
      }
    }
  );


  // Google reCAPTCHA
  $( '.g-recaptcha[form="' + this.attr( 'id' ) + '"] [name="g-recaptcha-response"]' ).each(
    function( i, elem ) {
      if( elem.name !== undefined && elem.name !== '' ) {
        // Set value
        if( formDataObj[ elem.name ] === undefined ) {
          formDataObj[ elem.name ] = {};
        }
        if( formDataObj[ elem.name ].value === undefined ) {
          formDataObj[ elem.name ].value = elem.value;
        }
        grecaptcha.reset();
      }
    }
  );


  cogumelo.log( 'serializeFormToObject: formDataObj', formDataObj );
  return formDataObj;
};
