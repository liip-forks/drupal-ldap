<?php
// $Id$

/**
 * @file 
 * Defines the entry class and related functions.
 */

/**
 * LDAP Entry Class
 *
 *  This class is used to create, work with, and eventually destroy ldap_server
 * objects.
 */
class ldap_entry {
  // LDAP Settings
  protected $dn;
  protected $sid; // The Server ID to which this entry belongs
  private $leaf;
  private $open;
  private $children;
  private $readonly
  protected $properties
  const properties = array('sid', 'name', 'address', 'port', 'tls', 'base', 'binddn', 'bindpw');

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
  }


  /**
   * Invoke Method
   */
  function __invoke() {
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
   * Add Method
   */
  function add() {
  }

  /**
   * Modify Method
   */
  function modify() {
  }

  /**
   * Rename Method
   */
  function rename() {
  }

  /**
   * Delete Method
   */
  function delete() {
  }

  /**
   * Move Method
   */
  function move() {
  }


}

/**
 * LDAP Entry Functions
 *
 *   These functions operate on the entry class while not quite fitting
 *   within the class.
 */

function create_entry($dn, $attributes) {
  set_error_handler(array('ldap_server', 'void_error_handler'));
  $ret = ldap_add($this->connection, $dn, $attributes);
  restore_error_handler();
    
  return $ret;
} 
    
function rename_entry($dn, $newrdn, $newparent, $deleteoldrdn) {
  set_error_handler(array('ldap_server', 'void_error_handler'));
  $ret = ldap_rename($this->connection, $dn, $newrdn, $newparent, $deleteoldrdn);
  restore_error_handler();
    
  return $ret;
}
    
function delete_entry($dn) {
  set_error_handler(array('ldap_server', 'void_error_handler'));
  $ret = ldap_delete($this->connection, $dn);
  restore_error_handler();

  return $ret;
}


// vim:fenc=utf-8:ft=php:ai:si:ts=2:sw=2:et:
