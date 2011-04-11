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
  public $type;
  public $address;
  public $port = 389;
  public $tls = FALSE;
  public $bind_method = 0;
  public $basedn = array();
  public $binddn = FALSE; // Default to an anonymous bind.
  public $bindpw = FALSE; // Default to an anonymous bind.
  public $user_dn_expression;
  public $user_attr;
  public $mail_attr;
  public $ldapToDrupalUserPhp;
  public $testingDrupalUsername;

  public $errorMsg = NULL;
  public $hasError = FALSE;
  public $errorName = NULL;

  public $inDatabase = FALSE;

  public function clearError() {
    $this->hasError = FALSE;
    $this->errorMsg = NULL;
    $this->errorName = NULL;
  }

  protected $connection;
  // direct mapping of db to object properties
  public static function field_to_properties_map() {
    return array( 'sid' => 'sid',
    'name'  => 'name' ,
    'status'  => 'status',
    'type'  => 'type',
    'address'  => 'address',
    'port'  => 'port',
    'tls'  => 'tls',
    'bind_method' => 'bind_method',
    'basedn'  => 'basedn',
    'binddn'  => 'binddn',
    'user_dn_expression' => 'user_dn_expression',
    'user_attr'  => 'user_attr',
    'mail_attr'  => 'mail_attr',
    'ldap_to_drupal_user'  => 'ldapToDrupalUserPhp',
    'testing_drupal_username'  => 'testingDrupalUsername'
    );

  }

  /**
   * Constructor Method
   */
  function __construct($sid) {
    if (!is_scalar($sid)) {
      return;
    }

    $this->sid = $sid;

    $select = db_select('ldap_servers', 'ldap_servers');
    $select->fields('ldap_servers');
    $select->condition('ldap_servers.sid',  $this->sid);


    $server_record = $select->execute()->fetchAllAssoc('sid',  PDO::FETCH_ASSOC);
    if (!isset($server_record[$sid])) {
      $this->inDatabase = FALSE;
      return;
    }
    $server_record = $server_record[$sid];

    if ($server_record) {
      $this->inDatabase = TRUE;
    }
    else {
      // @todo throw error
    }
    foreach ($this->field_to_properties_map() as $db_field_name => $property_name ) {
      if (isset($server_record[$db_field_name])) {
        $this->{$property_name} = $server_record[$db_field_name];
      }
    }
    if (is_scalar($this->basedn)) {
      $this->basedn = unserialize($this->basedn);
    }
    if (isset($server_record['bindpw']) && $server_record['bindpw'] != '') {
      $this->bindpw = $server_record['bindpw'];
      $this->bindpw = ldap_servers_decrypt($this->bindpw);
    }
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

  public function getErrorMsg($type = NULL) {
    if ($type == 'ldap' && $this->connection) {
      return ldap_error($this->connection);
    }
    elseif ($type == NULL) {
      return $this->errorMsg;
    }
    else {
      return NULL;
    }

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
  function bind($userdn = NULL, $pass = NULL) {
    $userdn = ($userdn != NULL) ? $userdn : $this->binddn;
    $pass = ($pass != NULL) ? $pass : $this->bindpw;
    // Ensure that we have an active server connection.
    if (!$this->connection) {
      watchdog('ldap', "LDAP bind failure for user %user. Not connected to LDAP server.", array('%user' => $userdn));
      return LDAP_CONNECT_ERROR;
    }


    if (!@ldap_bind($this->connection, $userdn, $pass)) {
      watchdog('ldap', "LDAP bind failure for user %user. Error %errno: %error", array('%user' => $userdn, '%errno' => ldap_errno($this->connection), '%error' => ldap_error($this->connection)));
      return ldap_errno($this->connection);
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

  /**
   * Preform an LDAP search.
   *
   * @peram string $filter
   *   The search filter.
   * @peram strign $basedn
   *   The search base. If NULL, we use $this->basedn
   * @peram array $attributes
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

    $result = ldap_search($this->connection, $basedn, $filter, $attributes);
   // restore_error_handler();
    if ($result && ldap_count_entries($this->connection, $result)) {
      return ldap_get_entries($this->connection, $result);
    }

    return $result;
  }



  /**
   * Queries LDAP server for the user.
   *
   * @param $drupal_user_name
   *  drupal user name.
   *
   * @return
   *   An array with users LDAP data or NULL if not found.
   */
  function user_lookup($drupal_user_name) {

    foreach ($this->basedn as $basedn) {
      if (empty($basedn)) continue;

      $filter = $this->user_attr . '=' . $drupal_user_name;

      $result = $this->search($filter, $basedn);

      if (!$result) continue;

      // Must find exactly one user for authentication to.
      if ($result['count'] != 1) {
        $count = $result['count'];
        watchdog('ldap_authentication', "Error:  $count users found with $filter under $basedn.", array(), WATCHDOG_ERROR);
        continue;
      }
      $match = $result[0];

      // These lines serve to fix the attribute name in case a
      // naughty server (i.e.: MS Active Directory) is messing the
      // characters' case.
      // This was contributed by Dan "Gribnif" Wilga, and described
      // here: http://drupal.org/node/87833
      $name_attr = $this->user_attr;
      if (!isset($match[$name_attr][0])) {
        $name_attr = drupal_strtolower($name_attr);
        if (!isset($match[$name_attr][0]))
          continue;
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
        if (drupal_strtolower(trim($value)) == drupal_strtolower($drupal_user_name)) {
          $result = array(
            'dn' =>  $match['dn'],
            'mail' => @$match[$this->mail_attr][0],
            'attr' => $match,
          );

          return $result;
        }
      }
    }
  }




}
