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

/**
 * Abstract class that defines output logs strategies.
 *
 * @author  Marco Vassura
 * @version $Revision: 1.4 $
 * @package log4php
 */
class LoggerAppender {

    /**
     * @var boolean closed appender flag
     */
    var $closed;
    
    /**
     * @var object unused
     */
    var $errorHandler;
           
    /**
     * @var object The first filter in the filter chain
     */
    var $headFilter = null;
            
    /**
     * @var object {@link LoggerLayout} for this appender. It can be null if appender has its own layout
     */
    var $layout = null; 
           
    /**
     * @var string Appender name
     */
    var $name;
           
    /**
     * @var object The last filter in the filter chain
     */
    var $tailFilter = null; 
           
    /**
     * @var integer There is no level threshold filtering by default.
     */
    var $threshold;
    
    /**
     * @var boolean needs a layout formatting ?
     */
    var $requiresLayout = false;
    
    /**
     * @var array output filters (not yet impl.)
     */
    var $filters = array();

/* --------------------------------------------------------------------------*/
/* --------------------------------------------------------------------------*/
/* --------------------------------------------------------------------------*/
    
    /**
     * Factory
     *
     * @param string $name appender name
     * @param string $class create an instance of this appender class
     * @return object a {@link LoggerAppender}
     */
    function factory($name, $class)
    {
        $class = basename($class);
        include_once("log4php/appender/$class.php");
        return new $class($name);
    }
    
    /**
     * Singleton
     *
     * @param string $name appender name
     * @param string $class create or get a reference instance of this class
     * @return mixed a {@link LoggerAppender} or an array of {@link LoggerAppender} 
     */
    function &singleton($name, $class = '')
    {
        static $instances;
        
        if (empty($name))
            return array_values($instances);
       
        if (!isset($instances[$name])) {
            $instances[$name] = LoggerAppender::factory($name, $class);
        }
        return $instances[$name];
    }  
    
/* -------------------------------------------------------------------------- */
/* -------------------------------------------------------------------------- */
/* -------------------------------------------------------------------------- */

    /**
     * Constructor
     *
     * @param string $name appender name
     */
    function LoggerAppender($name)
    {
        $this->name = $name;
        $this->clearFilters();
    }

    /**
     * Add a filter to the end of the filter list.
     *
     * @param object $newFilter a LoggerFilter to add
     */
    function addFilter($newFilter)
    {
        $this->filters[] = $newFilter;
        $this->headFilter = $this->filters[0];
        $this->tailFilter = end($this->filters);
        reset($this->filters);
    } 
 
    /**
     * Clear the list of filters by removing all the filters in it.
     */
    function clearFilters()
    {
        $this->filters = array();
    }
           
    /**
     * Release any resources allocated.
     */
    function close()
    {
        //override me
    }
            
    /**
     * Log in Appender specific way.
     *
     * @param object a LoggerEvent
     * @see LoggerEvent     
     */
    function doAppend($event)
    {
        // override me
    } 
            
    /**
     * Returns the ErrorHandler for this appender.
     */
    function getErrorHandler()
    {
        return $this->errorHandler;
    } 
           
    /**
     * Returns the head Filter.
     */
    function getFilter()
    {
        $this->headFilter;
    } 
            
    /**
     * Returns this appender layout.
     * @return object {@link LoggerLayout}
     */
    function getLayout()
    {
        return $this->layout;
    }
           
    /**
     * Get the name of this appender.
     * @return string
     */
    function getName()
    {
        return $this->name;
    } 
            
    /**
     * Configurators call this method to determine if the appender requires a layout.
     */
    function requiresLayout()
    {
        // override me
    }
            
    /**
     * Set the ErrorHandler for this appender.
     */
    function setErrorHandler($errorHandler)
    {
        $this->errorHandler = $errorHandler;
    } 
           
    /**
     * Set the Layout for this appender.
     *
     * @param object $layout a {@link LoggerLayout}
     */
    function setLayout($layout)
    {
        if ($this->requiresLayout)
            $this->layout = $layout;
    } 
 
    /**
     * Set the name of this appender.
     *
     * @param string $name appender name
     */
    function setName($name) 
    {
        $this->name = $name;    
    }
}
?>