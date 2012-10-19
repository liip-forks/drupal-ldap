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

class LdapTestFunctionsv3  {

  public $data = array();
  public $ldapData = array();  // data in ldap array format, but keyed on dn
  public $csvTables = array();
  public $ldapTypeConf;
  
  function __construct() {
    $this->data['ldap_servers'] = ldap_test_ldap_servers_data();
    $this->data['ldap_user'] = ldap_test_ldap_user_data();
    $this->data['ldap_authorization'] = ldap_test_ldap_authorization_data();
    $this->data['ldap_authentication'] = ldap_test_ldap_authentication_data();
  }

 
  
  function configureLdapServers($sids, $feetures = FALSE, $feature_name = NULL) {
    //debug('configureLdapServers data'); debug($sids); debug($this->data);
    foreach ($sids as $i => $sid) {
      $current_sids[$sid] = $sid;
     // $this->data['ldap_servers'][$sid]['entries'] = $this->getLdifData($sid . '.ldif');
     // debug("configureLdapServers, $sid"); debug($this->data['ldap_servers'][$sid]['entries']);
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
// $this->testFunctions->setFakeServerUserAttribute($sid, 'cn=hpotter,ou=people,dc=hogwarts,dc=edu', 'mail', 'hpotter@owlcarriers.com', 0);
  function setFakeServerUserAttribute($sid, $dn, $attr_name, $attr_value, $i=0) {
    
    $test_data = variable_get('ldap_test_server__' . $sid, array());
 //   debug("setFakeServerUserAttribute test data keys"); debug(array_keys($test_data));
   // if ($attr_value == 'jkeats@hotmail.com' || $attr_value == 'jkeats@yahoo.com') {
   //   debug("setFakeServerUserAttribute: test data before set: $sid, $dn, $attr_name, $attr_value, $i"); debug($test_data['entries']['CN=jkeats,CN=Users,DC=activedirectory,DC=ldap,DC=pixotech,DC=com']['mail']);
  //  }
    $test_data['entries'][$dn][$attr_name][$i] = $attr_value;
    $test_data['ldap'][$dn][$attr_name][$i] = $attr_value;
  //  if ($attr_value == 'jkeats@hotmail.com' || $attr_value == 'jkeats@yahoo.com') {
  //    debug('setFakeServerUserAttribute: test data after set'); debug($test_data['entries']['CN=jkeats,CN=Users,DC=activedirectory,DC=ldap,DC=pixotech,DC=com']['mail']);
  //  }
  //  debug("setFakeServerUserAttribute v3 $sid, $dn, $attr_name, $attr_value, $i");
   // if (!empty($test_data['entries'][$dn])) {
   //   debug($test_data['entries'][$dn]);
   // }
    variable_set('ldap_test_server__' . $sid, $test_data);
    //debug($test_data);
    $ldap_server = ldap_servers_get_servers($sid, NULL, TRUE, TRUE); // clear server cache;
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

  function configureLdapUser($ldap_user_test_conf_id) {
    module_load_include('php', 'ldap_user', 'LdapUserConfAdmin.class');

    $ldapUserConfAdmin = new LdapUserConfAdmin();
    $options = $this->data['ldap_user'][$ldap_user_test_conf_id];
   // if (!isset($options['sids'])) { // if sids for provisioning have not been set, enable all available sids
    //  foreach ($sids as $i => $sid) {
     //   $options['sids'][$sid] = TRUE;
     // }
   // }
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
    return ($authmaps && in_array('ldap_user', array_keys($authmaps)));
  }

  function drupalLdapUpdateUser($edit = array(), $ldap_authenticated = FALSE, $user) {

    if (count($edit)) {
      $user = user_save($user, $edit);
    }

    if ($ldap_authenticated) {
      user_set_authmaps($user, array('authname_ldap_user' => $user->name));
    }

    return $user;
  }


 /**
   * set variable with fake test data
   *
   * @param string $test_ldap_id eg. 'hogwarts'
   * @param string $test_ldap_type e.g. openLdap, openLdapTest1, etc.
   * @parma string $sid where fake data is stored. e.g. 'default', 
   */
  public function populateFakeLdapServerData($test_ldap_id, $sid = 'default') {
    
    // read csvs into key/value array
    // create fake ldap data array
    
    $server_properties = $this->data['ldap_servers'][$sid]['properties'];
   // dpm($server_properties);
    $this->getCsvLdapData($test_ldap_id);
  //  dpm($this->csvTables);
    foreach ($this->csvTables['users'] as $guid => $user) {
      $dn = 'cn=' . $user['cn'] . ',' . $this->csvTables['conf'][$test_ldap_id]['userbasedn'];
      $this->csvTables['users'][$guid]['dn'] = $dn;
      $attributes = array(
        'cn' => array(
          0 => $user['cn'],
          'count' => 1,
        ),
        'mail' => array(
          0 => $user['cn'] . '@' . $this->csvTables['conf'][$test_ldap_id]['mailhostname'],
          'count' => 1,
        ),
        'uid' => array(
          0 => $user['uid'],
          'count' => 1,
        ),
        'guid' => array(
          0 => $user['guid'],
          'count' => 1,
        ),
        'sn' => array(
          0 => $user['lname'],
          'count' => 1,
        ),
        'givenname' => array(
          0 => $user['fname'],
          'count' => 1,
        ),
        'house' => array(
          0 => $user['house'],
          'count' => 1,
        ),
        'department' => array(
          0 => $user['department'],
          'count' => 1,
        ),
        'faculty' => array(
          0 => (int)(boolean)$user['faculty'],
          'count' => 1,
        ),
        'staff' => array(
          0 => (int)(boolean)$user['staff'],
          'count' => 1,
        ),
        'student' => array(
          0 => (int)(boolean)$user['student'],
          'count' => 1,
        ),
        'gpa' => array(
          0 => $user['gpa'],
          'count' => 1,
        ),
        'probation' => array(
          0 => (int)(boolean)$user['probation'],
          'count' => 1,
        ),
        'password'  => array(
          0 => 'goodpwd',
          'count' => 1,
        ),
      );
      if ($server_properties['ldap_type']  == 'activedirectory') {
        $attributes[$server_properties['user_attr']] =  array( 0 => $user['cn'], 'count' => 1);
        $attributes['distinguishedname'] =  array( 0 => $dn, 'count' => 1);
      }
      elseif ($server_properties['ldap_type']  == 'openldap') {
        
      }
      
      $this->data['ldap_servers'][$sid]['users'][$dn]['attr'] = $attributes;
      $this->data['ldap_servers_by_guid'][$sid][$user['guid']]['attr'] = $attributes;
      $this->data['ldap_servers_by_guid'][$sid][$user['guid']]['dn'] = $dn;
      $this->ldapData['ldap_servers'][$sid][$dn] = $attributes;
      $this->ldapData['ldap_servers'][$sid][$dn]['count'] = count($attributes);
       
    }
    

    foreach ($this->csvTables['groups'] as $guid => $group) {
      //guid,gid,cn
      // 201,1,gryffindor
      $dn = 'cn=' . $group['cn'] . ',' . $this->csvTables['conf'][$test_ldap_id]['groupbasedn'];
      $this->csvTables['groups'][$guid]['dn'] = $dn;
      $attributes = array(
        'cn' => array(
          0 => $group['cn'],
          'count' => 1,
        ),
        'gid' => array(
          0 => $group['gid'],
          'count' => 1,
        ),
        'guid' => array(
          0 => $guid,
          'count' => 1,
        ),
      );

      if ($server_properties['groupMembershipsAttr']) {
        // gid,group_cn,member_guid
        // 1,gryffindor,101
        $membershipAttr = $server_properties['groupMembershipsAttr'];
        foreach ($this->csvTables['memberships'] as $membership_id => $membership) {
         // dpm("$gid == " . $group['gid']);
          
          if ($membership['gid'] == $group['gid']) {
            $member_guid = $membership['member_guid'];
            if (isset($this->csvTables['users'][$member_guid])) {
              $member = $this->csvTables['users'][$member_guid];
            }
            elseif (isset($this->csvTables['groups'][$member_guid])) {
              $member = $this->csvTables['groups'][$member_guid];
            }
           // dpm("group=$gid"); dpm($membership); dpm($member);
            if ($server_properties['groupMembershipsAttrMatchingUserAttr'] == 'dn') {
              $attributes[$server_properties['groupMembershipsAttr']][] = $member['dn'];
            }
            else {
              $attributes[$server_properties['groupMembershipsAttr']][] = $member['attr'][$membershipAttr][0];
            }
          }
        }
        $attributes[$membershipAttr]['count'] = count($attributes[$membershipAttr]);
        
      }
      // need to figure out if memberOf type attribute is desired and populate it
      $this->data['ldap_servers_by_guid'][$sid][$group['guid']]['attr'] = $attributes;
      $this->data['ldap_servers_by_guid'][$sid][$group['guid']]['dn'] = $dn;
      $this->data['ldap_servers'][$sid]['groups'][$dn]['attr'] = $attributes;
      $this->ldapData['ldap_servers'][$sid][$dn] = $attributes;
       
    }
    if ($server_properties['groupUserMembershipsAttrExists']) {
      $member_attr = $server_properties['groupUserMembershipsAttr'];
      foreach ($this->csvTables['memberships'] as $gid => $membership) {
        $group_dn =  $this->data['ldap_servers_by_guid'][$sid][$membership['group_guid']]['dn'];
        $user_dn =  $this->data['ldap_servers_by_guid'][$sid][$membership['member_guid']]['dn'];
        $this->ldapData['ldap_servers'][$sid][$user_dn][$member_attr][] = $group_dn;
        if (isset($this->ldapData['ldap_servers'][$sid][$user_dn][$member_attr]['count'])) {
          unset($this->ldapData['ldap_servers'][$sid][$user_dn][$member_attr]['count']);
        }
        $this->ldapData['ldap_servers'][$sid][$user_dn][$member_attr]['count'] =
        count( $this->ldapData['ldap_servers'][$sid][$user_dn][$member_attr]);
      }
    }
    
    //dpm('this->data'); dpm("sid=$sid"); dpm($this->data['ldap_servers'][$sid]);
    $this->data['ldap_servers'][$sid]['ldap'] =  $this->ldapData['ldap_servers'][$sid];
    $this->data['ldap_servers'][$sid]['csv'] =  $this->csvTables;
    variable_set('ldap_test_server__' . $sid, $this->data['ldap_servers'][$sid]);
    $current_sids = variable_get('ldap_test_servers', array());
    $current_sids[] = $sid;
    variable_set('ldap_test_servers', array_unique($current_sids));
  }
  
  public function getCsvLdapData($test_ldap_id) {
    foreach (array('groups','users','memberships','conf') as $type) {
      $path = drupal_get_path('module','ldap_test') . '/test_ldap/'. $test_ldap_id . '/' . $type . '.csv';
      $this->csvTables[$type] = $this->parseCsv($path);
    }
  }
  
  public function parseCsv($filepath) {
    $row = 1;
    $table = array();
    if (($handle = fopen($filepath, "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        if (count($data) > 1) {
          $table[] = $data;
        }
      }
      fclose($handle);
    }
    
    $table_associative = array();
    $headings = array_shift($table);
    foreach ($table as $i => $row) {
      $row_id = $row[0];
      foreach ($row as $j => $item) {
        $table_associative[$row_id][$headings[$j]] = $item;
      }
    }
    
    return $table_associative;

  }
  
}
