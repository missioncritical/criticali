<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * Formats information in an ASCII table for output by command line utilities.
 *
 * Several formatting options are available. They are:
 *  - <b>max-width:</b>     The maximum width of the table in characters (default 76)
 *  - <b>border-top:</b>    A string to use for the table top border (default '')
 *  - <b>border-header:</b> A string to use for the border below the header (default '-')
 *  - <b>border-row:</b>    A string to use for the border between rows (default '')
 *  - <b>border-bottom:</b> A string to use for the table bottom border (default '')
 *  - <b>border-left:</b>   A string to use for the table left border (default '')
 *  - <b>border-right:</b>  A string to use for the table right border (default '')
 *  - <b>border-cell:</b>   A string to use for the vertical border between cells (default ' ')
 */
class CriticalI_Command_TableFormatter {
  
  protected $options;
  protected $header;
  protected $rows;
  
  /**
   * Constructor
   *
   * @param array $options Formatting options
   */
  public function __construct($options = array()) {
    if (!is_array($options)) $options = array();
    
    $this->options = array_merge(array(
      'max-width'=>76,
      'border-top'=>'',
      'border-header'=>'-',
      'border-row'=>'',
      'border-bottom'=>'',
      'border-left'=>'',
      'border-right'=>'',
      'border-cell'=>' '
    ), $options);
    
    $this->header = null;
    $this->rows = array();
  }
  
  /**
   * Set the header for the table
   *
   * @param mixed $header A string or array of strings for the header
   */
  public function set_header($header) {
    $this->header = array();
    
    if (is_array($header)) {
      foreach ($header as $cell) { $this->header[] = strval($cell); }
    } else {
      $this->header[] = strval($header);
    }
  }
  
  /**
   * Add a row of data to the table
   *
   * @param mixed $row A string or array of string for the row
   */
  public function add_row($row) {
    $cells = array();
    
    if (is_array($row)) {
      foreach ($row as $cell) { $cells[] = strval($cell); }
    } else {
      $cels[] = strval($row);
    }
    
    $this->rows[] = $cells;
  }
  
  /**
   * Format the table and return it as a string
   *
   * @return string
   */
  public function to_string() {
    $width = 0;
    $columns = $this->build_columns($width);
    
    $output = '';
    $lastRow = count($rows) - 1;

    if ($this->options['border-top'] !== '')
      $output .= str_pad('', $width, $this->options['border-top']) . "\n";
    
    if ($this->header) {
      $this->format_row($columns, $width, $this->header, $output);

      if ($this->options['border-header'] !== '')
        $output .= str_pad('', $width, $this->options['border-header']) . "\n";
    }
    
    foreach ($this->rows as $rowIdx=>$row) {
      $this->format_row($columns, $width, $row, $output);

      if (($this->options['border-row'] !== '') && ($rowIdx < $lastRow))
        $output .= str_pad('', $width, $this->options['border-row']) . "\n";
    }
    
    if ($this->options['border-bottom'] !== '')
      $output .= str_pad('', $width, $this->options['border-bottom']) . "\n";

    return $output;
  }
  
  /**
   * Format an individual row of data for output
   */
  protected function format_row(&$columns, $width, &$row, &$output) {
    $count = count($row);
    $lineCount = 1;
    $cells = array();

    // format each cell
    foreach ($columns as $idx=>$col) {
      $val = ($idx < $count) ? $row[$idx] : '';
      $lines = $col->format($val);
      
      $lineCount = max($lineCount, count($lines));
      
      $cells[] = $lines;
    }
    
    // output each line of data
    for ($i = 0; $i < $lineCount; $i++) {
      foreach($cells as $colIdx=>$cell) {
        if (count($cell) > $i)
          $output .= $cell[$i];
        else
          $output .= $columns[$colIdx]->format('');
      }
      
      $output .= "\n";
    }
  }
  
  /**
   * Build the columns for formatting the table
   */
  protected function build_columns(&$width) {
    $columns = array();
    $fullWidth = 0;
    $wordWidth = 0;
    
    // determine how many columns we need
    $columnCount = $this->header ? count($this->header) : 0;
    foreach ($this->rows as $row) { $columnCount = max($columnCount, count($row)); }
    
    // build them
    for ($i = 0; $i < $columnCount; $i++) {
      $col = new CriticalI_Command_TableFormatter_Column();
      
      if ($i == 0)
        $col->leftText = $this->options['border-left'];
      elseif ($i == $columnCount - 1)
        $col->leftText = $this->options['border-cell'];
      else {
        $col->leftText = $this->options['border-cell'];
        $col->rightText = $this->options['border-right'];
      }
      
      if ($this->header && count($this->header) > $i)
        $col->add_to_width($this->header[$i]);
      foreach ($this->rows as $row) {
        if (count($row) > $i) $col->add_to_width($row[$i]);
      }
      
      $fullWidth += $col->max_width();
      $wordWidth += $col->min_word_width();
      
      $columns[] = $col;
    }

    return $this->calculate_column_width($columns, $fullWidth, $wordWidth, $width);
  }
  
  /**
   * Calculate the width to use for a set of columns
   */
  protected function calculate_column_width($columns, $fullWidth, $wordWidth, &$width) {
    $max = intval($this->options['max-width']);
    
    if ($max >= $fullWidth) {
      $width = $fullWidth;
      foreach ($columns as $col) { $col->width = $col->max_width(); }
      return $columns;
      
    } elseif ($max >= $wordWidth) {
      return $this->apportion_width($columns, $fullWidth, $max, 'max_width', 'min_word_width', $width);
    } else {
      return $this->apportion_width($columns, $fullWidth, $max, 'min_word_width', 'min_width', $width);
    }
  }
  
  /**
   * Determines column width when it is constrained
   */
  protected function apportion_width($columns, $fullWidth, $maxWidth, $maxMethod, $minMethod, &$width) {
    $width = 0;
    $extra = 0;
    $count = count($columns);
    
    // calculate any potential savings
    foreach ($columns as $idx=>$col) {
      $weight = $col->max_width() / $fullWidth;
      $suggested = round($max * $weight);
    
      $max = $col->$maxMethod();
      $min = $col->$minMethod();
      
      if ($suggested > $max)
        $extra += ($suggested - $max);
      elseif ($suggested < $min)
        $extra -= ($min - $suggested);
    }
    
    $extra = max(0, $extra);
    
    // determine the widths
    foreach ($columns as $idx=>$col) {
      $weight = $col->max_width() / $fullWidth;
      $suggested = round($max * $weight);
    
      $max = $col->$maxMethod();
      $min = $col->$minMethod();
      
      if ($suggested > $max) {
        $col->width = $max;
      } elseif ($extra > 0) {
        $suggested += round($extra * $weight);
      }
      
      if (($idx == $count - 1) && ($maxWidth - $width > $min)) {
        $col->width = $maxWidth - $width;
      } elseif ($suggested < $min) {
        $col->width = $min;
      } else {
        $col->width = $suggested;
      }
      
      $width += $col->width;
    }
    
    return $columns;
  }
  
}

/**
 * Internal class used by CriticalI_Command_TableFormatter to represent a
 * column
 */
class CriticalI_Command_TableFormatter_Column {
  public $leftText = '';
  public $rightText = '';
  public $widestCell = 0;
  public $widestWord = 0;
  public $width = 0;
  
  /**
   * Update width statistics with information from a cell
   */
  public function add_to_width($data) {
    $this->widestCell = max($this->widestCell, strlen($data));
    foreach (preg_split("/\s+/", $data) as $word) {
      $this->widestWord = max($this->widestWord, strlen($word));
    }
  }
  
  /**
   * Return the maximum width of the cell
   */
  public function max_width() {
    return strlen($this->leftText) + $this->widestCell + strlen($this->rightText);
  }

  /**
   * Return the minimum width of the cell that will not involve word
   * breaks
   */
  public function min_word_width() {
    return strlen($this->leftText) + $this->widestWord + strlen($this->rightText);
  }

  /**
   * Return the minimum possible width of the cell
   */
  public function min_width() {
    return strlen($this->leftText) + 1 + strlen($this->rightText);
  }
  
  /**
   * Format a cell and return the results as an array of lines
   */
  public function format($data) {
    $dataWidth = $this->width - (strlen($this->leftText) + strlen($this->rightText));
    
    $lines = array();
    
    if (strlen($data) > $dataWidth) {
      foreach (explode("\n", wordwrap($data, $dataWidth, "\n", true)) as $line) {
        $lines[] = $this->leftText . str_pad($line, $dataWidth) . $this->rightText;
      }
    } else {
      $lines[] = $this->leftText . str_pad($data, $dataWidth) . $this->rightText;
    }
    
    return $lines;
  }

}

?>