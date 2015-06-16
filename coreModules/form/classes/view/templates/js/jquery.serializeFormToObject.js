$.fn.serializeFormToObject = function () {
  var ser = {};

  var fa = this.serializeArray();


  $.each( fa, function () {
    if( ser[ this.name ] === undefined ) {
      ser[ this.name ] = {};
      ser[ this.name ].value = this.value || '';
    }
    else {
      if( !ser[ this.name ].value.push ) {
        ser[ this.name ].value = [ ser[ this.name ].value ];
      }
      ser[ this.name ].value.push( this.value || '' );
    }
  });


  $( ':input[form="' + this.attr( 'id' ) + '"]' ).each(
    function( i, elem ) {
      if( elem.name !== undefined && elem.name !== '' ) {
        // Set false value
        if( ser[ elem.name ] === undefined ) {
          ser[ elem.name ] = {};
          ser[ elem.name ].value = false;
        }
        // Order select values
        if( elem.multiple === true  && ser[ elem.name ].value.push ) {
          ser[ elem.name ].value = $( elem ).find( 'option' ).filter( ':selected').toArray()
            .sort( function( a, b ) { return( parseInt( $( a ).data( 'order' ) ) - parseInt( $( b ).data( 'order' ) ) ); } )
            .map( function( e ) { return( e.value ); } );
        }

        $dataInfo = $( elem ).data();
        ser[ elem.name ].dataInfo = false;

        $.each( $dataInfo, function( k, v ) {
          if( ser[ elem.name ].dataInfo === false ) {
            ser[ elem.name ].dataInfo = {};
          }
          ser[ elem.name ].dataInfo[ k ] = v;
        } );
      }
    }
  );


  console.log( 'serializeFormToObject: ', ser );
  return ser;
};


