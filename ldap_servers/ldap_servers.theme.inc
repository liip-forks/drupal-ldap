<?php
/**
 * Returns HTML for ldap servers list.
 *
 * @param $variables
 *   An associative array containing:
 *   - ldap_servers: an array of one or more ldap server configurations.
 *   - actions:  true or false indicating include update, delete, etc. links
 *   - type:  'table', 'list', etc for style to render
 *
 * @ingroup themeable
 */
function theme_ldap_servers_list($variables) {
  extract($variables);

  $table = array(
    'header' => array("Name", "Type", "Enabled", "Server Address"),
    'attributes' => array('id' => 'ldap_api_servers', 'class' => 'data'),
    'colgroups' => array(),
    'sticky' => FALSE,
    'empty' => '',
    'caption' => 'LDAP API Server Configurations',
  );

  if ($actions) {
    $table['header'][] = "Actions";
  }

  if (count($ldap_servers)) {
    foreach($ldap_servers as $sid => $ldap_server ) {
      $row =  array(
        $ldap_server->name,
        $ldap_server->type,
        ($ldap_server->status == 1) ? "Yes" : "No",
        $ldap_server->address
          );
      if ($actions) {
        $switch = ($ldap_server->status ) ? 'disable' : 'enable';
        $actions =  ' | '. l('update', LDAP_API_MENU_BASE_PATH .'/servers/edit/'. $sid) . ' | ' .
            l('delete', LDAP_API_MENU_BASE_PATH .'/servers/delete/'. $sid) . ' | ' .
            l('test', LDAP_API_MENU_BASE_PATH .'/servers/test/'. $sid) . ' | ' .
            l($switch, LDAP_API_MENU_BASE_PATH .'/servers/'. $switch .'/'. $sid) . ' | ';


        $row[] = $actions;
      }

      $table['rows'][] = $row;
    }
  } else {
    $table['rows'] = array();
  }
  $output = theme('table', $table);

  return $output;
}

/**
 * Returns HTML for ldap server.
 *
 * @param $variables
 *   An associative array containing:
 *   - ldap_servers_config: an array of one or more ldap server configurations.
 *   - actions:  true or false indicating include update, delete, etc. links
 *   - type:  'table', 'list', etc for style to render
 *
 * @ingroup themeable
 */

function theme_ldap_servers_server($variables) {
  extract($variables);  // $ldap_server, $actions, $type (see above)

  require_once('LdapServer.class.php');
  $properties = array();
  foreach (LdapServer::field_to_properties_map() as $field_name => $property_name) {
      $properties[] = "$field_name = ". $ldap_server->$property_name; // $value";
  }

  if ($actions) {
    $switch = ($ldap_server->status) ? 'disable' : 'enable';
    $properties[] =   ' | '. l('update', LDAP_API_MENU_BASE_PATH .'/servers/edit/'. $sid) . ' | ' .
        l('delete', LDAP_API_MENU_BASE_PATH .'/servers/delete/'. $sid) . ' | ' .
        l('test', LDAP_API_MENU_BASE_PATH .'/servers/test/'. $sid) . ' | ' .
        l($switch, LDAP_API_MENU_BASE_PATH .'/servers/'. $switch .'/'. $sid) . ' | ';
  }

  $output = theme_item_list(
     array(
        'items' => $properties,
        'type' => 'ul',
        'title' => 'Server Properties',
        'attributes' => array()
         )
  );

  return $output;
}