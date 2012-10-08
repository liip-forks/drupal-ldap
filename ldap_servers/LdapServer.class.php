<?php
// $Id: LdapServer.class.php,v 1.5.2.1 2011/02/08 06:01:00 johnbarclay Exp $

/**
 * @file
 * Defines server classes and related functions.
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
class LdapServer {
  // LDAP Settings

  const LDAP_CONNECT_ERROR = 0x5b;
  const LDAP_SUCCESS = 0x00;
  const LDAP_OPERATIONS_ERROR = 0x01;
  const LDAP_PROTOCOL_ERROR = 0x02;

  public $sid;
  public $name;
  public $status;
  public $ldap_type;
  public $address;
  public $port = 389;
  public $tls = FALSE;
  public $bind_method = 0;
  public $basedn = array();
  public $binddn = FALSE; // Default to an anonymous bind.
  public $bindpw = FALSE; // Default to an anonymous bind.
  public $user_dn_expression;
  public $user_attr;
  public $account_name_attr; //lowercase
  public $mail_attr; //lowercase
  public $mail_template;
  public $unique_persistent_attr; //lowercase
  public $unique_persistent_attr_binary = FALSE;
  public $ldapToDrupalUserPhp;
  public $testingDrupalUsername;
  public $detailed_watchdog_log;
  public $editPath;
  public $queriableWithoutUserCredentials = FALSE; // can this server be queried without user credentials provided?
  public $userAttributeNeededCache = array(); // array of attributes needed keyed on $op such as 'user_update'

  public $groupFunctionalityUnused = FALSE;
  public $groupObjectClass;
  public $groupNested = 0; // 1 | 0
  public $groupDeriveFromDn = FALSE;
  public $groupDeriveFromDnAttr = NULL; //lowercase
  public $groupUserMembershipsAttrExists = FALSE; // does a user attribute containing groups exist?
  public $groupUserMembershipsAttr = NULL;   //lowercase     // name of user attribute containing groups
  public $groupUserMembershipsConfigured = FALSE; // user attribute containing memberships is configured enough to use

  public $groupMembershipsAttr = NULL;  //lowercase // members, uniquemember, memberUid
  public $groupMembershipsAttrMatchingUserAttr = NULL; //lowercase // dn, cn, etc contained in groupMembershipsAttr
  public $groupGroupEntryMembershipsConfigured = FALSE; // are groupMembershipsAttrMatchingUserAttr and groupGroupEntryMembershipsConfigured populated
  
  public $groupTestGroupDn = NULL;
  
  private $group_properties = array(
    'groupObjectClass', 'groupNested', 'groupDeriveFromDn', 'groupDeriveFromDnAttr', 'groupUserMembershipsAttrExists',
    'groupUserMembershipsAttr', 'groupMembershipsAttrMatchingUserAttr', 'groupTestGroupDn'
  );
   
  public $paginationEnabled = FALSE; // (boolean)(function_exists('ldap_control_paged_result_response') && function_exists('ldap_control_paged_result'));
  public $searchPagination = FALSE;
  public $searchPageSize = 1000;
  public $searchPageStart = 0;
  public $searchPageEnd = NULL;
  
  public $inDatabase = FALSE;
  public $connection;
  


  
  // direct mapping of db to object properties
  public static function field_to_properties_map() {
    return array( 'sid' => 'sid',
    'name'  => 'name' ,
    'status'  => 'status',
    'ldap_type'  => 'ldap_type',
    'address'  => 'address',
    'port'  => 'port',
    'tls'  => 'tls',
    'bind_method' => 'bind_method',
    'basedn'  => 'basedn',
    'binddn'  => 'binddn',
    'user_dn_expression' => 'user_dn_expression',
    'user_attr'  => 'user_attr',
    'account_name_attr'  => 'account_name_attr',
    'mail_attr'  => 'mail_attr',
    'mail_template'  => 'mail_template',
    'unique_persistent_attr' => 'unique_persistent_attr',
    'unique_persistent_attr_binary' => 'unique_persistent_attr_binary',
    'ldap_to_drupal_user'  => 'ldapToDrupalUserPhp',
    'testing_drupal_username'  => 'testingDrupalUsername',
    
    'groupFunctionalityUnused' => 'groupFunctionalityUnused',
    'group_object_category' => 'groupObjectClass',
    'groupNested' => 'groupNested',
    'groupUserMembershipsAttrExists' => 'groupUserMembershipsAttrExists',
    'groupUserMembershipsAttr'=> 'groupUserMembershipsAttr',
    'groupMembershipsAttr' => 'groupMembershipsAttr',
    'groupMembershipsAttrMatchingUserAttr' => 'groupMembershipsAttrMatchingUserAttr',
    'groupDeriveFromDn' => 'groupDeriveFromDn',
    'groupDeriveFromDnAttr' => 'groupDeriveFromDnAttr',
    'groupTestGroupDn' =>  'groupTestGroupDn',

    'search_pagination' => 'searchPagination',
    'search_page_size' => 'searchPageSize',
    
    );

  }

  /**
   * Constructor Method
   */
  function __construct($sid) {
    if (!is_scalar($sid)) {
      return;
    }
    $this->detailed_watchdog_log = variable_get('ldap_help_watchdog_detail', 0);
    $server_record = array();
    if (module_exists('ctools')) {
      ctools_include('export');
      $result = ctools_export_load_object('ldap_servers', 'names', array($sid));
      if (isset($result[$sid])) {
        $server_record[$sid] = $result[$sid];
        foreach ($server_record[$sid] as $property => $value) {
          $this->{$property} = $value;
        }
      }
    }
    else {
      $select = db_select('ldap_servers')
        ->fields('ldap_servers')
        ->condition('ldap_servers.sid',  $sid)
        ->execute();
      foreach ($select as $record) {
        $server_record[$record->sid] = $record;
      }
    }
    if (!isset($server_record[$sid])) {
      $this->inDatabase = FALSE;
      return;
    }
    $server_record = $server_record[$sid];
    if ($server_record) {
      $this->inDatabase = TRUE;
      $this->sid = $sid;
      $this->detailedWatchdogLog = variable_get('ldap_help_watchdog_detail', 0);
    }
    else {
      // @todo throw error
    }
    
    $groups_unused = (isset($server_record->groupFunctionalityUnused) && $server_record->groupFunctionalityUnused);
    foreach ($this->field_to_properties_map() as $db_field_name => $property_name ) {
      if (isset($server_record->$db_field_name)) {
        if ($groups_unused && in_array($db_field_name, $this->group_properties)) {
          // leave as default
        }
        else {
          $this->{$property_name} = $server_record->$db_field_name;
        }
      }
    }
    if (is_scalar($this->basedn)) {
      $this->basedn = unserialize($this->basedn);
    }
    if (isset($server_record->bindpw) && $server_record->bindpw != '') {
      $this->bindpw = $server_record->bindpw;
      $this->bindpw = ldap_servers_decrypt($this->bindpw);
    }
    
    $this->paginationEnabled = (boolean)(ldap_servers_php_supports_pagination() && $this->searchPagination);

    $this->queriableWithoutUserCredentials = (boolean)(
      $this->bind_method == LDAP_SERVERS_BIND_METHOD_SERVICE_ACCT ||
      $this->bind_method == LDAP_SERVERS_BIND_METHOD_ANON_USER
    );
    $this->editPath = 'admin/config/people/ldap/servers/edit/' . $this->sid;
    
    $this->groupGroupEntryMembershipsConfigured = ($this->groupMembershipsAttrMatchingUserAttr && $this->groupMembershipsAttr);
    $this->groupUserMembershipsConfigured = ($this->groupUserMembershipsAttrExists && $this->groupUserMembershipsAttr);
  }

  /**
   * Destructor Method
   */
  function __destruct() {
    // Close the server connection to be sure.
    $this->disconnect();
  }


  /**
   * Invoke Method
   */
  function __invoke() {
    $this->connect();
    $this->bind();
  }



  /**
   * Connect Method
   */
  function connect() {

    if (!$con = ldap_connect($this->address, $this->port)) {
      watchdog('user', 'LDAP Connect failure to ' . $this->address . ':' . $this->port);
      return LDAP_CONNECT_ERROR;
    }

    ldap_set_option($con, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($con, LDAP_OPT_REFERRALS, 0);

    // Use TLS if we are configured and able to.
    if ($this->tls) {
      ldap_get_option($con, LDAP_OPT_PROTOCOL_VERSION, $vers);
      if ($vers == -1) {
        watchdog('user', 'Could not get LDAP protocol version.');
        return LDAP_PROTOCOL_ERROR;
      }
      if ($vers != 3) {
        watchdog('user', 'Could not start TLS, only supported by LDAP v3.');
        return LDAP_CONNECT_ERROR;
      }
      elseif (!function_exists('ldap_start_tls')) {
        watchdog('user', 'Could not start TLS. It does not seem to be supported by this PHP setup.');
        return LDAP_CONNECT_ERROR;
      }
      elseif (!ldap_start_tls($con)) {
        $msg =  t("Could not start TLS. (Error %errno: %error).", array('%errno' => ldap_errno($con), '%error' => ldap_error($con)));
        watchdog('user', $msg);
        return LDAP_CONNECT_ERROR;
      }
    }

  // Store the resulting resource
  $this->connection = $con;
  return LDAP_SUCCESS;
  }


  /**
	 * Bind (authenticate) against an active LDAP database.
	 *
	 * @param $userdn
	 *   The DN to bind against. If NULL, we use $this->binddn
	 * @param $pass
	 *   The password search base. If NULL, we use $this->bindpw
   *
   * @return
   *   Result of bind; TRUE if successful, FALSE otherwise.
   */
  function bind($userdn = NULL, $pass = NULL, $anon_bind = FALSE) {

    // Ensure that we have an active server connection.
    if (!$this->connection) {
      watchdog('ldap', "LDAP bind failure for user %user. Not connected to LDAP server.", array('%user' => $userdn));
      return LDAP_CONNECT_ERROR;
    }
    if ($anon_bind) {
      if (@!ldap_bind($this->connection)) {
        watchdog('ldap', "LDAP anonymous bind error. Error %errno: %error", array('%errno' => ldap_errno($this->connection), '%error' => ldap_error($this->connection)));
        return ldap_errno($this->connection);
      }
    }
    else {
      $userdn = ($userdn != NULL) ? $userdn : $this->binddn;
      $pass = ($pass != NULL) ? $pass : $this->bindpw;
      if (@!ldap_bind($this->connection, $userdn, $pass)) {
        watchdog('ldap', "LDAP bind failure for user %user. Error %errno: %error", array('%user' => $userdn, '%errno' => ldap_errno($this->connection), '%error' => ldap_error($this->connection)));
        return ldap_errno($this->connection);
      }
    }

    return LDAP_SUCCESS;
  }

  /**
   * Disconnect (unbind) from an active LDAP server.
   */
  function disconnect() {
    if (!$this->connection) {
      // never bound or not currently bound, so no need to disconnect
      //watchdog('ldap', 'LDAP disconnect failure from '. $this->server_addr . ':' . $this->port);
    }
    else {
      ldap_unbind($this->connection);
      $this->connection = NULL;
    }
  }

  public function connectAndBindIfNotAlready() {
    if (! $this->connection) {
      $this->connect();
      $this->bind();
    } 
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
    if ($result !== FALSE) {
      if ($return == 'boolean') {
        return TRUE;
      }

      $entries = @ldap_get_entries($this->connection, $result);
      if ($entries !== FALSE) {
        return $entries[0];
      }
    }

    return FALSE;

  }
    
  
  public function countEntries($ldap_result) {
    return ldap_count_entries($this->connection, $ldap_result);
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
    // dpm("createLdapEntry, dn=$dn"); dpm($ldap_entry);
    if (!$this->connection) {
      $this->connect();
      $this->bind();
    }
    if (isset($ldap_entry['dn'])) {
      $dn = $ldap_entry['dn'];
      unset($ldap_entry['dn']);
    }
    elseif(!$dn) {
      return FALSE;
    }
    

    $result = @ldap_add($this->connection, $dn, $ldap_entry); 
    if (!$result) {
      $error = "LDAP Server ldap_add(%dn) Error Server ID = %sid, LDAP Err No: %ldap_errno LDAP Err Message: %ldap_err2str ";
      $tokens = array('%dn' => $dn, '%sid' => $this->sid, '%ldap_errno' => ldap_errno($this->connection), '%ldap_err2str' => ldap_err2str(ldap_errno($this->connection)));
      watchdog('ldap_server', $error, $tokens, WATCHDOG_ERROR);
    }


    return $result;
  }



/**
 * given 2 ldap entries, old and new, removed unchanged values to avoid security errors and incorrect date modifieds
 *
 * @param ldap entry array $new_entry in form <attribute> => <value>
 * @param ldap entry array $old_entry in form <attribute> => array('count' => N, array(<value>,...<value>
 *
 * @return ldap array with no values that have NOT changed
 */

  static public function removeUnchangedAttributes($new_entry, $old_entry) {

    foreach ($new_entry as $key => $new_val) {
      $old_value = FALSE;
      $key_lcase = drupal_strtolower($key);
      if (isset($old_entry[$key_lcase])) {
        if ($old_entry[$key_lcase]['count'] == 1) {
          $old_value = $old_entry[$key_lcase][0];
          $old_value_is_scalar = TRUE;
        }
        else {
          unset($old_entry[$key_lcase]['count']);
          $old_value = $old_entry[$key_lcase];
          $old_value_is_scalar = FALSE;
        }
      }
      
      // identical multivalued attributes
      if (is_array($new_val) && is_array($old_value) && count(array_diff($new_val, $old_value)) == 0) {
        unset($new_entry[$key]);
      }
      elseif ($old_value_is_scalar && !is_array($new_val) && drupal_strtolower($old_value) == drupal_strtolower($new_val)) {
        unset($new_entry[$key]); // don't change values that aren't changing to avoid false permission constraints
      }
    }
    return $new_entry;
  }
  
     
    


  /**
   * modify attributes of ldap entry
   *
   * @param string $dn DN of entry
   * @param array $attributes should follow the structure of ldap_add functions
   *   entry array: http://us.php.net/manual/en/function.ldap-add.php
        $attributes["attribute1"] = "value";
        $attributes["attribute2"][0] = "value1";
        $attributes["attribute2"][1] = "value2";
   */

  function modifyLdapEntry($dn, $attributes = array(), $old_attributes = FALSE) {
    
    $this->connectAndBindIfNotAlready();
    
    if (!$old_attributes) {
      $result = ldap_read($this->connection, $dn, 'objectClass=*');
      $entries = ldap_get_entries($this->connection, $result);
      if (is_array($entries) && $entries['count'] == 1) {
        $old_attributes =  $entries[0];
      }
    }
    $attributes = $this->removeUnchangedAttributes($attributes, $old_attributes);

    foreach ($attributes as $key => $cur_val) {
      $old_value = FALSE;
      $key_lcase = drupal_strtolower($key);
      if (isset($old_attributes[$key_lcase])) {
        if ($old_attributes[$key_lcase]['count'] == 1) {
          $old_value = $old_attributes[$key_lcase][0];
        }
        else {
          unset($old_attributes[$key_lcase]['count']);
          $old_value = $old_attributes[$key_lcase];
        }
      }

      if ($cur_val == '' && $old_value != '') { // remove enpty attributes
        unset($attributes[$key]);
        ldap_mod_del($this->connection, $dn, array($key_lcase => $old_value));
      }
      elseif (is_array($cur_val)) {
        foreach ($cur_val as $mv_key => $mv_cur_val) {
          if ($mv_cur_val == '') {
            unset($attributes[$key][$mv_key]); // remove empty values in multivalues attributes
          }
          else {
            $attributes[$key][$mv_key] = $mv_cur_val;
          }
        }
      }
    }
  //  dpm('modifyLdapEntry, attributes to modify'); dpm($attributes);
    if (count($attributes) > 0) {
      $status = ldap_modify($this->connection, $dn, $attributes);
    }
    else {
      $status = TRUE; // since no changes, TRUE
    }

    if (!$status) {
      watchdog(
      'ldap_servers',
      'Error: ldapModify() failed to modify ldap entry w/ DN "!dn" with values: !values',
      array('!dn' => $dn, '!value' => var_export($attributes, TRUE)),
      WATCHDOG_ERROR
      );
    }

    return $status;

  }

  /**
   * Perform an LDAP delete.
   *
   * @param string $dn
   *
   * @return boolean result per ldap_delete
   */

  public function delete($dn) {
    if (!$this->connection) {
      $this->connect();
      $this->bind();
    }
    $result = @ldap_delete($this->connection, $dn);
    return $result;
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

  function search($base_dn = NULL, $filter, $attributes = array(),
    $attrsonly = 0, $sizelimit = 0, $timelimit = 0, $deref = NULL, $scope = LDAP_SCOPE_SUBTREE) {

     /**
      * pagingation issues:
      * -- see documentation queue: http://markmail.org/message/52w24iae3g43ikix#query:+page:1+mid:bez5vpl6smgzmymy+state:results
      * -- wait for php 5.4? https://svn.php.net/repository/php/php-src/tags/php_5_4_0RC6/NEWS (ldap_control_paged_result
      * -- http://sgehrig.wordpress.com/2009/11/06/reading-paged-ldap-results-with-php-is-a-show-stopper/
      */


    if ($base_dn == NULL) {
      if (count($this->basedn) == 1) {
        $base_dn = $this->basedn[0];
      }
      else {
        return FALSE;
      }
    }

    $attr_display =  is_array($attributes) ? join(',', $attributes) : 'none';
    $query = 'ldap_search() call: '. join(",\n", array(
      'base_dn: ' . $base_dn,
      'filter = ' . $filter,
      'attributes: ' . $attr_display,
      'attrsonly = ' .  $attrsonly,
      'sizelimit = ' .  $sizelimit,
      'timelimit = ' .  $timelimit,
      'deref = ' .  $deref,
      'scope = ' .  $scope,
      )
    );
    if ($this->detailed_watchdog_log) {
      watchdog('ldap_server', $query, array());
    }

    // When checking multiple servers, there's a chance we might not be connected yet.
    if (! $this->connection) {
      $this->connect();
      $this->bind();
    }

    $ldap_query_params = array(
      'connection' => $this->connection,
      'base_dn' => $base_dn,
      'filter' => $filter,
      'attributes' => $attributes,
      'attrsonly' => $attrsonly,
      'sizelimit' => $sizelimit,
      'timelimit' => $timelimit,
      'deref' => $deref,
      'query_display' => $query,
      'scope' => $scope,
    );
   // dpm($ldap_query_params); dpm("searchPagination=" . $this->searchPagination .",paginationEnabled=". $this->paginationEnabled .", searchPageStart=" . $this->searchPageStart);
    if ($this->searchPagination && $this->paginationEnabled) {
      $aggregated_entries = $this->pagedLdapQuery($ldap_query_params);
      return $aggregated_entries;
    }
    else {
      $result = $this->ldapQuery($scope, $ldap_query_params);
      if ($result && ($this->countEntries($result) !== FALSE) ) {
        $entries = ldap_get_entries($this->connection, $result);
        drupal_alter('ldap_server_search_results', $entries, $ldap_query_params);
        return (is_array($entries)) ? $entries : FALSE;
      }
      elseif ($this->ldapErrorNumber()) {
        $watchdog_tokens =  array('%basedn' => $ldap_query_params['base_dn'], '%filter' => $ldap_query_params['filter'],
          '%attributes' => print_r($ldap_query_params['attributes'], TRUE), '%errmsg' => $this->errorMsg('ldap'),
          '%errno' => $this->ldapErrorNumber());
        watchdog('ldap', "LDAP ldap_search error. basedn: %basedn| filter: %filter| attributes:
          %attributes| errmsg: %errmsg| ldap err no: %errno|", $watchdog_tokens);
        return FALSE;
      }
      else {
        return FALSE;
      }
    }
  }


  /**
   * execute a paged ldap query and return entries as one aggregated array
   *
   * $this->searchPageStart and $this->searchPageEnd should be set before calling if
   *   a particular set of pages is desired
   *
   * @param array $ldap_query_params of form:
      'base_dn' => base_dn,
      'filter' =>  filter,
      'attributes' => attributes,
      'attrsonly' => attrsonly,
      'sizelimit' => sizelimit,
      'timelimit' => timelimit,
      'deref' => deref,
      'scope' => scope,

      (this array of parameters is primarily passed on to ldapQuery() method)
   *
   * @return array of ldap entries or FALSE on error.
   *
   */
  public function pagedLdapQuery($ldap_query_params) {

    if (!($this->searchPagination && $this->paginationEnabled)) {
      watchdog('ldap', "LDAP server pagedLdapQuery() called when functionality not available in php install or
        not enabled in ldap server configuration.  error. basedn: %basedn| filter: %filter| attributes:
         %attributes| errmsg: %errmsg| ldap err no: %errno|", $watchdog_tokens);
      RETURN FALSE;
    }

    $paged_entries = array();
    $page_token = '';
    $page = 0;
    $estimated_entries = 0;
    $aggregated_entries = array();
    $aggregated_entries_count = 0;
    $has_page_results = FALSE;

    do {
      ldap_control_paged_result($this->connection, $this->searchPageSize, true, $page_token);
      $result = $this->ldapQuery($ldap_query_params['scope'], $ldap_query_params);

      if ($page >= $this->searchPageStart) {
        $skipped_page = FALSE;
        if ($result && ($this->countEntries($result) !== FALSE) ) {
          $page_entries = ldap_get_entries($this->connection, $result);
          unset($page_entries['count']);
          $has_page_results = (is_array($page_entries) && count($page_entries) > 0);
          $aggregated_entries = array_merge($aggregated_entries, $page_entries);
          $aggregated_entries_count = count($aggregated_entries);
        }
        elseif ($this->ldapErrorNumber()) {
          $watchdog_tokens =  array('%basedn' => $ldap_query_params['base_dn'], '%filter' => $ldap_query_params['filter'],
            '%attributes' => print_r($ldap_query_params['attributes'], TRUE), '%errmsg' => $this->errorMsg('ldap'),
            '%errno' => $this->ldapErrorNumber());
          watchdog('ldap', "LDAP ldap_search error. basedn: %basedn| filter: %filter| attributes:
            %attributes| errmsg: %errmsg| ldap err no: %errno|", $watchdog_tokens);
          RETURN FALSE;
        }
        else {
          return FALSE;
        }
      }
      else {
        $skipped_page = TRUE;
      }
      @ldap_control_paged_result_response($this->connection, $result, $page_token, $estimated_entries);
      if ($ldap_query_params['sizelimit'] && $this->ldapErrorNumber() == LDAP_SIZELIMIT_EXCEEDED) {
        // false positive error thrown.  do not set result limit error when $sizelimit specified
      }
      elseif ($this->hasError()) {
        watchdog('ldap_server', 'ldap_control_paged_result_response() function error. LDAP Error: %message, ldap_list() parameters: %query',
          array('%message' => $this->errorMsg('ldap'), '%query' => $ldap_query_params['query_display']),
          WATCHDOG_ERROR);
      }

      if (isset($ldap_query_params['sizelimit']) && $ldap_query_params['sizelimit'] && $aggregated_entries_count >= $ldap_query_params['sizelimit']) {
        $discarded_entries = array_splice($aggregated_entries, $ldap_query_params['sizelimit']);
        break;
      }
      elseif ($this->searchPageEnd !== NULL && $page >= $this->searchPageEnd) { // user defined pagination has run out
        break;
      }
      elseif ($page_token === NULL || $page_token == '') { // ldap reference pagination has run out
        break;
      }
      $page++;
    } while ($skipped_page || $has_page_results);

    $aggregated_entries['count'] = count($aggregated_entries);
    return $aggregated_entries;
  }

  function ldapQuery($scope, $params) {

    switch ($scope) {
      case LDAP_SCOPE_SUBTREE:
        $result = @ldap_search($this->connection, $params['base_dn'], $params['filter'], $params['attributes'], $params['attrsonly'],
          $params['sizelimit'], $params['timelimit'], $params['deref']);
        if ($params['sizelimit'] && $this->ldapErrorNumber() == LDAP_SIZELIMIT_EXCEEDED) {
          // false positive error thrown.  do not return result limit error when $sizelimit specified
        }
        elseif ($this->hasError()) {
          watchdog('ldap_server', 'ldap_search() function error. LDAP Error: %message, ldap_search() parameters: %query',
            array('%message' => $this->errorMsg('ldap'), '%query' => $params['query_display']),
            WATCHDOG_ERROR);
        }
        break;

      case LDAP_SCOPE_BASE:
        $result = @ldap_read($this->connection, $params['base_dn'], $params['filter'], $params['attributes'], $params['attrsonly'],
           $params['sizelimit'], $params['timelimit'], $params['deref']);
        if ($params['sizelimit'] && $this->ldapErrorNumber() == LDAP_SIZELIMIT_EXCEEDED) {
          // false positive error thrown.  do not result limit error when $sizelimit specified
        }
        elseif ($this->hasError()) {
          watchdog('ldap_server', 'ldap_read() function error.  LDAP Error: %message, ldap_read() parameters: %query',
            array('%message' => $this->errorMsg('ldap'), '%query' =>@$params['query_display']),
            WATCHDOG_ERROR);
        }
        break;

      case LDAP_SCOPE_ONELEVEL:
        $result = @ldap_list($this->connection, $params['base_dn'], $params['filter'], $params['attributes'], $params['attrsonly'],
           $params['sizelimit'], $params['timelimit'], $params['deref']);
        if ($params['sizelimit'] && $this->ldapErrorNumber() == LDAP_SIZELIMIT_EXCEEDED) {
          // false positive error thrown.  do not result limit error when $sizelimit specified
        }
        elseif ($this->hasError()) {
          watchdog('ldap_server', 'ldap_list() function error. LDAP Error: %message, ldap_list() parameters: %query',
            array('%message' => $this->errorMsg('ldap'), '%query' => $params['query_display']),
            WATCHDOG_ERROR);
        }
        break;
    }
    return $result;
  }
  
  public function userUserEntityFromPuid($puid) {
    
   // list($account, $user_entity) = ldap_user_load_user_acct_and_entity('jkeats');
    //debug('userUserEntityFromPuid:account and user entity'); debug($account); debug($user_entity);
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'user')
    ->fieldCondition('ldap_user_puid_sid', 'value', $this->sid, '=')
    ->fieldCondition('ldap_user_puid', 'value', $puid, '=')
    ->fieldCondition('ldap_user_puid_property', 'value', $this->unique_persistent_attr, '=')
    ->addMetaData('account', user_load(1)); // run the query as user 1
// ->entityCondition('bundle', 'user')
    $result = $query->execute();
   // debug("userUserEntityFromPuid: puid=$puid, sid=". $this->sid . "attr=" . $this->unique_persistent_attr); debug($result);
    if (isset($result['user'])) {
      $uids = array_keys($result['user']);
      if (count($uids) == 1) {
        $user = entity_load('user', array_keys($result['user']));
        return $user[$uids[0]];
      }
      else {
        $uids = join(',',$uids);
        $tokens = array('%uids' => $uids, '%puid' => $puid, '%sid' =>  $this->sid, '%ldap_user_puid_property' =>  $this->unique_persistent_attr);
        watchdog('ldap_server', 'multiple users (uids: %uids) with same puid (puid=%puid, sid=%sid, ldap_user_puid_property=%ldap_user_puid_property)', $tokens, WATCHDOG_ERROR);
        return FALSE;
      }
    }
    else {
      return FALSE;
    }

  }
  
  function userUsernameToLdapNameTransform($drupal_username, &$watchdog_tokens) {
    if ($this->ldapToDrupalUserPhp && module_exists('php')) {
      global $name;
      $old_name_value = $name;
      $name = $drupal_username;
      $code = "<?php global \$name; \n". $this->ldapToDrupalUserPhp . "; \n ?>";
      $watchdog_tokens['%code'] = $this->ldapToDrupalUserPhp;
      $code_result = php_eval($code);
      $watchdog_tokens['%code_result'] = $code_result;
      $ldap_username = $code_result;
      $watchdog_tokens['%ldap_username'] = $ldap_username;
      $name = $old_name_value;  // important because of global scope of $name
      if ($this->detailedWatchdogLog) {
        watchdog('ldap_server', '%drupal_user_name tansformed to %ldap_username by applying code <code>%code</code>', $watchdog_tokens, WATCHDOG_DEBUG);
      }
    }
    else {
      $ldap_username = $drupal_username;
    }

    return $ldap_username;

  }
  
  
 /**
   * @param ldap entry array $ldap_entry
   *
   * @return string user's username value
   */
  public function userUsernameFromLdapEntry($ldap_entry) {
    
    $accountname = FALSE;
    if ($this->account_name_attr) {
      $accountname = (empty($ldap_entry[$this->user_attr][0])) ? FALSE : $ldap_entry[$this->account_name_attr][0];
    }
    elseif ($this->user_attr)  {
      $accountname = (empty($ldap_entry[$this->user_attr][0])) ? FALSE : $ldap_entry[$this->user_attr][0];
    }

    return $accountname;
  }

 /**
   * @param string $dn ldap dn
   *
   * @return mixed string user's username value of FALSE
   */
  public function userUsernameFromDn($dn) {
    
    $ldap_entry = @$this->dnExists($dn, 'ldap_entry', array());
    if (!$ldap_entry || !is_array($ldap_entry)) {
      return FALSE;
    }
    else {
      return $this->userUsernameFromLdapEntry($ldap_entry);
    }
  
  }
  
  /**
   * @param ldap entry array $ldap_entry
   *
   * @return string user's mail value
   */
  public function userEmailFromLdapEntry($ldap_entry) {
    if ($this->mail_attr) { // not using template
      return @$ldap_entry[$this->mail_attr][0];
    }
    elseif ($this->mail_template) {  // template is of form [cn]@illinois.edu
      ldap_servers_module_load_include('inc', 'ldap_servers', 'ldap_servers.functions');
      return ldap_servers_token_replace($ldap_entry, $this->mail_template, 'ldap_entry');
    }
    else {
      return FALSE;
    }
  }


  /**
   * @param ldap entry array $ldap_entry
   *
   * @return string user's PUID or permanent user id (within ldap)
   */
  public function userPuidFromLdapEntry($ldap_entry) {
  
    if ($this->unique_persistent_attr
        && isset($ldap_entry[$this->unique_persistent_attr][0])
        && is_scalar($ldap_entry[$this->unique_persistent_attr][0])
        ) {

      //@todo this should go through whatever standard detokenizing function ldap_server module has
      return $ldap_entry[$this->unique_persistent_attr][0];
    }
    else {
      return FALSE;
    }
  }

   /**
   *  @param mixed $user
   *    - drupal user object (stdClass Object)
   *    - ldap entry of user (array)
   *    - ldap dn of user (string)
   *    - drupal username of user (string)
   *    
   *  @return array $ldap_user_entry
  */
 
  public function userUserToExistingLdapEntry($user) {
    
    if (is_object($user)) {
      $user_ldap_entry = $this->userUserNameToExistingLdapEntry($user->name);
    }
    elseif (is_array($user)) {
      $user_ldap_entry = $user;
    }
    elseif (is_scalar($user)) {
      if (strpos($user, '=') === FALSE) { // username
        $user_ldap_entry = $this->userUserNameToExistingLdapEntry($user);
      }
      else { 
        $user_ldap_entry = $this->dnExists($user, 'ldap_entry');
      }
    }
    return $user_ldap_entry;  
  }
  
  /**
   * Queries LDAP server for the user.
   *
   * @param $drupal_user_name
   *  drupal user name.
   *
   * @param string or int $prov_event
   *   This could be anything, particularly when used by other modules.  Other modules should use string like 'mymodule_myevent'
   *   LDAP_USER_EVENT_ALL signifies get all attributes needed by all other contexts/ops
   *
   * @return
   *   An array with user's LDAP data or NULL if not found.
   */
  function userUserNameToExistingLdapEntry($drupal_user_name, $ldap_context = NULL) {
   // dpm("userUserNameToExistingLdapEntry, drupal_user_name=$drupal_user_name, op=$op");
    $watchdog_tokens = array('%drupal_user_name' => $drupal_user_name);
    $ldap_username = $this->userUsernameToLdapNameTransform($drupal_user_name, $watchdog_tokens);
    if (!$ldap_username) {
      return FALSE;
    }
    if (!$ldap_context) {
      $attribute_maps = array();
    }
    else {
     // debug('ldap_servers_attributes_needed(this->sid, direction, prov_event)'); debug(array($this, $this->sid, $direction, $prov_event));
      $attribute_maps = ldap_servers_attributes_needed($this->sid, $ldap_context);
    }
    
    foreach ($this->basedn as $basedn) {
      if (empty($basedn)) continue;
      $filter = '('. $this->user_attr . '=' . ldap_server_massage_text($ldap_username, 'attr_value', LDAP_SERVER_MASSAGE_QUERY_LDAP)   . ')';
      $result = $this->search($basedn, $filter, array_keys($attribute_maps));
     // debug("ldap_server: userUserNameToExistingLdapEntry, filter=$filter, basedn=$basedn, result="); debug($result); debug('userUserNameToExistingLdapEntry:attributes needed'); debug($attribute_maps); 
      if (!$result || !isset($result['count']) || !$result['count']) continue;

      // Must find exactly one user for authentication to work.
      if ($result['count'] != 1) {
        $count = $result['count'];
        watchdog('ldap_servers', "Error: !count users found with $filter under $basedn.", array('!count' => $count), WATCHDOG_ERROR);
        continue;
      }
      $match = $result[0];
      // These lines serve to fix the attribute name in case a
      // naughty server (i.e.: MS Active Directory) is messing the
      // characters' case.
      // This was contributed by Dan "Gribnif" Wilga, and described
      // here: http://drupal.org/node/87833
      $name_attr = $this->user_attr;
      if (isset($match[$name_attr][0])) {

      }
      elseif (isset($match[drupal_strtolower($name_attr)][0])) {
        $name_attr = drupal_strtolower($name_attr);
      }
      else {
        if ($this->bind_method == LDAP_SERVERS_BIND_METHOD_ANON_USER) {
          $result = array(
            'dn' =>  $match['dn'],
            'mail' => $this->userEmailFromLdapEntry($match),
            'attr' => $match,
            'sid' => $this->sid,
            );
          return $result;
        }
        else {
          continue;
        }
      }

      // Finally, we must filter out results with spaces added before
      // or after, which are considered OK by LDAP but are no good for us
      // We allow lettercase independence, as requested by Marc Galera
      // on http://drupal.org/node/97728
      //
      // Some setups have multiple $name_attr per entry, as pointed out by
      // Clarence "sparr" Risher on http://drupal.org/node/102008, so we
      // loop through all possible options.
      foreach ($match[$name_attr] as $value) {
        if (drupal_strtolower(trim($value)) == drupal_strtolower($ldap_username)) {
          $result = array(
            'dn' =>  $match['dn'],
            'mail' => $this->userEmailFromLdapEntry($match),
            'attr' => $match,
            'sid' => $this->sid,
          );

          return $result;
        }
      }
    }
  }



  /**
   *  @param mixed
   *    - drupal user object (stdClass Object)
   *    - ldap entry of user (array)
   *    - ldap dn of user (array)
   *    - drupal username of user (string)
   *  @param enum $return = 'group_dns'
   *  @param boolean $nested if groups should be recursed or not.
   *
   *  @return array of groups dns in mixed case or FALSE on error
   */

  public function groupMembershipsFromUser($user, $return = 'group_dns', $nested = NULL) {
    
    $user_ldap_entry = @$this->userUserToExistingLdapEntry($user);
   // debug('groupMembershipsFromUser: user_ldap_entry'); debug($user_ldap_entry); debug($this->groupFunctionalityUnused);
    if (!$user_ldap_entry || $this->groupFunctionalityUnused) {
      return FALSE;
    }
    $nested = ($nested === TRUE || $nested === FALSE) ? $nested : $this->groupNested;
    
    
    if ($this->groupUserMembershipsConfigured) {
      $group_dns = $this->groupUserMembershipsFromUserAttr($user_ldap_entry, $nested);
    }
    elseif ($this->groupUserMembershipsConfigured) {
      $group_dns = $this->groupUserMembershipsFromEntry($user_ldap_entry, $nested);
    }
    
    if ($return == 'group_dns') {
      return $group_dns;
    }

  }
 

  
  
  /**
   * is a user's dn a member of group
   *
   * @param string $group_dn MIXED CASE
   * @param mixed $user
   *    - drupal user object (stdClass Object)
   *    - ldap entry of user (array)
   *    - ldap dn of user (array)
   *    - drupal user name
   * @param enum $nested = NULL (default to server configuration), TRUE, or FALSE indicating to test for nested groups
   */
  public function groupIsMember($group_dn, $user, $nested = NULL) {
    $nested = ($nested === TRUE || $nested === FALSE) ? $nested : $this->groupNested;
    $group_dns = $this->groupMembershipsFromUser($user, 'group_dns', $nested);
    return (is_array($group_dns) && in_array($group_dn, $group_ids));
  }
  

  /**
   * add a group entry
   *
   * @param string $group_dn as ldap dn
   * @param array $attributes in key value form
   * @return boolean success
   */
  public function groupAddGroup($group_dn, $attributes = array()) {
 
    $add = array();
    $attributes = array_change_key_case($attributes, CASE_LOWER);
    
    /**
     * 1. use $ldapServer defaults in any empty attributes
     */
    if (!$objectClass_key) {
      $attributes['objectclass'] = $this->groupObjectClass;
    }
    if ($empty($attributes['objectclass']) && $this->groupObjectClass) {
     
    }

    /**
     * 2. give other modules a chance to add or alter attributes
     */
    $context = array(
      'action' => 'add',
      'corresponding_drupal_data' => array($group_dn => $add),
      'corresponding_drupal_data_type' => 'group',
    );
    $ldap_entries = array($group_dn => $add);
    drupal_alter('ldap_entry_pre_provision', $ldap_entries, $this, $context);
    $proposed_ldap_entry = $ldap_entries[$group_dn];

     /**
     * 3. @todo check group schema against set attributes to log
     *    provisioning errors proactively.
     */
     
     
     /**
     * 4. provision ldap entry
     *   @todo how is error handling done here?
     */  
    $ldap_entry_created = $this->createLdapEntry($proposed_ldap_entry, $group_dn);
    

     /**
     * 5. allow other modules to react to provisioned ldap entry
     *   @todo how is error handling done here?
     */    
    if ($ldap_entry_created) {
      module_invoke_all('ldap_entry_post_provision', $ldap_entries, $this, $context);
      return TRUE;
    }
    else { 
      return false; 
    }

  }
  
  /**
   * remove a group entry
   *
   * @param string $group_dn as ldap dn
   * @param boolean $only_if_group_empty indicating group should not be removed if not empty
   */
  public function groupRemoveGroup($group_dn, $only_if_group_empty = TRUE) {
    
    if (!$only_if_group_empty || count($this->groupAllMembers($group_dn, FALSE)) == 0) {
      $result = $this->delete($group_dn);
    }
    else {
      return FALSE;
    }
  }

  /**
   * add a member to a group
   *
   * @param string $group_dn as ldap dn
   * @param string $ldap_user_dn as ldap dn
   */
  public function groupAddMember($group_dn, $ldap_user_dn) {
     //@todo finish
    // See if the group exists before trying this.
    $result = FALSE;
    if ($this->groupGroupEntryMembershipsConfigured) {
      $add = array();
      $add[$this->groupMembershipsAttr] = $ldap_user_dn;
      $this->connectAndBindIfNotAlready();
      $result = @ldap_mod_add($this->connection, $group_dn, $add);
    }
    return $result;
  }
    
  /**
   * remove a member from a group
   *
   * @param string $group_dn as ldap dn
   * @param string $ldap_user_dn as ldap dn
   */
  public function groupRemoveMember($group_dn, $ldap_user_dn) {
    //@todo finish
    // See if the group exists before trying this.
    $result = FALSE;
    if ($this->groupGroupEntryMembershipsConfigured) {
      $del = array();
      $del[$this->groupMembershipsAttr] = $ldap_user_dn;
      $this->connectAndBindIfNotAlready();
      $result = @ldap_mod_del($this->connection, $group_dn, $del);
    }
    return $result;
  }
 
 
  /**
   * get all members of a group 
   *
   * @param string $group_dn as ldap dn
   * @param array $object_classes as array of object classes to include
   * @param array of dns of all group members.  may be users or other groups
   *
   * @return FALSE on error otherwise array of group members (could be users or groups)
   */  
  public function groupAllMembers($group_dn, $object_classes = NULL, $nested = NULL) {
    
    $group_entry = $this->dnExists($group_dn, 'ldap_entry');
    if (!$group_entry) {
      return FALSE;
    }
    $current_group_entries = array($group_entry);
    $all_group_dns = array();
    $test_groups_ids = array();
    $nested = ($nested === TRUE || $nested === FALSE) ? $nested : $this->groupNested;
    $max_levels = ($nested) ? 10 : 1;
    $this->groupMembersResursive($current_group_entries, $all_group_dns, $tested_group_ids, 0, $max_levels, $object_classes);
    
    return $all_group_dns;
    
  }

/**
   * recurse through all child groups and add members. 
   *
   * @param array $current_group_entries of ldap group entries that are starting point.  should include at least 1 entry.
   * @param array $all_group_dns as array of all groups user is a member of.  MIXED CASE VALUES
   * @param array $tested_group_ids as array of tested group dn, cn, uid, etc.  MIXED CASE VALUES
   *   whether these value are dn, cn, uid, etc depends on what attribute members, uniquemember, memberUid contains
   *   whatever attribute is in $this->$tested_group_ids to avoid redundant recursing
   * @param int $level of recursion
   * @param int $max_levels as max recursion allowed
   *
   */
  
  public function groupMembersResursive($current_entries, &$all_member_dns, &$tested_group_ids, $level, $max_levels, $object_classes = FALSE) {
    
    if (!$this->groupGroupEntryMembershipsConfigured || !is_array($current_entries) || count($current_entries) == 0) {
      return FALSE;
    }
    if (isset($current_entries['count'])) {
      unset($current_entries['count']);
    };
    
    $current_entries = array();
    foreach ($current_entries as $i => $entry) {
      
      if (
          (!$object_classes || in_array($entry['objectclass'][0], $object_classes))
           && !in_array($entry['dn'], $all_member_dns)
        ) { // add member
        $all_member_dns[] = $entry['dn'];
      }
      if ($entry['objectclass'][0] == $this->groupObjectClass && $max_levels > $level) {
        if ($this->groupMembershipsAttrMatchingUserAttr == 'dn') {
          $group_id = $group_entry['dn'];
        }
        else {
          $group_id = $group_entry[$this->groupMembershipsAttrMatchingUserAttr][0];
        }
        if (!in_array($group_id, $tested_group_ids)) {
          $tested_group_ids[] = $group_id;
          $member_ids = $group_entry[$this->groupMembershipsAttr];
          if (isset($member_ids['count'])) {
            unset($member_ids['count']);
          };
          $ors = array();
          foreach ($member_ids as $i => $member_id) {
            $ors[] =  $this->groupMembershipsAttr . '=' . $member_id;
          }
          if (count($ors)) {
            $or = '(|(' . join(")\n(", $ors) . '))';  // e.g. (|(cn=group1)(cn=group2)) or   (|(dn=cn=group1,ou=blah...)(dn=cn=group2,ou=blah...))
            $query_for_child_groups = '&(objectClass=' . $this->groupObjectClass . ')' . $or . ')';
            foreach ($this->basedn as $base_dn) {  // need to search on all basedns one at a time
              $member_entries = $this->search($base_dn, $query_for_child_groups, array($this->groupMembershipsAttr));
              if ($member_entries !== FALSE) {
                $this->groupMembersResursive($member_entries, $all_member_dns, $tested_group_ids, $level + 1, $max_levels, $object_classes);
              }
            }
          }
        }
      }
    }
  }
  
  /**
   *  @param mixed
   *    - drupal user object (stdClass Object)
   *    - ldap entry of user (array)
   *    - ldap dn of user (array)
   *    - drupal username of user (string)
   *  @param boolean $nested if groups should be recursed or not.
   *
   *  @return array of group dns
   */

  public function groupUserMembershipsFromUserAttr($user, $nested = NULL) {
    
    if (!$this->groupUserMembershipsConfigured) {
      return FALSE;
    }
    $nested = ($nested === TRUE || $nested === FALSE) ? $nested : $this->groupNested;
    $user_ldap_entry = $this->userUserToExistingLdapEntry($user);
    if (!isset($user_ldap_entry[$this->groupUserMembershipsAttr])) {
      return FALSE; // user's membership attribute is not present.  either misconfigured or query failed
    }
    
    $all_group_dns = array();
    $tested_group_ids = array();
    $level = 0;
    $member_group_dns = $user_ldap_entry[$this->groupUserMembershipsAttr];
    if (isset($member_group_dns['count'])) {
      unset($member_group_dns['count']);
    };
    $ors = array();
    foreach ($member_group_dns as $i => $member_group_dn) {
      $all_group_dns[] = $member_group_dn;
      if ($nested) {
        $ors[] =  $this->groupMembershipsAttr .'=' . ldap_servers_get_first_rdn_value_from_dn($member_group_dn, $this->groupMembershipsAttrMatchingUserAttr);
      }
    }
    if ($nested) {
    //  $current_group_entries = get all current entries, not just dns so recursive funcation can be called
      $this->groupMembershipsResursive($current_group_entries, $all_group_dns, $tested_group_ids, $level + 1, 10); 
    }

    return $all_group_dns;
  }

  /**
   *  @param mixed
   *    - drupal user object (stdClass Object)
   *    - ldap entry of user (array)
   *    - ldap dn of user (array)
   *    - drupal username of user (string)
   *  @param boolean $nested if groups should be recursed or not.
   *
   *  @return array of group dns MIXED CASE VALUES
   *
   *  @see tests/DeriveFromEntry/ldap_servers.inc for fuller notes and test example
   */
  public function groupUserMembershipsFromEntry($user, $nested = FALSE) {

    if (!$this->groupGroupEntryMembershipsConfigured) {
      return FALSE;
    }
    $nested = ($nested === TRUE || $nested === FALSE) ? $nested : $this->groupNested;
    $user_ldap_entry = $this->userUserToExistingLdapEntry($user);
    
    $all_group_dns = array(); // MIXED CASE VALUES
    $tested_group_ids = array(); // array of dns already tested to avoid excess queries MIXED CASE VALUES
    $level = 0;
    
    if ($this->groupMembershipsAttrMatchingUserAttr == 'dn') {
      $member_value = $user_ldap_entry['dn'];
    }
    else {
      $member_value = $user_ldap_entry[$this->groupMembershipsAttrMatchingUserAttr][0];
    }

    $group_query = '(&(objectClass=' . $this->groupObjectClass . ')(' . $this->groupMembershipsAttr ."=$member_value))";
    foreach ($this->basedn as $base_dn) {  // need to search on all basedns one at a time
      $group_entries = $this->search($base_dn, $group_query, array()); // only need dn, so empty array forces return of no attributes 
      if ($group_entries !== FALSE) {
        $max_levels = ($nested) ? 10 : 1;
        $this->groupMembershipsResursive($group_entries, $all_group_dns, $tested_group_ids, $level, $max_levels);
      }
    }

    return $all_group_dns;
  }
  
  /**
   * recurse through all groups.  this model is applicable to all groups
   *
   * @param array $current_group_entries of ldap group entries that are starting point.  should include at least 1 entry.
   * @param array $all_group_dns as array of all groups user is a member of.  MIXED CASE VALUES
   * @param array $tested_group_ids as array of tested group dn, cn, uid, etc.  MIXED CASE VALUES
   *   whether these value are dn, cn, uid, etc depends on what attribute members, uniquemember, memberUid contains
   *   whatever attribute is in $this->$tested_group_ids to avoid redundant recursing
   * @param int $level of recursion
   * @param int $max_levels as max recursion allowed
   *
   * given set of groups entries ($current_group_entries such as it, hr, accounting),
   * find parent groups (such as staff, people, users) and add them to list of group memberships ($all_group_dns)
   *
   * (&(objectClass=[$this->groupObjectClass])(|([$this->groupMembershipsAttr]=groupid1)([$this->groupMembershipsAttr]=groupid2))
   *
   * @return FALSE for error or misconfiguration, otherwise TRUE.  results are passed by reference.
   */
  
  public function groupMembershipsResursive($current_group_entries, &$all_group_dns, &$tested_group_ids, $level, $max_levels) {
    
    if (!$this->groupGroupEntryMembershipsConfigured || !is_array($current_group_entries) || count($current_group_entries) == 0) {
      return FALSE;
    }
    if (isset($current_group_entries['count'])) {
      unset($current_group_entries['count']);
    };
    $ors = array();
    foreach ($current_group_entries as $i => $group_entry) {
      if ($this->groupMembershipsAttr == 'dn') {
        $member_id = $group_entry['dn'];
      }
      else {// maybe cn, uid, etc is held
        $member_id = ldap_servers_get_first_rdn_value_from_dn($group_entry['dn'], $this->groupMembershipsAttrMatchingUserAttr);
      }
      if ($member_id && !in_array($member_id, $tested_group_ids)) {
        $tested_group_ids[] = $member_id;
        $all_group_dns[] = $group_entry['dn'];
        // add $group_id (dn, cn, uid) to query
        $ors[] =  $this->groupMembershipsAttr .'=' . ldap_servers_get_first_rdn_value_from_dn($group_entry['dn'], $group_entry['dn']);
      }
    }
    if (count($ors)) {
      $or = '(|(' . join(")\n(", $ors) . '))';  // e.g. (|(cn=group1)(cn=group2)) or   (|(dn=cn=group1,ou=blah...)(dn=cn=group2,ou=blah...))
      $query_for_parent_groups = '&(objectClass=' . $this->groupObjectClass . ')' . $or . ')';
      foreach ($this->basedn as $base_dn) {  // need to search on all basedns one at a time
        $group_entries = $this->search($base_dn, $query_for_parent_groups, array());  // no attributes, just dns needed
        if ($group_entries !== FALSE  && $max_levels > $level) {
          $this->groupMembershipsResursive($group_entries, $all_group_dns, $tested_group_ids, $level + 1, $max_levels);
        }
      }
    }
  }
  


  /**
   * Error methods and properties.
   */

  public $detailedWatchdogLog = FALSE;
  protected $_errorMsg = NULL;
  protected $_hasError = FALSE;
  protected $_errorName = NULL;

  public function setError($_errorName, $_errorMsgText = NULL) {
    $this->_errorMsgText = $_errorMsgText;
    $this->_errorName = $_errorName;
    $this->_hasError = TRUE;
  }

  public function clearError() {
    $this->_hasError = FALSE;
    $this->_errorMsg = NULL;
    $this->_errorName = NULL;
  }

  public function hasError() {
    return ($this->_hasError || $this->ldapErrorNumber());
  }

  public function errorMsg($type = NULL) {
    if ($type == 'ldap' && $this->connection) {
      return ldap_err2str(ldap_errno($this->connection));
    }
    elseif ($type == NULL) {
      return $this->_errorMsg;
    }
    else {
      return NULL;
    }
  }

  public function errorName($type = NULL) {
    if ($type == 'ldap' && $this->connection) {
      return "LDAP Error: " . ldap_error($this->connection);
    }
    elseif ($type == NULL) {
      return $this->_errorName;
    }
    else {
      return NULL;
    }
  }

  public function ldapErrorNumber() {
    if ($this->connection && ldap_errno($this->connection)) {
      return ldap_errno($this->connection);
    }
    else {
      return FALSE;
    }
  }

}
