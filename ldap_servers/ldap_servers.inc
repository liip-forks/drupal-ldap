<?php


/**
 * see ldap_servers_get_servers()
 */
function _ldap_servers_get_servers($sid, $type, $flatten, $reset) {
 // dpm('_ldap_servers_get_servers params'); dpm(array($sid, $type, $flatten, $reset));
  ldap_servers_module_load_include('php', 'ldap_servers', 'LdapServerAdmin.class');
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
  if (variable_get('ldap_simpletest', 0) == 2) {
    ldap_servers_module_load_include('php', 'ldap_test', 'LdapServerTestv2.class');
    $servers['all'] = LdapServerTestv2::getLdapServerObjects(NULL, 'all', FALSE);
    foreach ($servers['all'] as $_sid => $ldap_server) {
      if ($ldap_server->status == 1) {
        $servers['enabled'][$_sid] = $ldap_server;
      }
    }
    // dpm('servers'); dpm($servers);
  }
  else {
     ldap_servers_module_load_include('php', 'ldap_servers', 'tests/LdapServerTest.class');
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

