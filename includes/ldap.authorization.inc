<?php
// $Id$

/**
 * @file
 * Defines the authorization class and related functions.
 *
 */

/**
 * LDAP authorization Class
 *
 *  This class is used to properly authenticate to an LDAP server.
 */
class ldap_authorization {

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

}

/**
 * LDAP authorization Functions
 *
 *   These functions provide additional authorization support while not quite 
 * fitting within the class.
 */


// vim:fenc=utf-8:ft=php:ai:si:ts=2:sw=2:et:
