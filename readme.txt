=== California State Grants ===
Contributors: castatelibrary
Tags: grants, loans, state government, California Grants Portal, submit your grants, California state agencies, post awards
Requires at least: 5.0
Tested up to: 6.0.2
Requires PHP: 7.4
Stable tag: 2.0.8
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The California State Grants Plugin is the official WordPress plugin allowing you to manage your grant data within your own site running WordPress.

== Description ==

The California Grants Portal, [grants.ca.gov](https://www.grants.ca.gov/), is managed and hosted by the California State Library. The [Grant Information Act of 2018](http://leginfo.legislature.ca.gov/faces/billNavClient.xhtml?bill_id=201720180AB2252) (Stats. 2018, Ch. 318) required the State Library to build one website by July 1, 2020, "that provides a centralized location ... to find state grant opportunities." State grantmaking agencies input and update their information into prescribed data fields to make all state grant opportunities searchable on [grants.ca.gov](https://www.grants.ca.gov/). The Grant Information Act requires state agencies to provide summaries of each of their grant or loan opportunities, including, among other items, information about how to apply and links that grantseekers can follow for more details. [AB132](https://leginfo.legislature.ca.gov/faces/billNavClient.xhtml?bill_id=202120220AB132) expanded the Grants Portal mission, requiring state grantmakers to submit post award data for all grants closing on or after July 1, 2022. This site was built in collaboration with our vendor [10up](https://10up.com/).

The California State Grants Plugin is the official WordPress plugin allowing the state agencies to submit their grant information and post award information via the plugin and manage the grant data within their own WordPress site.

**Policies Related to the California Grants Portal**

* [Use Policy](https://www.grants.ca.gov/use-policy/)
* [Privacy Policy](https://www.grants.ca.gov/privacy-policy/)

**Instructions**

Please follow the detailed instructions provided in the [State Grantmakers Guide](https://www.grants.ca.gov/state-grantmakers-guide/) **(state agencies must log in prior to accessing this link)** to learn how to submit your grant opportunities to the Grants Portal.

== Screenshots ==

1. Hover over “Plugins” on the left-hand toolbar. Select “Add New.”
2. Type California State Grants in the search box. When the California State Grants plugin appears, select “Install Now”.
3. The grant submission form
4. Endpoint URL submission form on the California Grants Portal
5. The Endpoint URL and Authorization Token

== Frequently Asked Questions ==

= Who should use a WordPress plugin to submit grants? =

This option is best if your organization uses a public WordPress website and has IT support in uploading grant opportunities. Your organization's Grant Contributor must be a WordPress admin.

= Where can I find more instructions for submitting grants as well as definitions for all of the terms and fields in the grants form? =

The [State Grantmakers Guide](https://www.grants.ca.gov/state-grantmakers-guide/) **(state agencies must log in prior to accessing this link)** includes instructions on creating accounts, uploading grants, and includes a glossary of terms and fields used in the grants form.

= Do I need to create a Grant Contributor account to publish grants via WordPress plugin? =

Yes, Grants Contributors must set up an account in order to submit a grant opportunity. See instructions under the WordPress Plugin tab on [the "For State Agencies" page](https://www.grants.ca.gov/for-state-agencies/) on setting up an account.

= Do I need to submit an Endpoint URL and Authorization Token for every new grant? =

No, Grant Contributors only need to submit an Endpoint URL and Authorization Token once. Once an Endpoint URL and Authorization Token are submitted for the Grant Contributor's first posted grant, the Grants Portal will automatically sync every 24 hours.

= How often does the Grants Portal sync? Can I force a sync? =

The Grants Portal automatically syncs every 24 hours. However, Grant Contributors have the option to force a sync of all published grants by navigating to their Grants Dashboard in the California Grants Portal. Once there, select "force sync" next to any grant. Note: When "force sync" is selected on any grant in the Grants Dashboard, all of the Grant Contributor’s published grants will also sync. Grant drafts will remain unpublished. See [State Grantmakers Guide](https://www.grants.ca.gov/part-iii-submitting-updating-and-maintaining-information/) **(state agencies must log in prior to accessing this link)** for more details.

= How do I edit previously published grants? =

Organizations using the WordPress plugin must edit their grants through their WordPress Admin Dashboard, rather than the California Grants Portal. See instructions in the [State Grantmakers Guide](https://www.grants.ca.gov/part-iii-submitting-updating-and-maintaining-information/) **(state agencies must log in prior to accessing this link)** about editing grants with the WordPress plugin.

= Can Grant Contributors submit grants through both the WordPress plugin and the online form? =

Yes, Grant Contributors can use both the WordPress plugin and online form to submit grant opportunities. Grant Contributors should reference the State Grantmakers Guide **(state agencies must log in prior to accessing this link)** for instructions on submitting opportunities through the online form. Grant Contributors should note that grants submitted via WordPress plugin must be edited in the WordPress admin dashboard while grants submitted via online form must be edited in the Grants Portal.

== Changelog ==

= 2.0.8 =
* Updates the language for the Application Deadline and Geographic Eligibility fields on the form
* Updates readme

= 2.0.7 =
* Fixes issue with the empty grant award stats in wp-admin
* Updates readme

= 2.0.6 =
* Fixes issue with the conditional required check in the form
* Updates readme

= 2.0.5 =
* Migrates old data
* Updates readme

= 2.0.4 =
* Fixes issue with default_post_metadata filter for legacy data and csv generation
* Updates readme

= 2.0.3 =
* Updates readme

= 2.0.2 =
* Updates readme

= 2.0.1 =
* Align version numbers

= 2.0.0 =
* Added Grant Awards

= 1.1.1 =
* Tested plugin functionality up to 5.8.1
* Added an "Other" option for the Funding Method field
* Updated the "Both" option for the Funding Source field to "Federal and State"

= 1.1 =
* Tested plugin functionality up to 5.7.1
* Updated Save Draft button to allow saving posts without needing to fill out the required fields
* Updated the Anticipated Open Date and Application Deadline form fields
* Removed Update Token setting that was only used for private beta from the Settings page

= 1.0 =
* Official public launch of the plugin
