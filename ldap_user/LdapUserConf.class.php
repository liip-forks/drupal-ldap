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

  public $wsKey = NULL;
  public $wsEnabled = 0;
  public $wsUserIps = array();
 // public $wsUserId = NULL;
  public $wsActions = array();
  // $this->synchMapping[LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER]['property_mail'][sid] => array(LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER, LDAP_USER_SYNCH_CONTEXT_UPDATE_DRUPAL_USER),

  public $synchTypes = NULL; // array of synch types (keys) and user friendly names (values)

  public $wsActionsOptions = array(
    'create' => 'create: Create User Account.',
    'synch' => 'synch: Synch User Account with Current LDAP Data.',
    'disable' => 'disable: Disable User Account.',
    'delete' => 'delete: Remove User Account.',
  );

  public $wsActionsContexts = array(
    'create' => LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER,
    'synch' => LDAP_USER_SYNCH_CONTEXT_UPDATE_DRUPAL_USER,
    'disable' => LDAP_USER_SYNCH_CONTEXT_DISABLE_DRUPAL_USER,
    'delete' => LDAP_USER_SYNCH_CONTEXT_DELETE_DRUPAL_USER,
  );

  public $saveable = array(
    'sids',
    'provisionMethods',
    'userConflictResolve',
    'acctCreation',
    'synchMapping',
    'wsKey',
    'wsEnabled',
    'wsUserIps',
    'wsActions',
  ); // 'wsUserId',



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
      dpm('saved'); dpm($saved);
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
dpm($this->wsKey);
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

  /**
   * given configuration of synching, determine is a given synch should occur
   *
   * @param string $field e.g. property.mail, field.ldap_user_puid_property
   * @param scalar $synch_context (see LDAP_USER_SYNCH_CONTEXT_* constants in ldap_user.module)
   * @param scalar $direction LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER or LDAP_USER_SYNCH_DIRECTION_TO_LDAP_ENTRY
   */

  public function isSynched($field, $synch_context, $direction) {
    return (isset($this->synchMapping[$direction][$field]) && in_array($synch_context, $this->synchMapping[$direction][$field]));
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


  /**
   * given a drupal account, query ldap and get all user fields and create user account
   *
   * @param array $account drupal account array with minimum of name or uid
   * @param array $user_edit drupal edit array in form user_save($account, $user_edit) would take.
   * @param int synch_context (see LDAP_USER_SYNCH_CONTEXT_* constants)
   * @param array $ldap_user as user's ldap entry.  passed to avoid requerying ldap in cases where already present
   * @param boolean $save indicating if drupal user should be saved.  generally depends on where function is called from.
   *
   * @return result of user_save() function is $save is true, otherwise return TRUE
   *   $user_edit data returned by reference
   */
  public function synchDrupalAccount($account, &$user_edit, $synch_context, $ldap_user = NULL, $save = FALSE) {
    if (
        (!$ldap_user  && !isset($account['name'])) ||
        (!$account && $save) ||
        ($ldap_user  && !isset($ldap_user['sid']))
    ) {
       // should throw watchdog error also
       return FALSE;
    }
    if (!$ldap_user) {
      $ldap_user = ldap_servers_get_user_ldap_data($account['name'], NULL, $synch_context);
    }
    $ldap_server = ldap_servers_get_servers($ldap_user['sid'], NULL, TRUE);
    $this->entryToUserEdit($ldap_entry, $ldap_server, $user_edit, $synch_context);

    if ($save) {
      return user_save($account, $user_edit);
    }
    else {
      return TRUE;
    }
  }

   /**
   * given a drupal account, query ldap and get all user fields and save user account
   * (note: parameters are in odd order to match synchDrupalAccount handle)
   *
   * @param array $account drupal account array with minimum of name
   * @param array $user_edit drupal edit array in form user_save($account, $user_edit) would take.
   * @param int synch_context (see LDAP_USER_SYNCH_CONTEXT_* constants)
   * @param array $ldap_user as user's ldap entry.  passed to avoid requerying ldap in cases where already present
   * @param boolean $save indicating if drupal user should be saved.  generally depends on where function is called from and if the
   *
   * @return result of user_save() function is $save is true, otherwise return TRUE
   *   $user_edit data returned by reference
   *
   */

  public function provisionDrupalAccount($account = array(), &$user_edit, $synch_context, $ldap_user = NULL, $save = TRUE) {

    /**
     * @todo
     * -- add check in for mail, puid, username, and existing drupal user conflicts
     */


    if (!$ldap_user && !isset($account['name'])) {
       return FALSE;
    }
    if (!$ldap_user) {
      $ldap_user = ldap_servers_get_user_ldap_data($account['name'], NULL, $synch_context);
    }

    $ldap_server = ldap_servers_get_servers($ldap_user['sid'], NULL, TRUE);
    $this->entryToUserEdit($ldap_entry, $ldap_server, $edit, $synch_context);


    $user_edit['pass'] = user_password(20);
    $user_edit['init'] = $edit['mail'];
    $user_edit['status'] = 1;

    // save 'init' data to know the origin of the ldap authentication provisioned account
    $user_edit['data']['ldap_authentication']['init'] = array(
      'sid'  => $ldap_user['sid'],
      'dn'   => $user_edit['dn'],
      'mail' => $user_edit['mail'],
    );

    if ($save) {
      $account = user_save( NULL, $user_edit);
      if (!$account) {
        drupal_set_message(t('User account creation failed because of system problems.'), 'error');
      }
      else {
        user_set_authmaps($account, array('authname_ldap_authentication' => $name));
      }
      return $account;
    }
    return TRUE;
  }

  /** populate $user edit array (used in hook_user_save, hook_user_update, etc)
   * ... should not assume all attribues are present in ldap entry
   *
   * @param array ldap entry $user_ldap_entry
   * @param array $edit see hook_user_save, hook_user_update, etc
   * @param drupal account object $account
   * @param string $op see hook_ldap_attributes_needed_alter
   */

  function entryToUserEdit($ldap_entry, $ldap_server, &$edit, $synch_context) {
    // need array of user fields and which direction and when they should be synched.

    if ($this->isSynched('property.mail', $synch_context, LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER)) {
      $mail = $ldap_server->deriveEmailFromEntry($ldap_entry);
      if ($mail) {
        $edit['mail'] = $mail;
      }
    }
  //  dpm("this->synchMapping,op=$op,direction=" . LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER); dpm($this->synchMapping);
    /**
     * basic $user ldap fields
     */
    if ($this->isSynched('field.ldap_user_puid', $synch_context, LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER)) {
      $ldap_user_puid = $ldap_server->derivePuidFromLdapEntry($ldap_entry);
      if ($ldap_user_puid) {
        $edit['ldap_user_puid'][LANGUAGE_NONE][0]['value'] = $ldap_user_puid; //
      }
    }
    if ($this->isSynched('field.ldap_user_puid_property', $synch_context, LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER)) {
      $edit['ldap_user_puid_property'][LANGUAGE_NONE][0]['value'] = $ldap_server->unique_persistent_attr;
    }
    if ($this->isSynched('field.ldap_user_puid_sid', $synch_context, LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER)) {
      $edit['ldap_user_puid_sid'][LANGUAGE_NONE][0]['value'] = $ldap_server->sid;
    }
    if ($this->isSynched('field.ldap_user_current_dn', $synch_context, LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER)) {
      $edit['ldap_user_current_dn'][LANGUAGE_NONE][0]['value'] = $ldap_entry['dn'];
    }

        /**
     * @todo
     * -- loop through all mapped entries or invoke hook to get them all (or both)
     */

  //  dpm('post-entryToUserEdit edit'); dpm($edit);
  }


} // end LdapUserConf class
