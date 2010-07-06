<?php

/**
 * Simple SMTP-based mail engine
 */
class Support_Mail_SMTPEngine {
  protected $sock;
  protected $capabilities;
  protected $nl;

  /**
   * Constructor
   */
  public function __construct() {
    $this->nl = Cfg::get('mail/line-ending', "\r\n");
  }

  /**
   * Send an email message
   *
   * @param MailMsg $msg  The mail message to send
   */
  public function send($msg) {
    // build the full email
    $email = $msg->assemble_header();

    if (!$msg->has_header('To'))
      $email .= "To: " . implode(', ', $msg->recipients()) . $this->nl;
    if (!$msg->has_header('Subject'))
      $email .= "Subject: " . $msg->subject() . $this->nl;
    if (!$msg->has_header('Date'))
      $email .= "Date: " . date('D, d M Y H:i:s O') . $this->nl;
    if (!$msg->has_header('Message-Id'))
      $email .= "Message-Id: <mailmsg." . getmypid() . '.' . time() . '@' . $_SERVER['SERVER_NAME'] . '>' . $this->nl;

    $email .= $this->nl . $msg->assemble_body();

    // negotiate the connection
    $this->connect();

    $this->negotiate();

    $this->mailFrom($msg->header('From'));

    foreach ($msg->recipients as $recip) {
      $this->rcptTo($recip);
    }

    $this->data($email);

    $this->disconnect();
  }

  /**
   * Connect to the mail server
   */
  protected function connect() {
    $host = Cfg::get('mail/host', 'localhost');
    $port = Cfg::get('mail/port', 25);
    $errno = 0;
    $errstr = '';

    $this->sock = fsockopen($host, $port, $errno, $errstr);
    if ($this->sock === FALSE)
      throw new Exception("Could not connect to mail server: $errstr");
  }

  /**
   * Negotiate the transaction
   */
  protected function negotiate() {
    $hostname = Cfg::get('mail/helo_host', $_SERVER['SERVER_NAME']);

    // wait for the server to respond
    $this->getResponse(220);

    $this->sendLine("EHLO $hostname");
    try {
      list($code, $info) = $this->getResponse(250);
      $this->capabilities = $info;
    } catch (Exception $e) {
      $this->sendLine("HELO $hostname");
      $this->getResponse(250);
    }
  }

  /**
   * Mail from command
   */
  protected function mailFrom($addr) {
    $this->sendLine("MAIL FROM:<$addr>");
    $this->getResponse(250);
  }

  /**
   * Recipient command
   */
  protected function rcptTo($addr) {
    $this->sendLine("RCPT TO:<$addr>");
    $this->getResponse(array(250, 251));
  }

  /**
   * Send message data
   */
  protected function data($data) {
    $this->sendLine("DATA");
    $this->getResponse(354);

    $data = $this->sanitizeData($data) . $this->nl . "." . $this->nl;

    if (fwrite($this->sock, $data, strlen($data)) === FALSE)
      throw new Exception("Error writing to socket");

    $this->getResponse(250);
  }

  /**
   * Sanitize data for sending
   */
  protected function sanitizeData($data) {
    // standardize line endings
    $data = preg_replace(array('/(?<!\r)\n/','/\r(?!\n)/'), $this->nl, $data);

    // escape lines beginning with a single period
    $data = str_replace("\n.", "\n..", $data);

    return $data;
  }

  /**
   * Close the connection
   */
  protected function disconnect() {
    $this->sendLine('QUIT');

    $this->getResponse(221);

    fclose($this->sock);
    $this->sock = NULL;
  }

  /**
   * Send a line to the remove server
   *
   * @param string $line  Line, not including line terminator
   */
  protected function sendLine($line) {
    $data = $line . $this->nl;
    $sent = fwrite($this->sock, $data, strlen($data));
    if ($sent === FALSE)
      throw new Exception("Error writing to socket");
  }

  /**
   * Get a reponse from the server
   */
  protected function getResponse($expected) {
    $code = 0;
    $info = array();

    while ($line = $this->getLine()) {
      $lineCode = substr($line, 0, 3);
      $lineInfo = substr($line, 4);

      $code = is_numeric($lineCode) ? intval($lineCode) : $code;
      $info[] = $lineInfo;

      if (substr($line, 3, 1) != '-')
        break;
    }

    if (is_array($expected)) {
      if (!in_array($code, $expected))
        throw new Exception("Error while communicating with server: $code " . implode("\n", $info));
    } elseif ($expected != $code) {
      throw new Exception("Error while communicating with server: $code " . implode("\n", $info));
    }

    return array($code, $info);
  }

  /**
   * Read and return a line from the socket
   */
  protected function getLine() {
    $maxlen = 4096;

    $line = '';
    while (!feof($this->sock) && (strlen($line) < $maxlen)) {
      $got = @fgets($this->sock, $maxlen);
      if ($got === FALSE)
        throw new Exception("Error reading from socket.");
      $line .= $got;
      if (substr($line, -1) == "\n")
        return rtrim($line, "\r\n");
    }

    return $line;
  }
}

?>