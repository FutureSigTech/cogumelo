var cogumelo = cogumelo || {};

// console.log(' *** ckAcepto cargado *** ');

cogumelo.ckAcepto = {
  base: 'ck-acepto-eu-law', // No cambiar
  version: '2010',
  link: '/cookies',
  txtAceptar: 'Aceptar',
  keyGA: false
};
cogumelo.ckAcepto.name = cogumelo.ckAcepto.base + '-' + cogumelo.ckAcepto.version;


// Google Analytics function
function gtag() {}

cogumelo.ckAcepto.set = function set( acepto ) {
  var fecha = new Date();
  fecha.setTime( fecha.getTime() + (365*24*60*60*1000) );
  document.cookie = this.name + '=' + acepto + '; expires=' + fecha.toGMTString() + '; path=/; SameSite=Strict';
};

cogumelo.ckAcepto.get = function get() {
  var estado = null;

  var ckValues = document.cookie.replace( ' ', '' );
  if( ckValues.indexOf( this.name + '=' ) !== -1 ) {
    estado = ( ckValues.indexOf( this.name + '=1' ) !== -1 ) ? 1 : 0;
  }

  return estado;
};

cogumelo.ckAcepto.remove = function remove() {
  var cookies = document.cookie.split( ';' );

  for( var i = 0; i < cookies.length; i++ ) {
    var cookie = cookies[ i ];
    if( cookie.indexOf( this.base ) >= 0 ) {
      var eqPos = cookie.indexOf( '=' );
      var name = eqPos >= 0 ? cookie.substr( 0, eqPos ) : cookie;
      // console.log( 'ckAceptoRemove ' + name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/' );
      document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
    }
  }
};

cogumelo.ckAcepto.removeAll = function removeAll() {
  // var cookies = document.cookie.split("; ");
  // for (var c = 0; c < cookies.length; c++) {
  //   var d = window.location.hostname.split(".");
  //   var ckBaseName = encodeURIComponent(cookies[c].split(";")[0].split("=")[0])
  //   while( ckBaseName && d.length > 0 ) {
  //     var cookieBase = ckBaseName + '=; expires=Thu, 01-Jan-1970 00:00:01 GMT; domain=' + d.join('.') + ' ;path=';
  //     var p = location.pathname.split('/');
  //     document.cookie = cookieBase + '/';
  //     while (p.length > 0) {
  //       console.log('ckAceptoRemoveAll '+cookieBase + p.join('/') );
  //       document.cookie = cookieBase + p.join('/');
  //       p.pop();
  //     };
  //     d.shift();
  //   }
  // }
};

cogumelo.ckAcepto.removeAll2 = function removeAll2() {
  // var cookies = document.cookie.split(";");
  // for (var i = 0; i < cookies.length; i++) {
  //   var cookie = cookies[i];
  //   var eqPos = cookie.indexOf('=');
  //   var name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
  //   console.log('ckAceptoRemoveAll2 ' + name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT' );
  //   document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT';
  // }
};



cogumelo.ckAcepto.showPanel = function showPanel() {
  // Obligatorio: El ID exterior tiene que ser (this.base + '-panel')
  // ...o reemplazar cogumelo.ckAcepto.hidePanel

  if( document.getElementById( this.base + '-panel' ) === null ) {

    var ckBlockMsg = 'Utilizamos cookies propias y de terceros para analizar sus preferencias y hábitos de navegación.' +
      'Puede cambiar la configuración, seleccionar de manera específica qué cookies consiente y cuáles no y obtener más información. Puedes cambiar la configuración u obtener más información ' +
      '<a style="font-weight:700;color:#CCC;text-decoration:underline;" href="' + this.link + '">aquí</a>. ' +
      'Pulsa el botón "' + this.txtAceptar + '" para consentir el uso de todas las cookies.';
    var ckBlockStyle = 'position:fixed;bottom:0;width:100%;padding:25px;' +
      'color:#CCC;background-color:rgba(22,22,22,0.9);font-size:15px;line-height:26px;' +
      'border:2px solid #EEE;z-index:99999;';
    var ckBotStyle = 'float:right;margin:5px 0 5px 20px;padding:6px;font-weight:700;' +
      'color:#333;background-color:#ACA;cursor:pointer';

    var ckBlk = document.createElement( 'div' );
    ckBlk.id = this.base + '-panel';
    ckBlk.style = ckBlockStyle;
    ckBlk.innerHTML = '' +
      '<div class="ckBlock" style="margin:0 auto;padding:15px;">' +
        '<span class="ckBlockSub" style="margin:0;padding:0;">' +
          '<a class="ckButton acepto" style="' + ckBotStyle + '">' + this.txtAceptar + '</a>' +
          '<span class="ckBlockSub">' + ckBlockMsg + '</span>' +
        '</span>' +
      '</div>'
    ;

    document.getElementsByTagName( 'body' )[ 0 ].appendChild( ckBlk );
    document.querySelectorAll( '#' + this.base + '-panel a.acepto' )[ 0 ].onclick = function ckBotAcepta() {
      cogumelo.ckAcepto.hidePanel();
      cogumelo.ckAcepto.set( 1 );
      cogumelo.ckAcepto.loadExternals();
    };
  }
};

cogumelo.ckAcepto.hidePanel = function hidePanel() {
  document.getElementById( this.base + '-panel' ).remove();
};

cogumelo.ckAcepto.loadExternals = function loadExternals() {
  // console.log( 'cogumelo.ckAcepto.loadExternals' );

  // Google Analytics load+init
  if( this.keyGA && this.keyGA.length > 0 ) {
    var loadJS = document.createElement( 'script' );
    loadJS.type = 'text/javascript';
    loadJS.src = 'https://www.googletagmanager.com/gtag/js?id=' + this.keyGA;
    document.body.appendChild( loadJS );

    window.dataLayer = window.dataLayer || [];
    function gtag() { dataLayer.push( arguments ); };
    gtag( 'js', new Date() );
    gtag( 'config', this.keyGA, { 'anonymize_ip': true } ); // gtag('config', keyGA);
  }
};



cogumelo.ckAcepto.init = function init( keyGA, version ) {
  // console.log(' *** cogumelo.ckAcepto.init *** ');
  if( typeof keyGA !== 'undefined' ) {
    this.keyGA = keyGA;
  }
  if( typeof version !== 'undefined' ) {
    this.version = version;
    this.name = this.base + '-' + this.version;
  }

  var val = this.get();
  if( val !== null ) {
    if( val === 1 ) {
      this.loadExternals();
    }
  }
  else {
    this.remove();
    this.showPanel();
  }
};

