<?php

function ldap_help_main() {

  $text = '<h3>'. t('LDAP Help Module') .'</h3><p>'.
  t('This module is meant to assist Drupal admins in configuring, debugging, sharing, and submitting
  support and bug request related to LDAP modules.') .'<strong><em>'.
  t(' LDAP Help Module should be disabled unless you are debugging or configuring ldap problems.') .'</em></strong>'.
  t(' It adds no functionality to the LDAP modules.') .'</p>';

  $path = drupal_get_path("module", "ldap_servers");

  $text .= <<<EOT

  <h3>LDAP Integration Module Resources</h3>
  <ul>
  <li>The <a href="http://drupal.org/node/997082">Drupal.org Documentation</a> covers basics of module.</li>
  <li>Search <a href="http://drupal.org/project/issues/search/ldap">issue que</a>  For best results,
  select version and category before searching.</li>
  <li><a href="http://drupal.org/project/issues/ldap">View all issues</a></li>
  </ul>

  <h3>General Drupal Support Help</h3>
  <ul>
  <li><a href="http://drupal.org/node/314185">How to report a problem in Drupal</a></li>
  <li><a href="http://drupal.org/node/19279">How to report bugs in Drupal</a></li>
  <li><a href="http://drupal.org/Troubleshooting-FAQ">Drupal Troubleshooting FAQ</a></li>
  </ul>

  <h3>General LDAP Resources</h3>
  <ul>
  <li><a href="http://us.php.net/manual/en/book.ldap.php">PHP LDAP</a></li>
  <li><a href="http://directory.apache.org/studio/">Apache Directory Studio</a> LDAP Browser and Directory Client.</li>
  <li><a href="http://www.novell.com/documentation/edir873/index.html?page=/documentation/edir873/edir873/data/h0000007.html">Novell Edirectory</a></li>
  <li><a href="http://images.apple.com/server/macosx/docs/Open_Directory_Admin_v10.5_3rd_Ed.pdf">Apple Open Directory</a></li>
  <li><a href="http://msdn.microsoft.com/en-us/library/aa705886(VS.85).aspx">Microsoft Active Directory LDAP</a></li>
  </ul>

EOT;


  return $text;
}
