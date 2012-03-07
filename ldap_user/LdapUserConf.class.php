<?php
// $Id: LdapUserConf.class.php,v 1.4.2.2 2011/02/08 20:05:41 johnbarclay Exp $

/**
 * @file
 * This class represents an ldap_user module's configuration
 * It is extended by LdapUserConfAdmin for configuration and other admin functions
 */

class LdapUserConf {

  // no need for LdapUserConf id as only one instance will exist per drupal install

  public $sids = array();  // server configuration ids being used for ldap user provisioning
  public $servers = array(); // ldap server objects
  public $provisionMethods = array(LDAP_USER_PROV_ON_LOGON, LDAP_USER_PROV_ON_MANUAL_ACCT_CREATE, LDAP_USER_PROV_ON_ALL_USER_CREATION);
  public $userConflictResolve = LDAP_USER_CONFLICT_RESOLVE_DEFAULT;
  public $acctCreation = LDAP_USER_ACCT_CREATION_LDAP_BEHAVIOR_DEFAULT;
  public $inDatabase = FALSE;

  public $saveable = array(
    'sids',
    'provisionMethods',
    'userConflictResolve',
    'acctCreation',
  );

  /** are any ldap servers that are enabled associated with ldap user **/
  public function enabled_servers() {
    return !(count(array_filter(array_values($this->sids))) == 0);
  }
  function __construct() {
    $this->load();
  }

  function load() {

    if ($saved = variable_get("ldap_user_conf", FALSE)) {
      $this->inDatabase = TRUE;
      foreach ($this->saveable as $property) {
        if (isset($saved[$property])) {
          $this->{$property} = $saved[$property];
        }
      }
      foreach ($this->sids as $sid => $is_enabled) {
        if ($is_enabled) {
          $this->servers[$sid] = ldap_servers_get_servers($sid, 'enabled', TRUE);
        }
      }

    }
    else {
      $this->inDatabase = FALSE;
    }

    // determine account creation configuration
    $user_register = variable_get('user_register', USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL);
    if ($this->acctCreation == LDAP_USER_ACCT_CREATION_LDAP_BEHAVIOR_DEFAULT || $user_register == USER_REGISTER_VISITORS) {
      $this->createLDAPAccounts = TRUE;
      $this->createLDAPAccountsAdminApproval = FALSE;
    }
    elseif ($user_register == USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL) {
      $this->createLDAPAccounts = FALSE;
      $this->createLDAPAccountsAdminApproval = TRUE;
    }
    else {
      $this->createLDAPAccounts = FALSE;
      $this->createLDAPAccountsAdminApproval = FALSE;
    }

  }

  /**
   * Destructor Method
   */
  function __destruct() {


  }


}
