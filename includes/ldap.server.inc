<?php
// $Id$

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
 */
class ldap_server {
  // LDAP Settings
  public $sid;
  public $name;
  public $address;
  public $port = 389;
  public $tls = FALSE;
  public $basedn;
  private $binddn = FALSE; // Default to an anonymous bind.
  private $bindpw = FALSE; // Default to an anonymous bind.
  protected $connection;
  protected $properties = array('sid', 'name', 'address', 'port', 'tls', 'basedn', 'binddn', 'bindpw');

  /**
   * Constructor Method
   */
  function __construct($sid = 1) {
    if (!is_numeric($params)) {
      return;      
    }

    $this->sid = $sid;
    $record = db_fetch_object(db_query("SELECT * FROM {ldap_servers} WHERE sid = %d", $this->sid));
    foreach (properties as $property) {
      if (isset($record->$property)) {
        $this->$property = $record->$property;
      }
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

  /**
   * Error Handling Method
   *
   * @param int errno
   *   The level of the error raised.
   *
   * @param string errstr
   *   The error message.
   *
   * @param string errfile
   *   The filename that the error was raised in.
   *
   * @param int errline
   *   The line number the error was raised at.
   *
   * @param array errcontext
   *   An array of every variable that existed in the scope the error was 
   *   triggered in.
   *
   * @return bool
   *   Always return TRUE to avoid PHP's builtin handler.
   */
  function error_handler($errno, $errstr, $errfile, $errline, $errcontext) {
    return TRUE;
  }


  /**
   * Connect Method
   */
  function connect() {
    if (!$con = ldap_connect($this->server_addr, $this->port)) {
      watchdog('user', 'LDAP Connect failure to '. $this->server_addr .':'. $this->port);
      return;
    }

    ldap_set_option($con, LDAP_OPT_REFERRALS, 0);

    // Use TLS if we are configured and able to.
    if ($this->tls) {
      if (!ldap_set_option($con, LDAP_OPT_PROTOCOL_VERSION, 3)) {
        watchdog('user', 'Could not start TLS, only supported by LDAP v3.');
        return;
      }
      elseif (!function_exists('ldap_start_tls')) {
        watchdog('user', 'Could not start TLS. It does not seem to be supported by this PHP setup.');
        return;
      }
      elseif (!ldap_start_tls($con)) {
        watchdog('user', t("Could not start TLS. (Error %errno: %error).", array('%errno' => ldap_errno($con), '%error' => ldap_error($con))));
        return;
      }
    }

  // Store the resulting resource
  $this->connection = $con;
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
      watchdog('ldap', "LDAP bind failure for user %user. Not connected to LDAP server.", array('%user' => $dn));
      return FALSE;
    }

    if (!ldap_bind($this->connection, $userdn, $pass)) {
      watchdog('ldap', "LDAP bind failure for user %user. Error %errno: %error", array('%user' => $user, '$errno' => ldap_errno($this->connection), '%error' => ldap_error($this->connection)));
      return FALSE;
    }

  return TRUE;
  }

  /**
   * Disconnect (unbind) from an active LDAP server.
   */
  function disconnect() {
    if (!$this->connection) {
      watchdog('ldap', 'LDAP disconnect failure from '. $this->server_addr . ':' . $this->port);
    }

  ldap_unbind($this->connection);
  $this->connection = NULL;
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
    $basedn = ($basedn != NULL) ? $basedn : $this->basedn;
    $attributes = (attributes != NULL) ? $attributes : 'dn';
    
    // Change the error handler, ldap_search throws exceptions when results
    // are NULL
    set_error_handler(array('ldap_server', 'error_handler'));
    $result = @ldap_search($this->connection, $basedn, $filter, $attributes);
    restore_error_handler();

    if ($result && ldap_count_entries($this->connection, $result)) {
      return ldap_get_entries($this->connection, $result);
    }
  
    return FALSE;
  }

}

/**
 * LDAP Server Functions
 *
 *   These functions operate on the server class while not quite fitting
 *   within the class.
 */
function ldap_api_get_servers_by_object() {
  $items = array();

  $result = db_query("SELECT sid FROM {ldap_servers} ORDER BY weight ascending");
  while ($server = db_fetch_object($result)) {
    $servers[] = new server($server);
  }

  return $items;
}

// vim:fenc=utf-8:ft=php:ai:si:ts=2:sw=2:et:
