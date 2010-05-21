<?php
// $Id$

/**
 * @file 
 * Attribute Class and Manipulation Functions 
 */

/**
 * LDAP attribute Class
 *
 *  This class is used to create, work with, and eventually destroy ldap_server
 * objects.
 */
class ldap_attribute {
  // LDAP Settings
  private $name;
  private $value;
  protected $min_value_count;
  protected $max_value_count;
  protected $entry;
  protected $internal;
  protected $modified;
  protected $visable;
  protected $readonly;


  /**
   * Constructor Method
   */
  function __construct() {
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
   * Delete Method
   */
  function delete() {
  }


}

/**
 * LDAP attribute Functions
 *
 *   These functions operate on the attribute class while not quite fitting
 *   within the class.
 */

function retrieveAttribute($dn, $attrname) {
  $entries = $this->retrieveAttributes($dn);
  return isset($entries[strtolower($attrname)]) ? $entries[strtolower($attrname)][0] : NULL;
}

// WARNING! WARNING! WARNING!
// This function returns its entries with lowercase attribute names.
// Don't blame me, blame PHP's own ldap_get_entries()
function retrieveAttributes($dn) {
  set_error_handler(array('ldap_server', 'void_error_handler'));
  $result = ldap_read($this->connection, $dn, 'objectClass=*');
  $entries = ldap_get_entries($this->connection, $result);
  restore_error_handler();

  return call_user_func($this->attr_filter, $this->sid, $entries[0]);
}

function retrieveMultiAttribute($dn, $attrname) {
  $entries = $this->retrieveAttributes($dn);

  $result = array();
  $retrieved = $entries[strtolower($attrname)];
  $retrieved = $retrieved ? $retrieved : array();
  foreach ($retrieved as $key => $value) {
    if ($key !== 'count') {
      $result[] = $value;
    }
  }
  return $result;
}

function writeAttributes($dn, $attributes) {
  foreach ($attributes as $key => $cur_val) {
    if ($cur_val == '') {
      unset($attributes[$key]);
      $old_value = $this->retrieveAttribute($dn, $key);
      if (isset($old_value)) {
        ldap_mod_del($this->connection, $dn, array($key => $old_value));
      }
    }
    if (is_array ($cur_val)) {
      foreach ($cur_val as $mv_key => $mv_cur_val) {
        if ($mv_cur_val == '') {
          unset($attributes[$key][$mv_key]);
        }
        else {
          $attributes[$key][$mv_key] = $mv_cur_val;
        }
      }
    }
  }

  ldap_modify($this->connection, $dn, $attributes);
}

// This function is used by other modules to delete attributes once they are
// moved to profiles cause ldap_mod_del does not delete facsimileTelephoneNumber if
// attribute value to delete is passed to the function.
// OpenLDAP as per RFC 2252 doesn't have equality matching for facsimileTelephoneNumber
// http://bugs.php.net/bug.php?id=7168
function deleteAttribute($dn, $attribute) {
  ldap_mod_del($this->connection, $dn, array($attribute => array()));
}


// vim:fenc=utf-8:ft=php:ai:si:ts=2:sw=2:et:
