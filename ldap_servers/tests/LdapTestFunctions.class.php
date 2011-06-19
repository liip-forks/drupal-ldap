<?php
// $Id$


/**
 * @file
 *  LdapTestCase.class.php
 *
 * stub for test class for any ldap module.  has functionality of fake ldap server
 * with user configurations.  Intended to help unifying test environment
 *
 */

class LdapTestFunctions  {

  function prepTestServers($servers) {
    $current_sids = array();
    foreach ($servers as $sid => $server_data) {
      $current_sids[$sid] = $sid;
      variable_set('ldap_test_server__' . $sid, $server_data);
    }
    variable_set('ldap_test_servers', $current_sids);
  }


  function configureAuthentication($options) {
    require_once(drupal_get_path('module', 'ldap_authentication') . '/LdapAuthenticationConfAdmin.class.php');

    $ldapServerAdmin = new LdapAuthenticationConfAdmin();

    foreach ($ldapServerAdmin->saveable as $prop_name) {
      if (isset( $options[$prop_name])) {
        $ldapServerAdmin->{$prop_name} = $options[$prop_name];
      }
    }
    $ldapServerAdmin->save();
  }


  function removeTestServers($sids = NULL) {

    $current_sids = variable_get('ldap_test_servers', array());
    $remaining_sids = $current_sids;
    if (! $sids) {
      $sids = $current_sids;
    }
    elseif (is_scalar($sids)) {
      $sids = array($sids);
    }
    foreach ($sids as $sid => $discard) {
      variable_del('ldap_authorization_test_server__' . $sid);  // remove fake server configuation
      unset($remaining_sids[$sid]);
    }
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
