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
require_once('log4php/LoggerLevel.php');

/**
 * This layout outputs events in a HTML table.
 *
 * @author Marco Vassura
 * @version $Revision: 1.3 $
 * @package log4php
 * @subpackage layout  
 */
class LoggerLayoutHtml extends LoggerLayout {

    var $TRACE_PREFIX = "<br>&nbsp;&nbsp;&nbsp;&nbsp;";

    /**
     * A string constant used in naming the option for setting the the
     * HTML document title.  Current value of this string
     * constant is <b>Title</b>.
     */
    var $TITLE_OPTION = "Title";

    var $title = "Log4php Log Messages";
    
    function LoggerLayoutHtml()
    {
        return;
    }

    /**
     * The <b>Title</b> option takes a String value. This option sets the
     * document title of the generated HTML document.
     * Defaults to 'Log4php Log Messages'.
     */
    function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns the current value of the <b>Title</b> option.
     */
    function getTitle()
    {
        return $this->title;
    }
    
    /**
     * Returns the content type output by this layout, i.e "text/html".
     */
    function getContentType()
    {
        return "text/html";
    }
    
    /**
     * No options to activate.
     */
    function activateOptions()
    {
        return true;
    }
    
    function format($event)
    {
        $sbuf = "\n<tr>\n";
    
        $sbuf .= "<td>";
        $sbuf .= strftime('%c',$event->timeStamp);
        $sbuf .= "</td>\n";
    
        $sbuf .= "<td title=\"" . $event->getThreadName() . " thread\">";
        $sbuf .= $event->getThreadName();
        $sbuf .= "</td>\n";
    
        $sbuf .= "<td title=\"Level\">";
        if ($event->getLevel() == LOGGER_LEVEL_DEBUG) {
          $sbuf .= "<font color=\"#339933\">";
          $sbuf .= LoggerLevel::toString($event->getLevel());
          $sbuf .= "</font>";
        }elseif($event->getLevel() >= LOGGER_LEVEL_WARN) {
          $sbuf .= "<font color=\"#993300\"><strong>";
          $sbuf .= LoggerLevel::toString($event->getLevel());
          $sbuf .= "</strong></font>";
        } else {
          $sbuf .= LoggerLevel::toString($event->getLevel());
        }
        $sbuf .= "</td>\n";
    
        $sbuf .= "<td title=\"" . $event->getLoggerName() . " category\">";
        $sbuf .= $event->getLoggerName();
        $sbuf .= "</td>\n";
    
        $sbuf .= "<td title=\"Message\">";
        $sbuf .= htmlentities($event->getRenderedMessage(), ENT_QUOTES);
        $sbuf .= "</td>\n";
        $sbuf .= "</tr>\n";
    
        return $sbuf;
    }

    /**
     * Returns appropriate HTML headers.
     */
    function getHeader()
    {
        $sbuf = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n";
        $sbuf .= "<html>\n";
        $sbuf .= "<head>\n";
        $sbuf .= "<title>" . $this->title . "</title>\n";
        $sbuf .= "<style type=\"text/css\">\n";
        $sbuf .= "<!--\n";
        $sbuf .= "body, table {font-family: arial,sans-serif; font-size: x-small;}\n";
        $sbuf .= "th {background: #336699; color: #FFFFFF; text-align: left;}\n";
        $sbuf .= "-->\n";
        $sbuf .= "</style>\n";
        $sbuf .= "</head>\n";
        $sbuf .= "<body bgcolor=\"#FFFFFF\" topmargin=\"6\" leftmargin=\"6\">\n";
        $sbuf .= "<hr size=\"1\" noshade>\n";
        $sbuf .= "Log session start time " . strftime('%c', time()) . "<br>\n";
        $sbuf .= "<br>\n";
        $sbuf .= "<table cellspacing=\"0\" cellpadding=\"4\" border=\"1\" bordercolor=\"#224466\" width=\"100%\">\n";
        $sbuf .= "<tr>\n";
        $sbuf .= "<th>Time</th>\n";
        $sbuf .= "<th>Thread</th>\n";
        $sbuf .= "<th>Level</th>\n";
        $sbuf .= "<th>Category</th>\n";
        $sbuf .= "<th>Message</th>\n";
        $sbuf .= "</tr>\n";

        return $sbuf;
    }

    /**
     * Returns the appropriate HTML footers.
     */
    function getFooter()
    {
        $sbuf = "</table>\n";
        $sbuf .= "<br>\n";
        $sbuf .= "</body></html>";

        return $sbuf;
    }
}
?>