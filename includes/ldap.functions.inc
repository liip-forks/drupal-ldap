<?php
// $Id$

/**
 * @file
 * Other LDAP Functions
 *
 *   This file contains a collection of useful functions that do not fit in
 * any other category.
 */


/**
* get one or more ldap_api_server objects
*
* @param $server
*   - integer representing server id,
*   - or name => value array of server parameters,
*   - or instantiated server object
*   - or NULL signifying find all servers configured
*  
* @param $module
*   if present, only return servers which a module has enabled.
*
* @param $reset
*   clear any cached servers
*  
* @return
*   Array of ldap_server objects
*   or single server object if server id passed in
*/

function ldap_api_get_server_objects($server = NULL, $module = NULL, $reset = FALSE) {
  require_once('ldap_api.functions.inc');
  return _ldap_api_get_server_objects($server, $module, $reset);
}


/**
* update cache
*
*/
function ldap_api_config_update_cache() {
  $ldap_api_config = ldap_api_config_get($reset = TRUE);
}

/**
* get configuration array from cache or db
*   http://www.lullabot.com/articles/a_beginners_guide_to_caching_data
*   http://dc2009.drupalcon.org/session/inside-drupal-caching-static-variabl... *
*
* @param $reset
*   true signifies override cache or static variable and get from db
*  
* @param $sid
*   if present, only return a single server's configuration
*
* @return
*  ldap configuration as array or single ldap server configuration
*/
function ldap_api_config_get($reset = FALSE, $sid = FALSE) {
  require_once('ldap_api.functions.inc');
  return _ldap_api_config_get($reset, $sid);
}

function ldap_api_module_server_config_get($sid, $module) {
  require_once('ldap_api.functions.inc');
  $ldap_server_config = _ldap_api_config_get(FALSE, $sid);
  return $ldap_server_config['module_data'][$module];
}

function ldap_api_module_server_config_set($sid, $module, $data, $merge = FALSE) { // merge means merge arrays, if not will overwrite
  require_once('ldap_api.functions.inc');
// return _ldap_api_module_server_config_set($sid, $module, $data, $merge);  // needs to be written. 
}


/**
* get single ldap server attribute
*
* @param $sid
*   ldap server id
*  
* @param $attr
*   attribute name
*
* @module
*    module name.  if given, attribute is returned from module_data array
*
* @return
*  ldap configuration as array or single ldap server configuration
*/
function ldap_api_server_get_attribute($sid, $attr, $module) {
  require_once('ldap_api.functions.inc');
  return _ldap_api_server_get_attribute($sid, $attr, $module);
}

function ldap_api_encrypt($text) {
  require_once('ldap_api.functions.inc');
  return _ldap_api_encrypt($text);
}

function ldap_api_decrypt($encrypted, $encryption) {
  require_once('ldap_api.functions.inc');
  return _ldap_api_decrypt($encrypted, $encryption);
}

function ldap_api_ldap_extension_loaded() {
  require_once('ldap_api.functions.inc');
  return _ldap_api_ldap_extension_loaded();
}

// vim:fenc=utf-8:ft=php:ai:si:ts=2:sw=2:et:

