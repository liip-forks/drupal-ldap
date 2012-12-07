<?php
/**
 * @file
 * ldap_ad_provision_ldap_users.features.inc
 */

/**
 * Implements hook_ctools_plugin_api().
 */
function ldap_ad_provision_ldap_users_ctools_plugin_api() {
  list($module, $api) = func_get_args();
  if ($module == "ldap_servers" && $api == "ldap_servers") {
    return array("version" => "1");
  }
  list($module, $api) = func_get_args();
  if ($module == "strongarm" && $api == "strongarm") {
    return array("version" => "1");
  }
}
