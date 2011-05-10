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
  public $methodResponses;
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
      $test_data = variable_get('ldap_authorization_test_server__'. $sid, array());
    }

    $this->sid = $sid;
    $this->methodResponses = $test_data['methodResponses'];
    $this->testUsers = $test_data['users'];

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


  function bind($userdn = NULL, $pass = NULL) {

    if (! isset($this->testUsers[$userdn])) {
      $ldap_errno = LDAP_NO_SUCH_OBJECT;
      if (function_exists('ldap_err2str')) {
         $ldap_error = ldap_err2str($ldap_errno);
      }
      else {
         $ldap_error = "Failed to find $userdn in LdapServerTest.class.php";
      }
    }
    elseif ($this->testUsers[$userdn]['password'] != $pass) {
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

    watchdog('ldap', "LDAP bind failure for user %user. Error %errno: %error",
      array('%user' => $userdn,
            '%errno' => $ldap_errno,
            '%error' => $ldap_error,
       ));

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
  function search($filter, $basedn = NULL, $attributes = array()) {
    if ($basedn == NULL) {
      if (count($this->basedn) == 1) {
        $basedn = $this->basedn[0];
      }
      else {
        return FALSE;
      }
    }
    $basedn = strtolower($basedn);
    $filter_parts = explode('=', $filter);
    // need to perform feaux ldap search here with data in
    $results = array();
    foreach ($this->testUsers as $dn => $user_data) {


      // if not in basedn, skip
      // eg. basedn ou=campus accounts,dc=ad,dc=myuniveristy,dc=edu
      // should be leftmost string in:
      // cn=jdoe,ou=campus accounts,dc=ad,dc=myuniveristy,dc=edu
      $pos = strpos($dn, $basedn);
      if ($pos === FALSE || strcasecmp($basedn, substr($dn, 0, $pos + 1)) == FALSE) {
        continue; // not in basedn
      }

      // if doesn't match filter, continue
      if ($user_data[$filter_parts[0]] != $filter_parts[1]) {
        continue;
      }
      // loop through all attributes, if any don't match continue
      $user_data['dn'] = $dn;
      if ($attributes) {
        $selected_user_data = array();
        foreach ($attributes as $key => $value) {
          $selected_user_data[$key] = (isset($user_data[$key])) ? $user_data[$key] : NULL;
        }
        $results[] = $selected_user_data;
      }
      else {
        $results[] = $user_data;
      }
    }

    return $results;
  }


  public static function getLdapServerObjects($sid = NULL, $type = NULL, $class = 'LdapServerTest') {

    $servers = variable_get('ldap_test_servers', array());

    foreach ($servers as $sid => $server) {
      $server_data = variable_get('ldap_test_server__'. $sid, array());
      $servers[$sid] = new LdapServerTest($server_data);
    }
    return $servers;

  }

}
