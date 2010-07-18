<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Objects that perform validations on an ActiveRecord.
 *
 * @package activerecord
 */


/**
 * ActiveRecord_Validation is an interface for objects that can
 * perform validations on an ActiveRecord instance.  One instance of
 * an ActiveRecord_Validation may have validate invoked for multiple
 * ActiveRecord instances.
 */
abstract class ActiveRecord_Validation {
  /**
   * Type for validations that always run
   */
  const ON_SAVE   = 1;
  /**
   * Type for validations that run only on update
   */
  const ON_CREATE = 2;
  /**
   * Type for validations that always run
   */
  const ON_UPDATE = 3;

  /**
   * Validation type.  One of ON_SAVE, ON_CREATE, or ON_UPDATE.
   */
  public $type;

  /**
   * Optional condition method for determining whether or not to run
   * the validation
   */
  protected $condition;

  /**
   * Constructor
   *
   * @param int $type  The validation type (ON_SAVE, ON_CREATE, or ON_UPDATE)
   * @param string $condition Optional method to call for determining whether to run the validation or not
   */
  public function __construct($type = ActiveRecord_Validation::ON_SAVE, $condition = false) {
    $this->type = $type;
    $this->condition = $condition;
  }

  /**
   * Validate the object provided.  If the object is invalid, it is
   * the responsibility of this method to add any errors appropriate
   * to the object's error collection.
   *
   * @param ActiveRecord_Base $obj  The object to validate
   */
  abstract public function validate($obj);

  /**
   * Determine if the object should be validated or not based on the
   * configured condition
   *
   * @param ActiveRecord_Base $obj  The object to validate
   *
   * @return bool
   */
  public function should_validate($obj) {
    $condition = $this->condition;
    if (!$condition)
      return true;
    return ($obj->$condition() === false ? false : true);
  }
}

/**
 * A validation to check that a confirmation attribute for an
 * attribute exists and has the same value as the main attribute.  The
 * confirmation attribute must have the format
 * <i>attribute_name</i>_confirmation.  For example an email and an
 * email_confirmation attribute, or a password and a
 * password_confirmation attribute.
 */
class ActiveRecord_ConfirmationValidation extends ActiveRecord_Validation {
  /**
   * List of attribute names to validate
   */
  protected $attrNames;
  /**
   * Error message to use
   */
  protected $msg;

  /**
   * Constructor
   *
   * @param array  $attrNames An array of attribute names to validate
   * @param string $msg       Error message to use
   * @param int    $type      The validation type (ON_SAVE, ON_CREATE, or ON_UPDATE)
   * @param string $condition Optional method to call for determining whether to run the validation or not
   */
  public function __construct($attrNames, $msg = false, $type = ActiveRecord_Validation::ON_SAVE, $condition = false) {
    parent::__construct($type, $condition);
    $this->attrNames = $attrNames;
    $this->msg = $msg ? $msg : "doesn't match confirmation";
  }


  /**
   * Validate the object provided.  If the object is invalid, it is
   * the responsibility of this method to add any errors appropriate
   * to the object's error collection.
   *
   * @param ActiveRecord_Base $obj  The object to validate
   */
  public function validate($obj) {
    if (!$this->should_validate($obj)) return;

    foreach ($this->attrNames as $attr) {
      $conf = "${attr}_confirmation";
      if ($obj->$attr != $obj->$conf)
        $obj->errors()->add($attr, $this->msg);
    }
  }
}

/**
 * A validation to check that an attribute is non-empty (as
 * determined by the PHP function empty()).
 */
class ActiveRecord_PresentValidation extends ActiveRecord_Validation {
  /**
   * List of attribute names to validate
   */
  protected $attrNames;
  /**
   * Error message to use
   */
  protected $msg;

  /**
   * Constructor
   *
   * @param array  $attrNames An array of attribute names to validate
   * @param string $msg       Error message to use
   * @param int    $type      The validation type (ON_SAVE, ON_CREATE, or ON_UPDATE)
   * @param string $condition Optional method to call for determining whether to run the validation or not
   */
  public function __construct($attrNames, $msg = false, $type = ActiveRecord_Validation::ON_SAVE, $condition = false) {
    parent::__construct($type, $condition);
    $this->attrNames = $attrNames;
    $this->msg = $msg;
  }


  /**
   * Validate the object provided.  If the object is invalid, it is
   * the responsibility of this method to add any errors appropriate
   * to the object's error collection.
   *
   * @param ActiveRecord_Base $obj  The object to validate
   */
  public function validate($obj) {
    if (!$this->should_validate($obj)) return;

    foreach ($this->attrNames as $attr) {
      $obj->errors()->add_on_empty($attr, $this->msg);
    }
  }
}

/**
 * A validation to check that an attribute's string length is within
 * a certain range.
 */
class ActiveRecord_BoundsValidation extends ActiveRecord_Validation {
  /**
   * List of attribute names to validate
   */
  protected $attrNames;
  /**
   * Error message to use for attributes that are too long
   */
  protected $longMsg;
  /**
   * Error message to use for attributes that are too short
   */
  protected $shortMsg;
  /**
   * Minimum size
   */
  protected $lowerBound;
  /**
   * Maximum size
   */
  protected $upperBound;
  /**
   * Flag indicating whether nulls are allowed
   */
  protected $allowNull;

  /**
   * Constructor
   *
   * @param array  $attrNames  An array of attribute names to validate
   * @param int    $lowerBound The miminum size
   * @param int    $upperBound The maximum size
   * @param string $shortMsg   Error message to use for short attributes
   * @param string $longMsg    Error message to use for long attributes
   * @param bool   $allowNull  If true and the value is null, validation is skipped
   * @param int    $type       The validation type (ON_SAVE, ON_CREATE, or ON_UPDATE)
   * @param string $condition Optional method to call for determining whether to run the validation or not
   */
  public function __construct($attrNames, $lowerBound, $upperBound, $shortMsg = false, $longMsg = false, $allowNull = false, $type = ActiveRecord_Validation::ON_SAVE, $condition = false) {
    parent::__construct($type, $condition);
    $this->attrNames  = $attrNames;
    $this->lowerBound = $lowerBound;
    $this->upperBound = $upperBound;
    $this->shortMsg   = $shortMsg;
    $this->longMsg    = $longMsg;
    $this->allowNull  = $allowNull;
  }


  /**
   * Validate the object provided.  If the object is invalid, it is
   * the responsibility of this method to add any errors appropriate
   * to the object's error collection.
   *
   * @param ActiveRecord_Base $obj  The object to validate
   */
  public function validate($obj) {
    if (!$this->should_validate($obj)) return;

    foreach ($this->attrNames as $attr) {
      if ($this->allowNull && is_null($obj->$attr))
        continue;
      $obj->errors()->add_on_boundary_breaking($attr, $this->lowerBound, $this->upperBound, $this->shortMsg, $this->longMsg);
    }
  }
}

/**
 * A validation to check that an attribute's value is unique.
 */
class ActiveRecord_UniqueValidation extends ActiveRecord_Validation {
  /**
   * List of attribute names to validate
   */
  protected $attrNames;
  /**
   * Error message to use
   */
  protected $msg;
  /**
   * Flag indicating case sensitivity
   */
  protected $caseInsenstive;
  /**
   * Flag indicating whether nulls are allowed
   */
  protected $allowNull;
  /**
   * Scoping conditions
   */
  protected $scope;
  /**
   * Declaring class
   */
  protected $for_class;

  /**
   * Constructor
   *
   * @param array  $attrNames  An array of attribute names to validate
   * @param bool   $caseInsensitive If true, case insensitivy is forced (otherwise DB dependent)
   * @param array  $scope      An array of attribute names to use for scoping conditions
   * @param string $msg        Error message to use
   * @param bool   $allowNull  If true and the value is null, validation is skipped
   * @param int    $type       The validation type (ON_SAVE, ON_CREATE, or ON_UPDATE)
   * @param string $condition Optional method to call for determining whether to run the validation or not
   * @param string $forClass   Optional class name to operate on (defaults to whatever record type it is given to validate)
   */
  public function __construct($attrNames, $caseInsensitive = false, $scope = false, $msg = false, $allowNull = false, $type = ActiveRecord_Validation::ON_SAVE, $condition = false, $forClass = null) {
    parent::__construct($type, $condition);
    $this->attrNames       = $attrNames;
    $this->caseInsensitive = $caseInsensitive;
    $this->allowNull       = $allowNull;
    $this->scope           = $scope;
    $this->msg             = $msg ? $msg : "is not unique (that value has already been used)";
    $this->for_class       = $forClass;
  }


  /**
   * Validate the object provided.  If the object is invalid, it is
   * the responsibility of this method to add any errors appropriate
   * to the object's error collection.
   *
   * @param ActiveRecord_Base $obj  The object to validate
   */
  public function validate($obj) {
    if (!$this->should_validate($obj)) return;

    // determine which class to use for DB searches
    if ($this->for_class) {
      $klassName = $this->for_class;
      $klass = new $klassName();
    } else {
      $klass = $obj;
    }

    foreach ($this->attrNames as $attr) {
      $value = $obj->$attr;
      $conditions = '';
      if (is_null($value)) {
        if ($this->allowNull)
          continue;
        $conditions = $attr . ' IS NULL';
      } else {
        if ($this->caseInsenstive)
          $conditions = "LOWER($attr)=" . $klass->connection()->quote(strtolower($value));
        else
          $conditions = $attr . '=' . $klass->connection()->quote($value);
      }

      if ($this->scope) {
        foreach ($this->scope as $scopeAttr) {
          $scopeVal = $obj->$scopeAttr;
          $conditions .= " AND $scopeAttr" . (is_null($scopeVal) ? ' IS NULL' : '=' . $klass->connection()->quote($scopeVal));
        }
      }

      if (!$obj->new_record()) {
        $pk = $klass->primary_key();
        $conditions .= " AND $pk<>".$klass->connection()->quote($obj->$pk);
      }

      if ($klass->find_first(array('conditions'=>$conditions)))
        $obj->errors()->add($attr, $this->msg);
    }
  }
}

/**
 * A validation to check that an attribute's value conforms to a regular expression.
 */
class ActiveRecord_RegExValidation extends ActiveRecord_Validation {
  /**
   * List of attribute names to validate
   */
  protected $attrNames;
  /**
   * Error message to use
   */
  protected $msg;
  /**
   * Regular expression to validate against
   */
  protected $pattern;
  /**
   * Flag indicating whether nulls are allowed
   */
  protected $allowNull;

  /**
   * Constructor
   *
   * @param array  $attrNames  An array of attribute names to validate
   * @param string $pattern    Regular expression to use for validation
   * @param string $msg        Error message to use
   * @param bool   $allowNull  If true and the value is null, validation is skipped
   * @param int    $type       The validation type (ON_SAVE, ON_CREATE, or ON_UPDATE)
   * @param string $condition Optional method to call for determining whether to run the validation or not
   */
  public function __construct($attrNames, $pattern, $msg = false, $allowNull = false, $type = ActiveRecord_Validation::ON_SAVE, $condition = false) {
    parent::__construct($type, $condition);
    $this->attrNames       = $attrNames;
    $this->pattern         = $pattern;
    $this->msg             = $msg;
    $this->allowNull       = $allowNull;
  }


  /**
   * Validate the object provided.  If the object is invalid, it is
   * the responsibility of this method to add any errors appropriate
   * to the object's error collection.
   *
   * @param ActiveRecord_Base $obj  The object to validate
   */
  public function validate($obj) {
    if (!$this->should_validate($obj)) return;

    foreach ($this->attrNames as $attr) {
      if ($this->allowNull && is_null($obj->$attr))
        continue;
      if (preg_match($this->pattern, $obj->$attr) == 0)
        $obj->errors()->add($attr, $this->msg);
    }
  }
}

/**
 * A validation to check that an attribute's value is contained in a specified list.
 */
class ActiveRecord_SetValidation extends ActiveRecord_Validation {
  /**
   * List of attribute names to validate
   */
  protected $attrNames;
  /**
   * Error message to use
   */
  protected $msg;
  /**
   * List to validate against
   */
  protected $validValues;
  /**
   * Flag indicating whether nulls are allowed
   */
  protected $allowNull;
  /**
   * Flag indicating type of test being performed
   */
  protected $testExistence;

  /**
   * Constructor
   *
   * @param array  $attrNames  An array of attribute names to validate
   * @param array  $in         List of allowed/disallowed values
   * @param bool   $testInList If true, valid values MUST exist in the provided list, otherwise valid values MUST NOT exist in the list
   * @param string $msg        Error message to use
   * @param bool   $allowNull  If true and the value is null, validation is skipped
   * @param int    $type       The validation type (ON_SAVE, ON_CREATE, or ON_UPDATE)
   * @param string $condition Optional method to call for determining whether to run the validation or not
   */
  public function __construct($attrNames, $in, $testInList = true, $msg = false, $allowNull = false, $type = ActiveRecord_Validation::ON_SAVE, $condition = false) {
    parent::__construct($type, $condition);
    $this->attrNames       = $attrNames;
    $this->validValues     = array_fill_keys($in, 1);
    $this->msg             = $msg;
    $this->allowNull       = $allowNull;
    $this->testExistence   = $testInList;
  }


  /**
   * Validate the object provided.  If the object is invalid, it is
   * the responsibility of this method to add any errors appropriate
   * to the object's error collection.
   *
   * @param ActiveRecord_Base $obj  The object to validate
   */
  public function validate($obj) {
    if (!$this->should_validate($obj)) return;

    foreach ($this->attrNames as $attr) {
      $value = $obj->$attr;
      if ($this->allowNull && is_null($value))
        continue;
      $inList = isset($this->validValues[$value]);

      if ( ($this->testExistence && (!$inList)) || ((!$this->testExistence) && $inList) )
        $obj->errors()->add($attr, $this->msg);
    }
  }
}

/**
 * A validation to check that an attribute's value is numeric.
 */
class ActiveRecord_NumericValidation extends ActiveRecord_Validation {
  /**
   * List of attribute names to validate
   */
  protected $attrNames;
  /**
   * Error message to use
   */
  protected $msg;
  /**
   * Flag indicating only an integer value is allowed
   */
  protected $intOnly;
  /**
   * Flag indicating whether nulls are allowed
   */
  protected $allowNull;

  /**
   * Constructor
   *
   * @param array  $attrNames  An array of attribute names to validate
   * @param bool   $intOnly    If true, only an integer value is allowed
   * @param string $msg        Error message to use
   * @param bool   $allowNull  If true and the value is null, validation is skipped
   * @param int    $type       The validation type (ON_SAVE, ON_CREATE, or ON_UPDATE)
   * @param string $condition Optional method to call for determining whether to run the validation or not
   */
  public function __construct($attrNames, $intOnly = false, $msg = false, $allowNull = false, $type = ActiveRecord_Validation::ON_SAVE, $condition = false) {
    parent::__construct($type, $condition);
    $this->attrNames       = $attrNames;
    $this->intOnly         = false;
    $this->msg             = $msg ? $msg : "is not a number";
    $this->allowNull       = $allowNull;
  }


  /**
   * Validate the object provided.  If the object is invalid, it is
   * the responsibility of this method to add any errors appropriate
   * to the object's error collection.
   *
   * @param ActiveRecord_Base $obj  The object to validate
   */
  public function validate($obj) {
    if (!$this->should_validate($obj)) return;

    foreach ($this->attrNames as $attr) {
      $value = $obj->$attr;
      if ($this->allowNull && is_null($value))
        continue;

      if ($this->intOnly) {
        if (preg_match('/\A[+-]?\d+\Z/', $value) == 0)
          $obj->errors()->add($attr, $this->msg);
      } else {
        if (!is_numeric($value))
          $obj->errors()->add($attr, $this->msg);
      }
    }
  }
}

?>
