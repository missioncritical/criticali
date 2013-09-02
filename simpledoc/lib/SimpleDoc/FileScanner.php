<?php
// Copyright (c) 2013, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * This class is the producer of a a compilation (a compiler) in the
 * original sense of the word. It parses a PHP file and compiles a set
 * of information relevant to documentation.
 *
 * An instance of this class can be used to scan multiple files and the
 * collected documentation-related information from all files can then be
 * accessed by calling `package_list`.
 *
 * @package simpledoc
 */
class SimpleDoc_FileScanner {
  
  protected $packages;
  protected $defaultPackage;
  protected $filePrefix;
  protected $printer;
  protected $showPrivate = false;
  protected $showProtected = true;
  
  /**
   * Constructor
   */
  public function __construct() {
    $this->packages = array();
    $this->defaultPackage = 'default';
    $this->filePrefix = '';
    
    $this->parser = new PHPParser_Parser(new PHPParser_Lexer());
    
    $this->printer = new PHPParser_PrettyPrinter_Zend();
  }
  
  /**
   * Set the default package name to use when scanning.
   * @param string $name The name to use
   */
  public function set_default_package($name) {
    $this->defaultPackage = $name;
  }
  
  /**
   * Set the file path prefix (the portion of the file path that should
   * be ignored for documentation purposes)
   * @param string $prefix The prefix to ignore
   */
  public function set_file_prefix($prefix) {
    $this->filePrefix = $prefix;
  }
  
  /**
   * Return the list of packages produced by the scanned files
   * @return array An array of SimpleDoc_Model_Package objects
   */
  public function package_list() {
    return $this->packages;
  }
  
  /**
   * Scan a file and add its documentation information to the collection.
   */
  public function scan($filename) {
    $file = new SimpleDoc_Model_File($filename, $this->filePrefix);
    
    $aast = $this->parser->parse(file_get_contents($file->path));
    
    $firstComment = $this->first_comment($aast, $forFile);
    
    if ($forFile && $firstComment && $firstComment->tags['nodoc'])
      // stop here
      return;
    
    if ($forFile)
      $file->comment = $firstComment;
    
    $pkgName = $firstComment && $firstComment->tags['package'] ?
      $firstComment->tags['package'] : $this->defaultPackage;
    
    $currentPkg = $this->named_package($pkgName);
    
    $this->add_file_contents_to_package($currentPkg, $file, $aast);
  }
  
  /**
   * Get the first doc comment from a parsed file
   * @return SimpleDoc_Model_Comment
   */
  protected function first_comment($nodes, &$is_file_comment) {
    $is_file_comment = false;
    
    foreach ($nodes as $idx=>$n) {
      if ($n->getAttribute('comment')) {
        $docComments = array();
        foreach ($n->getAttribute('comment') as $c) {
          if ($c instanceof PHPParser_Comment_Doc) $docComments[] = $c;
        }
        
        if (count($docComments) > 1 && $idx == 0)
          $is_file_comment = true;
        
        return new SimpleDoc_Model_Comment($docComments[0]);
      }
      
    }
    
    return false;
  }
  
  /**
   * Return a named package
   * @param string $name The package to return
   * @return SimpleDoc_Model_Package
   */
  protected function named_package($name) {
    if (!isset($this->packages[$name]))
      $this->packages[$name] = new SimpleDoc_Model_Package($name);
    
    return $this->packages[$name];
  }
  
  /**
   * Traverse the parsed contents of a file and add the documentation
   * information found to a file
   */
  protected function add_file_contents_to_package($pkg, $file, $nodes) {
    $pkg->files[] = $file;
    
    // visit top-level nodes
    foreach ($nodes as $node) {
      // scan for:
      
      // 1) class declarations
      if ($node instanceof PHPParser_Node_Stmt_Class) {
        $this->scan_class($pkg, $file, $node);
        
      // 2) interface declarations
      } elseif ($node instanceof PHPParser_Node_Stmt_Interface) {

      // 3) function declarations
      } elseif ($node instanceof PHPParser_Node_Stmt_Function) {
        
      // 4) variable assignments
      } elseif ($node instanceof PHPParser_Node_Expr_Assign) {

      // 5) calls to define
      } elseif ($node instanceof PHPParser_Node_Expr_FuncCall &&
                $node->name->toString() == 'define') {
      
      // 6) variable without assignment
      } elseif ($node instanceof PHPParser_Node_Expr_Variable) {
      }
      
    }
  }
  
  /**
   * Add class definition information from a class declaration
   */
  protected function scan_class($pkg, $file, $classNode) {
    $curPkg = $pkg;
    $docComment = null;

    $commentNode = $classNode->getDocComment;

    if ($commentNode) {
      $docComment = new SimpleDoc_Model_Comment($commentNode);
      
      if ($docComment->tags['nodoc'])
        return;

      if ($docComment->tags['package'] && $docComment->tags['package'] != $pkg->name)
        $curPkg = $this->named_package($docComment->tags['package']);
    }
    
    $klass = new SimpleDoc_Model_Class($classNode->name, $file->filename, $curPkg->name);
    $klass->comment = $docComment;
    
    $curPkg->classes[] = $klass;
    $file->class_names[] = $klass->name;
    
    // process any property tags
    $this->process_property_tags($klass, $docComment);
    
    // process any method tags
    if ($docComment && $docComment->tags['methods']) {
      foreach ($docComment->tags['methods'] as $prop) {
      }
    }
    
    $klass->is_final = $classNode->isFinal();
    
    $klass->is_abstract = $classNode->isAbstract();
    
    if ($classNode->extends) $klass->extends = $classNode->extends;
    
    foreach ($classNode->implements as $iface) $klass->add_implemented_interface($iface);
    
    // process the class body
    $this->scan_class_body($curPkg, $classNode, $klass);
  }
  
  /**
   * Process any property tags present in a class's doc comment
   */
  protected function process_property_tags($klass, $docComment) {
    if ( (!$docComment) || (!isset($docComment->tags['properties'])) )
      return;
      
    foreach ($docComment->tags['properties'] as $prop) {
      $propComment = new SimpleDoc_Model_Comment('');
      $propComment->text = $prop['description'];

      $klass->add_property(
        $prop['name'],
        null,
        $prop['type'],
        true,
        false,
        false,
        false,
        true,
        $prop['rw'],
        $propComment
      );
    }
  }
  
  /**
   * Add class definition information contained in the body of a class
   * declaration
   */
  protected function scan_class_body($pkg, $classNode, $klass) {
    foreach ($classNode->stmts as $node) {

      // constants
      if ($node instanceof PHPParser_Node_Stmt_ClassConst) {
        $this->process_class_constant($klass, $node);
      
      // method declarations
      } elseif ($node instanceof PHPParser_Node_Stmt_ClassMethod) {
        
      // property declarations
      } elseif ($node instanceof PHPParser_Node_Stmt_Property) {
        $this->process_class_property($klass, $node);
        
      }
    }
  }
  
  /**
   * Process a class constant node
   */
  protected function process_class_constant($klass, $node) {
    $comment = $node->getDocComment();
    $comment = $comment ? new SimpleDoc_Model_Comment($comment) : null;

    foreach ($node->consts as $const) {
      $klass->add_constant(
        $const->name,
        ($const->value ? $this->printer->prettyPrintExpr($const->value) : null),
        $comment
      );
    }
  }
  
  /**
   * Process a class property node
   */
  protected function process_class_property($klass, $node) {
    if (!$this->include_node_in_docs($node))
      return;
  
    $comment = $node->getDocComment();
    $comment = $comment ? new SimpleDoc_Model_Comment($comment) : null;
  
    foreach ($node->props as $prop) {
      $propType = null;
      $propComment = $comment;
    
      if ($comment && isset($comment->tags['vars'])) {
        $varTag = $this->var_info_for_name($comment->tags['vars'], $prop->name);
        $propType = $varTag['type'];
        if ($varTag['description'] && (!trim($comment->text))) {
          $propComment = new SimpleDoc_Model_Comment('');
          $propComment->text = $varTag['description'];
        }
      }

      $klass->add_property(
        $prop->name,
        ($default ? $this->printer->prettyPrintExpr($prop->default) : null),
        $propType,
        $node->isPublic(),
        $node->isProtected(),
        $node->isPrivate(),
        $node->isStatic(),
        false,
        null,
        $propComment
      );
    }
  }
  
  /**
   * Return true if `$node` should be included in the documentation, false otherwise
   * @return bool
   */
  protected function include_node_in_docs($node) {
    return ( ((!$node->isPrivate()) || $this->showPrivate) &&
             ((!$node->isProtected()) || $this->showProtected) );
  }

  /**
   * Returns the var info to use from a collection of var tags for a named variable
   */
  protected function var_info_for_name($vars, $name) {
    $last = array();
    
    if (!$vars)
      return $last;
    
    foreach ($vars as $var) {
      if ($var['name'] === $name)
        return $var;
      else
        $last = $var;
    }
    
    if (isset($last['name']))
      return array();

    return $last;
  }
}
