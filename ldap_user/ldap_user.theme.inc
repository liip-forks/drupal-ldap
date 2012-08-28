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
  global $base_url;
 // $drupal_icon =  "<img src=\"$base_url/themes/seven/logo.png\" width=\"22\" height=\"25\" />";
 // $ldap_icon = '<img src="' . $base_url . '/' .  drupal_get_path('module', 'ldap_servers') . '/ldapicon.png' . '"  />'; // width="19" height="14"

  foreach ($tables as $direction => $ldap_servers) {
    foreach ($ldap_servers as $sid => $table) {
 
      if ($direction == LDAP_USER_PROV_DIRECTION_TO_DRUPAL_USER) {
        $direction_text = 'todrupal';
        $header = array(
          array('data' => t('Remove'), 'rowspan' => 1, 'class' => 'mixedcase'),
          array('data' => t('Source LDAP Tokens. (See http://drupal.org/node/1245736) ') ,
                'class' => 'mixedcase',
                'rowspan' => 1,
                'colspan' => 2,
                ),
          array('data' => t('Target Drupal Attribute'),
                'rowspan' => 1,
                'class' => 'mixedcase',
                ),
          array('data' => t('When Should Attribute Be Synchronized to LDAP?'), 'colspan' => count($ldap_user_conf_admin->provisionsDrupalEvents), 'rowspan' => 1, 'class' => 'mixedcase'),
          
        );
        
        $second_header = array(
          array('data' => t(''),
                'header' => TRUE,
                'class' => 'mixedcase',
                ),
          array('data' => t('e.g: "[sn]", "[mail:0]", "[ou:last]", "[sn], [givenName]" etc.
                  Constants such as "17" or "imported" should not be enclosed in [].'),
                'header' => TRUE,
                'class' => 'mixedcase',
                ),
          array('data' => t('Convert From Binary?'),
                'class' => 'mixedcase',
                'header' => TRUE
                ),
          array('data' => "",
                'header' => TRUE,
                ),
        );
      
        foreach ($ldap_user_conf_admin->provisionsDrupalEvents as $col_id => $col_name) {
          $second_header[] = array('data' => $col_name, 'header' => TRUE, 'class' => 'mixedcase');
        }
      }
      else { // to ldap
        $direction_text = 'toldap';
        $header = array(
          array(
            'data' => t('Remove'),
            'rowspan' => 1,
            'class' => 'mixedcase'
          ),
          array(
            'data' => t('Source Drupal User Attribute') ,
            'class' => 'mixedcase',
            'rowspan' => 1,
            'colspan' => 3,
          ),
          array(
            'data' => t('Target LDAP Token'),
            'rowspan' => 1,
            'class' => 'mixedcase',
          ),
          array(
            'data' => t('When Should Attribute Be Synchronized to Drupal?'),
            'colspan' => count($ldap_user_conf_admin->provisionsLdapEvents),
            'rowspan' => 1,
            'class' => 'mixedcase',
          ),
        );

        $second_header = array(
          array(
            'data' => t(''),
            'header' => TRUE
          ),
          array(
            'data' => t('(Select "user tokens" to use token field)'),
            'header' => TRUE,
            'class' => 'mixedcase'
          ),
          array(
            'data' => t('Source Drupal User tokens such as: "[property.name]", "[field.field_fname] [field.field_lname]". Constants such as "from_drupal" or "18" should not be enclosed in []'),
            'header' => TRUE,
            'class' => 'mixedcase'
          ),
          array(
            'data' => t('Convert From Binary -- not implemented yet.'),
            'header' => TRUE,
            'class' => 'mixedcase'
          ),
          array(
            'data' => t('Use singular token format such as [sn], [givenName], etc.'),
            'header' => TRUE,
            'class' => 'mixedcase'
          ),
        );
        foreach ($ldap_user_conf_admin->provisionsLdapEvents as $col_id => $col_name) {
          $second_header[] = array('data' => $col_name, 'header' => TRUE, 'class' => 'mixedcase');
        }
      }

      // #col is ignored, so sort table columns here
      foreach ($table['rows'] as $row_id => $row) {
        $discard = ksort($row);
        $table['rows'][$row_id] = $row;
      }

      $table['header'] = $header;
      $table['attributes'] = array('class' => array('ldap-provision-' . $direction_text));
      array_unshift($table['rows'], $second_header);
      $table_themed = theme('table', $table);
      $rendered_table = theme('table', $table);
      $output = str_replace( '[replace_with_table__' . $direction. ']', $rendered_table, $output);
    }
  }

  return $output . $submit;

}
