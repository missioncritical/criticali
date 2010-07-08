<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package support */

/**
 * Interface for providing template engines for Support_Resources
 */
interface Support_Resources_TemplateProvider {
  /**
   * Return an instance of a template engine
   * @return object
   */
  public function get();
}

?>