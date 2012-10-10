<?php
// $Id: ldap_servers.test_form.inc,v 1.5.2.1 2011/02/08 06:01:00 johnbarclay Exp $

module_load_include('inc', 'ldap_servers', 'ldap_servers.functions');

/**
 * @file
 */

/**
 * Implements the LDAP server test page.
 *
 * @param $form_state
 *   A form state array.
 * @param $op
 *   An operatin - add or edit.
 * @param $sid
 *   A LDAP server ID.
 *
 * @return
 *   The form structure.
 */

function ldap_servers_test_form($form, &$form_state, $op = NULL, $sid = NULL) {

  $ldap_server = ldap_servers_get_servers($sid, 'all', TRUE);

  $form['#prefix'] = t('<h1>Test %name LDAP Server Configuration</h1>', array('%name' => $ldap_server->name));

  $form['#prefix'] .= t('This form simply tests an LDAP configuration to see if
    it can bind and if the sample username is mapped correctly.  It does not
    alter the settings.');

  $variables = array(
    'ldap_server' => $ldap_server, 'actions' => FALSE, 'type' => 'detail'
  );

  $form['#prefix'] .= theme('ldap_servers_server', $variables);

  $form['sid'] = array(
      '#type' => 'hidden',
      '#default_value' => $sid,
    );

  $form['binding']['bindpw'] = array(
    '#type' => 'password',
    '#title' => t('Password for non-anonymous search'),
    '#size' => 20,
    '#maxlength' => 255
  );

  if ( $form['binding']['bindpw']) {
    $form['binding']['bindpw']['#description'] = t('<p>Leave empty to test with currently stored password.</p>');
  }

  $form['testing_drupal_username'] = array(
    '#type' => 'textfield',
    '#title' => t('Testing Drupal Username'),
    '#default_value' => $ldap_server->testingDrupalUsername,
    '#size' => 30,
    '#maxlength' => 255,
    '#description' => t('This is optional and used for testing this server\'s configuration against an actual username.  The user need not exist in Drupal and testing will not affect the user\'s LDAP or Drupal Account.'),
  );

  $form['groupTestGroupDn'] = array(
    '#type' => 'textfield',
    '#title' => t('Testing Group DN'),
    '#default_value' => $ldap_server->groupTestGroupDn,
    '#size' => 80,
    '#maxlength' => 255,
    '#description' => t('This is optional and used for testing this server\'s group configuration.'),
  );
  
  if ($ldap_server->bind_method ==  LDAP_SERVERS_BIND_METHOD_ANON_USER) {
    $form['testing_drupal_userpw'] = array(
      '#type' => 'password',
      '#title' => t('Testing Drupal User Password'),
      '#size' => 30,
      '#maxlength' => 255,
      '#description' => t('This is optional and used for testing this server\'s configuration against the username above.'),
    );
  }

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'test',
    '#weight' => 100,
  );

  if (isset($_SESSION['ldap_server_test_data']) && count($_SESSION['ldap_server_test_data']) > 0) {
    $form['#suffix'] = theme('ldap_server_ldap_entry_table',
      array(
        'entry' => $_SESSION['ldap_server_test_data']['LDAP Entry']['attr'],
        'username' => $_SESSION['ldap_server_test_data']['username'],
        'dn' => $_SESSION['ldap_server_test_data']['LDAP Entry']['dn'],
      ));
    
    $form['#suffix'] .= '<h2>Group Test (dn: ' . $_SESSION['ldap_server_test_data']['Group DN'] . ')</h2>' . theme('table', array('header' => array('Test', 'Result'), 'rows' => $_SESSION['ldap_server_test_data']['Group Results']));
    if (function_exists('dpm')) {
      $user_name =  $_SESSION['ldap_server_test_data']['username'];
      $user = user_load_by_name($user_name);

      if (is_object($user)) {
        dpm("Corresponding Drupal user object for: $user_name");
        dpm($user);
        $user_entity = entity_load_single('user', $user->uid);
        dpm("drupal user entity for: $user_name");
        dpm($user_entity);
      }
      else {
        dpm('No corresponding Drupal user object found');
      }
    }

    unset($_SESSION['ldap_server_test_data']);
  }
  return $form;
}

/**
 * Validate hook for the LDAP server form.
 */
function ldap_servers_test_form_validate($form, &$form_state) {
  $values = $form_state['values'];
  if (!$values['sid']) {
    form_set_error(NULL, t('No server id found in form'));
  }
  elseif (!$ldap_server = ldap_servers_get_servers($values['sid'], 'all', TRUE)) {
    form_set_error(NULL, t('Failed to create server object for server with server id=%sid', array('%sid' => $values['sid'])));
  }
}

/**
 * Submit hook for the LDAP server form.
 */
function ldap_servers_test_form_submit($form, &$form_state) {

  $errors = FALSE;
  $values = $form_state['values'];
  $sid = $values['sid'];
  $ldap_server = ldap_servers_get_servers($sid, 'all', TRUE);
  $fields = ldap_servers_fields();
  //$result = t('<h1>Test of name </h2>',$server_conf);
  $results = array();
  if ($values['bindpw']) {
    $bindpw = $values['bindpw'];
    $bindpw_type = t('entered in form.');
  }
  else {
    $bindpw = NULL;
    $bindpw_type = t('stored in configuration');
  }

  $results[] = t('Binding with DN for non-anonymous search (%bind_dn).  Using password ',
    array('%bind_dn' =>  $ldap_server->binddn)) . ' ' . $bindpw_type;

  if ($ldap_server->bind_method ==  LDAP_SERVERS_BIND_METHOD_SERVICE_ACCT) {
    $results[] = t('Binding with DN for non-anonymous search (%bind_dn).  Using password ',
      array('%bind_dn' =>  $ldap_server->binddn)) . ' ' . $bindpw_type;
  }
  else {
    $results[] = t('Binding with null DN for anonymous search.');
  }

  // connect to ldap
  // $ldap_server = NULL;
  list($has_errors, $more_results) = ldap_servers_test_binding_credentials($ldap_server, $bindpw);
  $results = array_merge($results, $more_results);
  if ($ldap_server->bind_method == LDAP_SERVERS_BIND_METHOD_ANON_USER) {
    list($has_errors, $more_results, $ldap_user) = ldap_servers_test_user_mapping($values['testing_drupal_username'], $ldap_server);
    $results = array_merge($results, $more_results);
    if (!$has_errors) {
      $mapping[] = "dn = " . $ldap_user['dn'];
      foreach ($ldap_user['attr'] as $key => $value) {
        if (is_array($value)) {
          $mapping[] = "$key = " . $value[0];
        }
      }
      $results[] =  theme_item_list(array('items' => $mapping, 'title' => t('Attributes available to anonymous search', array('%bind_dn' =>  $ldap_server->binddn)) , 'type' => 'ul', 'attributes' => array()));
    }
    $results[] = t('Binding with DN (%bind_dn).  Using supplied password ',
      array('%bind_dn' =>  $ldap_user['dn']));
    $result = $ldap_server->bind($ldap_user['dn'], $values['testing_drupal_userpw']);
    if ($result == LDAP_SUCCESS) {
      $results[] = t('Successfully bound to server');
    }
    else {
      $results[] = t('Failed to bind to server. ldap error #') . $result . ' ' . $ldap_server->errorMsg('ldap') ;
    }
  }

  if (!$has_errors && @$values['testing_drupal_username']) {
    ldap_servers_module_load_include('inc', 'ldap_servers', 'ldap_servers.functions');
    list($has_errors, $more_results, $ldap_user) = ldap_servers_test_user_mapping($values['testing_drupal_username'], $ldap_server);
  //  dpm('ldap_user'); dpm($ldap_user);
    $results = array_merge($results, $more_results);
    $tokens = ($ldap_user && isset($ldap_user['attr'])) ? ldap_servers_token_tokenize_entry($ldap_user['attr'], 'all') : array();
    $_SESSION['ldap_server_test_data'] = array(
      'username' => $values['testing_drupal_username'],
      'Tokens Available' => $tokens,
      'LDAP Entry' => $ldap_user,
    );
  }
  
  $group_results = array();
  if (!$has_errors && @$values['groupTestGroupDn']) {
    $group_dn = $values['groupTestGroupDn'];
    $user = @$values['testing_drupal_username'];
    
    foreach (array(FALSE, TRUE) as $nested) { //FALSE
      $nested_display = ($nested) ? 'Yes' : 'No';
      if ($user) {
        // this is the parent function that will call FromUserAttr or FromEntry
        $memberships = $ldap_server->groupMembershipsFromUser($user, 'group_dns', $nested);
        $result = theme('item_list', array('items' => $memberships, 'type' => 'ul'));
        $group_results[] = array("ldap_server->groupMembershipsFromUser($user, 'group_dns', nested=$nested_display)<br/>count=" . count($memberships), $result);
        
        $result = ($ldap_server->groupIsMember($group_dn, $user, $nested)) ? 'Yes' : 'No';
        $group_results[] = array("ldap_server->groupIsMember($group_dn, $user, nested=$nested_display)", $result);
        
        if ($ldap_server->groupUserMembershipsConfigured) {
          $groupUserMembershipsFromUserAttr = $ldap_server->groupUserMembershipsFromUserAttr($user, $nested);
          $count =  count($groupUserMembershipsFromUserAttr);
          $result = theme('item_list', array('items' => $groupUserMembershipsFromUserAttr, 'type' => 'ul'));
        }
        else {
          $groupUserMembershipsFromUserAttr = array();
          $result = "'A user LDAP attribute such as memberOf exists that contains a list of their group' is not configured.";
        }
        $group_results[] = array("ldap_server->groupUserMembershipsFromUserAttr($user, nested=$nested_display)<br/> count=" .
            count($groupUserMembershipsFromUserAttr) , $result);
        

        if ($ldap_server->groupGroupEntryMembershipsConfigured) {
          $groupUserMembershipsFromEntry = $ldap_server->groupUserMembershipsFromEntry($user, $nested);
          $result = theme('item_list', array('items' => $groupUserMembershipsFromEntry, 'type' => 'ul'));
        }
        else {
          $groupUserMembershipsFromEntry = array();
          $result = "Groups by entry not configured.";
        }
        $group_results[] = array("ldap_server->groupUserMembershipsFromEntry($user, nested=$nested_display)<br/>count=" .
                                 count($groupUserMembershipsFromEntry) , $result);
        
        if (count($groupUserMembershipsFromEntry) && count($groupUserMembershipsFromUserAttr)) {
          $diff1 = array_diff($groupUserMembershipsFromUserAttr, $groupUserMembershipsFromEntry);
          $diff2 = array_diff($groupUserMembershipsFromEntry, $groupUserMembershipsFromUserAttr);
       //   dpm('diff'); dpm($diff);
          $result1 = theme('item_list', array('items' => $diff1, 'type' => 'ul'));
          $result2 = theme('item_list', array('items' => $diff2, 'type' => 'ul'));
          $group_results[] = array("groupUserMembershipsFromEntry and FromUserAttr Diff)", $result1);
          $group_results[] = array("FromUserAttr and groupUserMembershipsFromEntry Diff)", $result2);
        }

                 // 
         // dpm("diff"); dpm($diff);
      }

      // this is the opposite of memberships, it drills down to child groups
      // it is not implemented
     // $members = $ldap_server->groupAllMembers($group_dn, NULL, $nested);
     // $result = theme('item_list', array('items' => $members, 'type' => 'ul'));
     // $group_results[] = array("ldap_server->groupAllMembers($group_dn, NULL, $nested_display)<br/>count=". count($members), $result);


     //$ldap_server->groupAddGroup($group_dn);
     // $ldap_server->groupRemoveGroup($group_dn, TRUE);
        // // $ldap_server->groupAddMember($group_dn, $ldap_user_dn);
      //  //$ldap_server->groupRemoveMember($group_dn, $ldap_user_dn);   
      
    }
  // dpm('results'); dpm($group_results);

      
  //  dpm('ldap_user'); dpm($ldap_user);
    $results = array_merge($results, $more_results);
    $tokens = ($ldap_user && isset($ldap_user['attr'])) ? ldap_servers_token_tokenize_entry($ldap_user['attr'], 'all') : array();
    $_SESSION['ldap_server_test_data'] = array(
      'username' => $values['testing_drupal_username'],
      'Tokens Available' => $tokens,
      'LDAP Entry' => $ldap_user,
      'Group Results' => $group_results,
      'Group DN' => @$values['groupTestGroupDn'],
    );
  }
  $message = theme_item_list(array('items' => $results, 'title' => t('Result Messages (some results may not show if devel module is not enabled)'), 'type' => 'ul', 'attributes' => array()));

  drupal_set_message(t($message), ($has_errors) ? 'error' : 'status');
  $form_state['redirect'] = LDAP_SERVERS_MENU_BASE_PATH . "/servers/test/$sid";

}

/**
 * Submit hook for the LDAP server form.
 */


function ldap_servers_test_binding_credentials(&$ldap_server, $bindpw) {

  $errors = FALSE;
  $results = array();

  $ldap_result = $ldap_server->connect();
  if ($ldap_result != LDAP_SUCCESS) {
    $results[] = t('Failed to connect to LDAP server.  See watchdog error logs for details.') . $ldap_server->errorMsg('ldap');
    $errors = TRUE;
  }

  if (!$errors) {
    $bind_result = $ldap_server->bind($ldap_server->binddn, $bindpw);
    if ($bind_result == LDAP_SUCCESS) {
      $results[] =  t('Successfully bound to server');
    }
    else {
      $results[] = t('Failed to bind to server. ldap error #') . $bind_result . ' ' . $ldap_server->errorMsg('ldap') ;
      $errors = TRUE;
    }
  }

  return array($errors, $results);
}

function ldap_servers_test_user_mapping($drupal_username, &$ldap_server, $direction = LDAP_USER_PROV_DIRECTION_ALL, $ldap_context = NULL) {
  //dpm("this needs to be reworked");
  $ldap_user = $ldap_server->userUserNameToExistingLdapEntry($drupal_username, $ldap_context);
 // dpm("ldap_user"); dpm($ldap_user);
  $errors = FALSE;
  if (! $ldap_user ) {
    $results[] = t('Failed to find test user %username by searching on  %user_attr = %username.',
      array(
        '%username' => $drupal_username,
        '%user_attr' => $ldap_server->user_attr )
      )
    . ' ' . t('Error Message: ') . $ldap_server->errorMsg('ldap');
    $errors = TRUE;
  }
  else {
    $results[] = t('Found test user %username by searching on  %user_attr = %username.',
      array('%username' => $drupal_username, '%user_attr' => $ldap_server->user_attr ));
  }
  return array($errors, $results, $ldap_user);
}
