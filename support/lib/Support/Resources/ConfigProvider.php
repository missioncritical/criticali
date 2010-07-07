<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Interface for providing config data for Support_Resources
 */
interface Support_Resources_ConfigProvider {
  /**
   * Return the array of application configuration data.
   *
   * @return array
   */
  public function get();
}

?>