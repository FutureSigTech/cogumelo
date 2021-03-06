cogumelo.log('Core form ckeditor-config.js');

CKEDITOR.editorConfig = function( config ) {
  // http://docs.ckeditor.com/#!/api/CKEDITOR.config
  config.toolbarGroups = [
    { name: 'clipboard', groups: [ 'clipboard', 'undo' ] },
    { name: 'editing', groups: [ 'find', 'selection', 'spellchecker', 'editing' ] },
    { name: 'forms', groups: [ 'forms' ] },
    { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
    { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi', 'paragraph' ] },
    { name: 'links', groups: [ 'links' ] },
    { name: 'insert', groups: [ 'insert' ] },
    { name: 'styles', groups: [ 'styles' ] },
    { name: 'colors', groups: [ 'colors' ] },
    { name: 'others', groups: [ 'others' ] },
    { name: 'about', groups: [ 'about' ] },
    { name: 'tools', groups: [ 'tools' ] },
    { name: 'document', groups: [ 'mode', 'document', 'doctools' ] }
  ];

  config.removeButtons = 'Save,NewPage,Preview,Print,Templates,Cut,Copy,Redo,Undo,Find,Replace,SelectAll,Scayt,Form,HiddenField,Checkbox,Radio,TextField,Textarea,Select,Button,ImageButton,Strike,Subscript,Superscript,CopyFormatting,Blockquote,BidiLtr,BidiRtl,Language,Flash,Smiley,SpecialChar,PageBreak,Styles,Format,Font,FontSize,TextColor,BGColor,About,Paste,PasteText,PasteFromWord,CreateDiv,HorizontalRule';
  config.removeDialogTabs = 'image:advanced;link:advanced;iframe:advanced';
  config.removePlugins = 'elementspath, autogrow';

  // Set the most common block elements.
  config.format_tags = 'p;h1;h2;h3;h4;h5;pre';
  config.entities = false;

  config.height = '150';
};


// bootstrap-ckeditor-modal-fix
// hack to fix ckeditor/bootstrap compatiability bug when ckeditor appears in a bootstrap modal dialog
//
// Include this AFTER both bootstrap and ckeditor are loaded.
$.fn.modal.Constructor.prototype.enforceFocus = function() {
  var modalThis = this;
  $( document ).off( 'focusin.modal' );
  $( document ).on( 'focusin.modal', function( e ) {
    if( modalThis.$element[0] !== e.target
      && !modalThis.$element.has(e.target).length
      && !$(e.target.parentNode).hasClass('cke_dialog_ui_input_select')
      && !$(e.target.parentNode).hasClass('cke_dialog_ui_input_text') )
    {
      modalThis.$element.focus();
    }
  });
};
