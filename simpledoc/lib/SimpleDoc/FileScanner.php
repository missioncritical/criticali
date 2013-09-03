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
  protected $sourceExtensions;
  protected $guideExtensions;
  protected $guideIndex;
  protected $topIndex;
  
  /**
   * Constructor
   */
  public function __construct() {
    $this->packages = array();
    $this->defaultPackage = 'default';
    $this->filePrefix = '';
    $this->sourceExtensions = array('php'=>1);
    $this->guideExtensions = array('md'=>1, 'markdown'=>1);
    $this->guideIndex = array('readme.md'=>1, 'readme.markdown'=>1, 'index.md'=>1, 'index.markdown'=>1);
    $this->topIndex = null;
    
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
    $finfo = pathinfo($filename);

    if (isset($this->sourceExtensions[$finfo['extension']]))
      $this->scan_source_file($filename);
    elseif (isset($this->guideExtensions[$finfo['extension']]))
      $this->scan_guide_file($filename, $finfo);
  }
  
  /**
   * Scan a file and use it as the top-level index for the collection
   */
  public function scan_as_index($filename) {
    $finfo = pathinfo($filename);
    $this->scan_guide_file($filename, $finfo, true);
  }
  
  /**
   * Return the index to use for the entire collection, if any
   * @return SimpleDoc_Model_Guide
   */
  protected function index_guide() {
    return $this->topIndex;
  }
  
  /**
   * Scan a source file
   */
  protected function scan_source_file($filename) {
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
   * Scan a guide/tutorial file
   */
  protected function scan_guide_file($filename, $pathinfo, $asIndex = false) {
    $text = file_get_contents($filename);
    $name = ucwords(Support_Inflector::humanize($pathinfo['filename']));
    
    $guide = new Support_Model_Guide($name, $text);
    
    $pkgName = isset($guide->tags['package']) ? $guide->tags['package'] : $this->defaultPackage;
    
    if (!$asIndex) $currentPkg = $this->named_package($pkgName);
    
    if ($asIndex) {
      $this->topIndex = $guide;

    } elseif ( isset($this->guideIndex[strtolower($pathinfo['basename'])]) &&
               (count($currentPkg->guides) > 0) && (!$currentPkg->guides[0]->is_index) ) {

      $guide->is_index = true;
      array_unshift($currentPkg->guides, $guide);
      
    } else {
      $currentPkg->guides[] = $guide;
    }
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
        $this->scan_interface($pkg, $file, $node);

      // 3) function declarations
      } elseif ($node instanceof PHPParser_Node_Stmt_Function) {
        $this->scan_function($pkg, $file, $node);
        
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
    $this->process_method_tags($klass, $docComment);
    
    $klass->is_final = $classNode->isFinal();
    
    $klass->is_abstract = $classNode->isAbstract();
    
    if ($classNode->extends) $klass->extends = $classNode->extends;
    
    foreach ($classNode->implements as $iface) $klass->add_implemented_interface($iface);
    
    // process the class body
    $this->scan_class_body($curPkg, $classNode, $klass);
  }
  
  /**
   * Add interface definition information from an interface declaration
   */
  protected function scan_interface($pkg, $file, $ifaceNode) {
    $curPkg = $pkg;
    $docComment = null;

    $commentNode = $ifaceNode->getDocComment;

    if ($commentNode) {
      $docComment = new SimpleDoc_Model_Comment($commentNode);
      
      if ($docComment->tags['nodoc'])
        return;

      if ($docComment->tags['package'] && $docComment->tags['package'] != $pkg->name)
        $curPkg = $this->named_package($docComment->tags['package']);
    }
    
    $iface = new SimpleDoc_Model_Interface($ifaceNode->name, $file->filename, $curPkg->name);
    $iface->comment = $docComment;
    
    $curPkg->classes[] = $iface;
    $file->class_names[] = $iface->name;
    
    $iface->is_final = false;
    $iface->is_abstract = true;
    
    if ($ifaceNode->extends) $iface->extends = $ifaceNode->extends;
    
    // process the interface body
    $this->scan_class_body($curPkg, $ifaceNode, $iface);
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
   * Add function definition information from a function declaration
   */
  protected function scan_function($pkg, $file, $node) {
    if (!$this->include_node_in_docs($node))
      return;
    
    $curPkg = $pkg;
    
    $comment = $node->getDocComment();
    $comment = $comment ? new SimpleDoc_Model_Comment($comment) : null;
    
    if ($comment) {
      if ($docComment->tags['nodoc'])
        return;

      if ($docComment->tags['package'] && $docComment->tags['package'] != $pkg->name)
        $curPkg = $this->named_package($docComment->tags['package']);
    }
    
    $func = new SimpleDoc_Model_Function($node->name, null, $node->byRef, $comment);
    
    // handle return information
    if ($comment && isset($comment->tags['return'])) {
      $func->type = $comment->tags['return']['type'];
      $func->return_description = $comment->tags['return']['description'];
    }
    
    // add parameters
    foreach ($node->params as $param) {
      if ($comment && isset($comment->tags['params'])) {
        $ptag = $this->tag_info_for_name($comment->tags['params'], '$' . $param->name);
      } else {
        $pTag = array();
      }
      
      $func->add_parameter(
          $param->name,
          ($param->default ? $this->printer->prettyPrintExpr($param->default) : null),
          ($param->type ? strval($param->type) : $pTag['type']),
          $param->byRef,
          $pTag['description']
        );
    }

    $curPkg->functions[] = $func;
    $file->function_names[] = $func->name;
  }

  /**
   * Process any method tags present in a class's doc comment
   */
  protected function process_method_tags($klass, $docComment) {
    if ( (!$docComment) || (!isset($docComment->tags['methods'])) )
      return;
      
    foreach ($docComment->tags['methods'] as $methInfo) {
      $methComment = new SimpleDoc_Model_Comment('');
      $methComment->text = $methInfo['description'];

      $name = $methInfo['name'];
      $byRef = false;
      if ($name[0] === '&') {
        $name = substr($name, 1);
        $byRef = true;
      }
      
      $method = $klass->add_method(
        $name,
        $methInfo['type'],
        $byRef,
        true,
        false,
        false,
        false,
        false,
        false,
        true,
        $methComment
      );
      
      foreach ($methInfo['params'] as $p) {
        $name = $p['name'];
        $byRef = false;
        if ($name[0] === '&') { $byRef = true; $name = substr($name, 1); }
        if ($name[0] === '$') $name = substr($name, 1);
        
        $method->add_parameter(
            $name,
            null,
            $p['type'],
            $byRef,
            null
        );
      }
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
        $this->process_class_method($klass, $node);
        
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
        $varTag = $this->tag_info_for_name($comment->tags['vars'], $prop->name);
        $propType = $varTag['type'];
        if ($varTag['description'] && (!trim($comment->text))) {
          $propComment = new SimpleDoc_Model_Comment('');
          $propComment->text = $varTag['description'];
        }
      }

      $klass->add_property(
        $prop->name,
        ($prop->default ? $this->printer->prettyPrintExpr($prop->default) : null),
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
   * Process a class method node
   */
  protected function process_class_method($klass, $node) {
    if (!$this->include_node_in_docs($node))
      return;
  
    $comment = $node->getDocComment();
    $comment = $comment ? new SimpleDoc_Model_Comment($comment) : null;
    
    $method = $klass->add_method(
        $node->name,
        null,
        $node->byRef,
        $node->isPublic(),
        $node->isProtected(),
        $node->isPrivate(),
        $node->isAbstract(),
        $node->isFinal(),
        $node->isStatic(),
        false,
        $comment
      );
    
    // handle return information
    if ($comment && isset($comment->tags['return'])) {
      $method->type = $comment->tags['return']['type'];
      $method->return_description = $comment->tags['return']['description'];
    }
    
    // add parameters
    foreach ($node->params as $param) {
      if ($comment && isset($comment->tags['params'])) {
        $ptag = $this->tag_info_for_name($comment->tags['params'], '$' . $param->name);
      } else {
        $pTag = array();
      }
      
      $method->add_parameter(
          $param->name,
          ($param->default ? $this->printer->prettyPrintExpr($param->default) : null),
          ($param->type ? strval($param->type) : $pTag['type']),
          $param->byRef,
          $pTag['description']
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
   * Returns the info to use from a collection of tag details (such as
   * vars or params) for a named item.
   */
  protected function tag_info_for_name($vars, $name) {
    $last = array();
    
    if (!$vars)
      return $last;
    
    foreach ($vars as $var) {
      if (($var['name'] === $name) || ("&$var[name]" === $name))
        return $var;
      else
        $last = $var;
    }
    
    if (isset($last['name']))
      return array();

    return $last;
  }
}
