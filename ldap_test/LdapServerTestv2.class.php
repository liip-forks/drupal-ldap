<?php
// $Id: LdapServerTest.class.php,v 1.5.2.1 2011/02/08 06:01:00 johnbarclay Exp $

/**
 * @file
 * Simpletest ldapServer class for testing without an actual ldap server
 *
 */

/**
 * LDAP Server Class
 *
 *  This class is used to create, work with, and eventually destroy ldap_server
 * objects.
 *
 * @todo make bindpw protected
 */

// require_once(drupal_get_path('module', 'ldap_servers') . '/LdapServer.class.php');

ldap_servers_module_load_include('php', 'ldap_servers', 'LdapServer.class');

class LdapServerTestv2 extends LdapServer {
  // LDAP Settings

  public $entries;
  public $methodResponses;
  public $searchResults;
  public $binddn = FALSE; // Default to an anonymous bind.
  public $bindpw = FALSE; // Default to an anonymous bind.

  /**
   * Constructor Method
   *
   * can take array of form property_name => property_value
   * or $sid, where sid is used to derive the include file.
   */
  function __construct($sid) {
    if (!is_scalar($sid)) {
      $test_data = $sid;
      $sid = $test_data['sid'];
    }
    else {
      $test_data = variable_get('ldap_test_server__' . $sid, array());
    }
    // debug("test data ldapservertest.class.php, sid=$sid"); debug($test_data);

    $this->sid = $sid;
    $this->refreshFakeData();
    $this->initDerivedProperties();
  }

  public function refreshFakeData() {
   // debug("refreshFakeData sid=". $this->sid);
    $test_data = variable_get('ldap_test_server__' . $this->sid, array());
    $this->methodResponses = (is_array($test_data) && isset($test_data['methodResponses'])) ? $test_data['methodResponses'] : array();
    $this->entries = (is_array($test_data) && isset($test_data['ldap'])) ? $test_data['ldap'] : array();
  //  debug('this->entries');debug($this->entries);
    $this->searchResults = (isset($test_data['search_results'])) ? $test_data['search_results'] : array();
    $this->detailedWatchdogLog = variable_get('ldap_help_watchdog_detail', 0);
    foreach ($test_data['properties'] as $property_name => $property_value ) {
      $this->{$property_name} = $property_value;
    }
    if (is_scalar($this->basedn)) {
      $this->basedn = unserialize($this->basedn);
    }
    if (isset($server_record['bindpw']) && $server_record['bindpw'] != '') {
      $this->bindpw = ldap_servers_decrypt($this->bindpw);
    }
  }
  
  /**
   * Destructor Method
   */
  function __destruct() {
     // if alterations to server configuration must be maintained throughout simpletest, variable_set('ldap_authorization_test_server__'. $sid, array());
  }

  /**
   * Connect Method
   */
  function connect() {
    return $this->methodResponses['connect'];
  }


  function bind($userdn = NULL, $pass = NULL, $anon_bind = FALSE) {
    $userdn = ($userdn != NULL) ? $userdn : $this->binddn;
    $pass = ($pass != NULL) ? $pass : $this->bindpw;
    
    //debug("bind userdn=$userdn, pass=$pass, anon_bind=$anon_bind ");
    if (! isset($this->entries[$userdn])) {
      $ldap_errno = LDAP_NO_SUCH_OBJECT;  // 0x20 or 32
      if (function_exists('ldap_err2str')) {
        $ldap_error = ldap_err2str($ldap_errno);
      }
      else {
        $ldap_error = "Failed to find $userdn in LdapServerTestv2.class.php";
      }
      debug("failed to find $userdn in"); debug($this->entries);
    }
    elseif (isset($this->entries[$userdn]['password'][0]) && $this->entries[$userdn]['password'][0] == $pass && $pass) {
     // debug("bind success");
      return LDAP_SUCCESS;
    }
    else {
      if (!$pass) {
        debug("Simpletest failure for $userdn.  No password submitted");
      }
      if (! isset($this->entries[$userdn]['password'][0])) {
        debug("Simpletest failure for $userdn.  No password in entry to test for bind"); debug($this->entries[$userdn]);
      }
      $ldap_errno = LDAP_INVALID_CREDENTIALS;
      if (function_exists('ldap_err2str')) {
        $ldap_error = ldap_err2str($ldap_errno);
      }
      else {
        $ldap_error = "Credentials for $userdn failed in LdapServerTestv2.class.php";
      }
    }

    $watchdog_tokens = array('%user' => $userdn, '%errno' => $ldap_errno, '%error' => $ldap_error);
    watchdog('ldap', "LDAP bind failure for user %user. Error %errno: %error", $watchdog_tokens);
    return $ldap_errno;

  }

  /**
   * Disconnect (unbind) from an active LDAP server.
   */
  function disconnect() {

  }

  /**
   * Preform an LDAP search.
   *
   * @param string $filter
   *   The search filter. such as sAMAccountName=jbarclay
   * @param string $basedn
   *   The search base. If NULL, we use $this->basedn
   * @param array $attributes
   *   List of desired attributes. If omitted, we only return "dn".
   *
   * @return
   *   An array of matching entries->attributes, or FALSE if the search is
   *   empty.
   */
  function search($base_dn = NULL, $filter, $attributes = array(), $attrsonly = 0, $sizelimit = 0, $timelimit = 0, $deref = LDAP_DEREF_NEVER, $scope = LDAP_SCOPE_SUBTREE) {
    
    // debug("ldap test v2 server search base_dn=$base_dn, filter=$filter"); 
    $lcase_attribute = array();
    foreach ($attributes as $i => $attribute_name) {
      $lcase_attribute[] = drupal_strtolower($attribute_name);
    }
    $attributes = $lcase_attribute;

    $filter = trim(str_replace(array("\n", "  "),array('',''), $filter)); // for test matching simplicity remove line breaks and tab spacing

    if ($base_dn == NULL) {
      if (count($this->basedn) == 1) {
        $base_dn = $this->basedn[0];
      }
      else {
        return FALSE;
      }
    }

    // return prepolulated search results in test data array if present
    if (isset($this->searchResults[$filter][$base_dn])) {
      $results = $this->searchResults[$filter][$base_dn];
      foreach ($results as $i => $entry) {
        if (is_array($entry) && isset($entry['FULLENTRY'])) {
          unset($results[$i]['FULLENTRY']);
          $dn = $results[$i]['dn'];
          $results[$i] = $this->entries[$dn];
          $results[$i]['dn'] = $dn;
        }
      }
      return $results;
    }

    $base_dn = drupal_strtolower($base_dn);
    $filter = trim($filter,"()");

    list($filter_attribute, $filter_value) = explode('=', $filter);
    $filter_attribute =  drupal_strtolower($filter_attribute);
  //  debug("filter attribute, $filter_attribute, filter value $filter_value");
    // need to perform feaux ldap search here with data in
    $results = array();
   //debug('this->entries');
    foreach ($this->entries as $dn => $entry) {
      $dn_lcase = drupal_strtolower($dn);
       
      // if not in basedn, skip
      // eg. basedn ou=campus accounts,dc=ad,dc=myuniversity,dc=edu
      // should be leftmost string in:
      // cn=jdoe,ou=campus accounts,dc=ad,dc=myuniversity,dc=edu
      //$pos = strpos($dn_lcase, $base_dn);
      $substring = strrev(substr(strrev($dn_lcase), 0, strlen($base_dn)));
      $cascmp =  strcasecmp($base_dn, $substring);
      //debug("dn_lcase=$dn_lcase, base_dn=$base_dn,pos=$pos,substring=$substring,cascmp=$cascmp");
      if ($cascmp !== 0) {
        continue; // not in basedn
      }
      // if doesn't filter attribute has no data, continue
      $attr_value_to_compare = FALSE;
      foreach ($entry as $attr_name => $attr_value) {
        if (drupal_strtolower($attr_name) == $filter_attribute) {
          $attr_value_to_compare = $attr_value;
          break;
        }
      }
     // debug("filter value=$filter_value, attr_value_to_compare="); debug($attr_value_to_compare);
      if (!$attr_value_to_compare || drupal_strtolower($attr_value_to_compare[0]) != $filter_value) {
        continue;
      }

      // match!
     // debug("match"); debug($attr_value); debug($attributes);
      $entry['dn'] = $dn;
      if ($attributes) {
        $selected_data = array();
        foreach ($attributes as $i => $attr_name) {
          $selected_data[$attr_name] = (isset($entry[$attr_name])) ? $entry[$attr_name] : NULL;
        }
        $results[] = $selected_data;
      }
      else {
        $results[] = $entry;
      }
    }

    $results['count'] = count($results);
    //debug("ldap test server search results"); debug($results);
    return $results;
  }

/**
 * does dn exist for this server?
 *
 * @param string $dn
 * @param enum $return = 'boolean' or 'ldap_entry'
 *
 * @param return FALSE or ldap entry array
 */
  function dnExists($find_dn, $return = 'boolean', $attributes = array('objectclass')) {
    $this->refreshFakeData();
    $test_data = variable_get('ldap_test_server__' . $this->sid, array());
//    debug("testserver:dnExists test variable entry keys: find_dn=$find_dn"); debug(join(', ', array_keys($test_data['entries'])));
   // debug("testserver:dnExists,find_dn=$find_dn"); debug(array_keys($this->entries));
    foreach ($this->entries as $entry_dn => $entry) {
      $match = (strcasecmp($entry_dn, $find_dn) == 0);
      
      if ($match) {
      //  debug("testserver:dnExists,match=$match, entry_dn=$entry_dn, find_dn=$find_dn"); debug($entry);
        return ($return == 'boolean') ? TRUE : $entry;
      }
    }
   // debug("testserver:dnExists, no match"); 
    return FALSE; // not match found in loop
    
  }
  
  public function countEntries($ldap_result) {
    return ldap_count_entries($this->connection, $ldap_result);
  }

  public static function getLdapServerObjects($sid = NULL, $type = NULL, $flatten = FALSE) {
 
    $servers = array();   
    if ($sid) {
      $servers[$sid] = new LdapServerTestv2($sid);
    }
    else {
      $server_ids = variable_get('ldap_test_servers', array());
      foreach ($server_ids as $sid => $_sid) {
        $servers[$sid] = new LdapServerTestv2($sid);
      }
    }
    
    if ($flatten && $sid) {
      return $servers[$sid];
    }
    else {
      return $servers;
    }
    

  }
  
  
  /**
   * create ldap entry.
   *
   * @param array $ldap_entry should follow the structure of ldap_add functions
   *   entry array: http://us.php.net/manual/en/function.ldap-add.php
        $attributes["attribute1"] = "value";
        $attributes["attribute2"][0] = "value1";
        $attributes["attribute2"][1] = "value2";
   * @return boolean result
   */

  public function createLdapEntry($ldap_entry, $dn = NULL) {
    $result = FALSE;
    $test_data = variable_get('ldap_test_server__' . $this->sid, array());
    
    if (isset($ldap_entry['dn'])) {
      $dn = $ldap_entry['dn'];
      unset($ldap_entry['dn']);
    }

   // debug("createLdapEntry dn=$dn"); debug($ldap_entry);
   // debug('server test data before save'); debug($test_data['entries']);
    
    if ($dn && !isset($test_data['entries'][$dn])) {
      $test_data['entries'][$dn] = $ldap_entry;
      $test_data['ldap'][$dn] = $ldap_entry;
      variable_set('ldap_test_server__' . $this->sid, $test_data);
      $this->refreshFakeData();
      $result = TRUE;
      
    }
  //  $test_data2 = variable_get('ldap_test_server__' . $this->sid, array());
  //  debug('server test data after save'); debug($test_data2['entries']);
    return $result;
    
  }

  function modifyLdapEntry($dn, $attributes = NULL, $old_attributes = FALSE) {
    if (!$attributes) {
      $attributes = array();
    }
    $test_data = variable_get('ldap_test_server__' . $this->sid, array());
    //debug('test server modifyLdapEntry,dn='. $dn); debug($attributes); debug('test data'); debug($test_data['entries'][$dn]);
    if (!isset($test_data['entries'][$dn])) {
      return FALSE;
    }
    $ldap_entry = $test_data['entries'][$dn];
    
   // if (!$old_attributes) {
   //   if (is_array($entries) && $entries['count'] == 1) {
    //    $old_attributes =  $ldap_entry;
   //   }
  //  }
    
   // $attributes = $this->removeUnchangedAttributes($attributes, $old_attributes);

    foreach ($attributes as $key => $cur_val) {
    //  debug("key=$key"); debug($cur_val);
      if ($cur_val == '') {
        unset($ldap_entry[$key]);
      }
      elseif (is_array($cur_val)) {
        foreach ($cur_val as $mv_key => $mv_cur_val) {
          if ($mv_cur_val == '') {
            unset($ldap_entry[$key][$mv_key]);
          }
          else {
            if (is_array($mv_cur_val)) {
              $ldap_entry[$key][$mv_key] = $mv_cur_val;
            }
            else {
              $ldap_entry[$key][$mv_key][] = $mv_cur_val;
            }
          }
        }
        unset($ldap_entry[$key]['count']);
        $ldap_entry[$key]['count'] = count($ldap_entry[$key]);
      }
      else {
        $ldap_entry[$key][0] = $cur_val;
        $ldap_entry[$key]['count'] = count($ldap_entry[$key]);
      }
     // debug('ldap_entry.'. $key); debug($ldap_entry[$key]);
    }
    
    $test_data['entries'][$dn] = $ldap_entry;
    $test_data['ldap'][$dn] = $ldap_entry;
  //  debug("modifyLdapEntry:server test data before save $dn"); debug($test_data['entries'][$dn]);
    variable_set('ldap_test_server__' . $this->sid, $test_data);
    $this->refreshFakeData();
    return TRUE;
    
  }
  
    /**
   * Perform an LDAP delete.
   *
   * @param string $dn
   *
   * @return boolean result per ldap_delete
   */

  public function delete($dn) {

    $test_data = variable_get('ldap_test_server__' . $this->sid, array());
    if (isset($test_data['entries'][$dn])) {
      unset($test_data['entries'][$dn]);
      unset($test_data['ldap'][$dn]);
      variable_set('ldap_test_server__' . $this->sid, $test_data);
      $this->refreshFakeData();
      return TRUE;
    }
    else {
      return FALSE;
    }

  }
  
}
