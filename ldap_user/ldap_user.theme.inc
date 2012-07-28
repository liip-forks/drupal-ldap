<?php

/**
 * @file
 *   theme functions for ldap_user module
 */


/**
 * turns array of variables to a form embedded in one table for each ldap server
 */
function theme_ldap_user_conf_form($variables) {
  //dsm('theme_ldap_user_conf_form variables'); dsm($variables);
  $form = $variables['form'];
  $tables = array();
  foreach ($form as $key => $element) {
    $parts = explode('__', $key);
    if (count($parts) == 5 && isset($element['#row']) && isset($element['#col'])) {
      list($direction, $sid, $discard, $column_name, $i) = $parts;
      $sid = $form['#storage']['synch_mapping_fields'][$direction][$i]['sid'];
      // dpm("sid=$sid, key=$key");

      $row = $element['#row'];
      $col = $element['#col'];
      $tables[$direction][$sid]['rows'][$row][$col] = array('data' => drupal_render($form[$key]), 'class' => array('module'),  'colspan' => 1);
    }
  }
  $ldap_servers = ldap_servers_get_servers(NULL, 'all');
 // dsm('tables');  dsm($tables);
  $submit = drupal_render($form['submit']);
  $output = drupal_render_children($form);
  $ldap_user_conf_admin = ldap_user_conf('admin');

  foreach ($tables as $direction => $ldap_servers) {
    foreach ($ldap_servers as $sid => $table) {
      
      $header = array(
        array('data' => t('Remove'), 'rowspan' => 1),
        array('data' => t('Source LDAP Attribute'), 'rowspan' => 1, 'colspan' => 2),
        array('data' => t('Target User Attribute'), 'rowspan' => 1),
        array('data' => t('LDAP Tokens'), 'rowspan' => 1),
        array('data' => t('When Should Data Be Synchronized?'), 'colspan' => count($ldap_user_conf_admin->synchTypes), 'rowspan' => 1),
        
      );
    
      $second_header = array(
        array('data' => t(''), 'header' => TRUE),
        array('data' => t(''), 'header' => TRUE),
        array('data' => t('Convert From Binary'), 'header' => TRUE),
        array('data' => t(''), 'header' => TRUE),
        array('data' => 'e.g. "[sn], [givenName] might map to users display name.".
        Select "token" in the LDAP Source column to enable.', 'header' => FALSE)
      );
    
      foreach ($ldap_user_conf_admin->synchTypes as $col_id => $col_name) {
        $second_header[] = array('data' => $col_name, 'header' => TRUE);
      }
 
      if ($direction == LDAP_USER_SYNCH_DIRECTION_TO_LDAP_ENTRY) {
        $second_header[1]['data'] =  t('Target LDAP Attribute such as [dn], [cn], etc.');
        $second_header[4]['data'] = t('Source User tokens.  e.g. "[property.name], [field.field_lname].".
        Select "token" in the Drupal User Source column to enable.');
      }
  
  
      $table['header'] = $header;
      array_unshift($table['rows'], $second_header);
      $table_themed = theme('table', $table);
      $rendered_table = theme('table', $table);
      $output = str_replace( '[replace_with_table__' . $direction. ']', $rendered_table, $output);
    }
  }

  return $output . $submit;

}
