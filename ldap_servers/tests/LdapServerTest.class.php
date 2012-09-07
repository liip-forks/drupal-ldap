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

require_once(drupal_get_path('module', 'ldap_servers') . '/LdapServer.class.php');

class LdapServerTest extends LdapServer {
  // LDAP Settings

  public $testUsers;
  public $testGroups;
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
    }
    else {
      $test_data = variable_get('ldap_test_server__' . $sid, array());
    }
    $this->sid = $sid;
    $this->methodResponses = $test_data['methodResponses'];
    $this->testUsers = $test_data['users'];
    $this->testGroups = (is_array($test_data) && isset($test_data['groups'])) ? $test_data['groups'] : array();
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

    if (! isset($this->testUsers[$userdn])) {
      $ldap_errno = LDAP_NO_SUCH_OBJECT;
      if (function_exists('ldap_err2str')) {
        $ldap_error = ldap_err2str($ldap_errno);
      }
      else {
        $ldap_error = "Failed to find $userdn in LdapServerTest.class.php";
      }
    }
    elseif (isset($this->testUsers[$userdn]['attr']['password'][0]) && $this->testUsers[$userdn]['attr']['password'][0] != $pass) {
      $ldap_errno = LDAP_INVALID_CREDENTIALS;
      if (function_exists('ldap_err2str')) {
        $ldap_error = ldap_err2str($ldap_errno);
      }
      else {
        $ldap_error = "Credentials for $userdn failed in LdapServerTest.class.php";
      }
    }
    else {
      return LDAP_SUCCESS;
    }

    debug(t("LDAP bind failure for user %user. Error %errno: %error",
      array('%user' => $userdn,
            '%errno' => $ldap_errno,
            '%error' => $ldap_error,
      )));

    return $ldap_errno;

  }

/**
 * does dn exist for this server?
 *
 * @param string $dn
 * @param enum $return = 'boolean' or 'ldap_entry'
 *
 * @param return FALSE or ldap entry array
 */
  function dnExists($dn, $return = 'boolean', $attributes = array('objectclass')) {

    $params = array(
      'base_dn' => $dn,
      'attributes' => $attributes,
      'attrsonly' => TRUE,
      'filter' => '(objectclass=*)',
      'sizelimit' => 0,
      'timelimit' => 0,
      'deref' => NULL,
    );
 
    $result = $this->ldapQuery(LDAP_SCOPE_BASE, $params);
   
    if ($result && ($this->countEntries($result) !== FALSE)) {
       return ($return == 'boolean') ? TRUE : $result[0];
    }
    else {
       return FALSE;
    }
  }
  
  /**
   * Disconnect (unbind) from an active LDAP server.
   */
  function disconnect() {

  }

  /**
   * Perform an LDAP search.
   * @param string $basedn
   *   The search base. If NULL, we use $this->basedn. should not be esacaped
   *
   * @param string $filter
   *   The search filter. such as sAMAccountName=jbarclay.  attribute values (e.g. jbarclay) should be esacaped before calling

   * @param array $attributes
   *   List of desired attributes. If omitted, we only return "dn".
   *
   * @remaining params mimick ldap_search() function params
   *
   * @return
   *   An array of matching entries->attributes, or FALSE if the search is empty.
   */
  function search($base_dn = NULL, $filter, $attributes = array(), $attrsonly = 0, $sizelimit = 0, $timelimit = 0, $deref = LDAP_DEREF_NEVER, $scope = LDAP_SCOPE_SUBTREE) {

    $filter = trim(str_replace(array("\n", "  "),array('',''), $filter)); // for test matching simplicity remove line breaks and tab spacing
    //debug('search ldapservertest v0');  debug("base_dn: $base_dn"); debug("filter:<pre>$filter</pre>");
    $my_debug = ($base_dn == 'ou=guest accounts,dc=ad,dc=myuniversity,dc=edu' && $filter == "(sAMAccountName=wilmaf)");
  //  if ($my_debug) {debug('search');  debug("base_dn: $base_dn"); debug("filter:<pre>$filter</pre>");}

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
      return $this->searchResults[$filter][$base_dn];
    }

    $base_dn = drupal_strtolower($base_dn);
    $filter = strtolower(trim($filter,"()"));

    list($filter_attribute, $filter_value) = explode('=', $filter);
    $filter_attribute = drupal_strtolower($filter_attribute);
    // need to perform feaux ldap search here with data in
    $results = array();
   // debug('test users'); debug($this->testUsers); debug("filter_attribute=$filter_attribute, filter_value=$filter_value");
    foreach ($this->testUsers as $dn => $user_data) {
      $user_data_lcase = array();
      foreach ($user_data['attr'] as $attr => $values) {
        $user_data_lcase['attr'][strtolower($attr)] = $values;
      }
      $dn = strtolower($dn);
      $substring = strrev(substr(strrev($dn), 0, strlen($base_dn)));
      if (strcasecmp($base_dn, $substring) !== 0) {
        continue; // not in basedn
      }
   
      if (isset($user_data['attr'][$filter_attribute])) {
        $contained_values = $user_data['attr'][$filter_attribute];
      }
      elseif (isset($user_data_lcase['attr'][$filter_attribute])) {
        $contained_values = $user_data_lcase['attr'][$filter_attribute];
      }
      else {
        continue;
      }

      unset($contained_values['count']);
      if (!in_array($filter_value, array_values($contained_values))) {
        continue;
      }

      // loop through all attributes, if any don't match continue
      $user_data_lcase['attr']['dn'] = $dn;
      if ($attributes) {
        $selected_user_data = array();
        foreach ($attributes as $i => $attr_name) {
          $attr_name = strtolower($attr_name);
          $selected_user_data[$attr_name] = (isset($user_data_lcase['attr'][$attr_name])) ? $user_data_lcase['attr'][$attr_name] : NULL;
        }
        $results[] = $selected_user_data;
      }
      elseif (isset($user_data_lcase['attr'])) {
        $results[] = $user_data_lcase['attr'];
      }
    }
//    if ($my_debug) { debug("results post user loop"); debug($results);}
    foreach ($this->testGroups as $dn => $group_data) {
      // debug("group dn $dn"); debug($group_data);
      // if not in basedn, skip
      // eg. basedn ou=campus accounts,dc=ad,dc=myuniversity,dc=edu
      // should be leftmost string in:
      // cn=jdoe,ou=campus accounts,dc=ad,dc=myuniversity,dc=edu
      $dn = strtolower($dn);
      $substring = strrev(substr(strrev($dn), 0, strlen($base_dn)));
      if (strcasecmp($base_dn, $substring) !== 0) {
        continue; // not in basedn
      }


      // if doesn't filter attribute has no data, continue
      if (!isset($group_data['attr'][$filter_attribute])) {
        continue;
      }

      // if doesn't match filter, continue
      $contained_values = $group_data['attr'][$filter_attribute];
      unset($contained_values['count']);
      if (!in_array($filter_value, array_values($contained_values))) {
        continue;
      }

      // loop through all attributes, if any don't match continue
      $group_data['attr']['dn'] = $dn;
      $group_data['distinguishedname'] = $dn;

      if ($attributes) {
        $selected_group_data = array();
        foreach ($attributes as $key => $value) {
          $selected_group_data[$key] = (isset($group_data['attr'][$key])) ? $group_data['attr'][$key] : NULL;
        }
        $results[] = $selected_group_data;
      }
      else {
        $results[] = $group_data['attr'];
      }
     // debug($results);
    }

    $results['count'] = count($results);
    $results = ($results['count'] > 0) ? $results : FALSE;
 // if ($my_debug) {  debug('search-results 2'); debug($results);}
    return $results;
  }


  public static function getLdapServerObjects($sid = NULL, $type = NULL, $class = 'LdapServerTest') {

    $server_ids = variable_get('ldap_test_servers', array());
    $servers = array();
    foreach ($server_ids as $sid => $_sid) {
      $server_data = variable_get('ldap_test_server__' . $sid, array());
      $servers[$sid] = new LdapServerTest($server_data);
    }

    return $servers;

  }



}
