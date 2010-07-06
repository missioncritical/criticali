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

//
// PLEASE NOTE:
// This file has been modified by Jeffrey Hunter to use XML parsing
// native to PHP 5.  These modifications are provided under the same
// license as the rest of the file.

require_once('log4php/LoggerManager.php');
require_once('log4php/LoggerAppender.php');
require_once('log4php/LoggerLayout.php');
require_once('log4php/LoggerLevel.php');
require_once('log4php/LoggerLog.php');

if (!defined('LOG4PHP_CONFIG_FILENAME')) {
    define('LOG4PHP_CONFIG_FILENAME', 'log4php.xml');
}

/**
 * Parse xml logger configuration and creates the {@link LoggerHierarchy}
 *
 * @author       Marco Vassura
 * @version      $Revision: 1.6 $
 * @package      log4php
 */
class LoggerConfigXmlParser {

    /**
     * @var array tag stack
     */
    var $parentTags    = array();
    
    /**
     * @var array tag attrib stack
     */
    var $parentAttribs = array();

    /**
     * @var string input file name
     */
    var $inputFile = LOG4PHP_CONFIG_FILENAME;
    

    /**
     * @param string $filename config filename
     */
    function LoggerConfigXmlParser($filename = null)
    {
        if ($filename != null) {
          $this->inputFile = $filename;
        }
    }

    /**
     * Parse the configuration file
     */
    function parse() {
      $parser = xml_parser_create();
      xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, true);
      xml_set_element_handler($parser, array($this, 'startElement'), array($this, 'endElement'));

      if (!($fp = fopen($this->inputFile, "r")))
        throw new Exception("Could not open logging configuration file \"".$this->inputFile."\"");

      while ($data = fread($fp, 4096)) {
        if (!xml_parse($parser, $data, feof($fp)))
          throw new Exception("Error parsing XML in file \"".$this->inputFile."\" line ".xml_get_current_line_number($parser).": ".xml_error_string(xml_get_error_code($parser)));
      }

      fclose($fp);

      xml_parser_free($parser);
    }

    function startElement($xp, $name, $attributes) {
      $method = "xmltag_" . strtolower($name);
      if (method_exists($this, $method))
        $this->$method($xp, $name, $attributes);
    }

    function endElement($xp, $name) {
      $method = "xmltag_" . strtolower($name) . "_";
      if (method_exists($this, $method))
        $this->$method($xp, $name);
    }
    
    function xmltag_configuration($xp, $elem, $attribs)
    {
        LoggerLog::log("LoggerConfigXmlParser::configuration()");
        $lr =& LoggerManager::getLoggerRepository();
        if (isset($attribs['THRESHOLD']))
            $lr->setThreshold(LoggerLevel::toCode($attribs['THRESHOLD']));
        if (isset($attribs['DEBUG'])) {
            $lr->debug = (bool)(strtolower($attribs['DEBUG']) == 'true'); 
            // ?? dont known what to do
        }
        
    }
    
    function xmltag_root($xp, $elem, $attribs)
    {
        LoggerLog::log("LoggerConfigXmlParser::root()");
        $this->parentTags[] = 'root';
        $this->parentAttribs[] = $attribs;
    }
    
    function xmltag_priority($xp, $elem, $attribs)
    {
        LoggerLog::log("LoggerConfigXmlParser::xmltag_priority() value={$attribs['VALUE']}");
        $parentTag = end($this->parentTags);
        switch ($parentTag) {
            case 'root':
                $rootLogger =& LoggerManager::getRootLogger();
                $rootLogger->setPriority(LoggerLevel::toCode($attribs['VALUE']));
                LoggerLog::log("LoggerConfigXmlParser::xmltag_priority() setting priority for root");                
                break;
            case 'logger':
                $parentAttribs = end($this->parentAttribs);
                $loggerName = $parentAttribs['NAME'];
                $logger =& LoggerManager::getLogger($loggerName);
                $logger->setLevel(LoggerLevel::toCode($attribs['VALUE']));
                LoggerLog::log("LoggerConfigXmlParser::xmltag_priority() setting priority for '$loggerName'");                
                break;
        }
    }
    
    function xmltag_appender_ref($xp, $elem, $attribs)
    {
        if (isset($attribs['REF']) and !empty($attribs['REF'])) {
            $parentTag = end($this->parentTags);
            $appenderName = $attribs['REF'];
            LoggerLog::log("LoggerConfigXmlParser::appender_ref() ref='$appenderName'");        
            
            $appender =& LoggerAppender::singleton($appenderName);
            switch ($parentTag) {
                case 'root':
                    $rootLogger =& LoggerManager::getRootLogger();
                    $rootLogger->addAppender($appender);
                    break;
                case 'logger':
                    $parentAttribs = end($this->parentAttribs);
                    $loggerName = $parentAttribs['NAME'];
                    $logger =& LoggerManager::getLogger($loggerName);
                    $logger->addAppender($appender);
                    break;
            }
        }
    }
    
    function xmltag_root_($xp, $elem)
    {
        LoggerLog::log("LoggerConfigXmlParser::root_()");    
        array_pop($this->parentTags);
        array_pop($this->parentAttribs);        
    }

    function xmltag_appender($xp, $elem, $attribs)
    {
        LoggerLog::log("LoggerConfigXmlParser::appender()");
        $appenderName = $attribs['NAME'];
        LoggerAppender::singleton($appenderName, $attribs['CLASS']);
        $this->parentTags[] = 'appender';
        $this->parentAttribs[] = $attribs;        
    }

    function xmltag_param($xp, $elem, $attribs)
    {
        LoggerLog::log("LoggerConfigXmlParser::param()");    
        $parentTag = end($this->parentTags);
        switch ($parentTag) {
            case 'appender':
                $parentAttrib = end($this->parentAttribs);
                $appenderName = $parentAttrib['NAME'];            
                $appender =& LoggerAppender::singleton($appenderName);
                $methodName = 'set'.ucfirst($attribs['NAME']);

                if (method_exists($appender, $methodName)) {
                    LoggerLog::log("Calling ".get_class($appender)."::$methodName({$attribs['VALUE']})");
                    call_user_func(array(&$appender, $methodName), $attribs['VALUE']);
                } else {
                    LoggerLog::log("Calling ".get_class($appender)."::$methodName({$attribs['VALUE']})");
                }
                break;
            case 'layout':
                end($this->parentAttribs);
                $parentParentAttrib = prev($this->parentAttribs);
                $appenderName = $parentParentAttrib['NAME'];
                $appender =& LoggerAppender::singleton($appenderName);
                $layout =& $appender->getLayout();
                $methodName = 'set'.ucfirst($attribs['NAME']);                
                if (method_exists(&$layout, $methodName)) {
                    LoggerLog::log("Calling ".get_class($layout)."::$methodName({$attribs['VALUE']})");                
                    call_user_func(array(&$layout, $methodName), $attribs['VALUE']);
                } else {
                    LoggerLog::log("Calling ".get_class($layout)."::$methodName({$attribs['VALUE']})");
                }
                break;
        }
    }
    
    function xmltag_layout($xp, $elem, $attribs)
    {
        LoggerLog::log("LoggerConfigXmlParser::layout()");
        $parentAttrib = end($this->parentAttribs);
        $appenderName = $parentAttrib['NAME'];
        $appender =& LoggerAppender::singleton($appenderName);
        $layout =& LoggerLayout::factory($attribs['CLASS']);
        $appender->setLayout($layout);
        $this->parentTags[] = 'layout';
        $this->parentAttribs[] = $attribs;        
    }
    
    function xmltag_appender_($xp, $elem)
    {
        LoggerLog::log("LoggerConfigXmlParser::appender_()");
        
        $parentAttrib = end($this->parentAttribs);        
        $appenderName = $parentAttrib['NAME'];            
        $appender =& LoggerAppender::singleton($appenderName);
        $appender->activateOptions();        
        
        array_pop($this->parentTags);
        array_pop($this->parentAttribs);        
    }
    
    function xmltag_logger($xp, $elem, $attribs)
    {
        $loggerName = $attribs['NAME'];
        LoggerLog::log("LoggerConfigXmlParser::logger() name='$loggerName'");        
        $logger =& Logger::getLogger($loggerName);
        if (isset($attribs['ADDITIVITY'])) {
            $additivity = (bool)(strtolower($attribs['ADDITIVITY']) == 'yes');     
            $logger->setAdditivity($additivity);
        }
        $this->parentTags[] = 'logger';
        $this->parentAttribs[] = $attribs;        
    }
    
    function xmltag_layout_($xp, $elem)
    {
        LoggerLog::log("LoggerConfigXmlParser::layout_()");    
        array_pop($this->parentTags);
        array_pop($this->parentAttribs);        
    }

    function xmltag_configuration_($xp, $elem)
    {
        LoggerLog::log("LoggerConfigXmlParser::configuration_()");        
        $this->parentTags    = array();
        $this->parentAttribs = array();                
    }

}

?>