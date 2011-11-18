<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * List command
 */
class CriticalI_Command_ReposList extends CriticalI_Command {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct('list', 'List the packages in the repository', <<<DESC
  criticali list [options] [search]
  
Lists the packages currently installed in the repository. If a search term
is provided, only packages whose names contain the search string are
displayed.
DESC
, array(
  new CriticalI_OptionSpec('details', CriticalI_OptionSpec::NONE, null, 'Output details, including full description, with each package.'),
  new CriticalI_OptionSpec('no-versions', CriticalI_OptionSpec::NONE, null, 'Do not include version numbers in output.'),
  new CriticalI_OptionSpec('remote', CriticalI_OptionSpec::NONE, null, 'List (or search) remote repositories'),
  new CriticalI_OptionSpec('verbose', CriticalI_OptionSpec::NONE, null, 'Include summary in listing.'),
  new CriticalI_OptionSpec('version', CriticalI_OptionSpec::REQUIRED, 'version', 'The version number to list.')));
  }
  
  /**
   * Run the command
   */
  protected function run_command() {
    // get the listing
    $matchingPackages = $this->find_matching_packages();
    
    $this->show_listing($matchingPackages);
  }
  
  /**
   * Search the list of packages for ones that match our criteria
   */
  protected function find_matching_packages() {
    $matches = array();
    
    $list = $this->pick_list();
    
    // evaluate any name criteria
    if (count($this->args) > 0) {
      foreach ($list as $pkg) {
        if (strpos(strtolower($pkg->name()), strtolower($this->args[0])) !== false)
          $matches[] = $pkg;
      }
    } else {
      $matches = $list;
    }
    
    // evaluate any version criteria
    if (isset($this->options['version'])) {
      $pkgs = $matches;
      $matches = array();
      foreach ($pkgs as $pkg) {
        if (isset($pkg[$this->options['version']])) $matches[] = $pkg;
      }
    }
    
    return $matches;
  }
  
  /**
   * Pick the correct listing to use
   */
  protected function pick_list() {
    if (isset($this->options['remote'])) {
      
      $remotes = CriticalI_Remote_Repository::default_remotes();
      if (!$remotes)
        throw new Exception("No remote repositories are configured");
      
      return new CriticalI_Remote_PackageList($remotes);
      
    } else {
      return CriticalI_Package_List::get();
    }
  }
  
  /**
   * Show a simple listing
   */
  protected function show_listing($pkgs) {
    // sort the list
    if (!is_array($pkgs)) {
      $pkgs2 = array();
      foreach ($pkgs as $pkg) { $pkgs2[] = $pkg; }
      $pkgs = $pkgs2;
    }
    
    usort($pkgs, create_function('$a,$b', 'return strcmp($a->name(), $b->name());'));
    
    // detail format is handled differently
    if (isset($this->options['details'])) {
      foreach ($pkgs as $pkg) {
        if (isset($this->options['version']))
          print $this->format_detail_version($pkg[$this->options['version']]) . "\n";
        else
          print $this->format_detail_package($pkg) . "\n";
      }
      
    // everything else is a table
    } else {
      $table = new CriticalI_Command_TableFormatter(array('border-cell'=>'  '));
      
      $header = array('Package');
      if (!isset($this->options['no-versions']))
        $header[] = 'Version(s)';
      if (isset($this->options['verbose']))
        $header[] = 'Summary';
      $table->set_header($header);

      foreach ($pkgs as $pkg) {
        if (isset($this->options['version']))
          $table->add_row($this->format_version($pkg[$this->options['version']]));
        else
          $table->add_row($this->format_package($pkg));
      }
      
      print $table->to_string();
    }
  }
  
  /**
   * Format the display of a package in the listing
   */
  protected function format_package($pkg) {
    $row = array($pkg->name());
    
    if (!isset($this->options['no-versions']))
      $row[] = $pkg->versions_string();
    if (isset($this->options['verbose']))
      $row[] = $pkg->newest()->property('package.summary', '');

    return $row;
  }
  
  /**
   * Format the detailed display of a package in the listing
   */
  protected function format_detail_package($pkg) {
    $str = $pkg->name();
    
    $str .= "\n";
    if (!isset($this->options['no-versions']))
      $str .= "  Version(s): ".wordwrap($pkg->versions_string(), 75, "\n              ")."\n";

    $str .= "  Summary:    ".wordwrap($pkg->newest()->property('package.summary'),
      75, "\n              ")."\n";
    $str .= "\n";

    $str .= "  ".wordwrap($pkg->newest()->property('package.description'), 75, "\n  ")."\n";

    return $str;
  }

  /**
   * Format the display of a version in the listing
   */
  protected function format_version($ver) {
    $row = array($ver->package()->name());
    
    if (!isset($this->options['no-versions']))
      $row[] = $ver->version_string();
    if (isset($this->options['verbose']))
      $row[] = $ver->property('package.summary', '');

    return $row;
  }

  /**
   * Format the detailed display of a version in the listing
   */
  protected function format_detail_version($ver) {
    $str = $ver->package()->name();
    
    $str .= "\n";
    if (!isset($this->options['no-versions']))
      $str .= "  Version(s): ".wordwrap($ver->version_string(), 75, "\n              ")."\n";

    $str .= "  Summary:    ".wordwrap($ver->property('package.summary'), 75, "\n              ")."\n";
    $str .= "\n";

    $str .= "  ".wordwrap($ver->property('package.description'), 75, "\n  ")."\n";

    return $str;
  }

}

?>