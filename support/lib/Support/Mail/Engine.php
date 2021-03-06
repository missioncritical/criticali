<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package support */

/**
 * Interface for sending email
 */
abstract class Support_Mail_Engine {

  /**
   * Factory method for accessing the configured engine
   */
  public static function create() {
    $engine = Cfg::get('mail/engine');
    if ($engine == 'SMTP')
      return new Support_Mail_SMTPEngine();
    else
      return new Support_Mail_MailEngine();
  }

  /**
   * Send an email message
   *
   * @param MailMsg $msg  The mail message to send
   */
  abstract public function send($msg);
}

?>