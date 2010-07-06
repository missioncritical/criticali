<?php

require_once('Smarty/Smarty.class.php');

/**
 * The Smarty template engine provider.  This provider returns an
 * instance of Smarty.
 */
class Smarty_TemplateProvider implements Support_Resources_TemplateProvider {
  /**
   * Return a template engine instance.
   *
   * @return object
   */
  public function get() {
    $smarty = new Smarty();

    $here = dirname(__FILE__);

    $smarty->config_dir    = Cfg::get('Smarty/ConfigDir',   "$here");
    $smarty->template_dir  = Cfg::get('Smarty/TemplateDir', "$here/../../../views");
    $smarty->compile_dir   = Cfg::get('Smarty/CompileDir',  "$here/../../../var/templates_c");
    $smarty->cache_dir     = Cfg::get('Smarty/CacheDir',    "$here/../../../var/cache");
    $smarty->plugins_dir[] = "$here/../framework_plugins";
    $smarty->plugins_dir[] = Cfg::get('Smarty/PluginsDir',  "$here/../../../helper_plugins");

    return $smarty;
  }
}

Support_Resources::register_template_engine(new Smarty_TemplateProvider(), 'smarty', true);

?>