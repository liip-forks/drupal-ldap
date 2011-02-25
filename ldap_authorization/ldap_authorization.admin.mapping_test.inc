<?php

/**
 * @file
 * form to test ldap mapping configuration
 */

/**
 * form for adding, updating, and deleting a single ldap authorization mapping
 *
 * @param <type> $form
 * @param <type> $form_state
 * @param string $mapping_id  unique identifier for authorization mapping
 * @return array drupal form array
 */


function ldap_authorization_mapping_test_form($form, &$form_state, $mapping_id) {
  $consumer = ldap_authorization_get_consumer_object(array('mapping_id' => $mapping_id));
  $consumer_tokens = $consumer->tokens();

  if (isset($_SESSION['ldap_authorization_test_result'])) {
    $form['result'] = array(
      '#type' => 'item',
      '#markup' => $_SESSION['ldap_authorization_test_result'],
      );

    unset($_SESSION['ldap_authorization_test_result']);
  }
    
    
    $form['intro'] = array(
        '#type' => 'item',
        '#markup' => t('<h1>Test LDAP to !consumer_name Mapping Configuration</h1>
        <p>This form will not actually grant any authorizations, its just to show
        what authorizations would be granted with this mapping.</p>', $consumer_tokens),
    );


    $form['mapping_id'] = array(
      '#type' => 'hidden',
      '#default_value' => $mapping_id,
    );

   
    $form['usernames'] = array(
      '#type' => 'textarea',
      '#title' => t('Drupal usernames to test !consumer_shortName mapping results for.  One per line.', $consumer_tokens),
      '#default_value' => @$_SESSION['ldap_authorization_mapping_test_form']['usernames'] ,
      '#cols' => 50,
      '#rows' => 6,
      '#description' => t('', $consumer_tokens),
    );

    $form['random_users'] = array(
      '#type' => 'checkbox',
      '#default_value' => @$_SESSION['ldap_authorization_mapping_test_form']['random_users'],
      '#title' => t('Use 10 random users', $consumer_tokens),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'test',
    );
    
  return $form;
}


/**
 * validate handler for the ldap_authorization_mapping_test
 */
function ldap_authorization_mapping_test_form_validate($form, &$form_state) {
  
  $values = $form_state['values'];
  if (!($values['usernames'] || $values['random_users'])) {
    form_set_error('No options chosen', t('Usernames must be given or random users checked.'));
  }

}

/**
 * submit handler function for ldap_authorization_mapping_test
 */


function ldap_authorization_mapping_test_form_submit($form, &$form_state) {
  $mapping_id = $form_state['values']['mapping_id'];
  $consumer = ldap_authorization_get_consumer_object(array('mapping_id' => $mapping_id));
  $consumer_tokens = $consumer->tokens();

  $results = array();
  $users_listed = explode("\n", $form_state['values']['usernames']);
  $random_users = array();
  $_SESSION['ldap_authorization_mapping_test_form']['random_users'] = $form_state['values']['random_users'];
  $_SESSION['ldap_authorization_mapping_test_form']['usernames'] = $form_state['values']['usernames'];
  if ($form_state['values']['random_users']) {  // add 10 random usernames to test
    // not using user_load_multiple because need randomness outside of query
    $select = db_select('users', 'u');
    $select->fields('u');

    try {
      $random_users = $select->execute()->fetchAllAssoc('name',  PDO::FETCH_ASSOC);
    }
    catch(Exception $e) {
      drupal_set_message(t('db users query failed. Message = %message, query= %query',
        array('%message' => $e->getMessage(), '%query' => $e->query_string)), 'error');
      return "";
    }
    
  }
  $user_names = array_unique(array_merge(array_keys($random_users),$users_listed));

  foreach ($user_names as $username) {
    if ($username) {  // ingore user 0
      if (!$user = user_load_by_name($username)) {
        $user = new stdClass();
        $user->name = $username;
      }
      $new_authorizations = ldap_authorizations_user_authorizations($user, 'query', $mapping_id, 'logon');
      $results[$username]['authorizations'] = $new_authorizations;
  
    }
  }
  $mapping = ldap_authorizations_mappings($mapping_id, NULL, TRUE);
  $table =  theme('ldap_authorization_test_results', array('data' => $results, 'consumer' => $consumer, 'mapping' => $mapping));
  $_SESSION['ldap_authorization_test_result'] = $table;
  $form_state['redirect'] = LDAP_SERVERS_MENU_BASE_PATH .'/authorization/test/'. $mapping_id;

}
