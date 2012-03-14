<?php

/**
 * @file
 *   theme functions for ldap_user module
 */


function theme_ldap_user_conf_form($variables) {

  $form = $variables['form'];

  $tables = array();
  foreach ($form as $key => $element) {
    $parts = explode('__', $key);
    $sid = $parts[0];
    $count = count($parts);
    if ($count > 2 && isset($element['#row']) && isset($element['#col'])) {
      $row = $element['#row'];
      $col = $element['#col'];
      $tables[$sid]['rows'][$row][$col] = array('data' => drupal_render($form[$key]), 'class' => array('module'),  'colspan' => 1);
    }
  }
  $submit = drupal_render($form['submit']);
  $output = drupal_render_children($form);

  $ldap_user_conf_admin = ldap_user_conf('admin');
  $header = array(
    array('data' => t('LDAP Source'), 'rowspan' => 2),
    array('data' => t('User Target'), 'rowspan' => 2),
    array('data' => t('When Should Data Be Synchronized?'), 'colspan' => count($ldap_user_conf_admin->synchTypes), 'rowspan' => 1),
    array('data' => t('Notes'), 'rowspan' => 2),
  );
  $second_header = array();
  $second_header = array(
		array('data' => t('LDAP attribute or tokens (see http://drupal.org/node/1245736)'), 'header' => TRUE),
		array('data' => t('Available user fields, properties, and profile2 fields'), 'header' => TRUE));
  foreach ($ldap_user_conf_admin->synchTypes as $col_id => $col_name) {
    $second_header[] = array('data' => $col_name, 'header' => TRUE);
  }
  $second_header[] = array('data' => '&#160;', 'header' => TRUE);

  foreach ($tables as $sid => $table) {
    $ldap_server = ldap_servers_get_servers($sid, 'all', TRUE);
    $table['header'] = $header;
     array_unshift($table['rows'], $second_header);
    $output .= '<h2>' . t('%ldap_server LDAP Server Mappings', array('%ldap_server' => $ldap_server->name)) . '</h2>' .  theme('table', $table);
  }

  $output .= $submit;
  return $output;

}
