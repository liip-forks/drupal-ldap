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

  //$ldap_server = ldap_servers_get_servers($sid, 'all', TRUE);
  $action = arg(count(explode('/',LDAP_USER_TEST_FORM_PATH)) + 1);
  $username = arg(count(explode('/',LDAP_USER_TEST_FORM_PATH)));

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

  $form['action'] = array(
      '#type' => 'radios',
      '#title' => t('Action to Test'),
      '#required' => FALSE,
      '#default_value' => $action,
      '#options' => array('create' => 'create', 'synch' => 'synch', 'delete' => 'delete'),
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

  if ($username) {
    $form['#suffix'] = "$username user test data results for action=$action:";
    $user = user_load_by_name($username);
    if ($user) {
      $user_entities = entity_load('user', array($user->uid));
      $user_entity = $user_entities[$user->uid];
    }
    else {
      $user_entity = NULL;
    }
    dpm("calling ldap_servers_get_user_ldap_data username=$username");

    $user_ldap_entry = ldap_servers_get_user_ldap_data($username, NULL, LDAP_TEST_QUERY_CONTEXT);

    if (function_exists('dpm')) {
      dpm("status before action: $action, username: $username");
      dpm('user'); dpm($user);
      dpm('user_entity'); dpm($user_entity);
      dpm('ldap_data'); dpm($user_ldap_entry);
    }

    $ldap_user_conf = ldap_user_conf();

    if ($action == 'create') {
      $account = NULL;
      $user_edit = array('name' => $username);
      $user = $ldap_user_conf->provisionDrupalAccount($account, $user_edit, LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER, NULL, TRUE);
      dpm('user after save'); dpm($user);
    }
    elseif ($action == 'delete') {
      $result = $ldap_user_conf->deleteDrupalAccount($username, LDAP_TEST_QUERY_CONTEXT);
      dpm("delete result: $result");
    }


  }
  return $form;
}

/**
 * Submit hook for the LDAP server form.
 */
function ldap_user_test_form_submit($form, &$form_state) {
  $name = $form_state['values']['testing_drupal_username'];
  $action = $form_state['values']['action'];
  $form_state['redirect'] = ($name) ? LDAP_USER_TEST_FORM_PATH  . "/" . $name . '/'. $action :  LDAP_USER_TEST_FORM_PATH;

}
