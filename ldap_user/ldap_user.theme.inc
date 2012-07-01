<?php

/**
 * @file
 *   theme functions for ldap_user module
 */


/**
 * turns array of variables to a form embedded in one table for each ldap server
 */
function theme_ldap_user_conf_form($variables) {
 // dsm('theme_ldap_user_conf_form variables'); dsm($variables);
  $form = $variables['form'];

  $tables = array();
  foreach ($form as $key => $element) {
    $parts = explode('__', $key);
    if (count($parts) == 3 && isset($element['#row']) && isset($element['#col'])) {
      list($discard, $column_name, $i) = $parts;
      $sid = $form['#storage']['synch_mapping_fields'][$i]['sid'];
      // dpm("sid=$sid, key=$key");

      $row = $element['#row'];
      $col = $element['#col'];
      $tables[$sid]['rows'][$row][$col] = array('data' => drupal_render($form[$key]), 'class' => array('module'),  'colspan' => 1);
    }
  }
 // dsm('tables');  dsm($tables);
  $submit = drupal_render($form['submit']);
  $output = drupal_render_children($form);

  $ldap_user_conf_admin = ldap_user_conf('admin');

  $header = array(
    array('data' => t('Remove'), 'rowspan' => 2),
    array('data' => t('LDAP Attribute'), 'rowspan' => 2, 'colspan' => 2),
    array('data' => t('Direction'), 'rowspan' => 2),
    array('data' => t('User Attribute'), 'rowspan' => 2),
    array('data' => t('When Should Data Be Synchronized?'), 'colspan' => count($ldap_user_conf_admin->synchTypes), 'rowspan' => 1),
    array('data' => t('User Tokens'), 'rowspan' => 1),
  );

  $second_header = array(
    array('data' => t(''), 'header' => TRUE),
    array('data' => t('LDAP attribute or tokens (see http://drupal.org/node/1245736)'), 'header' => TRUE),
    array('data' => t('Convert From Binary'), 'header' => TRUE),
    array('data' => t(''), 'header' => TRUE),
    array('data' => t('Available user fields, properties, and profile2 fields'), 'header' => TRUE),
  );

  foreach ($ldap_user_conf_admin->synchTypes as $col_id => $col_name) {
    $second_header[] = array('data' => $col_name, 'header' => TRUE);
  }
  $second_header[] = array('data' => 'User tokens for synching to ldap.  e.g. "[field.lname], [field.fname]".
    Select "token" in the User Source column to enable.  Only works for direction "to LDAP entry"', 'header' => FALSE);

  $ldap_servers = ldap_servers_get_servers(NULL, 'all');

  foreach ($tables as $sid => $table) {
    $table['header'] = $header;
    array_unshift($table['rows'], $second_header);
    $table_themed = theme('table', $table);
    $rendered_table = theme('table', $table);
    $output = str_replace( '[replace_with_table__' . $sid. ']', $rendered_table, $output);
  }

  return $output . $submit;

}
