<?php
// $Id$

/**
 * @file
 * Administrative page callbacks for the ldap_authorization module.
 */


function ldap_authorization_admin_form(&$form_state, $op = NULL, $sid = NULL) {

$form['notes'] = array(
    '#type' => 'fieldset',
    '#title' => t('Developer Markup Notes'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

$notes = <<<EOF
<p>Placeholder for authorization admin form:<br/>
Likely components:</p>
<ol>
<li>- ldap servers(s) to use for authorization.  perhaps limit to 1 in interface for usability but architect for multiple in data structure</li>
<li>- mapping configuration of ldap to drupal roles</li>
<li>- filtering of roles</li>
<li>- option to deny ldap authentication to users with no ldap granted roles</li>
</ol>
EOF;

$form['notes']['markup_notes'] = array(
  '#markup' => $notes,
  );

  return $form;
}

// vim:fenc=utf-8:ft=php:ai:si:ts=2:sw=2:et:
