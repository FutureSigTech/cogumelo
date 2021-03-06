var cogumelo = cogumelo || {};



// PREVENTS JQUERY CONFLICTS WITH MULTIPLE LOADS
if( jqueryIsLoaded === true ) {
  jQuery = originalJQueryObject;
  $ = originalJQueryObject;
}
else {
  originalJQueryObject = $ = jQuery = $.noConflict();
}



// prevent declares twice
if( typeof cogumelo.includes == "undefined"  ) {


  cogumelo.includedPaths = [];

  cogumelo.includes = function( includes ) {
    var scriptsLoadNow = [];


    // AVOID JS LOAD TIMESTAMP
    $.ajaxPrefilter(function( options, originalOptions, jqXHR ) {
      if ( options.dataType == 'script' || originalOptions.dataType == 'script' ) {
          options.cache = true;
      }
    });


    $.each(includes, function(i,e) {



      if( jQuery.inArray( e.src, cogumelo.includedPaths ) === -1 ) {

        if(e.type=='text/css' && e.rel=='stylesheet/less') {

          less.sheets.push(
            $('<link />', {
              href: e.src,
              rel: 'stylesheet/less',
              async:true,
              type: 'text/css'
            }).appendTo('head')[0]
          );

          cogumelo.includedPaths.push(e.src);
        }
        else if(e.type=='text/css') {
          $("<link/>", {
            rel: e.rel,
            async:true,
            type: "text/css",
            href: e.src
          }).appendTo("head");

          cogumelo.includedPaths.push(e.src);
        }
        else if(e.type=='text/javascript') {


          var jsLink = $("<script type='text/javascript' src='"+e.src+"'>");
          $("head").append(jsLink);

          cogumelo.includedPaths.push(e.src);
        }
      }
      else {
        cogumelo.log('Library lready loaded:', e.src);
      }
    });


    // this made $().ready() methods work
    $.holdReady( false );

    // refresh less if exist
    if(typeof less !== "undefined"){
      less.refresh();
    }
  };
}
