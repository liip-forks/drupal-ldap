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
        ldap_servers_extract_attributes_from_token($attributes,  $ldap_server_obj->mail_template);
        $attributes[] = $ldap_server->unique_persistent_attr;
      break;

    }
  }

}
