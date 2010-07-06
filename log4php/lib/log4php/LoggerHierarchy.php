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

require_once('log4php/LoggerLog.php');
require_once('log4php/LoggerLevel.php');
require_once('log4php/LoggerAppender.php');
require_once('log4php/Logger.php');
require_once('log4php/LoggerRoot.php');
require_once('log4php/LoggerConfigXmlParser.php');

/**
 * This class is specialized in retrieving loggers by name and also maintaining the logger hierarchy.
 *
 * <p>The casual user does not have to deal with this class directly.
 *
 * <p>The structure of the logger hierarchy is maintained by the
 * getLogger method. The hierarchy is such that children link
 * to their parent but parents do not have any pointers to their
 * children. Moreover, loggers can be instantiated in any order, in
 * particular descendant before ancestor.
 *
 * <p>In case a descendant is created before a particular ancestor,
 * then it creates a provision node for the ancestor and adds itself
 * to the provision node. Other descendants of the same ancestor add
 * themselves to the previously created provision node.
 *
 * @author Marco Vassura
 * @version $Revision: 1.7 $
 * @package log4php
 *
 */
class LoggerHierarchy {

    /**
     * @var object currently unused
     */
    var $defaultFactory;
    
    /**
     * @var boolean activate internal logging
     * @see LoggerLog
     */
    var $debug = false;

    /**
     * @var array hierarchy tree. saves here all loggers
     */
    var $ht = array();
    
    /**
     * @var object {@link LoggerRoot} instance
     */
    var $root = null;

    /**
     * @var integer main level threshold
     * @see LoggerConfigXmlParser::xmltag_config()
     */
    var $threshold = LOGGER_LEVEL_ALL;
    
    /**
     * @var boolean currently unused
     */
    var $emittedNoAppenderWarning       = false;

    /**
     * @var boolean currently unused
     */
    var $emittedNoResourceBundleWarning = false;
    
    /**
     * @var object {@link LoggerConfigXmlParser} instance
     */
    var $parser = null;
    
/* --------------------------------------------------------------------------*/
/* --------------------------------------------------------------------------*/
/* --------------------------------------------------------------------------*/
    
    /**
     * Create a new logger hierarchy.
     * @param object $root the root logger
     */
    function LoggerHierarchy($root)
    {
        $this->root    =& $root;
        // Enable all level levels by default.
        $this->setThreshold(LOGGER_LEVEL_ALL);
        // $this->root->setHierarchy($this);
    }
     
    /**
     * Add a HierarchyEventListener event to the repository. 
     * Not Yet Impl.
     */
    function addHierarchyEventListener($listener)
    {
        return;
    }
     
    /**
     * Add an object renderer for a specific class.
     * Not Yet Impl.
     */
    function addRenderer($classToRender, $or)
    {
        return;
    } 
    
    /**
     * This call will clear all logger definitions from the internal hashtable.
     */
    function clear()
    {
        unset($this->root);
        unset($this->ht);
    }
      
    function emitNoAppenderWarning($cat)
    {
        return;
    }
    
    /**
     * Check if the named logger exists in the hierarchy.
     */
    function exists($name)
    {
        return in_array($name, array_keys($this->ht));
    }

    function fireAddAppenderEvent($logger, $appender)
    {
        return;
    }
    
    /**
     * @deprecated Please use {@link getCurrentLoggers()} instead.
     */
    function getCurrentCategories()
    {
        return $this->getCurrentLoggers();
    }
    
    /**
     * Returns all the currently defined categories in this hierarchy as an Enumeration.
     */  
    function getCurrentLoggers()
    {
       return array_values($this->ht); 
    }
    
    /**
     * Return a new logger instance named as the first parameter using the default factory.
     * 
     * @param string $name logger name
     * @return object {@link Logger} instance
     */
    function &getLogger($name)
    {
        if (!isset($this->ht[$name])) {
            LoggerLog::log("LoggerHierarchy::getLogger($name) creating a new logger...");
            $this->ht[$name] = new Logger($name);
            $nodes = explode('.', $name);
            $firstNode = array_shift($nodes);
            if ( $firstNode != $name and isset($this->ht[$firstNode])) {
                LoggerLog::log("LoggerHierarchy::getLogger($name) parent is now $firstNode");            
                $this->ht[$name]->parent =& $this->ht[$firstNode];
            } else {
                LoggerLog::log("LoggerHierarchy::getLogger($name) parent is now root");            
                $this->ht[$name]->parent =& $this->root;
            } 
            if (sizeof($nodes) > 0) {
                // find parent node
                foreach ($nodes as $node) {
                    $parentNode = "$firstNode.$node";
                    if (isset($this->ht[$parentNode]) and $parentNode != $name) {
                        LoggerLog::log("LoggerHierarchy::getLogger($name) parent is now $parentNode");                    
                        $this->ht[$name]->parent =& $this->ht[$parentNode];
                    }
                    $firstNode .= ".$node";
                }
            }
            // update children
            /*
            $children = array();
            foreach (array_keys($this->ht) as $nodeName) {
                if ($nodeName != $name and substr($nodeName, 0, strlen($name)) == $name) {
                    $children[] = $nodeName;    
                }
            }
            */
        }            
        return $this->ht[$name];
    }
    
    function &getParser()
    {
        if ($this->parser == null)
            $this->parser = new LoggerConfigXmlParser();
        return $this->parser;
    }
    
    /**
     * Get the renderer map for this hierarchy.
     */
    function getRendererMap()
    {
        return;
    }
    
    /**
     * Get the root of this hierarchy.
     */ 
    function &getRootLogger()
    {
        if (!isset($this->root) or $this->root == null)
            $this->root = new LoggerRoot();
        return $this->root;
    }
     
    /**
     * Returns a Level representation of the enable state.
     */
    function getThreshold()
    {
        return $this->threshold;
    } 

    /**
     * This method will return true if this repository is disabled for level object passed as parameter and false otherwise.
     */
    function isDisabled($level)
    {
        return ($this->threshold > $level);
    }
    
    /**
     * @deprecated Deprecated with no replacement.
     */
    function overrideAsNeeded($override)
    {
        return;
    } 
    
    /**
     * Reset all values contained in this hierarchy instance to their default.
     */
    function resetConfiguration()
    {
        $this->clear();
        $this->setThreshold(LOGGER_LEVEL_ALL);
        $this->getParser();
        $this->parser->parse();
        // $this->root =& LoggerRoot::getRootLogger();
    }
      
    /**
     * @deprecated Deprecated with no replacement.
     */
    function setDisableOverride($override)
    {
        return;
    }
    
    /**
     * Used by subclasses to add a renderer to the hierarchy passed as parameter.
     */
    function setRenderer($renderedClass, $renderer)
    {
        // implement me!
        return;
    }
    
    /**
     * set a new threshold level
     *
     * @param integer $i
     */
    function setThreshold($l)
    {
        $this->threshold = $l;
    }
    
    /**
     * Shutting down a hierarchy will safely close and remove all appenders in all categories including the root logger
     */
    function shutdown()
    {
        $appenders = LoggerAppender::singleton('');
        foreach ($appenders as $appender) {
            $appender->close();
        }  
        return;
    }  
} 
?>