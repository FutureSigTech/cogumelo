var cogumelo = cogumelo || {};

console.log(' *** ckAcepto cargado *** ');

cogumelo.ckAcepto = {
  base: 'ck-acepto-eu-law', // No cambiar
  version: '2010',
  link: '/cookies',
  keyGA: false
};
cogumelo.ckAcepto.name = cogumelo.ckAcepto.base+'-'+cogumelo.ckAcepto.version;


// Google Analytics function
function gtag() {}

cogumelo.ckAcepto.loadCookies = function loadCookies() {
  console.log('loadCookies');

  // Google Analytics load+init
  if( cogumelo.ckAcepto.keyGA && cogumelo.ckAcepto.keyGA.length > 0 ) {
    var loadJS = document.createElement('script');
    loadJS.type = 'text/javascript';
    loadJS.src = 'https://www.googletagmanager.com/gtag/js?id='+cogumelo.ckAcepto.keyGA;
    document.body.appendChild(loadJS);

    window.dataLayer = window.dataLayer || [];
    function gtag() { dataLayer.push(arguments); };
    gtag('js', new Date());
    gtag('config', cogumelo.ckAcepto.keyGA, { 'anonymize_ip': true }); // gtag('config', keyGA);
  }
};


cogumelo.ckAcepto.set = function set( acepto ) {
  var fecha = new Date();
  fecha.setTime( fecha.getTime() + (365*24*60*60*1000) );
  document.cookie = cogumelo.ckAcepto.name+'='+acepto+'; expires='+fecha.toGMTString()+'; path=/';
};

cogumelo.ckAcepto.get = function get() {
  var estado = null;

  var ckValues = document.cookie.replace(' ','');
  if( ckValues.indexOf(cogumelo.ckAcepto.name+'=') !== -1 ) {
    estado = ( ckValues.indexOf(cogumelo.ckAcepto.name+'=1') !== -1 ) ? 1 : 0;
  }

  return estado;
};

cogumelo.ckAcepto.remove = function remove() {
  var cookies = document.cookie.split(";");

  for (var i = 0; i < cookies.length; i++) {
    var cookie = cookies[i];
    if( cookie.indexOf( cogumelo.ckAcepto.base ) >= 0 ) {
      var eqPos = cookie.indexOf('=');
      var name = eqPos >= 0 ? cookie.substr( 0, eqPos ) : cookie;
      console.log('ckAceptoRemove '+name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/' );
      document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
    }
  }
};

cogumelo.ckAcepto.removeAll = function removeAll() {
  var cookies = document.cookie.split("; ");
  for (var c = 0; c < cookies.length; c++) {
    var d = window.location.hostname.split(".");
    var ckBaseName = encodeURIComponent(cookies[c].split(";")[0].split("=")[0])
    while( ckBaseName && d.length > 0 ) {
      var cookieBase = ckBaseName + '=; expires=Thu, 01-Jan-1970 00:00:01 GMT; domain=' + d.join('.') + ' ;path=';
      var p = location.pathname.split('/');
      document.cookie = cookieBase + '/';
      while (p.length > 0) {
        console.log('ckAceptoRemoveAll '+cookieBase + p.join('/') );
        document.cookie = cookieBase + p.join('/');
        p.pop();
      };
      d.shift();
    }
  }
};

cogumelo.ckAcepto.removeAll2 = function removeAll2() {
  var cookies = document.cookie.split(";");
  for (var i = 0; i < cookies.length; i++) {
    var cookie = cookies[i];
    var eqPos = cookie.indexOf('=');
    var name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
    console.log('ckAceptoRemoveAll2 ' + name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT' );
    document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT';
  }
};

cogumelo.ckAcepto.show = function show() {
  if( document.getElementById(cogumelo.ckAcepto.base+'-blk') === null ) {

    var ckBotAceptar = 'Aceptar';
    var ckBlockMsg = 'Utilizamos cookies propias y de terceros para permitirte la navegación en '+
      'nuestra web y mejorar nuestros servicios. Puedes cambiar la configuración u obtener más información '+
      '<a style="font-weight:700;color:#CCC;text-decoration:underline;" href="'+cogumelo.ckAcepto.link+'">aquí</a>. '+
      'Pulsa el botón "'+ckBotAceptar+'" para confirmar que has leído y aceptado esta información.';
    var ckBlockStyle = 'position:fixed;bottom:0;width:100%;padding:25px;'+
      'color:#CCC;background-color:rgba(22,22,22,0.9);font-size:15px;line-height:26px;'+
      'border:2px solid #EEE;z-index:99999;';
    var ckBotStyle = 'float:right;margin:5px 0 5px 20px;padding:6px;font-weight:700;'+
      'color:#333;background-color:#ACA;cursor:pointer';

    var ckBlk = document.createElement('div');
    ckBlk.id = cogumelo.ckAcepto.base+'-blk';
    ckBlk.style = ckBlockStyle;
    ckBlk.innerHTML = ''+
      '<div class="ckBlock" style="margin:0 auto;padding:15px;">'+
        '<span class="ckBlockSub" style="margin:0;padding:0;">'+
          '<a class="ckButton acepto" style="'+ckBotStyle+'">'+ckBotAceptar+'</a>'+
          '<span class="ckBlockSub">'+ckBlockMsg+'</span>'+
        '</span>'+
      '</div>'
    ;

    document.getElementsByTagName('body')[0].appendChild( ckBlk );
    document.querySelectorAll('#'+cogumelo.ckAcepto.base+'-blk a.acepto')[0].onclick = function ckBotAcepta(){
      cogumelo.ckAcepto.set(1);
      document.getElementById(cogumelo.ckAcepto.base+'-blk').remove();
      cogumelo.ckAcepto.loadCookies();
    };
  }
};


cogumelo.ckAcepto.init = function init( keyGA, version ) {
  console.log(' *** ckAcepto.init *** ');
  if( typeof keyGA !== 'undefined' ) {
    cogumelo.ckAcepto.keyGA = keyGA;
  }
  if( typeof version !== 'undefined' ) {
    cogumelo.ckAcepto.version = version;
    cogumelo.ckAcepto.name = cogumelo.ckAcepto.base+'-'+cogumelo.ckAcepto.version;
  }

  var val = cogumelo.ckAcepto.get();
  if( val !== null ) {
    if( val === 1 ) {
      cogumelo.ckAcepto.loadCookies();
    }
  }
  else {
    cogumelo.ckAcepto.remove();
    cogumelo.ckAcepto.show();
  }
};

