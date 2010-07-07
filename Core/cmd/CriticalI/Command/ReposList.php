<?php

/**
 * List command
 */
class Vulture_Command_ReposList extends Vulture_Command {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct('list', 'List the packages in the repository', <<<DESC
  vulture [options] [search]
  
Lists the packages currently installed in the repository.
If a search term is provided, only packages whose names
contain the search string are displayed.
DESC
, array(
  new Vulture_OptionSpec('details', Vulture_OptionSpec::NONE, null, 'Output details, including full description, with each package.'),
  new Vulture_OptionSpec('no-versions', Vulture_OptionSpec::NONE, null, 'Do not include version numbers in output.'),
  new Vulture_OptionSpec('verbose', Vulture_OptionSpec::NONE, null, 'Include summary in listing.'),
  new Vulture_OptionSpec('version', Vulture_OptionSpec::REQUIRED, 'version', 'The version number to list.')));
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
    
    // evaluate any name criteria
    if (count($this->args) > 0) {
      foreach (Vulture_Package_List::get() as $pkg) {
        if (strpos(strtolower($pkg->name()), strtolower($this->args[0])) !== false)
          $matches[] = $pkg;
      }
    } else {
      $matches = Vulture_Package_List::get();
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
   * Show a simple listing
   */
  protected function show_listing($pkgs) {
    foreach ($pkgs as $pkg) {
      if (isset($this->options['version']))
        print $this->format_version($pkg[$this->options['version']]) . "\n";
      else
        print $this->format_package($pkg) . "\n";
    }
  }
  
  /**
   * Format the display of a package in the listing
   */
  protected function format_package($pkg) {
    $str = $pkg->name();
    
    if (isset($this->options['details'])) {
      $str .= "\n";
      if (!isset($this->options['no-versions']))
        $str .= "  Version(s): ".wordwrap($pkg->versions_string(), 75, "\n              ")."\n";
      $str .= "  Summary:    ".wordwrap($pkg->newest()->property('package.summary'), 75, "\n              ")."\n";
      $str .= "\n";
      $str .= "  ".wordwrap($pkg->newest()->property('package.description'), 75, "\n  ")."\n";
    } else {
      if (!isset($this->options['no-versions']))
        $str .= ' ('.$pkg->versions_string().')';
      if (isset($this->options['verbose']))
        $str .= ' - ' . $pkg->newest()->property('package.summary', '');
    }

    return $str;
  }
  
  /**
   * Format the display of a version in the listing
   */
  protected function format_version($ver) {
    $str = $ver->package()->name();
    
    if (isset($this->options['details'])) {
      $str .= "\n";
      if (!isset($this->options['no-versions']))
        $str .= "  Version(s): ".wordwrap($ver->version_string(), 75, "\n              ")."\n";
      $str .= "  Summary:    ".wordwrap($ver->property('package.summary'), 75, "\n              ")."\n";
      $str .= "\n";
      $str .= "  ".wordwrap($ver->property('package.description'), 75, "\n  ")."\n";
    } else {
      if (!isset($this->options['no-versions']))
        $str .= ' ('.$ver->version_string().')';
      if (isset($this->options['verbose']))
        $str .= ' - ' . $ver->property('package.summary', '');
    }

    return $str;
  }

}

?>