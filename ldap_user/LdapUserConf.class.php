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
  public $provisionMethods = array(LDAP_USER_PROV_ON_LOGON, LDAP_USER_PROV_ON_MANUAL_ACCT_CREATE);
  public $userConflictResolve = LDAP_USER_CONFLICT_RESOLVE_DEFAULT;
  public $acctCreation = LDAP_USER_ACCT_CREATION_LDAP_BEHAVIOR_DEFAULT;
  public $inDatabase = FALSE;
  public $synchMapping = NULL; // array of field synching directions for each operation
  // $this->synchMapping[LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER]['property_mail'][sid] => array(LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER, LDAP_USER_SYNCH_CONTEXT_UPDATE_DRUPAL_USER),

  public $synchTypes = NULL; // array of synch types (keys) and user friendly names (values)

  public $saveable = array(
    'sids',
    'provisionMethods',
    'userConflictResolve',
    'acctCreation',
    'synchMapping',
  );

  /** are any ldap servers that are enabled associated with ldap user **/
  public function enabled_servers() {
    return !(count(array_filter(array_values($this->sids))) == 0);
  }
  function __construct() {
    $this->load();

    $this->synchTypes = array(
      LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER => t('On User Creation'),
      LDAP_USER_SYNCH_CONTEXT_UPDATE_DRUPAL_USER => t('On User Update/Save'),
      LDAP_USER_SYNCH_CONTEXT_AUTHENTICATE_DRUPAL_USER => t('On User Logon'),
      LDAP_USER_SYNCH_CONTEXT_CRON => t('Via Cron Batch'),
    );
    $this->setSynchMapping();

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
  function __destruct() { }

  function getLdapUserEntry($drupal_username, $op) { //
   // dpm('getLdapUserEntry');
    foreach ($this->sids as $sid) {
   //   dpm("getLdapUserEntry.sid=$sid, drupal_username=$drupal_username, op=$op");
      $ldap_server = ldap_servers_get_servers($sid, 'enabled', TRUE);
     // dpm("getLdapUserEntry.ldap_server"); dpm($ldap_server);
      if ($ldap_server && $ldap_user = $ldap_server->user_lookup($drupal_username, $op)) {
     //   dpm("getLdapUserEntry.ldap_user"); dpm($ldap_user);
        return $ldap_user;
      }
    }
    return FALSE;
  }

  /** given configuration of synching, determine is a given synch should occur
   */

  function isSynched($field, $op, $direction) {
    return (isset($this->synchMapping[$direction][$field]) && in_array($op, $this->synchMapping[$direction][$field]));
  }


  /**
    derive mapping array from ldap user configuration and other configurations.
    if this becomes a resource hungry function should be moved to ldap_user functions
    and stored with static variable

  **/

  function setSynchMapping() {
    // @todo.  these are hard coded in, but need to be based on ldap user, ldap authentication, etc conf.
   /** $all = array(LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER, LDAP_USER_SYNCH_CONTEXT_UPDATE_DRUPAL_USER, LDAP_USER_SYNCH_CONTEXT_AUTHENTICATE_DRUPAL_USER);
    $this->synchMapping[LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER] = array(
      'property_mail' => array(LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER, LDAP_USER_SYNCH_CONTEXT_UPDATE_DRUPAL_USER),
      'property_name' => array(LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER, LDAP_USER_SYNCH_CONTEXT_UPDATE_DRUPAL_USER),
      'field_ldap_user_puid' => $all,
      'field_ldap_user_puid_property' => $all,
      'field_ldap_user_puid_sid' => $all,
      'field_ldap_user_current_dn' => $all,
      );
  **/
   // $this->synchMapping[LDAP_USER_SYNCH_DIRECTION_TO_LDAP_ENTRY] = array();

    drupal_alter('ldap_user_synch_mapping', $this->synchMapping);
  }


  /** populate $user edit array (used in hook_user_save, hook_user_update, etc)
   * ... should not assume all attribues are present in ldap entry
   *
   * @param array ldap entry $user_ldap_entry
   * @param array $edit see hook_user_save, hook_user_update, etc
   * @param drupal account object $account
   * @param string $op see hook_ldap_attributes_needed_alter
   */

  function entryToUserEdit($user_ldap_entry, $ldap_server, &$edit, $account, $op) {
    // need array of user fields and which direction and when they should be synched.

    if ($this->synch('property.mail', $op, LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER)) {
      $mail = $ldap_server->deriveEmailFromEntry($user_ldap_entry);
      if ($mail) {
        $edit['mail'] = $mail;
      }
    }
  //  dpm("this->synchMapping,op=$op,direction=" . LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER); dpm($this->synchMapping);
    /**
     * basic $user ldap fields
     */
    if ($this->isSynched('field.ldap_user_puid', $op, LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER)) {
      $ldap_user_puid = $ldap_server->derivePuidFromLdapEntry($user_ldap_entry);
      if ($ldap_user_puid) {
        $edit['ldap_user_puid'][LANGUAGE_NONE][0]['value'] = $ldap_user_puid; //
      }
    }
    if ($this->isSynched('field.ldap_user_puid_property', $op, LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER)) {
      $edit['ldap_user_puid_property'][LANGUAGE_NONE][0]['value'] = $ldap_server->unique_persistent_attr;
    }
    if ($this->isSynched('field.ldap_user_puid_sid', $op, LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER)) {
      $edit['ldap_user_puid_sid'][LANGUAGE_NONE][0]['value'] = $ldap_server->sid;
    }
    if ($this->isSynched('field.ldap_user_current_dn', $op, LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER)) {
      $edit['ldap_user_current_dn'][LANGUAGE_NONE][0]['value'] = $user_ldap_entry['dn'];
    }

  //  dpm('post-entryToUserEdit edit'); dpm($edit);
  }


} // end LdapUserConf class
