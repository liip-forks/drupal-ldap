<?php
// $Id$

/**
 * @file
 * Administrative page callbacks for the ldap_authentication module.
 */

function ldap_authentication_admin_form(&$form_state, $op = NULL, $sid = NULL) {

$form['notes'] = array(
    '#type' => 'fieldset',
    '#title' => t('Developer Markup Notes'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

$notes = <<<EOF
<p>Placeholder for authentication admin form:<br/>
Likely components:</p>
<ol>
<li>-ldap servers(s) to use</li>
<li>-mapping configuration of ldap user entry to drupal user</li>
<li>-preference of exposure of ldap username if ldap user exists but authentication
fails in error message</li>
<li>-how to integrate with other authentication modules</li>
<li>-which ldap user data to expose to other modules</li>
</ol>
EOF;

$form['notes']['markup_notes'] = array(
  '#markup' => $notes,
  );


  return $form;
}


// vim:fenc=utf-8:ft=php:ai:si:ts=2:sw=2:et:
