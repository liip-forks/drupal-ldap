<?php

/**
 * @file
 * This class represents a ldap_user module's configuration
 * It is extended by LdapUserConfAdmin for configuration and other admin functions
 */

class LdapUserConf {
  // sids -> drupalAcctProvisionServers
  public $drupalAcctProvisionServers = array();  // servers used for to drupal acct provisioning keyed on $sid => boolean
  public $ldapEntryProvisionServers = array();  // servers used for provisioning to ldap keyed on $sid => boolean
  public $provisionServers = array(); // ldap server objects enabled for ldap user
  public $drupalAcctProvisionEvents = array(LDAP_USER_DRUPAL_USER_CREATE_ON_LOGON, LDAP_USER_DRUPAL_USER_CREATE_ON_MANUAL_ACCT_CREATE);
  public $ldapEntryProvisionEvents = array();
  public $userConflictResolve = LDAP_USER_CONFLICT_RESOLVE_DEFAULT;
  public $acctCreation = LDAP_USER_ACCT_CREATION_LDAP_BEHAVIOR_DEFAULT;
  public $inDatabase = FALSE;
  public $synchMapping = NULL; // array of field synching directions for each operation.  should include ldapUserSynchMappings
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
    'drupalAcctProvisionServers',
    'ldapEntryProvisionServers',
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
   // dpm('this before setSynchMapping'); dpm($this->ldapUserSynchMappings);
    $this->setSynchMapping(TRUE);
   // dpm('this after setSynchMapping'); dpm($this->synchMapping); dpm($this->ldapUserSynchMappings);

    $this->detailedWatchdog = variable_get('ldap_help_watchdog_detail', 0);

    //dpm('this after construct'); dpm($this);
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
      foreach ($this->drupalAcctProvisionServers as $sid => $is_enabled) {
        if ($is_enabled) {
          $this->provisionServers[$sid] = ldap_servers_get_servers($sid, 'enabled', TRUE);
        }
      }
      foreach ($this->ldapEntryProvisionServers as $sid => $is_enabled) {
        if ($is_enabled && !isset($this->provisionServers[$sid])) {
          $this->provisionServers[$sid] = ldap_servers_get_servers($sid, 'enabled', TRUE);
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
   * Util to fetch mappings for a given server id
   *
   * @param string $sid
   *   The server id
   *
   * @return array/bool
   *   Array of mappings or FALSE if none found
  */
  private function getSynchMappings($sid) {
   // debug('this.getSynchMappings,$sid='. $sid);debug($this->ldapUserSynchMappings);
    if (!empty($this->ldapUserSynchMappings[$sid]) &&
        ($mappings = $this->ldapUserSynchMappings[$sid]) &&
        is_array($mappings)) {
      return $mappings;
    }
    return FALSE;
  }

  public function isDrupalAcctProvisionServer($sid) {
    return in_array($sid, array_filter($this->drupalAcctProvisionServers));
  }
  
  public function isLdapEntryProvisionServer($sid) {
    return in_array($sid, array_filter($this->ldapEntryProvisionServers));
  }
  
  /**
   * Util to fetch attributes required for this user conf, not other modules.
   *
   * @param int $synch_context
   *   Any valid sync context constant.
  */
  public function getRequiredAttributes($synch_context) {
    // Get the enabled servers.
    $sids = array_filter(array_values($this->drupalAcctProvisionServers));
    // Initialize our array.
    $attributes = array() ;
    // Loop over each server and fetch the mappings required.
    foreach ($sids as $sid) {
      $mappings = $this->getSynchMappings($sid);
    //  debug('getRequiredAttributes.mappings,'. $sid); debug($mappings);
      if ($mappings) {
      //  debug('getRequiredAttributes.mappings:,sid='. $sid); debug($mappings);
        foreach ($mappings as $detail) {
          // Make sure the mapping is relevant to this context.
          if (in_array($synch_context, $detail['contexts'])) {
            // Add the attribute to our array.
           // debug("token=" . $detail['ldap_attr'] . ", is token=" . ldap_servers_token_is_token($detail['ldap_attr']));
            ldap_servers_token_extract_attributes($attributes,  $detail['ldap_attr']);
           // if (ldap_servers_token_is_token($detail['ldap_attr'])) {
          //    $extracted = ldap_servers_token_extract_attribute_name($detail['ldap_attr']);
             // debug('extracted from '.$detail['ldap_attr'] . '='. $extracted);
            //  $attributes[] = $extracted;
           // }
          }
        }
      }
    }
  //  debug('attribute needed from getRequiredAttributes'); debug($attributes);
    return $attributes;
  }

  /**
   * @return boolean if any ldap servers are available for ldap user
   */
  public function enabled_servers() {
    return !(count(array_filter(array_values($this->drupalAcctProvisionServers))) == 0);
  }

  

  /**
   * given configuration of synching, determine is a given synch should occur
   *
   * @param string $field e.g. property.mail, field.ldap_user_puid_property
   * @param scalar $synch_context (see LDAP_USER_SYNCH_CONTEXT_* constants in ldap_user.module)
   * @param scalar $direction LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER or LDAP_USER_SYNCH_DIRECTION_TO_LDAP_ENTRY
   */

  public function isSynched($field, $ldap_server, $synch_context, $direction) {
   // debug('synchMapping'); debug($this->synchMapping[$ldap_server->sid][$field]);
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
     // $this->synchMapping = ldap_user_get_user_attrs();
      $ldap_servers = ldap_servers_get_servers(NULL, 'enabled', FALSE);
      $available_user_attrs = array();
      foreach ($ldap_servers as $sid => $ldap_server) {
        $available_user_attrs[$sid] = array();
        drupal_alter('ldap_user_attrs_list', $available_user_attrs[$sid], $ldap_server, $this);
      }
      $this->synchMapping = $available_user_attrs;
    //  dpm('available_user_attrs');dpm($available_user_attrs);
      cache_set('ldap_user_synch_mapping',  $this->synchMapping);
    }
  }
  
  /**
   * given a context, determine if ldap user configuration supports it.
   *   this is overall, not per field synching configuration
   *   
   * @param enum $synch_context
   * @param enaum $direction LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER or LDAP_USER_SYNCH_DIRECTION_TO_LDAP_ENTRY
   * @param enum 'synch', 'provision', 'delete_ldap_entry', 'delete_drupal_entry', 'cancel_drupal_entry'
   * @return boolean
   */
  
  public function contextEnabled($synch_context, $direction, $action = 'synch') {

   // debug("contextEnabled: $synch_context, $direction, $action");
    if ($direction == LDAP_USER_SYNCH_DIRECTION_TO_LDAP_ENTRY) {
  //    debug('LDAP_USER_SYNCH_DIRECTION_TO_LDAP_ENTRY'); debug($this->ldapEntryProvisionEvents);
      $configurations = array();
      if ($action == 'synch') {
        $configurations = array(
          LDAP_USER_LDAP_ENTRY_UPDATE_ON_USER_UPDATE,
          LDAP_USER_LDAP_ENTRY_UPDATE_ON_USER_AUTHENTICATE,
        );
      }
      elseif ($action == 'provision') {
        $configurations = array(
          LDAP_USER_LDAP_ENTRY_CREATE_ON_USER_CREATE,
          LDAP_USER_LDAP_ENTRY_CREATE_ON_USER_STATUS_IS_1,
          LDAP_USER_LDAP_ENTRY_CREATE_ON_USER_UPDATE,
          LDAP_USER_LDAP_ENTRY_CREATE_ON_USER_AUTHENTICATE,
        );
      }
      elseif ($action == 'delete_ldap_entry') {
        $configurations = array(LDAP_USER_LDAP_ENTRY_DELETE_ON_USER_DELETE);
      }
    //  debug("configurations"); debug($configurations);
      $result = count(array_intersect($configurations, $this->ldapEntryProvisionEvents));
    }
    else { // default to LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER
    //  debug('LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER'); debug($this->drupalAcctProvisionEvents);
            $configurations = array();
      if ($action == 'synch') {
        $configurations = array(
          LDAP_USER_DRUPAL_USER_UPDATE_ON_USER_AUTHENTICATE,
          LDAP_USER_DRUPAL_USER_UPDATE_ON_USER_UPDATE,
        );
      }
      elseif ($action == 'provision') {
        $configurations = array(
          LDAP_USER_DRUPAL_USER_CREATE_ON_MANUAL_ACCT_CREATE,
          LDAP_USER_DRUPAL_USER_CREATE_ON_ALL_USER_CREATION,
        );
      }
      elseif ($action == 'delete_drupal_entry') {
        $configurations = array(
          LDAP_USER_DRUPAL_USER_DELETE_ON_LDAP_ENTRY_MISSING,
        );
      }
      elseif ($action == 'cancel_drupal_entry') {
        $configurations = array(
          LDAP_USER_DRUPAL_USER_CANCEL_ON_LDAP_ENTRY_MISSING,
        );
      }
    //   debug("configurations"); debug($configurations);
      $result = count(array_intersect($configurations, $this->drupalAcctProvisionEvents));
    }
   // debug("result=$result");
    return $result;
  }

 
  /**
   * given a drupal account, synch to related ldap entry
   *
   * @param array $account.  Drupal user object
   * @param array $user_edit.  Edit array for user_save.  generally null unless user account is being created or modified in same synching
   * @param string $synch_context.
   * @param array $ldap_user.  current ldap data of user. see README.developers.txt for structure
   *
   * @return array of ldap_entry or LDAP_USER_PROVISION_LDAP_ENTRY_SYNCH_FAILED on fail.
   */

  public function synchToLdapEntry($account, $user_edit = NULL, $synch_context, $ldap_user = array()) {
    
    //ldap_servers_debug('synchToLdapEntry'); ldap_servers_debug($account); ldap_servers_debug($user_edit);

    $watchdog_tokens = array();
    $results = array();
    
    foreach ($this->ldapEntryProvisionServers as $sid => $discard) {
      $ldap_server = ldap_servers_get_servers($sid, NULL, TRUE);
      $params = array(
        'synch_context' => $synch_context,
        'op' => 'synch_ldap_user_entry',
        'module' => 'ldap_user',
        'function' => 'synchToLdapEntry',
        'include_count' => FALSE,
      );
      $proposed_ldap_entry = $this->drupalUserToLdapEntry($account, $ldap_server, $ldap_user, $params);
   //  debug('synchToLdapEntry:proposed_ldap_entry'); debug($proposed_ldap_entry);
      $existing_ldap_entry = $ldap_server->dnExists($proposed_ldap_entry['dn']);
     // ldap_servers_debug('provisionLdapEntry:$proposed_ldap_entry'); ldap_servers_debug($proposed_ldap_entry);
     // ldap_servers_debug('provisionLdapEntry:existing_ldap_entry'); ldap_servers_debug($existing_ldap_entry);
      
      $attributes = array(); // this array represents attributes to be modified; not comprehensive list of attributes
      foreach ($proposed_ldap_entry as $attr_name => $attr_values) {
        if ($attr_name != 'dn') {
          if (isset($attr_values['count'])) {
            unset($attr_values['count']);
          }
          if (count($attr_values) == 1) {
            $attributes[$attr_name] = $attr_values[0];
          }
          else {
            $attributes[$attr_name] = $attr_values;
          }
        }   
      }
//      debug('synchToLdapEntry:attributes passed to modifyLdapEntry, dn='. $proposed_ldap_entry['dn']); debug($attributes);
      $result['status'] = $ldap_server->modifyLdapEntry($proposed_ldap_entry['dn'], $attributes);

      //  $attributes["attribute1"] = "value";
     //   $attributes["attribute2"][0] = "value1";
      //  $attributes["attribute2"][1] = "value2";
    }
   // debug('provisionLdapEntry:results'); debug($results);
    foreach ($results as $sid => $result) {

      $tokens = array(
        '%dn' => $result['proposed']['dn'],
        '%sid' => $result['ldap_server']->sid,
        '%username' => $account->name,
        '%uid' => $account->uid,
      );

      if ($result['status']) {
        watchdog('ldap_user', 'LDAP entry on server %sid synched dn=%dn. username=%username, uid=%uid', array(), WATCHDOG_INFO);
      }
      elseif(!$result['status']) {
        watchdog('ldap_user', 'LDAP entry on server %sid not synched because error. username=%username, uid=%uid', array(), WATCHDOG_ERROR);
      }

    }

    return $results;
  
  }

  /**
   * given a drupal account, query ldap and get all user fields and create user account
   *
   * @param array $account drupal account array with minimum of name
   * @param array $user_edit drupal edit array in form user_save($account, $user_edit) would take,
   *   generally empty unless overriding synchToDrupalAccount derived values
   * @param int synch_context (see LDAP_USER_SYNCH_CONTEXT_* constants)
   * @param array $ldap_user as user's ldap entry.  passed to avoid requerying ldap in cases where already present
   * @param boolean $save indicating if drupal user should be saved.  generally depends on where function is called from.
   *
   * @return result of user_save() function is $save is true, otherwise return TRUE
   *   $user_edit data returned by reference
   *
   */
  public function synchToDrupalAccount($drupal_user, &$user_edit, $synch_context, $ldap_user = NULL, $save = FALSE) {
    $debug = array(
      'account' => $drupal_user,
      'user_edit' => $user_edit,
      'ldap_user' => $ldap_user,
      'synch_context' => $synch_context,
    );
   // debug('synchToDrupalAccount call'); debug($debug);
    if (
        (!$ldap_user  && !isset($drupal_user->name)) ||
        (!$drupal_user && $save) ||
        ($ldap_user && !isset($ldap_user['sid']))
    ) {
       // should throw watchdog error also
       return FALSE;
    }

    if (!$ldap_user) {
      $sids = array_keys($this->drupalAcctProvisionServers);
      $ldap_user = ldap_servers_get_user_ldap_data($drupal_user->name, $sids, $synch_context);
    }
    if (!$ldap_user) {
      return FALSE;
    }
  //  debug('ldap user data:,'. $drupal_user->name); debug($ldap_user);
    $ldap_servers = ldap_servers_get_servers(NULL, 'enabled', FALSE);
    foreach ($ldap_servers as $sid => $ldap_server) {
      $this->entryToUserEdit($ldap_user, $ldap_server, $user_edit, $synch_context, 'update');
    }
   // debug('user edit before save:'); debug($user_edit);
    if ($save) { 
      return user_save($account, $user_edit, 'ldap_user');
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
   * @return array of form:
   *   <sid> =>
   *     array('status' => 'success', 'fail', or 'conflict'),
   *     array('ldap_server' => ldap server object),
   *     array('proposed' => proposed ldap entry),
   *     array('existing' => existing ldap entry),
   *
   */

  public function provisionLdapEntry($account = FALSE, $synch_context = LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER, $ldap_user = NULL, $test_query = FALSE) {
    $watchdog_tokens = array();
    $results = array();
    //debug('provisionLdapEntry');
    foreach ($this->ldapEntryProvisionServers as $sid => $discard) {
      $ldap_server = ldap_servers_get_servers($sid, NULL, TRUE);
      $params = array(
        'synch_context' => $synch_context,
        'op' => 'create_ldap_user_entry',
        'module' => 'ldap_user',
        'function' => 'provisionLdapEntry',
        'include_count' => FALSE,
      );
      $proposed_ldap_entry = $this->drupalUserToLdapEntry($account, $ldap_server, $ldap_user, $params);
   //   debug('provisionLdapEntry:proposed_ldap_entry'); debug($proposed_ldap_entry);
      $existing_ldap_entry = $ldap_server->dnExists($proposed_ldap_entry['dn']);
      if ($existing_ldap_entry) {
       // debug('provisionLdapEntry:existing_ldap_entry');
        $results[$sid]['status'] = 'conflict';
        $results[$sid]['existing'] = $existing_ldap_entry;
        $results[$sid]['proposed'] = $proposed_ldap_entry;
        $results[$sid]['ldap_server'] = $ldap_server;
        continue;
      }
      
      if ($test_query) {
        $results[$sid]['status'] = 'not created because flagged as test query';
        $results[$sid]['proposed'] = $proposed_ldap_entry;
        $results[$sid]['created'] = FALSE;
        $results[$sid]['ldap_server'] = $ldap_server;        
      }
      else {
        $ldap_entry_created = $ldap_server->createLdapEntry($proposed_ldap_entry);
        if ($ldap_entry_created) {
          $results[$sid]['status'] = ($ldap_entry_created) ? 'success' : 'fail';
          $results[$sid]['proposed'] = $proposed_ldap_entry;
          $results[$sid]['created'] = $ldap_entry_created;
          $results[$sid]['ldap_server'] = $ldap_server;
        }
      }
    }
   // debug('provisionLdapEntry:results'); debug($results);
    foreach ($results as $sid => $result) {

      $tokens = array(
        '%dn' => $result['proposed']['dn'],
        '%sid' => $result['ldap_server']->sid,
        '%username' => $account->name,
        '%uid' => $account->uid,
      );
      if (!$test_query) {
        if ($result['status'] == 'success') {
          watchdog('ldap_user', 'LDAP entry on server %sid created dn=%dn. username=%username, uid=%uid', array(), WATCHDOG_INFO);
        }
        elseif($result['status'] == 'conflict') {
          watchdog('ldap_user', 'LDAP entry on server %sid not created because of existing ldap entry. username=%username, uid=%uid', array(), WATCHDOG_WARNING);
        }
        elseif($result['status'] == 'fail') {
          watchdog('ldap_user', 'LDAP entry on server %sid not created because error. username=%username, uid=%uid', array(), WATCHDOG_ERROR);
        }
      }
    }

    return $results;
  }


  /**
   * given a drupal account, delete ldap entry
   *
   * @param string $username drupal account name
   * @param int synch_context (see LDAP_USER_SYNCH_CONTEXT_* constants)
   *
   * @return TRUE or FALSE.  FALSE indicates failed or action not enabled in ldap user configuration
   */
  public function deleteCorrespondingLdapEntry($account) {
    // determine server that is associated with user
    list($account, $user_entity) = ldap_user_load_user_acct_and_entity($account->name);
    $dn = $user_entity->ldap_user_current_dn['und'][0]['value'];
    $sid = $user_entity->ldap_user_puid_sid['und'][0]['value'];
    $ldap_server = ldap_servers_get_servers($sid, NULL, TRUE);

    if (is_object($ldap_server) && $dn) {
      $result = $ldap_server->delete($dn);
    }
    else {
      $result = FALSE;
    }
    return $result;
  }

/** populate ldap entry array
   *
   * @param array $account drupal account
   * @param object $ldap_server
   * @param array $ldap_user ldap entry of user, returned by reference
   * @param array $params with the following key values:
   *    'synch_context' => LDAP_USER_SYNCH_CONTEXT_* constant
        'op' => 'create_ldap_user_entry', ...
        'module' => module calling function, e.g. 'ldap_user'
        'function' => function calling function, e.g. 'provisionLdapEntry'
        'include_count' => should 'count' array key be included
   *
   * @return ldap entry in ldap extension array format.
   */

  function drupalUserToLdapEntry($account, $ldap_server, $ldap_user_entry = array(), $params = array()) {
   // dpm('call to drupalUserToLdapEntry, account:'); dpm($account); dpm('params'); dpm($params); dpm('ldap entry'); dpm($ldap_user_entry);
    $watchdog_tokens = array(
      '%drupal_username' => $account->name,
    );
    $include_count = (isset($params['include_count']) && $params['include_count']);

    $mappings = $this->getSynchMappings($ldap_server->sid);
      //debug('getSynchMappings()');debug($this->getSynchMappings($ldap_server->sid));
      // Loop over the mappings.
   // debug('mappings'); debug($mappings);
   // debug('this->synchMapping'); debug($this->synchMapping);
    foreach ($mappings as $field_key => $field_detail) {
     // dpm('field_key'. $field_key);
     // ldap_servers_debug('field_key'. $field_key);
      $ldap_attr_name = ldap_servers_token_extract_attribute_name($field_key);  //trim($field_key, '[]');
      if (isset($ldap_user_entry[$ldap_attr_name])) { // don't override values passed in
        continue;
      }
      $synched = $this->isSynched($field_key, $ldap_server, $params['synch_context'], LDAP_USER_SYNCH_DIRECTION_TO_LDAP_ENTRY);
     // debug('field_key='. $field_key . ',ldap_attr_name='. $ldap_attr_name . ',synched=' . $synched);
      if ($synched) {
       // dpm("drupalUserToLdapEntry, synch=$synch, field_key = $field_key"); dpm($field_detail);
        
        $token = ($field_detail['user_attr'] == 'user_tokens') ? $field_detail['user_tokens'] : $field_detail['user_attr'];
     //   debug('call1'); debug($account);
        $value = check_plain(ldap_servers_token_replace($account, $token, 'user_account'));
        //ldap_servers_debug("$ldap_attr_name,token=$token, value=$value");
        if ($ldap_attr_name == 'dn') {
          $ldap_user_entry['dn'] = $value;
          $ldap_user_entry['distinguishedName'][0] = $value;
          if ($include_count) {
             $ldap_user_entry['distinguishedName']['count'] = 1;
          }         
        }
        else {
          $ldap_user_entry[$ldap_attr_name][0] = $value;
          if ($include_count) {
             $ldap_user_entry[$ldap_attr_name]['count'] = 1;
          }
        }
      }
    }
    


    /**
     * 4. call drupal_alter() to allow other modules to alter $ldap_user
     */
  // debug("drupalUserToLdapEntry: pre drupal alter ldap_user"); debug($ldap_user_entry);
    drupal_alter('ldap_entry', $ldap_user_entry, $params);
  //  debug("drupalUserToLdapEntry:final ldap_user"); debug($ldap_user_entry);
    return $ldap_user_entry;

  }



   /**
   * given a drupal account, query ldap and get all user fields and save user account
   * (note: parameters are in odd order to match synchDrupalAccount handle)
   *
   * @param array $account drupal account object or null
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

 //   debug('call to provisionDrupalAccount'); debug('account'); debug($account); debug('user_edit'); debug($user_edit);
 //   debug('synch_context'); debug($synch_context); debug('ldap_user'); debug($ldap_user); debug('save=' . $save);
  //  debug('this->sids'); debug($this->drupalAcctProvisionServers);  debug('this->provisionServers'); debug($this->provisionServers); debug('this'); debug($this);
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
      foreach ($this->drupalAcctProvisionServers as $sid => $discard) {
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

    // keep in mind here that different servers may provide different user properties, fields, etc.
    // @todo: need to add this idea into simpletest coverage
    foreach ($ldap_servers as $sid => $ldap_server) {
      $params = array(
        'account' => $account,
        'user_edit' => $user_edit,
        'synch_context' => $synch_context,
        'op' => 'create_drupal_user',
        'module' => 'ldap_user',
        'function' => 'provisionDrupalAccount',
      );
      drupal_alter('ldap_entry', $ldap_user, $params);
      $this->entryToUserEdit($ldap_user, $ldap_server, $user_edit, $synch_context, 'create');
    }

    if ($save) {
      $account = user_save(NULL, $user_edit, 'ldap_user');
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
   * @param array ldap entry $ldap_user
   * @param object $ldap_server
   * @param array $edit see hook_user_save, hook_user_update, etc
   * @param drupal account object $account
   * @param string $op see hook_ldap_attributes_needed_alter
   */

  function entryToUserEdit($ldap_user, $ldap_server, &$edit, $synch_context, $op) {
  //  debug('user_edit in entryToUserEdit start'); debug($edit);
  //  debug('entryToUserEdit'); debug($ldap_user);
    // need array of user fields and which direction and when they should be synched.
    
   // dpm('this->synchMapping'); dpm($this->synchMapping); dpm("sid=" . $ldap_server->sid . "synch context=$synch_context");
   
    if (!isset($edit['mail']) && $this->isSynched('property.mail', $ldap_server, $synch_context, LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER)) {
      $derived_mail = $ldap_server->deriveEmailFromLdapEntry($ldap_user['attr']);
      if ($derived_mail) {
        $edit['mail'] = $derived_mail;
      }
    }
    
    if ($this->isSynched('property.name', $ldap_server, $synch_context, LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER) && !isset($edit['name'])) {
      $name = $ldap_server->deriveUsernameFromLdapEntry($ldap_user['attr']);
      if ($name) {
        $edit['name'] = $name;
      }
    }

    if ($op == 'create') {
      $mail = isset($edit['mail']) ? $edit['mail'] : $ldap_user['mail'];
      $edit['pass'] = user_password(20);
      $edit['init'] = $mail;
      $edit['status'] = 1;
      if (!isset($edit['signature'])) {
        $edit['signature'] = '';
      }

      $edit['data']['ldap_authentication']['init'] = array(
        'sid'  => $ldap_user['sid'],
        'dn'   => $ldap_user['dn'],
        'mail' => $mail,
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

    // Get any additional mappings.
    if (($mappings = $this->getSynchMappings($ldap_server->sid))) {
      //debug('getSynchMappings()');debug($this->getSynchMappings($ldap_server->sid));
      // Loop over the mappings.
      foreach ($mappings as $user_attr_key => $field_detail) {
      //  debug('field detail'); debug($field_detail);
        // Make sure this mapping is relevant to the sync context.
        if (!$this->isSynched($user_attr_key, $ldap_server, $synch_context, LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER)) {
          //debug($field_detail);
          continue;
        }
        $value = ldap_servers_token_replace($ldap_user['attr'], $field_detail['ldap_attr'], 'ldap_entry');
        list($value_type, $value_name, $value_instance) = ldap_servers_parse_user_attr_name($user_attr_key);
        // $value_instance not used, may have future use case

        // Are we dealing with a field?
        if ($value_type == 'field') {
          // Field api field - first we get the field.
          $field = field_info_field($value_name);
          // Then the columns for the field in the schema.
          $columns = array_keys($field['columns']);
          // Then we convert the value into an array if it's scalar.
          $values = $field['cardinality'] == 1 ? array($value) : (array) $value;

          $items = array();
          // Loop over the values and set them in our $items array.
          foreach ($values as $delta => $value) {
            if (isset($value)) {
              // We set the first column value only, this is consistent with
              // the Entity Api (@see entity_metadata_field_property_set).
              $items[$delta][$columns[0]] = $value;
            }
          }
          // Add them to our edited item.
          $edit[$value_name][LANGUAGE_NONE] = $items;
        }
        elseif ($value_type == 'property') {
          // Straight property.
          $edit[$value_name] = $value;
        }
     //   debug("value_name=$value_name,value=$value");
      }
    }
    // Allow other modules to have a say.
  //  debug('user_edit in ldapuser before drupal alter'); debug($edit);
    drupal_alter('ldap_user_edit_user', $edit, $ldap_user, $ldap_server, $synch_context);
  //  debug('user_edit in ldapuser after drupal alter'); debug($edit);
  }


} // end LdapUserConf class
