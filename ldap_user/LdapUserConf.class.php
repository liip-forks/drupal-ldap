<?php

/**
 * @file
 * This class represents a ldap_user module's configuration
 * It is extended by LdapUserConfAdmin for configuration and other admin functions
 */

class LdapUserConf {

  public $sids = array();  // server configuration ids being used by ldap user
  public $servers = array(); // ldap server objects enabled for ldap user
  public $provisionMethods = array(LDAP_USER_PROV_ON_LOGON, LDAP_USER_PROV_ON_MANUAL_ACCT_CREATE);
  public $userConflictResolve = LDAP_USER_CONFLICT_RESOLVE_DEFAULT;
  public $acctCreation = LDAP_USER_ACCT_CREATION_LDAP_BEHAVIOR_DEFAULT;
  public $inDatabase = FALSE;
  public $synchMapping = NULL; // array of field synching directions for each operation
  public $detailedWatchdog = FALSE;

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
    'provisionMethods',
    'userConflictResolve',
    'acctCreation',
    'synchMapping',
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

    $this->synchTypes = array(
      LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER => t('On User Creation'),
      LDAP_USER_SYNCH_CONTEXT_UPDATE_DRUPAL_USER => t('On User Update/Save'),
      LDAP_USER_SYNCH_CONTEXT_AUTHENTICATE_DRUPAL_USER => t('On User Logon'),
      LDAP_USER_SYNCH_CONTEXT_CRON => t('Via Cron Batch'),
    );
    $this->setSynchMapping(TRUE);

    $this->detailedWatchdog = variable_get('ldap_help_watchdog_detail', 0);

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

  /**
   * given configuration of synching, determine is a given synch should occur
   *
   * @param string $field e.g. property.mail, field.ldap_user_puid_property
   * @param scalar $synch_context (see LDAP_USER_SYNCH_CONTEXT_* constants in ldap_user.module)
   * @param scalar $direction LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER or LDAP_USER_SYNCH_DIRECTION_TO_LDAP_ENTRY
   */

  public function isSynched($field, $synch_context, $direction) {
    return (
      isset($this->synchMapping[$field]) &&
      in_array($synch_context, $this->synchMapping[$field]) &&
      $this->synchMapping[$field]['direction'] == $direction
    );
  }


  /**
    derive mapping array from ldap user configuration and other configurations.
    if this becomes a resource hungry function should be moved to ldap_user functions
    and stored with static variable. should be cached also.

    this should be cached and modules implementing ldap_user_synch_mapping_alter
    should know when to invalidate cache.

  **/

  function setSynchMapping($reset = FALSE) {
    $synch_mapping_cache = cache_get('ldap_user_synch_mapping');
    if (!$reset && $synch_mapping_cache) {
      $this->synchMapping = $synch_mapping_cache->data;
    }
    else {
      drupal_alter('ldap_user_synch_mapping', $this->synchMapping);
      cache_set('ldap_user_synch_mapping', $this->synchMapping);
    }
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
    );
   // debug($debug);
    if (
        (!$ldap_user  && !isset($account->name)) ||
        (!$account && $save) ||
        ($ldap_user && !isset($ldap_user['sid']))
    ) {
       // should throw watchdog error also
      // debug('return false');
       return FALSE;
    }

    $drupal_user = user_load_by_name($account->name);
    //debug('drupal_user'); debug($drupal_user);

    if (!$ldap_user) {
      $sids = array_keys($this->sids);
      $ldap_user = ldap_servers_get_user_ldap_data($account->name, $sids, $synch_context);
    }
  //  debug('ldap_user'); debug($ldap_user);
  //  debug('synchToDrupalAccount, ldap user'); debug($ldap_user);
    $ldap_server = ldap_servers_get_servers($ldap_user['sid'], 'enabled', TRUE);
   // debug('ldap server'); debug($ldap_server);
    $this->entryToUserEdit($ldap_user, $ldap_server, $user_edit, $synch_context, 'update');
    // debug('synchToDrupalAccount, user_edit'); debug($user_edit);
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
   // dpm($user);
    if (is_object($user)) {
      user_delete($user->uid);
      return TRUE;
    }
    else {
      return FALSE;
    }
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

   // debug('call to provisionDrupalAccount'); debug('account'); debug($account); debug('user_edit'); debug($user_edit);
  //  debug('synch_context'); debug($synch_context); debug('ldap_user'); debug($ldap_user); debug('save=' . $save);
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
    //  debug('calling ldap_servers_get_user_ldap_data, sids='); debug($this->sids);
      $watchdog_tokens['%username'] = $user_edit['name'];
      foreach ($this->sids as $sid => $discard) {
        $ldap_user = ldap_servers_get_user_ldap_data($user_edit['name'], $sid, $synch_context);
        if ($ldap_user) {
          $watchdog_tokens['%user_sid'] = $sid;
          break;
        }
      }
      if (!$ldap_user) {
       // debug('no ldap user');
        if ($this->detailedWatchdog) {
          watchdog('ldap_user', '%username : failed to find associated ldap entry for username in provision.', $watchdog_tokens, WATCHDOG_DEBUG);
        }
        return FALSE;
      }
    }
   // debug('ldap_user'); debug($ldap_user);
    if (!isset($user_edit['name']) && isset($account->name)) {
      $user_edit['name'] = $account->name;
      $watchdog_tokens['%username'] = $user_edit['name'];
    }

    $ldap_server = ldap_servers_get_servers($ldap_user['sid'], 'enabled', TRUE);

  //  debug('provisionDrupalAccount ldap_user'); debug($ldap_user);  debug($ldap_server);debug($user_edit);
    $this->entryToUserEdit($ldap_user, $ldap_server, $user_edit, $synch_context, 'create');
  //  debug("edit after this->entryToUserEdit"); debug($user_edit);


    if ($save) {
      $account = user_save(NULL, $user_edit);
      if (!$account) {
        drupal_set_message(t('User account creation failed because of system problems.'), 'error');
      }
      else {
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
   * @param array $edit see hook_user_save, hook_user_update, etc
   * @param drupal account object $account
   * @param string $op see hook_ldap_attributes_needed_alter
   */

  function entryToUserEdit($ldap_user, $ldap_server, &$edit, $synch_context, $op) {
    // need array of user fields and which direction and when they should be synched.
   // dpm('entryToUserEdit');dpm($ldap_user); dpm($ldap_server);
    if ($this->isSynched('property.mail', $synch_context, LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER) && !isset($edit['mail'])) {
      $mail = $ldap_server->deriveEmailFromLdapEntry($ldap_user['attr']);
      if ($mail) {
        $edit['mail'] = $mail;
      }
    }

    if ($this->isSynched('property.name', $synch_context, LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER) && !isset($edit['name'])) {
      $name = $ldap_server->deriveUsernameFromLdapEntry($ldap_user['attr']);
      if ($name) {
        $edit['name'] = $name;
      }
    }

    if ($op == 'create') {
      $edit['pass'] = user_password(20);
      $edit['init'] = $edit['mail'];
      $edit['status'] = 1;
      // save 'init' data to know the origin of the ldap authentication provisioned account
      $edit['data']['ldap_authentication']['init'] = array(
        'sid'  => $ldap_user['sid'],
        'dn'   => $ldap_user['dn'],
        'mail' => $edit['mail'],
      );
    }

  //  dpm("this->synchMapping,op=$op,direction=" . LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER); dpm($this->synchMapping);
    /**
     * basic $user ldap fields
     */
    if ($this->isSynched('field.ldap_user_puid', $synch_context, LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER)) {

      $ldap_user_puid = $ldap_server->derivePuidFromLdapEntry($ldap_user['attr']);
     // dpm("ldap_user_puid=$ldap_user_puid");
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
      $edit['ldap_user_current_dn'][LANGUAGE_NONE][0]['value'] = $ldap_user['dn'];
    }


        /**
     * @todo
     * -- loop through all mapped entries AND invoke hook to get them all (or both)
     */

  //  dpm('post-entryToUserEdit edit'); dpm($edit);
  }


} // end LdapUserConf class
