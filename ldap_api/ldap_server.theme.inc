<?php
// $Id$

/**
 * @file
 * Additional theming considerations for the ldap_api module.
 */


/**
 * Theme callback function for the server list
 */
function theme_ldap_api_list_servers_form($form) {
  $header = array(t('Server'), array('data' => t('Operations'), 'colspan' => 3), t('Weight'));
//  $header = array(t('Server'), t('Type'), t('Status'), t('Address'), t('Modified'), array('data' => t('Operations'), 'colspan' => 3), t('Weight'));

  $rows = array();
  foreach (element_children($form['list']) as $key) {

    // Set the weight class
    $element = &$form['list'][$key];
    $element['weight']['#attributes']['class'] = 'weight';

    // Format/Render each column
    $row = array();
    $row[] = drupal_render($form['list'][$key]['name']);
//    $row[] = drupal_render($form['list'][$key]['type']);
//    $row[] = drupal_render($form['list'][$key]['status']);
//    $row[] = drupal_render($form['list'][$key]['address']);
//    $row[] = drupal_render($form['list'][$key]['modified']);
    $row[] = drupal_render($form['list'][$key]['edit']);
    $row[] = drupal_render($form['list'][$key]['active']);
    $row[] = drupal_render($form['list'][$key]['delete']);
    $row[] = drupal_render($form['list'][$key]['weight']);

    // Add a draggable class to every row (<tr>)
    $rows[] = array('data' => $row, 'class' => 'draggable');
  }

  if (empty($rows)) {
    $rows[] = array(array('data' => t('No servers defined.'), 'colspan' => 5));
    array_pop($header);
    unset($form['submit']);
  }

  drupal_add_tabledrag('ldap-api-list-servers-form-table', 'order', 'sibling', 'weight');

  $output = theme('table', $header, $rows, array('id' => 'ldap-api-list-servers-form-table'));
  $output .= drupal_render($form);
  
  return $output;
}

// vim:fenc=utf-8:ft=php:ai:si:ts=2:sw=2:et:
