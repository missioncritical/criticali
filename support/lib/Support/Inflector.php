<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Implementation of rules for converting words to singular or plural form.
 *
 * @package support
 */

/**
 * Used by the Support_Inflector class to hold conversion information
 */
class Support_InflectorConversion{
  public $pattern;
  public $replace;

  public function __construct($pattern, $replace) {
    $this->pattern = $pattern;
    $this->replace = $replace;
  }
}

/**
 * The Inflector class handles conversion of words between their
 * singular and plural forms.
 */
class Support_Inflector {
  /** Singular conversions */
  protected $singulars;

  /** Plural conversions */
  protected $plurals;

  /** Matches words which are the same in both the singular and plural forms */
  protected $ignore;

  /** Shared instance */
  protected static $inst = NULL;

  /**
   * Constructor
   */
  protected function __construct() {
    $this->singulars = array();
    $this->singulars[] = new Support_InflectorConversion('/people$/i',          'person');
    $this->singulars[] = new Support_InflectorConversion('/men$/i',             'man');
    $this->singulars[] = new Support_InflectorConversion('/children$/i',        'child');
    $this->singulars[] = new Support_InflectorConversion('/sexes$/i',           'sex');
    $this->singulars[] = new Support_InflectorConversion('/moves$/i',           'move');

    $this->singulars[] = new Support_InflectorConversion('/(quiz)zes$/i',       '$1');
    $this->singulars[] = new Support_InflectorConversion('/(matr)ices$/i',      '${1}ix');
    $this->singulars[] = new Support_InflectorConversion('/(vert|ind)ices$/i',  '${1}ex');
    $this->singulars[] = new Support_InflectorConversion('/\b(ox)en$/i',        '$1');
    $this->singulars[] = new Support_InflectorConversion('/(alias|status)es$/i', '$1');
    $this->singulars[] = new Support_InflectorConversion('/(octop|vir)i$/i',    '${1}us');
    $this->singulars[] = new Support_InflectorConversion('/(cris|ax|test)es$/i', '${1}is');
    $this->singulars[] = new Support_InflectorConversion('/(shoe)s$/i',         '$1');
    $this->singulars[] = new Support_InflectorConversion('/(o)es$/i',           '$1');
    $this->singulars[] = new Support_InflectorConversion('/(bus)es$/i',         '$1');
    $this->singulars[] = new Support_InflectorConversion('/([ml])ice$/i',       '${1}ouse');
    $this->singulars[] = new Support_InflectorConversion('/(x|ch|ss|sh)es$/i',  '$1');
    $this->singulars[] = new Support_InflectorConversion('/(m)ovies$/i',        '${1}ovie');
    $this->singulars[] = new Support_InflectorConversion('/(s)eries$/i',        '${1}eries');
    $this->singulars[] = new Support_InflectorConversion('/([^aeiouy]|qu)ies$/i', '${1}y');
    $this->singulars[] = new Support_InflectorConversion('/([lr])ves$/i',       '${1}f');
    $this->singulars[] = new Support_InflectorConversion('/(tive)s$/i',         '$1');
    $this->singulars[] = new Support_InflectorConversion('/(hive)s$/i',         '$1');
    $this->singulars[] = new Support_InflectorConversion('/([^f])ves$/i',       '${1}fe');
    $this->singulars[] = new Support_InflectorConversion('/\b(analy)ses$/i',    '${1}sis');
    $this->singulars[] = new Support_InflectorConversion('/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i', '${1}${2}sis');
    $this->singulars[] = new Support_InflectorConversion('/([ti])a$/i',         '${1}um');
    $this->singulars[] = new Support_InflectorConversion('/(n)ews$/i',          '${1}ews');
    $this->singulars[] = new Support_InflectorConversion('/s$/i',               '');

    $this->plurals = array();
    $this->plurals[] = new Support_InflectorConversion('/person$/i',            'people');
    $this->plurals[] = new Support_InflectorConversion('/man$/i',               'men');
    $this->plurals[] = new Support_InflectorConversion('/child$/i',             'children');
    $this->plurals[] = new Support_InflectorConversion('/sex$/i',               'sexes');
    $this->plurals[] = new Support_InflectorConversion('/move$/i',              'moves');

    $this->plurals[] = new Support_InflectorConversion('/(quiz)$/i',            '${1}zes');
    $this->plurals[] = new Support_InflectorConversion('/\b(ox)$/i',            '${1}en');
    $this->plurals[] = new Support_InflectorConversion('/([ml])ouse$/i',        '${1}ice');
    $this->plurals[] = new Support_InflectorConversion('/(matr|vert|ind)(?:ix|ex)$/i', '${1}ices');
    $this->plurals[] = new Support_InflectorConversion('/(x|ch|ss|sh)$/i',      '${1}es');
    $this->plurals[] = new Support_InflectorConversion('/([^aeiouy]|qu)y$/i',   '${1}ies');
    $this->plurals[] = new Support_InflectorConversion('/(hive)$/i',            '${1}s');
    $this->plurals[] = new Support_InflectorConversion('/(?:([^f])fe|([lr])f)$/i', '${1}ves');
    $this->plurals[] = new Support_InflectorConversion('/sis$/i',               'ses');
    $this->plurals[] = new Support_InflectorConversion('/([ti])um$/i',          '${1}a');
    $this->plurals[] = new Support_InflectorConversion('/(buffal|tomat)o$/i',   '${1}oes');
    $this->plurals[] = new Support_InflectorConversion('/(bu)s$/i',             '${1}ses');
    $this->plurals[] = new Support_InflectorConversion('/(alias|status)$/i',    '${1}es');
    $this->plurals[] = new Support_InflectorConversion('/(octop|vir)us$/i',     '${1}i');
    $this->plurals[] = new Support_InflectorConversion('/(ax|test)is$/i',       '${1}es');
    $this->plurals[] = new Support_InflectorConversion('/s$/i',                 's');
    $this->plurals[] = new Support_InflectorConversion('/$/i',                  's');

    // words to ignore
    $this->ignore = array();
    $this->ignore[] = '/\bequipment$/i';
    $this->ignore[] = '/\binformation$/i';
    $this->ignore[] = '/\brice$/i';
    $this->ignore[] = '/\bmoney$/i';
    $this->ignore[] = '/\bspecies$/i';
    $this->ignore[] = '/\bseries$/i';
    $this->ignore[] = '/\bfish$/i';
    $this->ignore[] = '/\bsheep$/i';
  }

  /**
   * Return the shared class instance
   */
  protected static function getInstance() {
    if (!self::$inst)
      self::$inst = new Support_Inflector();
    return self::$inst;
  }

  /**
   * Implementation for pluralize
   */
  protected function pluralize_impl($word) {
    if ($this->ignored($word))
      return $word;
    foreach ($this->plurals as $conversion) {
      $count = 0;
      $word = preg_replace($conversion->pattern, $conversion->replace, $word, -1, $count);
      if ($count > 0)
        return $word;
    }
    return $word;
  }

  /**
   * Implementation for singularize
   */
  protected function singularize_impl($word) {
    if ($this->ignored($word))
      return $word;
    foreach ($this->singulars as $conversion) {
      $count = 0;
      $word = preg_replace($conversion->pattern, $conversion->replace, $word, -1, $count);
      if ($count > 0)
        return $word;
    }
    return $word;
  }

  /**
   * Test a word to see if it matches a pattern on the ignore list
   *
   * @param string $word  The word to test
   *
   * @return bool
   */
  protected function ignored($word) {
    foreach ($this->ignore as $pat) {
      if (preg_match($pat, $word))
        return true;
    }

    return false;
  }

  /**
   * Implementation for tableize
   */
  protected function tableize_impl($className) {
    return $this->pluralize_impl(self::underscore($className));
  }

  /**
   * Return the plural form of a word
   *
   * @param string $word  The word to pluralize
   *
   * @return string
   */
  public static function pluralize($word) {
    $impl = self::getInstance();
    return $impl->pluralize_impl($word);
  }

  /**
   * Return the singlar form of a word
   *
   * @param string $word  The word to singularize
   *
   * @return string
   */
  public static function singularize($word) {
    $impl = self::getInstance();
    return $impl->singularize_impl($word);
  }

  /**
   * Convert space or underscore separated words to a single mixed
   * case name.  For example, table_name becomes TableName.  If
   * $firstWordUpper is false, the first word is not converted to
   * upper case (table_name would become tableName).
   *
   * @param string $word           The word to convert
   * @param bool   $firstWordUpper Whether or not to upcase the first
   *                               word (defaults to true).
   *
   * @return string The converted word
   */
  public static function camelize($word, $firstWordUpper = TRUE) {
    $parts = preg_split('/[\s_]+/', $word);
    $final = array();
    if (!$firstWordUpper)
      $final[] = array_shift($parts);
    foreach ($parts as $part)
      $final[] = ucfirst($part);
    return implode('', $final);
  }

  /**
   * Essentially the reverse of camelize.  Converts a mixed case
   * string to a lowercase underscore separated string of words.
   *
   * @param string $word  The word to convert
   *
   * @return string The converted word
   */
  public static function underscore($word) {
    $word = preg_replace('/([A-Z]+)([A-Z][a-z])/', '${1}_${2}', $word);
    $word = preg_replace('/([a-z\d])([A-Z])/', '${1}_${2}', $word);
    $word = str_replace('-', '_', $word);
    return strtolower($word);
  }

  /**
   * Convert a class name to a table name
   *
   * @param string $className  The name to convert
   *
   * @return string
   */
  public static function tableize($className) {
    $impl = self::getInstance();
    return $impl->tableize_impl($className);
  }

  /**
   * Convert underscore separated words to a space separated string
   * of words with an initial capital letter.
   *
   * @param string $word  The string to convert
   *
   * @return string The converted string
   */
  public static function humanize($word) {
    $word = substr($word, -3) == '_id' ? substr($word, 0, -3) : $word;
    $word = str_replace('_', ' ', $word);
    return ucfirst($word);
  }
}

?>