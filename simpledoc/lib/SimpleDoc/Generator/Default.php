<?php
// Copyright (c) 2013, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * The default generator.
 *
 * @package simpledoc
 */
class SimpleDoc_Generator_Default extends SimpleDoc_Generator_Base {

  /**
   * Invoked by the base class to generate the documentation.
   */
  protected function run() {
    $this->title = $this->documentor->title();
    $this->packages = $this->scanner->package_list();
    
    $this->render('index.html', 'index.html');
    
    foreach ($this->packages as $pkg) {
      $this->package = $pkg;
      
      foreach ($pkg->classes as $class) {
        $this->class = $class;
        
        $this->render('class.html', $this->file_safe($pkg->name) . '/' . $this->file_safe($class->name) . '.html');
      }
    }
  }

}
