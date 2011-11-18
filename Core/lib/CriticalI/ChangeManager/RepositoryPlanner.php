<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * A CriticalI_ChangeManager_Planner specifically for planning changes to
 * the repository
 */
class CriticalI_ChangeManager_RepositoryPlanner extends CriticalI_ChangeManager_Planner {
  
  /**
   * Constructor
   *
   * @param array $remotes The remote sources to use
   */
  public function __construct($remotes = null) {
    parent::__construct(new CriticalI_Remote_PackageList($remotes ? $remotes :
        CriticalI_Remote_Repository::default_remotes()),
      CriticalI_Package_List::get(), true);
  }

}

?>