<?php


/**
* MailSender Class
*
* Mail sender encapsulates the original phpmailer library to make less difficult it's use
*
* @author: pablinhob
* @author: jmpmato
*/
class MailSenderGmail {

  var $phpmailer;

  public function __construct() {
    $this->phpmailer = new PHPMailer( true );

    echo "\nTODO: Pendiente de implementar por la complejidad de XOAUTH2\n\n";
    exit();

    // TODO: Pendiente de implementar por la complejidad de XOAUTH2
    // https://github.com/PHPMailer/PHPMailer/wiki/Using-Gmail-with-XOAUTH2
    // https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting
    // https://developers.google.com/gmail/xoauth2_protocol

    /*
    $this->phpmailer->IsSMTP();
    $this->phpmailer->SMTPAuth = Cogumelo::getSetupValue( 'mail:auth' );
    $this->phpmailer->Host = Cogumelo::getSetupValue( 'mail:host' );
    $this->phpmailer->Port = Cogumelo::getSetupValue( 'mail:port' );
    $this->phpmailer->Username = Cogumelo::getSetupValue( 'mail:user' );
    $this->phpmailer->Password = Cogumelo::getSetupValue( 'mail:pass' );
    $this->phpmailer->SMTPKeepAlive = true;
    $this->phpmailer->CharSet = 'UTF-8';
    */
  }


  /**
   * Send mail message
   *
   * @param mixed $adresses are string of array of strings with recipient of mail sent
   * @param string $subject is the subject of the mail
   * @param string $bodyPlain of the e-mail
   * @param string $bodyHtml of the e-mail
   * @param mixed $files string or array of strings of filepaths
   * @param string $from_name sender name. Default is specified in conf.
   * @param string $from_maiol sender e-mail. Default especified in conf.
   *
   * @return boolean $mailResult
   **/
  public function send( $adresses, $subject, $bodyPlain = false, $bodyHtml = false, $files = false, $from_name = false, $from_mail = false ) {
    $mailResult = false;

    if( !$from_name ){
      $from_name = Cogumelo::getSetupValue( 'mail:fromName' );
    }

    if( !$from_mail ) {
      $from_mail = Cogumelo::getSetupValue( 'mail:fromEmail' );
    }


    // If $adresses is an array of adresses include all into mail
    if( is_array($adresses) ) {
      foreach( $adresses as $adress ) {
        $this->phpmailer->addBCC($adress);
      }
    }
    else {
      $this->phpmailer->AddAddress($adresses);
    }

    if( $files ) {
      if( is_array($files) ) {
        foreach( $files as $file ) {
          $this->phpmailer->AddAttachment($file);
        }
      }
      else {
        $this->phpmailer->AddAttachment($files);
      }
    }

    $this->phpmailer->SetFrom( $from_mail, $from_name );
    $this->phpmailer->AddReplyTo( $from_mail, $from_name );

    $this->phpmailer->Subject = $subject;

    if( $bodyHtml ) {
      $this->phpmailer->isHTML( true );
      $this->phpmailer->Body = $bodyHtml;
      if( $bodyPlain ) {
        $this->phpmailer->AltBody = $bodyPlain;
      }
    }
    else {
      $this->phpmailer->Body = $bodyPlain;
    }

    // $this->phpmailer->SMTPDebug = 2; // SOLO TEST - EL FORM NO FUNCIONA CON ESTO!!!

    $mailResult = false;
    try{
      $mailResult = $this->phpmailer->Send();
    } catch( Exception $e ) {
      $mailResult = false;
      Cogumelo::debug( 'Mail ERROR('.$this->phpmailer->MessageID.'): Exception: '.$e->getMessage(), 3 );
    }


    if( $mailResult ) {
      Cogumelo::debug( 'Mail Sent id='.$this->phpmailer->MessageID.' '.var_export($adresses, true), 3 );
    }
    else {
      Cogumelo::debug( 'Mail ERROR('.$this->phpmailer->MessageID.'): Adresses: '.var_export($adresses, true), 3 );
      Cogumelo::debug( 'Mail ERROR('.$this->phpmailer->MessageID.'): Subject: '.$subject, 3 );
      Cogumelo::debug( 'Mail ERROR('.$this->phpmailer->MessageID.'): ErrorInfo: '.$this->phpmailer->ErrorInfo, 3 );
      Cogumelo::log( 'Error sending mail' );
    }

    $this->phpmailer->ClearAllRecipients();

    return $mailResult;
  }
}
