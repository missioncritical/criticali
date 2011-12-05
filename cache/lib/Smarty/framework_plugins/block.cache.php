<?php
/** @package cache */

/**
 * cache Smarty function
 *
 * Cache the output from a template block and return the cached content
 * instead of evaluating the block as long as it remains in the cache.
 *
 * Options:
 *  - <b>name:</b>  The key to use for caching the content
 *  - <b>global:</b> If true, the cache key is scoped globally, not just to the action and controller
 *  - <b>profile:</b> The name of a cache profile to use (optional)
 *  - All other options are passed through to Cache_Store as cache options
 *
 * Note that if the profile option is provided, all other options except
 * key are ignored.
 *
 * @param array $options  The function options
 * @param mixed $content  The block content
 * @param Smarty &$smarty The Smarty instance
 * @param boolean &$repeat Controls whether the block is executed or not
 * @return string
 */
function smarty_block_cache($options, $content, &$smarty, &$repeat) {
  // gather the options
  $key  = isset($options['name']) ? $options['name'] : null;
  $global  = isset($options['global']) ? $options['global'] : false;
  $profile = isset($options['profile']) ? $options['profile'] : false;

  unset($options['name']);
  unset($options['global']);
  unset($options['profile']);
  $cacheOptions = $profile === false ? $options : $profile;
  
  // determine the cache key
  $controller = $global ? null : $smarty->get_template_vars('controller');
  $controllerName = $controller ? $controller->controller_name() : null;
  $action = $controller ? $controller->action() : null;

  $cacheKey = array('controller'=>$controllerName, 'action'=>$action);
  if ($key) $cacheKey['fragment'] = $key;

  $cache = Support_Resources::cache();

  // opening tag:
  if (is_null($content)) {
    $cachedContent = $cache->get($cacheKey, $cacheOptions);
    if (!is_null($cachedContent)) {
      $repeat = false;
      return $cachedContent;
    }

  // closing tag:
  } else {
    // store and return the value
    $cache->set($cacheKey, $content, $cacheOptions);
    return $content;
  }

}
