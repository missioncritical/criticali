<?php

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