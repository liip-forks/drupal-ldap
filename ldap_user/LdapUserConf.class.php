<?php

/**
 * @file
 * This class represents a ldap_user module's configuration
 * It is extended by LdapUserConfAdmin for configuration and other admin functions
 */

class LdapUserConf {

  public $sids = array();  // server configuration ids being used by ldap user
  public $servers = array(); // ldap server objects enabled for ldap user
  public $drupalAcctProvisionEvents = array(LDAP_USER_PROV_ON_LOGON, LDAP_USER_PROV_ON_MANUAL_ACCT_CREATE);
  public $ldapEntryProvisionEvents = array();
  public $userConflictResolve = LDAP_USER_CONFLICT_RESOLVE_DEFAULT;
  public $acctCreation = LDAP_USER_ACCT_CREATION_LDAP_BEHAVIOR_DEFAULT;
  public $inDatabase = FALSE;
  public $synchMapping = NULL; // array of field synching directions for each operation
  public $ldapUserSynchMappings = NULL;  // synch mappings configured in ldap user module
  public $detailedWatchdog = FALSE;
  public $provisionsDrupalAccountsFromLdap = FALSE;
  public $provisionsLdapEntriesFromDrupalUsers = FALSE;

  public $wsKey = NULL;
  public $wsEnabled = 0;
  public $wsUserIps = array();
  public $wsActions = array();

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
    'drupalAcctProvisionEvents',
    'ldapEntryProvisionEvents',
    'userConflictResolve',
    'acctCreation',
    'ldapUserSynchMappings',
    'wsKey',
    'wsEnabled',
    'wsUserIps',
    'wsActions',
  );


  /**
   * @return boolean if any ldap servers are available for ldap user
   */
  public function enabled_servers() {
    return !(count(array_filter(array_values($this->sids))) == 0);
  }

  function __construct() {
    $this->load();
   // dpm('filter'); dpm(array_filter(array_values($this->drupalAcctProvisionEvents)));
    $this->provisionsDrupalAccountsFromLdap = (count(array_filter(array_values($this->drupalAcctProvisionEvents))) > 0);
    $this->provisionsLdapEntriesFromDrupalUsers = (count(array_filter(array_values($this->ldapEntryProvisionEvents))) > 0);
    $this->synchTypes = array(
      LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER => t('On User Creation'),
      LDAP_USER_SYNCH_CONTEXT_UPDATE_DRUPAL_USER => t('On User Update/Save'),
      LDAP_USER_SYNCH_CONTEXT_AUTHENTICATE_DRUPAL_USER => t('On User Logon'),
      LDAP_USER_SYNCH_CONTEXT_CRON => t('Via Cron Batch'),
    );
   // dpm('this before setSynchMapping'); dpm($this->synchMapping); dpm($this->ldapUserSynchMappings);
    $this->setSynchMapping(TRUE);
  //  debug('this->provisionsDrupalAccountsFromLdap'); debug($this->provisionsDrupalAccountsFromLdap );
  //  debug('this after setSynchMapping'); debug($this->synchMapping); debug($this->ldapUserSynchMappings);

    $this->detailedWatchdog = variable_get('ldap_help_watchdog_detail', 0);

   // dpm('this after construct'); dpm($this->ldapUserSynchMappings['uiuc_ad']);
  }

  function load() {

    if ($saved = variable_get("ldap_user_conf", FALSE)) {
//      dpm('saved'); dpm($saved);
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

    //  dpm('loaded'); dpm($this);
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

  /**
   * given configuration of synching, determine is a given synch should occur
   *
   * @param string $field e.g. property.mail, field.ldap_user_puid_property
   * @param scalar $synch_context (see LDAP_USER_SYNCH_CONTEXT_* constants in ldap_user.module)
   * @param scalar $direction LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER or LDAP_USER_SYNCH_DIRECTION_TO_LDAP_ENTRY
   */

  public function isSynched($field, $ldap_server, $synch_context, $direction) {
    //debug($this->synchMapping[$ldap_server->sid][$field]);
    $result = (boolean)(
      isset($this->synchMapping[$ldap_server->sid][$field]['contexts']) &&
      in_array($synch_context, $this->synchMapping[$ldap_server->sid][$field]['contexts']) &&
      isset($this->synchMapping[$ldap_server->sid][$field]['direction']) &&
      $this->synchMapping[$ldap_server->sid][$field]['direction'] == $direction
    );

   // debug("synchMapping in isSynched=$result, field=$field, synch_context=$synch_context direction=$direction, server:" . $ldap_server->sid);
   // debug($ldap_server);
   // debug("this->synchMapping[sid][field] in isSynched"); debug($this->synchMapping[$ldap_server->sid][$field]);

    return $result;
  }


  /**
    derive mapping array from ldap user configuration and other configurations.
    if this becomes a resource hungry function should be moved to ldap_user functions
    and stored with static variable. should be cached also.

    this should be cached and modules implementing ldap_user_synch_mapping_alter
    should know when to invalidate cache.

  **/

  function setSynchMapping($reset = TRUE) {  // @todo change default to false after development
    $synch_mapping_cache = cache_get('ldap_user_synch_mapping');
    if (!$reset && $synch_mapping_cache) {
      $this->synchMapping = $synch_mapping_cache->data;
    }
    else {
     // $this->synchMapping = ldap_user_get_user_targets();
      $ldap_servers = ldap_servers_get_servers(NULL, 'enabled', FALSE);
      $available_user_targets = array();
      foreach ($ldap_servers as $sid => $ldap_server) {
        $available_user_targets[$sid] = array();
        drupal_alter('ldap_user_targets_list', $available_user_targets[$sid], $ldap_server, $this->provisionsDrupalAccountsFromLdap);
      }
      $this->synchMapping = $available_user_targets;
      cache_set('ldap_user_synch_mapping',  $this->synchMapping);
    }
  }

  /**
   * given a drupal account, synch to related ldap entry
   *
   * @param array $account.  Drupal user object
   * @param string $synch_context.
   * @param array $ldap_user_entry.  any overrides of ldap user entry attributes
   *
   * @return array of ldap_entry or LDAP_USER_PROVISION_LDAP_ENTRY_SYNCH_FAILED on fail.
   */

  public function synchToLdapEntry($account, $synch_context, $ldap_user_entry = array()) {




  }

  /**
   * given a drupal account, query ldap and get all user fields and create user account
   *
   * @param array $account drupal account array with minimum of name
   * @param array $user_edit drupal edit array in form user_save($account, $user_edit) would take.
   * @param int synch_context (see LDAP_USER_SYNCH_CONTEXT_* constants)
   * @param array $ldap_user as user's ldap entry.  passed to avoid requerying ldap in cases where already present
   * @param boolean $save indicating if drupal user should be saved.  generally depends on where function is called from.
   *
   * @return result of user_save() function is $save is true, otherwise return TRUE
   *   $user_edit data returned by reference
   */
  public function synchToDrupalAccount($account, &$user_edit, $synch_context, $ldap_user = NULL, $save = FALSE) {
    $debug = array(
      'account' => $account,
      'user_edit' => $user_edit,
      'ldap_user' => $ldap_user,
      'synch_context' => $synch_context,
    );
   // debug('synchToDrupalAccount call'); debug($debug);
    if (
        (!$ldap_user  && !isset($account->name)) ||
        (!$account && $save) ||
        ($ldap_user && !isset($ldap_user['sid']))
    ) {
       // should throw watchdog error also
       return FALSE;
    }

    $drupal_user = user_load_by_name($account->name);
    if (!$ldap_user) {
      $sids = array_keys($this->sids);
    //  debug("call ldap_servers_get_user_ldap_data,, account:"); debug($account);
      $ldap_user = ldap_servers_get_user_ldap_data($account->name, $sids, $synch_context);
    }
    $ldap_servers = ldap_servers_get_servers(NULL, 'enabled', FALSE);
    //debug("ldap user line 203 ldapuserconf.class, synch_context=$synch_context"); debug($ldap_user);
    foreach ($ldap_servers as $sid => $ldap_server) {
      $this->entryToUserEdit($ldap_user, $ldap_server, $user_edit, $synch_context, 'update');
    }
    if ($save) {
      return user_save($account, $user_edit);
    }
    else {
      return TRUE;
    }
  }


  /**
   * given a drupal account, delete user account
   *
   * @param string $username drupal account name
   * @param int synch_context (see LDAP_USER_SYNCH_CONTEXT_* constants)
   *
   * @return TRUE or FALSE.  FALSE indicates failed or action not enabled in ldap user configuration
   */
  public function deleteDrupalAccount($username, $synch_context) {
    // @todo check if deletion allowed/enabled in context
    $user = user_load_by_name($username);
    if (is_object($user)) {
      user_delete($user->uid);
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

 /**
   * given a drupal account, provision an ldap entry if none exists.  if one exists do nothing
   *
   * @param array $account drupal account array with minimum of name
   * @param int $synch_context (see LDAP_USER_SYNCH_CONTEXT_* constants)
   * @param array $ldap_user as prepopulated ldap entry.  usually not provided
   *
   * @return ldap entry that is created or one of the following constants:
   *   LDAP_USER_PROVISION_LDAP_ENTRY_EXISTS
   *   LDAP_USER_PROVISION_LDAP_ENTRY_CREATE_FAILED
   */

  public function provisionLdapEntry($account = FALSE, $synch_context = LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER, $ldap_user = NULL) {
    $watchdog_tokens = array();

    // @todo determine server to use for provisioning

    $ldap_entry = $this->drupalUserToLdapEntry($account, $ldap_server, $ldap_user, $synch_context, 'create');

    /**
     * @todo check if user exists, given proposed $ldap_entry
    if (user exists ) {
      return LDAP_USER_PROVISION_LDAP_ENTRY_EXISTS;
    }
    */

    // is a hook needed here, or is it in drupalUserToLdapEntry ?
   // @todo implement: $ldap_server->createLdapEntry($ldap_entry);

    /** @todo
     * create ldap entry
    if (entry created) {
      return $entry;
    }
    else {
      return LDAP_USER_PROVISION_LDAP_ENTRY_CREATE_FAILED;
    }
    **/

  }

/** populate ldap entry array
   *
   * @param array ldap entry $user_ldap_entry
   * @param object $ldap_server
   * @param array $edit see hook_user_save, hook_user_update, etc
   * @param drupal account object $account
   * @param string $op see hook_ldap_attributes_needed_alter
   *
   * @return ldap entry in ldap extension array format.
   */

  function drupalUserToLdapEntry($account, $ldap_server, $ldap_user_entry = array(), $synch_context, $op) {

    // @todo: special code to derive cn, dn and any other attributes that are not in standard mapping/synching code.

     /**
     * @todo
     * -- loop through all mapped fields
     * -- where do tokens fit in here?
     */

    $params = array(
      'account' => $account,
      'ldap_server' => $ldap_server,
      'synch_context' => $synch_context,
      'op' => $op,
    );

    drupal_alter('ldap_user_ldap_entry', $ldap_user_entry, $params);

    return $ldap_user_entry;

  }



   /**
   * given a drupal account, query ldap and get all user fields and save user account
   * (note: parameters are in odd order to match synchDrupalAccount handle)
   *
   * @param array $account drupal account array with minimum of name
   * @param array $user_edit drupal edit array in form user_save($account, $user_edit) would take.
   * @param int $synch_context (see LDAP_USER_SYNCH_CONTEXT_* constants)
   * @param array $ldap_user as user's ldap entry.  passed to avoid requerying ldap in cases where already present
   * @param boolean $save indicating if drupal user should be saved.  generally depends on where function is called from and if the
   *
   * @return result of user_save() function is $save is true, otherwise return TRUE on success or FALSE on any problem
   *   $user_edit data returned by reference
   *
   */

  public function provisionDrupalAccount($account = FALSE, &$user_edit, $synch_context = LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER, $ldap_user = NULL, $save = TRUE) {
    $watchdog_tokens = array();

//    debug('call to provisionDrupalAccount'); debug('account'); debug($account); debug('user_edit'); debug($user_edit);
 //   debug('synch_context'); debug($synch_context); debug('ldap_user'); debug($ldap_user); debug('save=' . $save);
  //  debug('this->sids'); debug($this->sids);  debug('this->servers'); debug($this->servers); debug('this'); debug($this);
    /**
     * @todo
     * -- add check in for mail, puid, username, and existing drupal user conflicts
     */

    if (!$account) {
      $account = new stdClass();
    }
    $account->is_new = TRUE;

    if (!$ldap_user && !isset($user_edit['name'])) {
       return FALSE;
    }
    if (!$ldap_user) {
      $watchdog_tokens['%username'] = $user_edit['name'];
      foreach ($this->sids as $sid => $discard) {
        $ldap_user = ldap_servers_get_user_ldap_data($user_edit['name'], $sid, $synch_context);
        if ($ldap_user) {
          $watchdog_tokens['%user_sid'] = $sid;
          break;
        }
      }
      if (!$ldap_user) {
        if ($this->detailedWatchdog) {
          watchdog('ldap_user', '%username : failed to find associated ldap entry for username in provision.', $watchdog_tokens, WATCHDOG_DEBUG);
        }
        return FALSE;
      }
    }
  //  debug('ldap_user'); debug($ldap_user);
    if (!isset($user_edit['name']) && isset($account->name)) {
      $user_edit['name'] = $account->name;
      $watchdog_tokens['%username'] = $user_edit['name'];
    }

    $ldap_servers = ldap_servers_get_servers(NULL, 'enabled', TRUE);  // $ldap_user['sid']
   // debug('ldap user line 302ish ldapuserconf.class'); debug($ldap_user);
    foreach ($ldap_servers as $sid => $ldap_server) {
      $this->entryToUserEdit($ldap_user, $ldap_server, $user_edit, $synch_context, 'create');
    }

    if ($save) {
      $account = user_save(NULL, $user_edit);
      if (!$account) {
        drupal_set_message(t('User account creation failed because of system problems.'), 'error');
      //  debug("user save fail"); debug($user_edit);
      }
      else {
      //  debug("user save success"); debug($account);
        user_set_authmaps($account, array('authname_ldap_authentication' => $user_edit['name']));
      }
      return $account;
    }
    return TRUE;
  }

  /** populate $user edit array (used in hook_user_save, hook_user_update, etc)
   * ... should not assume all attribues are present in ldap entry
   *
   * @param array ldap entry $user_ldap_entry
   * @param object $ldap_server
   * @param array $edit see hook_user_save, hook_user_update, etc
   * @param drupal account object $account
   * @param string $op see hook_ldap_attributes_needed_alter
   */

  function entryToUserEdit($ldap_user, $ldap_server, &$edit, $synch_context, $op) {
    // need array of user fields and which direction and when they should be synched.
   // dpm('entryToUserEdit'); dpm($ldap_server);

    $synch_email = $this->isSynched('property.mail', $ldap_server, $synch_context, LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER);
  //  debug("entryToUserEdit isSynched property.mail synch_context=$synch_context, issynched=$synch_email");
    if ($synch_email && !isset($edit['mail'])) {
    //  debug('entryToUserEdit ldap entry'); debug($ldap_user);
      $derived_mail = $ldap_server->deriveEmailFromLdapEntry($ldap_user['attr']);
   //    debug("isSynched derived_mail: $derived_mail");
      if ($derived_mail) {
        $edit['mail'] = $derived_mail;
      }
    }
    else {
      $edit['mail'] = NULL;
    }

  //  debug('edit'); debug($edit);

    if ($this->isSynched('property.name', $ldap_server, $synch_context, LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER) && !isset($edit['name'])) {
      $name = $ldap_server->deriveUsernameFromLdapEntry($ldap_user['attr']);
      if ($name) {
        $edit['name'] = $name;
      }
    }

    if ($op == 'create') {
      $edit['pass'] = user_password(20);
      $edit['init'] = $edit['mail'];
      $edit['status'] = 1;
      if (!isset($edit['signature'])) {
        $edit['signature'] = '';
      }
      // save 'init' data to know the origin of the ldap authentication provisioned account
      $edit['data']['ldap_authentication']['init'] = array(
        'sid'  => $ldap_user['sid'],
        'dn'   => $ldap_user['dn'],
        'mail' => $edit['mail'],
      );
    }

    /**
     * basic $user ldap fields
     */
    if ($this->isSynched('field.ldap_user_puid', $ldap_server, $synch_context, LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER)) {

      $ldap_user_puid = $ldap_server->derivePuidFromLdapEntry($ldap_user['attr']);
      if ($ldap_user_puid) {
        $edit['ldap_user_puid'][LANGUAGE_NONE][0]['value'] = $ldap_user_puid; //
      }
    }
    if ($this->isSynched('field.ldap_user_puid_property', $ldap_server, $synch_context, LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER)) {
      $edit['ldap_user_puid_property'][LANGUAGE_NONE][0]['value'] = $ldap_server->unique_persistent_attr;
    }
    if ($this->isSynched('field.ldap_user_puid_sid', $ldap_server, $synch_context, LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER)) {
      $edit['ldap_user_puid_sid'][LANGUAGE_NONE][0]['value'] = $ldap_server->sid;
    }
    if ($this->isSynched('field.ldap_user_current_dn', $ldap_server, $synch_context, LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER)) {
      $edit['ldap_user_current_dn'][LANGUAGE_NONE][0]['value'] = $ldap_user['dn'];
    }


        /**
     * @todo
     * -- loop through all mapped entries AND invoke hook to get them all (or both)
     */

  //  dpm('post-entryToUserEdit edit'); dpm($edit);
  }


} // end LdapUserConf class
