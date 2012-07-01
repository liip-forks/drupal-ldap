<?php

/**
 * @file
 * Hooks provided by ldap_servers module
 */

/**
 * Perform alterations of ldap attributes before query is made.
 *
 * To avoid excessive attributes in an ldap query, modules should
 * alter attributes needed based on $op parameter
 *
 * @param array $attributes
 *   array of attributes to be returned from ldap queries
 * @param enum $op
 *   context query will be run in such as 'user_update', 'user_insert', ...
 * @param mixed $server
 *   server id (sid) or server object.
 *
 */
function hook_ldap_attributes_needed_alter(&$attributes, $op, $server) {

  $attributes[] = 'dn';
  if ($server) { // puid attributes are server specific
    $ldap_server = (is_object($server)) ? $server : ldap_servers_get_servers($server, 'enabled', TRUE);

    switch ($op) {
      case 'user_insert':
      case 'user_update':
        $attributes[] = $ldap_server->user_attr;
        $attributes[] = $ldap_server->mail_attr;
        ldap_servers_token_extract_attributes($attributes,  $ldap_server_obj->mail_template);
        $attributes[] = $ldap_server->unique_persistent_attr;
      break;

    }
  }

}

/**
 * Perform alterations of ldap entry
 *
 *
 * @param array $ldap_entry
 *   array of an ldap entry as ldap php extension would return from a query
 * @param array $params context array with some or all of the following key/values
 *   'account' => drupal account object,
 *   'synch_context' => LDAP_USER_SYNCH_CONTEXT_* constant,
 *   'op' => 'create_ldap_user',
 *   'module' =>  module calling alter, e.g. 'ldap_user',
 *   'function' => function calling alter, e.g. 'provisionLdapEntry'
 *
 */

function hook_ldap_entry_alter(&$ldap_entry, $params) {


}

/**
 * Allow the results from the ldap search answer to be modified
 * The query parameters are provided as context infomation
 * (readonly)
 *
 */
function hook_ldap_server_search_results_alter(&$entries, $ldap_query_params) {
  // look for a specific part of the $results array
  // and maybe change it
}
