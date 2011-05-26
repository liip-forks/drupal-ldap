
This is a development version of an feeds fetcher and feeds parser for ldap.

The plan is to have 2 fetchers:
- FeedsLdapQueryFetcher for fetching generic ldap queries, configured by admins
- FeedsLdapDrupalUserFetcher for fetching ldap user entries associated with drupal users.


And 1 parser:

- FeedsLdapEntryParser that converts ldap entries array returned from ldap_search() to standard feed parser result format.


It is quite broken.  I tried to piece together parts of xml and html fetchers and xpath parser, but a lot of cutting and pasting and trial and error has made a mess of it.  If someone takes over on this, maybe best to start over again.
