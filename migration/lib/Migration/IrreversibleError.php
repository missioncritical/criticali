<?php

class Migration_IrreversibleError extends Exception {
  public $scope;
  public $class_name;
  
  /**
   * Constructor
   *
   * @param string $scope       The scope value of the migration, if any
   * @param string $class_name  The name of the irreversible migration class
   */
  public function __construct($scope, $class_name) {
    $this->scope = $scope;
    $this->class_name = $class_name;
    
    parent::__construct("The migration class \"$class_name\"" .
      ($scope ? " (scope \"$scope\")" : '') .
      " cannot be reversed.");
  }
}
