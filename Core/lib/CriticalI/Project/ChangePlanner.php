<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * A CriticalI_ChangeManager_Planner specifically for planning changes to
 * a CriticalI_Project
 */
class CriticalI_Project_ChangePlanner extends CriticalI_ChangeManager_Planner {
  
  /**
   * Constructor
   *
   * @param CriticalI_Project $project The project to plan changes for
   */
  public function __construct($project) {
    parent::__construct(CriticalI_Package_List::get(), $project->package_list(), false);
  }

}

?>