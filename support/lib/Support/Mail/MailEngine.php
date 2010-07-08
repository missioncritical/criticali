<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package support */

/**
 * Default mail engine uses PHP's builtin mail function
 */
class Support_Mail_MailEngine {
  /**
   * Send an email message
   *
   * @param MailMsg $msg  The mail message to send
   */
  public function send($msg) {
    // send the mail
    if ( $msg->has_header('From') && (!Cfg::get('mail/disable-from-flag')) )
      $result = mail(implode(', ', $msg->recipients()), $msg->subject(),
                     $msg->assemble_body(), $msg->assemble_header(),
                     '-f'.$msg->clean($msg->header('From')));
    else
      $result = mail(implode(', ', $msg->recipients()), $msg->subject(),
                     $msg->assemble_body(), $msg->assemble_header());

    if ($result === FALSE)
      throw new Exception("Could not send mail");
  }
}

?>