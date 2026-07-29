# atshift User Profile Fields Specification

## Product Goal

atshift User Profile Fields helps site administrators turn the default WordPress user profile screen into a cleaner, purpose-built profile editor.

The basic version focuses on two jobs:

- Add custom user profile fields.
- Hide unnecessary default WordPress profile fields from the admin profile screen.

The first release should feel complete for ordinary administrator-managed profile workflows while leaving advanced segmentation, frontend editing, and integrations available for future paid extensions.

## Basic Version Scope

### Custom Profile Fields

Administrators can create, edit, disable, reorder with drag and drop, and delete custom fields shown on WordPress user profile screens.

Supported field types:

- Text
- Textarea
- Email (Other)
- URL (Other)
- Phone
- Number
- Image
- Checkbox
- Radio
- Select

Supported structure field types:

- Horizontal Group
- Conditional Group

Supported default profile field types:

- Username
- Email
- First name
- Last name
- Nickname
- Display name
- Language
- Website
- Biographical info
- Password
- Send user notification
- Role

Each field supports:

- Field label
- Field name
- Description
- Placeholder
- Required flag
- Format validation flag for Email (Other), URL (Other), and Phone
- Drag-and-drop ordering
- Choices for radio/select fields
- Choices for conditional groups
- Parent group placement for fields when structure groups exist
- Field-type-specific settings that appear only when relevant

Custom field values are stored as user meta.

Default profile field types are edited in this field group UI but save back to WordPress default user fields. When a default profile field is added here, the original WordPress profile row is hidden to avoid duplicate editing controls.

Field-type-specific settings should follow the atshift Fields authoring experience. For example, Choices appear for Radio and Select fields, while Placeholder appears for text-like fields.

### Structure Fields

The basic version includes a small set of atshift Fields-inspired structure features for profile editing:

- Horizontal layout group
- Conditional group display based on field values

Accordion remains a possible future addition.

Tabs are intentionally not part of the basic profile editing experience.

Role-based visibility and permission-based field display are reserved for future paid or advanced extensions.

### Display Options

Administrators can choose how the field editing screen is displayed and hide unnecessary default WordPress profile fields.

Supported display options:

- 1-column field editor layout
- 2-column field editor layout with a save box
- Extras panel visibility

The basic version hides fields visually in the admin profile screen. It does not delete existing user data and does not remove WordPress core capabilities.

Field-level display conditions are not shown in the basic field settings. They can be reconsidered when conditional groups or parent structures are added.

The Extras panel sits below the field settings and contains default profile field visibility settings:

- Admin color scheme
- Syntax highlighting
- Keyboard shortcuts
- Toolbar preference
- Language
- First name
- Last name
- Nickname
- Display name
- Website
- Biographical info
- Password
- Sessions
- Send user notification
- Role
- Profile picture
- Application passwords

Visibility settings are global in the basic version.

### Admin Experience

The plugin provides a designed admin screen under Settings with a single atshift Fields-inspired editing flow:

- Display Options in the WordPress standard Screen Options drawer with immediate layout updates
- Fields list with drag-and-drop ordering and a single save box
- Field settings open inside the selected or newly added field row
- Extras below the field settings

The field creation flow uses the same core concepts as atshift Fields: Label, Name, Field Type, Choices, Notes, and field rows. A user who learns this profile-specific UI should feel comfortable creating field groups in atshift Fields later.

## Tools

WordPress Tools includes an `atshift User Profile Fields` screen with Export,
Import, and Delete tabs.

- Export downloads a named, versioned JSON distribution set containing field definitions and display settings.
- Import accepts a distribution-set file or pasted JSON from this plugin and replaces the current configuration after validation.
- Delete always removes field definitions and display settings.
- Custom user meta can be deleted separately with explicit confirmation.
- WordPress standard profile values are never deleted by the tools screen.

## Out Of Scope For Basic Version

These are reserved for future paid or extension features:

- Frontend profile edit forms
- Registration form integration
- Role-based field visibility
- Permission-based conditional display
- File and image uploads
- CSV import/export
- WooCommerce, BuddyPress, MemberPress, or LMS integrations
- REST API extensions
- Shortcodes and blocks
- Complex field groups
- Tabs
- Audit logs
- Per-field permissions
- Multisite-specific controls

## Data Storage

Field definitions are stored in the `atshift_upf_fields` option.

Plugin settings are stored in the `atshift_upf_settings` option.

When the field group is disabled, the plugin does not render, validate, save, or
hide managed profile fields. WordPress's native profile screen remains available
as an emergency fallback.

Managed WordPress fields use fail-open replacement. A native field is hidden only
after the corresponding replacement is confirmed in the rendered page. If a
required WordPress API is unavailable or a replacement is not rendered, that
field is omitted from plugin handling and the native WordPress field remains
visible. Site code can override compatibility per field with the
`atshift_upf_core_replacement_supported` filter.

For recovery when the plugin settings screen cannot be used, this line in
`wp-config.php` prevents the profile integration class from starting while
preserving all plugin settings:

```php
define( 'ATSHIFT_UPF_SAFE_MODE', true );
```

User values are stored in user meta using this format:

```text
_atshift_upf_{field_key}
```

## Validation And Sanitization

Field definitions are sanitized before saving.

User values are sanitized by field type:

- Text: `sanitize_text_field`
- Textarea: `sanitize_textarea_field`
- Email (Other): `sanitize_email` when format validation is enabled
- URL (Other): `esc_url_raw` when format validation is enabled
- Phone: `sanitize_text_field` with optional format validation
- Number: numeric value only
- Image: `esc_url_raw`
- Checkbox: `1` or `0`
- Radio/select: must match one configured choice

Required fields are validated during profile save.

## Uninstall Policy

The basic version keeps user meta on uninstall by default to avoid accidental data loss.

A later release may add a setting to delete plugin data on uninstall.
