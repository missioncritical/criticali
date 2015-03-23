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
    $this->index_guide = $this->scanner->index_guide();
    
    if ((!$this->index_guide) && (count($this->packages) == 1))
      $this->index_guide = reset($this->packages)->index_guide();
    
    $this->render('index.html', 'index.html');
    
    foreach ($this->packages as $pkg) {
      $this->package = $pkg;
      
      // index
      $this->index_guide = $pkg->index_guide();
      
      $this->render('package.html', $this->file_safe($pkg->name) . '/index.html');
      
      // classes
      foreach ($pkg->classes as $class) {
        $this->class = $class;
        
        $this->render('class.html', $this->file_safe($pkg->name) . '/' . $this->file_safe($class->name) . '.html');
      }
      
      // guides
      foreach ($pkg->guides as $guide) {
        $this->guide = $guide;
        
        if (!$guide->is_index)
          $this->render('guide.html', $this->file_safe($pkg->name) . '/_guides/' . $this->file_safe($guide->name) . '.html');
      }
      
      // functions
      if (count($pkg->functions) > 0) {
        $this->functions = $pkg->functions;
        
        $this->render('functions.html', $this->file_safe($pkg->name) . '/_functions.html');
      }

    }
  }

}
