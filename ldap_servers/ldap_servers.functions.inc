<?php
// $Id: ldap_servers.functions.inc,v 1.3.2.1 2011/02/08 06:01:00 johnbarclay Exp $

/**
 * @file
 * collection of functions that don't belong in server object
 */

/**
  * Modify an LDAP Entry
  */
  function ldap_user_modify($userdn, $attributes, $ldap_server) {
    $status = ldap_modify($ldap_server->connection, $userdn, $attributes);
    if (!$status) {
      watchdog(
        'ldap_servers',
        'Error: user_modify() failed to modify ldap entry w/ base DN "!dn" with values: !values',
        array('!dn' => $userdn, '!value' => var_export($attributes, TRUE)),
        WATCHDOG_ERROR
      );
    }

    return $status;
  }

  /**
   * Modify a password
   */
  function ldap_password_modify($userdn, $new_password, $ldap_server) {

    $new_password = "\"" . $new_password . "\"";
    $len = strlen($new_password);
    $new_pass = NULL;
    for ($i = 0; $i < $len; $i++) {
      $new_pass .= "{$new_password{$i}}\000";
    }

    $status = ldap_mod_replace($ldap_server->connection, $userdn, array('unicodePwd' => $new_pass));
    if (!$status) {
      watchdog(
        'ldap_servers',
        'Error: password_modify() failed to modify ldap password w/ base DN "!dn"',
        array('!dn' => $userdn),
        WATCHDOG_ERROR
      );
    }

    return $status;
  }

function ldap_baddn($dn, $dn_name) {
  $result = array();
  $valid_attr_name = '[a-zA-Z\d\s]';
  $valid_attr_values = '[\-a-zA-Z\d\s]';
  $regex = '/^(' . $valid_attr_name . '*\=' . $valid_attr_values . '*[,]{1})*(' . $valid_attr_name . '*\=' . $valid_attr_values . '*){1}$/';
  $match = (preg_match($regex, $dn)) ? TRUE : FALSE;
  $result['boolean'] = $match;
  if (!$match) {
    $tokens = array('%dn' => htmlspecialchars($dn), '%dn_name' => $dn_name);
    $result['text'] = t('Invalid format for:<br/> <code><strong>%dn</strong><code>.', $tokens);
  }
  return $result;
}

function ldap_badattr($attr, $attr_name) {
  $result = array();
  $valid_attr_name = '[a-zA-Z\d\s]';
  $regex = '/^(' . $valid_attr_name . '){1,}$/';
  $match = (preg_match($regex, $attr)) ? TRUE : FALSE;
  $result['boolean'] = $match;
  if (!$match) {
    $tokens = array('%attr' => htmlspecialchars($attr), '%attr_name' => $attr_name);
    $result['text'] = t('Invalid format for %attr_name:<br/> <code><strong>%attr</strong><code>', $tokens);
  }
  return $result;
}


