<?php
// $Id: ldap_help.module,v 1.1.2.1 2011/02/08 06:14:20 johnbarclay Exp $

/**
 * @file
 * The ldap_help issues provides a filtered watchdog view for ldap issues.
 *
 */

function ldap_help_issues() {

  $text = '<h3>' . t('LDAP Help Module') . '</h3><p>' .
  t('How to report bugs in LDAP Project.') . '</p>';
  require_once('ldap_help.status.inc');
  $ldap_config = ldap_help_status('html');

  $path = drupal_get_path("module", "ldap_servers");

  $text .= <<<EOT

  <ol>

  <li>Find where the functionality you are having trouble with is in one of the test grids. Make a note of relevant test ids.
     <ul>
     <li><a href="http://drupal.org/node/1053818">LDAP Authentication</a></li>
     </ul>
  </li>
  <li>Search for existing issues. Be sure to include all statuses and versions
      in your searches. An issue may be closed but still be relevant.
    <ul>
      <li>Use the advanced search (<a href="http://drupal.org/project/issues/search/ldap">http://drupal.org/project/issues/search/ldap</a>) in the issue queue, searching on the test id in <em>issue tags</em>
        (e.g. LDAP_authen.user_profile_form.ldap.password) or
        a shortened version of it (e.g. LDAP_authen.user_profile_form.ldap).
      </li>
      <li>Do other searches in issue queue as many issues may not be tagged by test id.</li>

  </ul>
</li>



<li>If an existing issue matches your concerns, update the status, priority, and version.  Add issue tags.  Attach your configuration.
These steps are outlined below. If no issue exists, create a new one
<ul>
<li>Title: if specific to a module, add that to the title first. e.g.
<code>Authorization: First ldapgroup ignored by roles</code> or
<code>Authentication: Option to prevent new accounts from LDAP from being added to Drupal and have admin approve them</code>,
</li>
<li>Version:  Match to version you have installed.  If you have a newer version than someone else, update to that.
<li>Assigned:  Assign to yourself if you plan to make a patch.  If its not a bug fix, best to check if it will be accepted.</li>
<li>Add Issue Tags: Do not make up new issue tags.  Use ldap test ids (e.g. LDAP_authen.user_profile_form.ldap)  </li>
<li>Attach File: with the following text named ldap_config.html (for bug reports. this isn't relvant for feature requests.)

</li>

</ul>

</li>


</ol>

<div style="border: 1px solid #000000; padding: 10px; margin: 10px; text-align: center;">
  <p>Cut and past the html below into a file called<code>ldap_config.html</code> and attach it when reporting errors.
  <br/>The table below is the same.  Check that no sensitive data is in it.</p>
  <form>
  <textarea rows=10 cols=120 border=1 style="border: 1px solid #000000">$ldap_config</textarea>
  </form>
</div>
$ldap_config
EOT;


  return $text;
}
