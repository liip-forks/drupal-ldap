<?php
// $Id:$


/**
 * @file LdapTestCase.class.php
 *
 * stub for test class for any ldap module.  has functionality of fake ldap server
 * with user configurations.  Intended to help unifying test environment
 *
 */

class LdapTestFunctions  {

//   $this->testFunctions->prepTestServers($consumer_conf['sid'],  $test_data['server']);
  function prepTestServers($sid, $data) {
    $current_sids = variable_get('ldap_test_servers', array());
    if (! in_array($sid, $current_sids)) {
      $current_sids[] = $sid;
      variable_set('ldap_test_servers', $current_sids);
    }
    variable_set('ldap_test_server__'. $sid, $data);
  //  debug('prepTestServers, ldap_test_server__'. $sid); debug(variable_get('ldap_test_servers', 'empty')); debug(variable_get('ldap_test_server__'. $sid, 'empty'));
  }

  function removeTestServers($sids = NULL) {

    $current_sids = variable_get('ldap_test_servers', array());

    if (! $sids) {
      $sids = $current_sids;
    }
    elseif(is_scalar($sids)) {
      $sids = array($sids);
    }
    foreach ($sids as $sid) {
      variable_del('ldap_authorization_test_server__'. $sid);  // remove fake server configuation
    }
    $remaining_sids = array_diff($current_sids, $sids);
    variable_set('ldap_test_servers', $remaining_sids);
  }



  function ldapUserIsAuthmapped($username) {
    $authmaps = user_get_authmaps($username);
    return ($authmaps && in_array('ldap_authentication', array_keys($authmaps)));
  }

  function drupalLdapUpdateUser($edit = array(), $ldap_authenticated = FALSE, $user) {


    if (count($edit)) {
      $user = user_save($user, $edit);
    }

    if ($ldap_authenticated) {
      user_set_authmaps($user, array('authname_ldap_authentication' => $user->name));
    }

    return $user;
  }

}
