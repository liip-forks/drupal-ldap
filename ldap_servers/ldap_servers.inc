<?php
// $Id: ldap_servers.inc,v 1.4.2.1 2011/02/08 06:01:00 johnbarclay Exp $

/**
 * @file
 * collection of functions that don't belong in server object
 */

function _ldap_servers_get_user_ldap_data($drupal_user, $sid, $ldap_context) {
  
  //debug("_ldap_servers_get_user_ldap_data, drupal_user sid=$sid, direction=$direction, prov_event=$prov_event"); debug($drupal_user);
  if (is_object($drupal_user) && property_exists($drupal_user, 'uid') && $authname = db_query("SELECT authname FROM {authmap} WHERE uid = :uid AND module = 'ldap_user'", array(':uid' => $drupal_user->uid))->fetchColumn()) {
    $drupal_username = $authname;
  }
  else {
    $drupal_username =  (is_object($drupal_user)) ? $drupal_user->name : $drupal_user;
  }
  
  //dpm("sid=$sid");
  $ldap_server = ldap_servers_get_servers($sid, 'enabled', TRUE);
  if ($ldap_server === FALSE) {
    watchdog('ldap_servers', 'Failed to load server object %sid in _ldap_servers_get_user_ldap_data', array('%sid' => $sid), WATCHDOG_ERROR);
    return FALSE;
  }
  
  $ldap_user = $ldap_server->user_lookup($drupal_username, $ldap_context);
  
  //debug("_ldap_servers_get_user_ldap_data,sid=$sid, drupal_username=$drupal_username, ldap_context=$ldap_context,ldap_server="); debug($ldap_server);
 // debug("ldap_user"); debug($ldap_user);
  
  if ($ldap_user) {
    $ldap_user['sid'] = $sid;
  }
  else {
    $ldap_user = FALSE;
  }

  return $ldap_user;
}

/**
 * see ldap_servers_get_servers()
 */
function _ldap_servers_get_servers($sid, $type, $flatten, $reset) {
 // dpm('_ldap_servers_get_servers params'); dpm(array($sid, $type, $flatten, $reset));
  ldap_server_module_load_include('php', 'ldap_servers', 'LdapServerAdmin.class');
  static $servers;
  $type = ($type) ? $type : 'all';
  if ($reset) {
    $servers = array();
  }
  if (!isset($servers['all'])) {
    $servers['all'] = LdapServerAdmin::getLdapServerObjects('all', NULL, 'LdapServer', $reset);
  }

  if (!isset($servers['enabled'])) {
    $servers['enabled'] = array();
    foreach ($servers['all'] as $_sid => $ldap_server) {
      if ($ldap_server->status == 1) {
        $servers['enabled'][$_sid] = $ldap_server;
      }
    }
  }

  if ($sid) {
    if (!isset($servers[$type][$sid])) {
      return FALSE;
    }
    return ($flatten) ? $servers[$type][$sid] : array($sid => $servers[$type][$sid]);
  }

  if (isset($servers[$type])) {
    return $servers[$type];
  }
}

/**
 * see ldap_servers_get_servers()
 */
function _ldap_servers_get_simpletest_servers($sid, $type = NULL, $flatten, $reset = TRUE) {
  if (!$type) {
    $type = 'all';
  }
  
  //two flavors of mock servers exist.  ultimately v2 will be used in all simpletests
  // which stores its fake date in ldif format.
  if (variable_get('ldap_simpletest', 0) == 2) {
    ldap_server_module_load_include('php', 'ldap_test', 'LdapServerTestv2.class');
    $servers['all'] = LdapServerTestv2::getLdapServerObjects('all', NULL, 'LdapServer', $reset );
    foreach ($servers['all'] as $_sid => $ldap_server) {
      if ($ldap_server->status == 1) {
        $servers['enabled'][$_sid] = $ldap_server;
      }
    }
  }
  else {
     ldap_server_module_load_include('php', 'ldap_servers', 'tests/LdapServerTest.class');
    $servers['all'] = LdapServerTest::getLdapServerObjects('all', NULL, 'LdapServer', $reset );
    foreach ($servers['all'] as $_sid => $ldap_server) {
      if ($ldap_server->status == 1) {
        $servers['enabled'][$_sid] = $ldap_server;
      }
    }
  }


  if ($sid) {
    if (!isset($servers[$type][$sid])) {
      return FALSE;
    }
    return ($flatten) ? $servers[$type][$sid] : array($sid => $servers[$type][$sid]);
  }

  if (isset($servers[$type])) {
    return $servers[$type];
  }
}


function _ldap_servers_fields() {

$fields = array(

  'sid' => array(
    'schema' => array(
      'type' => 'varchar',
      'length' => 20,
      'not null' => TRUE,
    )
  ),

  'name' =>  array(
    'schema' => array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
    )
  ),

  'status' => array(
    'schema' => array(
      'type' => 'int',
      'size' => 'tiny',
      'not null' => TRUE,
      'default' => 1,
    )
  ),

  'type' =>  array(
    'schema' => array(
      'type' => 'varchar',
      'length' => 20,
      'not null' => FALSE,
    )
  ),

  'address' => array(
  'schema' => array(
  'type' => 'varchar',
  'length' => 255,
  'not null' => TRUE,
  )
  ),

  'port' => array(
  'schema' => array(
  'type' => 'int',
  'not null' => TRUE,
  'default' => 389,
  )
  ),

  'tls' => array(
  'schema' => array(
  'type' => 'int',
  'size' => 'tiny',
  'not null' => TRUE,
  'default' => 0,
  )
  ),

  'basedn' => array(
  'schema' => array(
  'type' => 'text',
  )
  ),

  'binddn' => array(
    'schema' => array(
  'type' => 'varchar',
  'length' => 255,
  )
  ),

  'bindpw' => array(
  'schema' => array(
  'type' => 'varchar',
  'length' => 255,
  )
  ),


  'user_attr' =>  array(
    'schema' => array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
    )
  ),

  'user_attr' =>  array(
    'schema' => array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    )
  ),


  'mail_attr' =>  array(
    'schema' => array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    )
  ),


  'ldap_to_drupal_user' =>  array(
    'schema' => array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    )
  ),

 'testing_drupal_username' =>  array(
    'schema' => array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    )
  ),

  'weight' =>  array(
  'schema' => array(
  'type' => 'int',
  'not null' => TRUE,
  'default' => 0,
  ),
  )

  );


  return $fields;
}
