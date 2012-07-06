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

require_once('ldap_servers.conf.inc');
require_once('ldap_user.conf.inc');
require_once('ldap_authentication.conf.inc');
require_once('ldap_authorization.conf.inc');

class LdapTestFunctionsv2  {

  public $data = array();

  function __construct() {
    $this->data['ldap_servers'] = ldap_test_ldap_servers_data();
    $this->data['ldap_user'] = ldap_test_ldap_user_data();
    $this->data['ldap_authorization'] = ldap_test_ldap_authorization_data();
    $this->data['ldap_authentication'] = ldap_test_ldap_authentication_data();
  }


  function getLdifData($ldap_file_name) {
    require_once('Net/LDAP2/LDIF.php');
    $ldif = new Net_LDAP2_LDIF(drupal_get_path('module','ldap_test') .'/test_ldap/' . $ldap_file_name, 'r');
    $entries = array();
     do {
        $entry = $ldif->read_entry();
        $values = $entry->getValues('all');
        $dn = $entry->dn();
        foreach ($values as $key => $val) {
          $entries[$dn][drupal_strtolower($key)] = $val; // make attribute names lower case to avoid case sensitivity issues in testing.
        }
        if (@$ldif->error()) {
         debug('ERROR AT INPUT LINE ' . $ldif->error_lines() . ': '. @$ldif->error(true),'error');
        }
    } while (!$ldif->eof());
    $ldif->done();

    $entries_ldap_search_format = array();
    foreach ($entries as $dn => $entry) {
      // $ldap_search_format[$dn] = $entry;
      foreach ($entry as $attr => $values) {
        if (is_scalar($values)) {
          $entries_ldap_search_format[$dn][$attr] = array();
          $entries_ldap_search_format[$dn][$attr][0] = $values;
          $entries_ldap_search_format[$dn][$attr]['count'] = 1;
        }
        else {
          $entries_ldap_search_format[$dn][$attr]['count'] = count($values);
        }
      }
    }
    return $entries_ldap_search_format;
  }


  function prepTestConfiguration($test_data, $feetures = FALSE) {
    $this->configureLdapServers($test_data['servers'], $feetures);

    if (!$feetures && isset($test_data['authentication'])) {
      $this->configureLdapAuthentication($test_data['authentication']);
    }

    if (!$feetures && isset($test_data['ldap_user'])) {
      $this->configureLdapUser($test_data['ldap_user']);
    }

    if (!$feetures && isset($test_data['authorization'])) {
      $this->prepConsumerConf($test_data['authorization']);
    }

    if (!$feetures && isset($test_data['variables'])) {
      foreach ($test_data['variables'] as $name => $value) {
        variable_set($name, $value);
      }
    }

  $consumer_conf_admin = ldap_authorization_get_consumer_admin_object('drupal_role');
  $consumer_conf_admin->status = 1;
  $consumer_conf_admin->save();

  }


  function configureLdapServers($sids, $feetures = FALSE, $feature_name = NULL) {
    //debug('configureLdapServers data'); debug($sids); debug($this->data);
    foreach ($sids as $i => $sid) {
      $current_sids[$sid] = $sid;
      $this->data['ldap_servers'][$sid]['entries'] = $this->getLdifData($sid . '.ldif');
      variable_set('ldap_test_server__' . $sid, $this->data['ldap_servers'][$sid]);
    }

    variable_set('ldap_test_servers', $current_sids);
  }

  function setFakeServerProperty($sid, $prop, $value) {
    $test_data = variable_get('ldap_test_server__' . $sid, array());
    $test_data['properties'][$prop] = $value;
    variable_set('ldap_test_server__' . $sid, $test_data);
  }
// ('jkeats@hotmail.com', 'jkeats@yahoo.com')
  function setFakeServerUserAttribute($sid, $dn, $attr_name, $attr_value, $i=0) {
    $test_data = variable_get('ldap_test_server__' . $sid, array());
   // if ($attr_value == 'jkeats@hotmail.com' || $attr_value == 'jkeats@yahoo.com') {
   //   debug("setFakeServerUserAttribute: test data before set: $sid, $dn, $attr_name, $attr_value, $i"); debug($test_data['entries']['CN=jkeats,CN=Users,DC=activedirectory,DC=ldap,DC=pixotech,DC=com']['mail']);
  //  }
    $test_data['entries'][$dn][$attr_name][$i] = $attr_value;
  //  if ($attr_value == 'jkeats@hotmail.com' || $attr_value == 'jkeats@yahoo.com') {
  //    debug('setFakeServerUserAttribute: test data after set'); debug($test_data['entries']['CN=jkeats,CN=Users,DC=activedirectory,DC=ldap,DC=pixotech,DC=com']['mail']);
  //  }
    variable_set('ldap_test_server__' . $sid, $test_data);
  }

  function configureLdapAuthentication($ldap_authentication_test_conf_id, $sids) {

    module_load_include('php', 'ldap_authentication', 'LdapAuthenticationConfAdmin.class');

    $options = $this->data['ldap_authentication'][$ldap_authentication_test_conf_id];
    foreach ($sids as $i => $sid) {
      $options['sids'][$sid] = $sid;
    }
    $ldapServerAdmin = new LdapAuthenticationConfAdmin();

    foreach ($ldapServerAdmin->saveable as $prop_name) {
      if (isset($options[$prop_name])) {
        $ldapServerAdmin->{$prop_name} = $options[$prop_name];
      }
    }
    $ldapServerAdmin->save();
  }

  function configureLdapUser($ldap_user_test_conf_id, $sids) {
    module_load_include('php', 'ldap_user', 'LdapUserConfAdmin.class');

    $ldapUserConfAdmin = new LdapUserConfAdmin();
    $options = $this->data['ldap_user'][$ldap_user_test_conf_id];
    if (!isset($options['sids'])) { // if sids for provisioning have not been set, enable all available sids
      foreach ($sids as $i => $sid) {
        $options['sids'][$sid] = TRUE;
      }
    }
    foreach ($ldapUserConfAdmin->saveable as $prop_name) {
      if (isset($options[$prop_name])) {
        $ldapUserConfAdmin->{$prop_name} = $options[$prop_name];
      }
    }
    $ldapUserConfAdmin->save();
  }

  function prepConsumerConf($consumer_confs) {
    // create consumer authorization configuration.
    foreach ($consumer_confs as $consumer_type => $consumer_conf) {
      $consumer_obj = ldap_authorization_get_consumer_object($consumer_type);
      $consumer_conf_admin = new LdapAuthorizationConsumerConfAdmin($consumer_obj, TRUE);
      foreach ($consumer_conf as $property_name => $property_value) {
        $consumer_conf_admin->{$property_name} = $property_value;
      }
      $consumer_conf_admin->save();
    }
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
