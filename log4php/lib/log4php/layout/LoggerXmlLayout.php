<?php
// 
// This framework is based on log4j see http://jakarta.apache.org/log4j
// Copyright (C) The Apache Software Foundation. All rights reserved.
//
// PHP port and modifications by Marco Vassura. All rights reserved.
// For more information, please see <http://www.vxr.it/log4php/>. 
//
// This software is published under the terms of the Apache Software
// License version 1.1, a copy of which has been included with this
// distribution in the LICENSE.txt file.
//
// log4php: a php port of log4j java logging package
//

require_once('log4php/LoggerLayout.php'); 
require_once('log4php/LoggerEvent.php'); 
require_once('log4php/LoggerLevel.php');


/**
 * The output of the XMLLayout consists of a series of log4php:event elements. It does not output a
 * complete well-formed XML file. The output is designed to be included as an external entity in a separate file to form
 * a correct XML file.
 *
 * @author Marco Vassura
 * @version $Revision: 1.3 $
 * @package log4php
 * @subpackage layout
 * 
 */
class LoggerXmlLayout extends LoggerLayout {

  var $locationInfo = false;
 
  /**
   * The <b>LocationInfo</b> option takes a boolean value. By default,
   * it is set to false which means there will be no location
   * information output by this layout. If the the option is set to
   * true, then the file name and line number of the statement at the
   * origin of the log statement will be output.
   */
  function setLocationInfo($flag) {
    $this->locationInfo = (bool)$flag;
  }
  
  /**
   * Returns the current value of the <b>LocationInfo</b> option.
   */
  function getLocationInfo() {
    return $this->locationInfo;
  }
  
  /** 
   * No options to activate. 
   */
  function activateOptions() { return true; }


  /**
   * Formats a {@link LoggerEvent} in conformance with the log4j.dtd.
   */
  function format($event) {

    $buf  = "<log4php:event logger=\"" . 
                $event->getLoggerName() . 
            "\" timestamp=\"". 
                $event->timeStamp . 
            "\" level=\"" . 
                LoggerLevel::toString($event->getLevel()) . 
            "\">\r\n";
    $buf .= "<log4php:message><![CDATA[". htmlspecialchars($event->getRenderedMessage(), ENT_QUOTES) . "]]></log4j:message>\r\n";
    $buf .= "</log4php:event>\r\n\r\n";
    
    return $buf;
  }
  
  /**
   * The XMLLayout prints and does not ignore exceptions. Hence the
   * return value <code>false</code>.
   */
  function ignoresThrowable() {
    return false;
  }
  
}

?>