<?php

/**
 * @file
 * collection of functions related to ldap tokens
 */

//define('LDAP_SERVERS_TOKEN_PRE', '[');
//define('LDAP_SERVERS_TOKEN_POST', ']');
//define('LDAP_SERVERS_TOKEN_DEL', ':');
//define('LDAP_SERVERS_TOKEN_MODIFIER_DEL', ';');

function ldap_servers_token_is_token($text) {
  return  (strpos($text, LDAP_SERVERS_TOKEN_PRE) === 0 && strpos($text, LDAP_SERVERS_TOKEN_POST) === strlen($text) - 1);
}
/**
 * @param array $ldap_entry
 * @param string $text
 * @return string text with tokens replaced
 */

function ldap_servers_token_replace($ldap_entry, $text) {
  $desired_tokens = ldap_servers_token_tokens_needed_for_template($text);
  $tokens = ldap_servers_token_tokenize_entry($ldap_entry, $desired_tokens, LDAP_SERVERS_TOKEN_PRE, LDAP_SERVERS_TOKEN_POST);
  foreach ($tokens as $key => $value) {
    $tokens[drupal_strtolower($key)] = $value;
  }
 // debug("ldap_servers_token_replace,$text"); debug($ldap_entry); debug($tokens);
  $result = str_replace(array_keys($tokens), array_values($tokens), $text);
  return $result;
}

/**
 * @param array $attributes array of attributes passed by reference
 * @param string $text with tokens in it
 *
 * by reference return add ldap attribute names to $attributes
 */
function ldap_servers_token_extract_attributes(&$attributes,  $text) {
  $tokens = ldap_servers_token_tokens_needed_for_template($text);
  foreach ($tokens as $token) {
    $token = str_replace(array(LDAP_SERVERS_TOKEN_PRE, LDAP_SERVERS_TOKEN_POST), array('', ''), $token);
    $parts = explode(LDAP_SERVERS_TOKEN_DEL, $token);
    $attributes[] = $parts[0];
  }
}

/**
 * @param string $token with singular token in it, eg. [dn], [dn;binary], [titles:0]
 *
 * @return token converted to dn, titles, etc.
 */
function ldap_servers_token_extract_attribute_name($token) {
  $attr_name = trim($token, LDAP_SERVERS_TOKEN_PRE . LDAP_SERVERS_TOKEN_POST); // remove token brackets

  $parts = explode(LDAP_SERVERS_TOKEN_MODIFIER_DEL, $attr_name); //remove modifiers
  $attr_name = $parts[0];

  $parts = explode(LDAP_SERVERS_TOKEN_DEL, $attr_name); // remove ordinal
  $attr_name = $parts[0];

  return $attr_name;
}




/**
 * Turn an ldap entry into a token array suitable for the t() function
 * @param ldap entry array $ldap_entry
 * @param string prefix token prefix such as !,%,[
 * @param string suffix token suffix such as ]
 * @param $token_keys either an array of key names such as array('cn', 'dn') or string 'all' to return all tokens.
 * @return token array suitable for t() functions of with lowercase keys as exemplified below


an ldap entry such as:

   'dn' => 'cn=jdoe,ou=campus accounts,ou=toledo campus,dc=ad,dc=myuniversity,dc=edu',
    'mail' => array( 0 => 'jdoe@myuniversity.edu', 'count' => 1),
    'sAMAccountName' => array( 0 => 'jdoe', 'count' => 1),

should return tokens such as:

    -- from dn attribute
    [cn] = jdoe
    [cn:0] = jdoe
    [cn:last] => jdoe
    [ou] = campus accounts
    [ou:0] = campus accounts
    [ou:1] = toledo campus
    [ou:last] = toledo campus
    [dc] = ad
    [dc:0] = ad
    [dc:1] = myuniversity
    [dc:2] = edu
    [dc:last] = edu

    -- from other attributes
    [mail] = jdoe@myuniversity.edu
    [mail:0] = jdoe@myuniversity.edu
    [mail:last] = jdoe@myuniversity.edu
    [samaccountname] = jdoe
    [samaccountname:0] = jdoe
    [samaccountname:last] = jdoe

 */
function ldap_servers_token_tokenize_entry($ldap_entry, $token_keys = 'all', $pre = LDAP_SERVERS_TOKEN_PRE, $post = LDAP_SERVERS_TOKEN_POST) {
//  debug('ldap_servers_token_tokenize_entry,ldap_entry'); debug($ldap_entry);
  $detailed_watchdog_log = variable_get('ldap_help_watchdog_detail', 0);
  $tokens = array();
  
  // add lowercase keyed entries to ldap array
  foreach ($ldap_entry as $key => $values) {
    $ldap_entry[drupal_strtolower($key)] = $values;
  }

  // 1. tokenize dn
  $dn_parts = ldap_explode_dn($ldap_entry['dn'], 0); // escapes attribute values, need to be unescaped later.
  unset($dn_parts['count']);
  $parts_count = array();
  $parts_last_value = array();
  foreach ($dn_parts as $pair) {
    list($attr_name, $attr_value) = explode('=', $pair);
    $attr_value = ldap_pear_unescape_dn_value($attr_value);
    try {
      $attr_value = check_plain($attr_value);
    }
    catch (Exception $e) {
      if ($detailed_watchdog_log) {
        $watchdog_tokens = array('%attr_name' => $attr_name);
        watchdog('ldap_servers', 'skipped tokenization of attribute %attr_name because the value would not pass check_plain function.', $watchdog_tokens, WATCHDOG_DEBUG);
      }
       continue; // don't tokenize data that can't pass check_plain
    }
    if (!isset($parts_count[$attr_name])) {
      $tokens[$pre . ldap_server_massage_text($attr_name, 'attr_name', LDAP_SERVER_MASSAGE_TOKEN_REPLACE) . $post] = $attr_value;
      $parts_count[$attr_name] = 0;
    }
    $tokens[$pre . ldap_server_massage_text($attr_name, 'attr_name', LDAP_SERVER_MASSAGE_TOKEN_REPLACE) . LDAP_SERVERS_TOKEN_DEL . (int)$parts_count[$attr_name] . $post] = $attr_value;

    $parts_last_value[$attr_name] = $attr_value;
    $parts_count[$attr_name]++;
  }
  foreach ($parts_count as $attr_name => $count) {
    $tokens[$pre . ldap_server_massage_text($attr_name, 'attr_name', LDAP_SERVER_MASSAGE_TOKEN_REPLACE) . LDAP_SERVERS_TOKEN_DEL . 'last' . $post] = $parts_last_value[$attr_name];
  }

  // tokenize other attributes
  if ($token_keys == 'all') {
    $token_keys = array_keys($ldap_entry['attr']);
    $token_keys = array_filter($token_keys, "is_string");
    foreach ($token_keys as $attr_name) {
      $attr_value = $ldap_entry['attr'][$attr_name];
      if (is_array($attr_value) && is_scalar($attr_value[0]) && $attr_value['count'] == 1) {
        $tokens[$pre . ldap_server_massage_text($attr_name, 'attr_name', LDAP_SERVER_MASSAGE_TOKEN_REPLACE) . $post] = check_plain($attr_value[0]);
        $tokens[$pre . ldap_server_massage_text($attr_name, 'attr_name', LDAP_SERVER_MASSAGE_TOKEN_REPLACE) . LDAP_SERVERS_TOKEN_DEL . '0' . $post] = check_plain($attr_value[0]);
        $tokens[$pre . ldap_server_massage_text($attr_name, 'attr_name', LDAP_SERVER_MASSAGE_TOKEN_REPLACE) . LDAP_SERVERS_TOKEN_DEL . 'last' . $post] = check_plain($attr_value[0]);
      }
      elseif (is_array($attr_value) && $attr_value['count'] > 1) {
        $tokens[$pre . ldap_server_massage_text($attr_name, 'attr_name', LDAP_SERVER_MASSAGE_TOKEN_REPLACE) . LDAP_SERVERS_TOKEN_DEL . 'last' . $post] = check_plain($attr_value[$attr_value['count']-1]);
        for ($i=0; $i<$attr_value['count']; $i++) {
          $tokens[$pre . ldap_server_massage_text($attr_name, 'attr_name', LDAP_SERVER_MASSAGE_TOKEN_REPLACE) . LDAP_SERVERS_TOKEN_DEL . $i . $post] = check_plain($attr_value[$i]);
        }
      }
      elseif (is_scalar($attr_value)) {
        $tokens[$pre . ldap_server_massage_text($attr_name, 'attr_name', LDAP_SERVER_MASSAGE_TOKEN_REPLACE) . $post] = check_plain($attr_value);
      }
    }
  }
  else {

    foreach ($token_keys as $token_key) {
      // $token_key = 'dn', 'mail', 'mail:0', 'mail:last', 'dept:1', etc.
      $parts = explode(LDAP_SERVERS_TOKEN_DEL, $token_key);
      $attr_name = $parts[0];
      $ordinal_key = isset($parts[1]) ? (int)$parts[1] : 0;
      
      if ($attr_name == 'dn' || !isset($ldap_entry['attr'][$attr_name])) { // don't use empty() since a 0, "", etc value may be a desired value
          continue;
      }
      else {
        $count = $ldap_entry['attr'][$attr_name]['count'];
        if ($ordinal_key == 'last') {
          $i = ($count > 0) ? $count - 1 : 0;
          $value = $ldap_entry['attr'][$attr_name][$i];
        }
        elseif (is_numeric($ordinal_key) || $ordinal_key == '0') {
          $value = $ldap_entry['attr'][$attr_name][$ordinal_key];
        }
        else { 
          continue;  // don't add token if case not covered
        }
      }
      $tokens[$pre . $token_key . $post] = check_plain($value);
      if ($token_key != drupal_strtolower($token_key)) {
        $tokens[$pre . drupal_strtolower($token_key) . $post] = check_plain($value);
      }
    }
  }

  // include the dn.  it will not be handled correctly by previous loops
  $tokens[$pre . 'dn' . $post] = check_plain($ldap_entry['dn']);
  return $tokens;
}

/**
 * @param string $template in form [cn]@myuniversity.edu
 * @return array of all tokens in the template such as array('cn')
 */
function ldap_servers_token_tokens_needed_for_template($template, $pre = LDAP_SERVERS_TOKEN_PRE, $post = LDAP_SERVERS_TOKEN_POST) {
  preg_match_all('/
    \[             # [ - pattern start
    ([^\[\]]*)  # match $type not containing whitespace : [ or ]
    \]             # ] - pattern end
    /x', $template, $matches);

  return @$matches[1];

}

function ldap_servers_token_show_sample_user_tokens($sid) {

  $ldap_server = ldap_servers_get_servers($sid, 'all', TRUE);
  $test_username = $ldap_server->testingDrupalUsername;
  if (!$test_username || ! (
     $ldap_server->bind_method == LDAP_SERVERS_BIND_METHOD_SERVICE_ACCT ||
     $ldap_server->bind_method == LDAP_SERVERS_BIND_METHOD_ANON
    )
   ) {
    return FALSE;
  }

  if ($ldap_user = $ldap_server->user_lookup($test_username)) {
    $table = theme('ldap_server_ldap_entry_table', array(
      'entry' => $ldap_user['attr'],
      'username' => $test_username,
      ));
  }
  else {
    $table = '<p>' . t('No sample user data found') . '</p>';
  }

  return $table;
}

