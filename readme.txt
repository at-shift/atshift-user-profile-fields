=== atshift User Profile Fields ===
Contributors: atshift
Tags: user profile, profile fields, custom fields, users, admin
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.1.104
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add custom user profile fields and hide unnecessary default WordPress profile fields.

== Description ==

atshift User Profile Fields helps administrators create cleaner WordPress user profile screens.

The basic version includes:

* Custom profile fields
* Required field validation
* Select, radio, checkbox, text, textarea, email, URL, and number fields
* Default WordPress profile field visibility controls
* Downloadable JSON distribution sets and validated file import
* Bundled WordPress default profile preset for user creation and saved-user editing
* Protected deletion of plugin settings and optional custom profile values
* A designed admin interface for everyday profile management

== Displaying Custom Field Values ==

Custom fields are saved as user meta. Use the field key shown in the field editor.

The recommended helper returns the raw saved value, so escape it for the output context:

`
$value = atshift_upf_get_user_field( 'your_field_key', $user_id );
echo esc_html( $value );
`

You can also read the user meta directly. The meta key is `_atshift_upf_` followed by the saved field key:

`
$value = get_user_meta( $user_id, '_atshift_upf_your_field_key', true );
echo esc_html( $value );
`

Text, textarea, email, URL, number, select, and radio fields save a single sanitized value. Image fields save the selected image URL, so escape them as URLs:

`
$image_url = atshift_upf_get_user_field( 'profile_image', $user_id );
if ( $image_url ) {
	echo '<img src="' . esc_url( $image_url ) . '" alt="">';
}
`

Checkbox fields save `1` when checked and `0` when unchecked. Standard WordPress profile fields, such as email, first name, and website, use the normal WordPress user APIs instead of the `_atshift_upf_` custom meta key.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin from the Plugins screen.
3. Open Settings > atshift User Profile Fields.

== Changelog ==

= 0.1.104 =
* Add a helper and documentation for displaying saved custom profile field values.

= 0.1.103 =
* Preview bundled default profile field labels and notes immediately when the profile language selector changes.
* Keep bundled default profile labels and notes aligned with the current site or user display language.
* Refine Japanese bundled profile text to match the atshift Fields writing style.
* Require server-side confirmation before the tools screen deletes plugin data.

= 0.1.100 =
* Use a plain-language label for the bundled public display-name field.

= 0.1.99 =
* Clarify the bundled display-name field label as a name display format setting.

= 0.1.98 =
* Keep Nickname visually optional in bundled defaults and validate only attempts to save it blank.

= 0.1.97 =
* Place Username outside and immediately before the Name group in bundled default field sets.

= 0.1.96 =
* Match the bundled default field set to the current admin user's display language, falling back to the site language.

= 0.1.95 =
* Add a one-click, site-language-aware bundled default field set to the empty editor.

= 0.1.94 =
* Preserve the selected first, middle, or last position when sorting fields inside groups.

= 0.1.93 =
* Keep the feature-control gray background when feature fields are placed inside a Box Group.

= 0.1.92 =
* Strengthen the dedicated save action button sizing against WordPress core button styles.

= 0.1.91 =
* Give the required Add / Save User field a distinct full-width action section.

= 0.1.90 =
* Keep profile label and content columns stable when a full-width Box Group appears first.

= 0.1.89 =
* Use the Box Group as the only frame when it contains feature controls.

= 0.1.88 =
* Preserve horizontal group columns for feature controls while enclosing them in one shared outer frame.

= 0.1.87 =
* Add role-based display controls to Sessions and Application Passwords while keeping ordinary fields and groups role-neutral.

= 0.1.86 =
* Hide the native Account Management heading when all native account controls are moved into the field set or hidden in Extras.

= 0.1.85 =
* Remove the empty native Account Management heading after its controls are placed in the managed field set.

= 0.1.84 =
* Include Tools in the WordPress Tools submenu label.

= 0.1.83 =
* Ensure the save section uses the same desktop content-column alignment as grouped feature controls.

= 0.1.82 =
* Align the standalone save section with the control columns used by grouped feature fields.

= 0.1.81 =
* Display the profile picture as an unframed full-width field instead of a feature panel.

= 0.1.80 =
* Expand biographical info in horizontal groups and place functional WordPress session and application-password controls in managed fields.

= 0.1.79 =
* Match WordPress profile editing with Gravatar guidance and an expandable new-password generator.

= 0.1.78 =
* Fix horizontal field alignment, preview admin color schemes instantly, and group feature controls in one frame.

= 0.1.77 =
* Rename distribution-set wording to field-set wording throughout the Tools screen.

= 0.1.76 =
* Bundle a WordPress default profile preset and keep complex native account controls as safe fallbacks.

= 0.1.75 =
* Align the empty-state upload icon and Extras checkboxes, and complete the Japanese translations.

= 0.1.74 =
* Let an empty editor upload and validate a field-set JSON file directly.

= 0.1.73 =
* Show Start Over only after at least one field has been saved.

= 0.1.72 =
* Replace the reset wording with a clearer Start Over action that returns fields and Extra settings to their initial state.

= 0.1.71 =
* Rename the status control, align its options horizontally, and add a protected reset link.

= 0.1.70 =
* Use quiet neutral styling for Group, Box, Conditional, and Accordion editor badges.

= 0.1.69 =
* Move required badges before field titles in the editor and match their color-scheme behavior to profile screens.

= 0.1.68 =
* Export named distribution sets as JSON files and import them directly from a file.
* Keep export-code copy and paste as a recovery fallback.

= 0.1.67 =
* Add Tools > atshift User Profile Fields with versioned JSON export, validated import, and protected plugin-data deletion.

= 0.1.66 =
* Let profile textareas use the full available width instead of WordPress's fixed profile-page width.

= 0.1.65 =
* Use the dark admin color-scheme tone with white text for required badges.

= 0.1.64 =
* Place required badges before field headings.
* Use the darker admin color-scheme tone for group outlines, required badges, and conditional emphasis.

= 0.1.63 =
* Show required badges only on actual input fields, without repeating them on parent Box or Accordion Group headings.

= 0.1.62 =
* Use a neutral gray background for profile feature groups.

= 0.1.61 =
* Allow Conditional Groups to be placed inside Box Groups.
* Remove the long safety-mode explanation from the Publish panel.

= 0.1.60 =
* Added fail-open compatibility protection for managed WordPress profile fields.
* Disabling the field group now restores the native WordPress profile screen without hiding default fields.
* Unsupported managed fields automatically fall back to their native WordPress display.
* Added a wp-config.php emergency safe mode that bypasses all profile-screen integration without deleting settings.

= 0.1.0 =
* Initial basic version.
