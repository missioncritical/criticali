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
    
    foreach ($this->packages as $pkg) {
      $this->package = $pkg;
      $this->files = $pkg->files;
      $this->render('files.html', $this->file_safe($this->package->name) . '-files.html');
    }
  }

}
