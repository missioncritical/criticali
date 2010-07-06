<?php
/**
 * Simple class for constructing an HTML mail message
 */
class Support_Mail_Msg {
  /**
   * List of message recipients
   * @var array
   */
  protected $recipients;
  /**
   * Message subject
   * @var string
   */
  protected $subject;
  /**
   * Additional mail headers as an associative array
   * @var array
   */
  protected $headers;
  /**
   * Text body
   * @var string
   */
  protected $text = NULL;
  /**
   * HTML body
   * @var string
   */
  protected $html = NULL;
  /**
   * MIME boundary
   * @var string
   */
  protected $boundary = "--=_NextPart_000_000E_01C5256B.0AEFE730";
  /**
   * Outer MIME boundary (for attachemnts)
   * @var string
   */
  protected $outerBoundary = "--=_NextPart_001_01C8E2C1.C11DE4B3";
  /**
   * Attachments
   */
  protected $attachments;

  /**
   * Constructor
   *
   * @param mixed  $recipients A single recipient or array of recipients
   * @param string $subject    Subject for the message
   * @param array  $headers    An associative array of additional headers
   */
  public function __construct($recipients, $subject, $headers = NULL) {
    $this->recipients = (is_array($recipients) ? $recipients : array($recipients));
    $this->subject = $subject;
    $this->headers = (is_array($headers) ? $headers : array());
    $this->attachments = array();
  }

  /**
   * Add a header field
   *
   * @param string $name  The name of the field
   * @param string $value The value for the header
   */
  public function set_header($name, $value) {
    $this->headers[$name] = $value;
  }

  /**
   * Return the value of a header field
   *
   * @param string $name  The name of the header
   * @return string
   */
  public function header($name) {
    return $this->headers[$name];
  }
  
  /**
   * Return true if the specified header has a value
   *
   * @param string $name  The name of the header to check
   * @return boolean
   */
  public function has_header($name) {
    return isset($this->headers[$name]);
  }
  
  /**
   * Return the message's subject
   *
   * @return string
   */
  public function subject() {
    return $this->subject;
  }

  /**
   * Return the message's recipients
   *
   * @return array
   */
  public function recipients() {
    return $this->recipients;
  }

  /**
   * Set the text body
   *
   * @param string $body
   */
  public function set_text_body($body) {
    $this->text = $body;
  }

  /**
   * Set the HTML body
   *
   * @param string $body
   */
  public function set_html_body($body) {
    $this->html = $body;
  }

  /**
   * Add an attachment
   *
   * @param Support_Mail_Attachment $attachment  The attachment to add
   */
  public function add_attachment($attachment) {
    $this->attachments[] = $attachment;
  }

  /**
   * Strip non-printable characters from a value
   *
   * @param string $value         The value to clean
   * @param bool   $allowNewLines Whether or not to allow newlines
   *                              (default FALSE)
   */
  public function clean($value, $allowNewLines = FALSE) {
    if ($allowNewLines) {
      $pat = '/[^\x09\x0A -~\x80-\xFF]/m';
      $limit = 1024;
    } else {
      $pat = '/[^ -~\x80-\xFF]/';
      $limit = 255;
    }

    // strip bad characters
    $value = preg_replace($pat, '', $value);

    // limit the length
    if (strlen($value) > $limit)
      $value = substr($value, 0, $limit);

    return $value;
  }

  /**
   * Assemble and return the header
   */
  public function assemble_header() {
    $header = '';
    $nl = Cfg::get('mail/line-ending', "\r\n");

    foreach ($this->headers as $name => $value) {
      $header .= "$name: " . $this->clean($value) . $nl;
    }

    return $header;
  }
  
  /**
   * Assemble and return the body
   */
  public function assemble_body() {
    if (count($this->attachments)) {
      return $this->assemble_mixed();
    } else {
      if ($this->has_alternatives())
        return $this->assemble_alternative();
      elseif (!is_null($this->html))
        return $this->html;
      elseif (!is_null($this->text))
        return $this->text;
      else
        throw new Exception("Mail message must have a body.");
    }
  }
  
  /**
   * Returns true if the message contains alternative formats
   *
   * @return boolean
   */
  public function has_alternatives() {
    return ((!is_null($this->text)) && (!is_null($this->html))) ? true : false;
  }

  /**
   * Return the underlying message mime type (irrespective of attachments)
   *
   * @return boolean
   */
  public function message_mime_type() {
    if ((!is_null($this->text)) && (!is_null($this->html))) {
      return 'multipart/alternative';
    } elseif (!is_null($this->html)) {
      return 'text/html';
    } else {
      return 'text/plain';
    }
  }

  /**
   * Assembles an alternative body
   */
  protected function assemble_alternative() {
    $nl = Cfg::get('mail/line-ending', "\r\n");

    $body = "${nl}This is a multipart message in MIME format${nl}";
    $body .= "--" . $this->boundary . "${nl}";
    $body .= "Content-Type: text/plain${nl}";
    $body .= "Content-Transfer-Encoding: 8bit${nl}${nl}";

    $body .= $this->text;

    $body .= "${nl}--" . $this->boundary . "${nl}";
    $body .= "Content-Type: text/html${nl}";
    $body .= "Content-Transfer-Encoding: 8bit${nl}${nl}";

    $body .= $this->html;

    $body .= "${nl}--" . $this->boundary . "--${nl}";

    return $body;
  }

  /**
   * Assembles a mixed body with an alternative one inside
   */
  protected function assemble_mixed() {
    $nl = Cfg::get('mail/line-ending', "\r\n");

    $body = "${nl}This is a multipart message in MIME format${nl}";
    $body .= "--" . $this->outerBoundary . "${nl}";
    
    if ($this->has_alternatives()) {
      $body .= "Content-Type: multipart/alternative; boundary=\"" . $this->boundary . "\"${nl}";
      $body .= "${nl}";
      $body .= $this->assemble_alternative();
    } elseif (!is_null($this->html)) {
      $body .= "Content-Type: text/html{$nl}";
      $body .= "${nl}";
      $body .= $this->html;
    } elseif (!is_null($this->text)) {
      $body .= "Content-Type: text/plain{$nl}";
      $body .= "${nl}";
      $body .= $this->text;
    } else {
      $body .= "Content-Type: text/plain{$nl}";
      $body .= "${nl}";
      $body .= ' ';
    }

    foreach ($this->attachments as $att) {
      $body .= "${nl}--" . $this->outerBoundary . "${nl}";
      $body .= "Content-Type: {$att->type}${nl}";
      $body .= "Content-Transfer-Encoding: base64${nl}";
      $body .= "Content-Disposition: attachment; filename=\"{$att->name}\"${nl}${nl}";

      $data = $att->data_is_path ? file_get_contents($att->data) : $att->data;
      $body .= chunk_split(base64_encode($data), 76, $nl);
    }

    $body .= "${nl}--" . $this->outerBoundary . "--${nl}";

    return $body;
  }

  /**
   * Send the mail message
   */
  public function send() {
    // set some additional headers
    $this->set_header('MIME-Version', '1.0');
    if (count($this->attachments)) {
      $this->set_header('Content-Type', 'multipart/mixed; boundary="' . $this->outerBoundary . '"');
    } else {
      if ($this->has_alternatives())
        $this->set_header('Content-Type', 'multipart/alternative; boundary="' . $this->boundary . '"');
      else
        $this->set_header('Content-Type', $this->message_mime_type());
      $this->set_header('Content-Transfer-Encoding', '8bit');
    }
    
    // send the mail
    $engine = Support_Mail_Engine::create();
    $engine->send($this);
  }
};

?>
