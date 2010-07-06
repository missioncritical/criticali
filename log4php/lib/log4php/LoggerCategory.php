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

require_once('log4php/LoggerEvent.php');
require_once('log4php/LoggerLevel.php');

/**
 * This class has been deprecated and replaced by the Logger subclass.
 *
 * @author       Marco Vassura
 * @version      $Revision: 1.3 $
 * @package      log4php
 */
class LoggerCategory {

    /**
     * @var boolean Additivity is set to true by default, that is children inherit the appenders of their ancestors by default.
     */
    var $additive       = true;
    
    /**
     * @var string fully qualified class name
     */  
    var $fqcn           = 'LoggerCategory';

    /**
     * @var integer The assigned level of this category.
     */
    var $level          = null;
    
    /**
     * @var string name of this category.
     */
    var $name           = '';
    
    /**
     * @var object The parent of this category.
     * @see Logger
     */
    var $parent         = null;

    /**
     * @var object the object repository
     * @see LoggerHierarchy
     */
    var $repository     = null; 

    /**
     * @var array collection of appenders
     * @see LoggerAppender
     */
    var $aai            = array();
    
/* --------------------------------------------------------------------------*/
/* --------------------------------------------------------------------------*/
/* --------------------------------------------------------------------------*/

    /**
     * Constructor.
     *
     * @param  string  $name  Category name   
     */
    function LoggerCategory($name)
    {
        $this->name = $name;
        $this->repository =& LoggerManager::getLoggerRepository();
    }
    
    /**
     * Add newAppender to the list of appenders of this Category instance.
     *
     * @param object $newAppender Appender Object
     * @see LoggerAppender
     */
    function addAppender(&$newAppender)
    {
        $appenderName = $newAppender->getName();
        $this->aai[$appenderName] =& $newAppender;
    } 
            
    /**
     * If assertion parameter is false, then logs msg as an error statement.
     *
     * @param bool $assertion
     * @param string $msg message to log
     */
    function assertLog($assertion = true, $msg = '')
    {
        if ($assertion == false) {
            $this->error($msg);
        }
    } 

    /**
     * Call the appenders in the hierrachy starting at this.
     *
     * @param object $event LoggerEvent
     * @see LoggerEvent
     */
    function callAppenders($event) 
    {
        if (sizeof($this->aai) > 0) {
            foreach (array_keys($this->aai) as $appenderName) {
                $this->aai[$appenderName]->doAppend($event);
            }
        }
        if ($this->parent != null and $this->getAdditivity()) {
            $this->parent->callAppenders($event);
        }
    }
    
    /**
     * Log a message object with the DEBUG level including the caller.
     *
     * @param string $message message
     * @param mixed $caller caller object or caller string id     
     */
    function debug($message, $caller = null)
    {
        if ($this->repository->isDisabled(LOGGER_LEVEL_DEBUG)) {
            return;
        }
        if (LOGGER_LEVEL_DEBUG >= $this->getEffectiveLevel()) {
            $this->forcedLog($caller, LOGGER_LEVEL_DEBUG, $message);
        }
    } 

    /**
     * Log a message object with the LOGGER_LEVEL_ERROR level including the caller.
     *
     * @param string $message message
     * @param mixed $caller caller object or caller string id     
     */
    function error($message, $caller = null)
    {
        if ($this->repository->isDisabled(LOGGER_LEVEL_ERROR)) {
            return;
        }
        if (LOGGER_LEVEL_ERROR >= $this->getEffectiveLevel()) {
            $this->forcedLog($caller, LOGGER_LEVEL_ERROR, $message);
        }
    }
  
    /**
     * Deprecated. Please use LoggerManager::exists() instead.
     *
     * @param string $name
     * @see LoggerManager::exists()
     * @deprecated
     */
    function exists($name)
    {
        return LoggerManager::exists($name);
    } 
 
    /**
     * Log a message object with the LOGGER_LEVEL_FATAL level including the caller.
     *
     * @param string $message message
     * @param mixed $caller caller object or caller string id     
     */
    function fatal($message, $caller = null)
    {
        if ($this->repository->isDisabled(LOGGER_LEVEL_FATAL)) {
            return;
        }
        if (LOGGER_LEVEL_FATAL >= $this->getEffectiveLevel()) {
            $this->forcedLog($caller, LOGGER_LEVEL_FATAL, $message);
        }
    } 
  
    /**
     * This method creates a new logging event and logs the event without further checks.
     *
     * @param mixed $caller caller object or caller string id
     * @param integer $level log level     
     * @param string $message message
     * @see LoggerEvent          
     */
    function forcedLog($caller, $level, $message)
    {
        $fqcn = is_object($caller) ? get_class($caller) : (string)$caller;
        $this->callAppenders(new LoggerEvent($fqcn, $this, $level, $message));
    } 

    /**
     * Get the additivity flag for this Category instance.
     */
    function getAdditivity()
    {
        return $this->additive;
    }
 
    /**
     * Get the appenders contained in this category as an Enumeration.
     * @return array collection of appenders
     */
    function getAllAppenders() 
    {
        return array_values($this->aai);
    }
    
    /**
     * Look for the appender named as name.
     * @return object
     * @see LoggerAppender
     */
    function &getAppender($name) 
    {
        return $this->aai[$name];
    }
    
    /**
     * Please use the the getEffectiveLevel() method instead.
     * @deprecated
     */
    function getChainedPriority()
    {
        return $this->getEffectiveLevel();
    } 
 
    /**
     * Please use LogManager::getCurrentLoggers() instead.
     * @deprecated
     */
    function getCurrentCategories()
    {
        return LoggerManager::getCurrentLoggers();
    } 
 
    /**
     * Please use LogManager::getLoggerRepository() instead.
     * @deprecated 
     */
    function getDefaultHierarchy()
    {
        return LoggerManager::getLoggerRepository();
    } 
 
    /**
     * Starting from this category, search the category hierarchy for a non-null level and return it.
     */
    function getEffectiveLevel()
    {
        for($c = $this; $c != null; $c = $c->parent) {
            if($c->level != null)
            	return $c->level;
        }
        return null;
    }
  
    /**
     * Please use getLoggerRepository() instead.
     * @deprecated 
     */
    function &getHierarchy()
    {
        return $this->getLoggerRepository();
    }
 
    /**
     * Retrieve a category with named as the name parameter.
     */
    function &getInstance($name)
    {
        return LogManager::getLogger($name);
    }

    /**
     * Returns the assigned Level, if any, for this Category. 
     */
    function getLevel()
    {
        return $this->level;
    } 

    /**
     * Return the the LoggerRepository where this Category is attached.
     */
    function &getLoggerRepository()
    {
        return $this->repository;
    } 

    /**
     * Return the category name.
     */
    function getName()
    {
        return $this->name;
    } 

    /**
     * Returns the parent of this category.
     */
    function &getParent() 
    {
        return $this->parent;
    }      

    /**
     * @deprecated Please use getLevel() instead.
     */
    function getPriority()
    {
        return $this->getLevel();
    }
          
    /**
     * Return the inherited ResourceBundle for this category.
     */
    function getResourceBundle()
    {
        return;
    } 

    /**
     * Returns the string resource coresponding to key in this category's inherited resource bundle.
     */
    function getResourceBundleString($key)
    {
        return;
    } 

    /**
     * Return the root of the default category hierrachy.
     */
    function &getRoot()
    {
        return LoggerManager::getRootLogger();
    } 

    /**
     * Log a message object with the LOGGER_LEVEL_INFO Level.
     *
     * @param string $message message
     * @param mixed $caller caller object or caller string id     
     */
    function info($message, $caller = null) {
        if ($this->repository->isDisabled(LOGGER_LEVEL_INFO)) {
            return;
        }
        if (LOGGER_LEVEL_INFO >= $this->getEffectiveLevel()) {
            $this->forcedLog($caller, LOGGER_LEVEL_INFO, $message);
        }
    }
     
    /**
     * Is the appender passed as parameter attached to this category?
     *
     * @param object $appender appender to test
     */
    function isAttached($appender)
    {
        return in_array($appender->getName(), array_keys($this->aai));
    } 
           
    /**
     * Check whether this category is enabled for the LOGGER_LEVEL_DEBUG Level.
     */
    function isDebugEnabled()
    {
        if ($this->repository->isDisabled(LOGGER_LEVEL_DEBUG)) {
            return false;
        }
        return (LOGGER_LEVEL_DEBUG >= $this->getEffectiveLevel());
    }       

    /**
     * Check whether this category is enabled for a given Level passed as parameter.
     *
     * @param integer level
     * @see LoggerLevel
     */
    function isEnabledFor($level)
    {
        if ($this->repository->isDisabled($level)) {
            return false;
        }
        return (bool)($level >= $this->getEffectiveLevel());
    } 

    /**
     * Check whether this category is enabled for the info Level.
     */
    function isInfoEnabled()
    {
        if ($this->repository->isDisabled(LEVEL_INFO_INT)) {
            return false;
        }
        return (LOGGER_LEVEL_INFO >= $this->getEffectiveLevel());
    } 

    /**
     * Log a localized and parameterized message.
     */
    function l7dlog($priority, $key, $params, $t)
    {
        return;
    } 

    /**
     * This generic form is intended to be used by wrappers.
     *
     * @param integer $priority log with this level
     * @param string $message message
     * @param mixed $caller caller object or caller string id     
     */
    function log($priority, $message, $caller = null)
    {
        if ($this->repository->isDisabled($priority)) {
            return;
        }
        if ($priority >= $this->getEffectiveLevel()) {
            $this->forcedLog($caller, $priority, $message);
        }
    }

    /**
     * Remove all previously added appenders from this Category instance.
     */
    function removeAllAppenders()
    {
        $this->aai = array();
    } 
            
    /**
     * Remove the appender passed as parameter form the list of appenders.
     *
     * @param mixed $appender can be an appender name or a LoggerAppender object
     */
    function removeAppender($appender)
    {
        if (is_object($appender)) {
            unset($this->aai[$appender->getName()]);
        } elseif (is_string($appender)) {
            unset($this->aai[$appender]);
        }
    } 

    /**
     * Set the additivity flag for this Category instance.
     *
     * @param boolean $additive
     */
    function setAdditivity($additive) 
    {
        $this->additive = (bool)$additive;
    }
    
    /**
     * @deprecated Please use setLevel($Level) instead.
     * @see setLevel()
     */
    function setPriority($priority)
    {
        $this->setLevel($priority);
    } 

    /**
     * Set the level of this Category.
     *
     * @param integer $level 
     */
    function setLevel($level)
    {
        if (LoggerLevel::isLevel($level))
            $this->level = $level;
    } 

    /**
     * Set the resource bundle to be used with localized logging methods 
     */
    function setResourceBundle($bundle)
    {
        return;
    } 
           
    /**
     * @deprecated use LoggerManager::shutdown() instead.
     * @see LoggerManager::shutdown()
     */
    function shutdown()
    {
        LoggerManager::shutdown();
    } 
 
    /**
     * Log a message with the LOGGER_LEVEL_WARN level.
     *
     * @param string $message message
     * @param mixed $caller caller object or caller string id     
     */
    function warn($message, $caller = null)
    {
        if ($this->repository->isDisabled(LOGGER_LEVEL_WARN)) {
            return;
        }
        if (LOGGER_LEVEL_WARN >= $this->getEffectiveLevel()) {
            $this->forcedLog($caller, LOGGER_LEVEL_WARN, $message);
        }
    } 
}  
?>