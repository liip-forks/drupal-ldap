<?php
// $Id: ldap_servers.test_form.inc,v 1.5.2.1 2011/02/08 06:01:00 johnbarclay Exp $


/**
 * @file
 */

/**
 * Implements the LDAP user test page.
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

function ldap_user_test_form($form, &$form_state, $op = NULL) {

  $username = arg(count(explode('/', LDAP_USER_TEST_FORM_PATH)));

  $form['#prefix'] = t('<h1>Test LDAP User Configuration</h1>');

  $form['#prefix'] .= t('This form simply tests an LDAP User configuration against an individual ldap or drupal user.
    It makes no changes to the drupal or ldap user.');

  $form['testing_drupal_username'] = array(
    '#type' => 'textfield',
    '#title' => t('Testing Drupal Username'),
    '#default_value' => $username,
    '#size' => 30,
    '#maxlength' => 255,
    '#description' => t('This is optional and used for testing this server\'s configuration against an actual username.  The user need not exist in Drupal and testing will not affect the user\'s LDAP or Drupal Account.'),
  );
  
  $synch_context_options = ldap_user_synch_contexts_key_values();
  $selected_actions = isset($_SESSION['ldap_user_test_form']) ? $_SESSION['ldap_user_test_form'] : array();
  $form['action'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Actions/Event Handlers to Test'),
      '#required' => FALSE,
      '#default_value' => $selected_actions,
      '#options' => $synch_context_options,
      '#states' => array(
        'visible' => array(   // action to take.
          ':input[name="wsEnabled"]' => array('checked' => TRUE),
        ),
      ),
    );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'test',
    '#weight' => 100,
  );

  return $form;
}

/**
 * Submit hook for the LDAP server form.
 */
function ldap_user_test_form_submit($form, &$form_state) {
  $username = $form_state['values']['testing_drupal_username'];
  $selected_actions = $form_state['values']['action'];
  
 if ($username && count($selected_actions) > 0) {
    $synch_context_options = ldap_user_synch_contexts_key_values();
    $user_object = user_load_by_name($username);
    if ($user_object) {
      $user_entities = entity_load('user', array($user_object->uid));
      $user_entity = $user_entities[$user_object->uid];
    }
    else {
      $user_entity = NULL;
    }

    $user_ldap_entry = ldap_servers_get_user_ldap_data($username, NULL, LDAP_TEST_QUERY_CONTEXT);
    
    $results = array();
    $results['username'] = $username;
    $results['user object'] = $user_object;
    $results['user entity'] = $user_entity;
    $results['related ldap entry'] = $user_ldap_entry;
    
    if (is_object($user_object)) {
      $authmaps = db_query("SELECT aid, uid, module, authname FROM {authmap} WHERE uid = :uid", array(':uid' => $user_object->uid))->fetchAllAssoc('aid',  PDO::FETCH_ASSOC);
    }
    else {
      $authmaps = 'No authmaps available.  Authmaps only shown if user account exists beforehand';
    }
    $results['User Authmap'] = $authmaps;
    
    $ldap_user_conf = ldap_user_conf();
    $results['LDAP User Configuration Object'] = $ldap_user_conf;
    
    foreach ($selected_actions as $i => $synch_context) {
      if (!$synch_context) {
        continue;
      }
      $synch_context_description = $synch_context_options[$synch_context];
      $user_edit = array('name' => $username);
      if ($synch_context == LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER) {
        $discard = $ldap_user_conf->provisionDrupalAccount(NULL, $user_edit, $synch_context, NULL, FALSE);
        $results['provisionDrupalAccount method results']["context = $synch_context_description"]['user edit array on provision'] = $user_edit;
        
        $provision_result = $ldap_user_conf->provisionLdapEntry($user_object, LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER, NULL, TRUE);
        $results['provisionLdapEntry method results']["context = $synch_context_description"] = $provision_result;
      }
      
      $user_edit = array('name' => $username);
      $discard = $ldap_user_conf->synchToDrupalAccount(NULL, $user_edit, $synch_context, NULL, FALSE); 
      $results['synchToDrupalAccount method results']["context = $synch_context_description"]['user edit array on synch'] = $user_edit;
    }

    if (function_exists('dpm')) {
      dpm($results);
    }
    else {
      drupal_set_message('This form will not display results unless the devel module is enabled.', 'warning');  
    }
  }
  
  $_SESSION['ldap_user_test_form'] = $form_state['values']['action'];
  $form_state['redirect'] = ($username) ? LDAP_USER_TEST_FORM_PATH . "/" . $username :  LDAP_USER_TEST_FORM_PATH;

}
