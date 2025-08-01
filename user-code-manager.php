<?php
/**
 * Plugin Name: User Code Manager for Contact Form 7 
 * Description: Manages unique user codes with Contact Form 7 validation and admin UI. 
 * Version: 1.0
 * Author: Julian Muslia
 * Author URI: https://julianmuslia.com
 * Requires Plugins: contact-form-7,contact-form-7-dynamic-text-extension,advanced-custom-fields, admin-columns-for-acf-fields
 */
if (!defined('ABSPATH')) exit;
require_once plugin_dir_path(__FILE__) . 'includes/qrlib.php';

add_action('admin_enqueue_scripts', 'ucm_enqueue_admin_styles');
function ucm_enqueue_admin_styles($hook) {
    // Optional: limit to a specific page if needed

    wp_enqueue_style('ucm-admin-style', plugin_dir_url(__FILE__) . 'css/style.css');
    
}
add_action('admin_enqueue_scripts', 'ucm_enqueue_readme_css');
function ucm_enqueue_readme_css($hook_suffix) {
    // Check if we're on the correct submenu page under Contact Form 7
if ($hook_suffix !== 'contact_page_ucm-unique-codes') {
    return;
}
    wp_enqueue_style(
        'ucm-readme-style',plugin_dir_url(__FILE__) . 'css/readme.css');
}




function ucm_add_admin_menu() {
    add_submenu_page(
        'wpcf7',
        'Unique Codes',
        'Unique Codes',
        'manage_options',
        'ucm-unique-codes',
        'ucm_config_page'
    );
}
add_action('admin_menu', 'ucm_add_admin_menu');

function ucm_config_page() {
echo'<h1>User Code Manager for Contact Form 7 - Configuration Guide</h1>
<div class="intro">
    <div class="info">
      <strong>Plugin Requirements:</strong>
      <ul>
        <li>Contact Form 7</li>
        <li>Contact Form 7 Dynamic Text Extension</li>
        <li>Advanced Custom Fields (ACF)</li>
        <li>ACF Admin Columns</li>
      </ul>
    </div>

    <h2>Setup Instructions</h2>

    <div class="step">
      <h3>Step 1: Create ACF Post Type</h3>
      <ol>
        <li>Go to Custom Post Types → Add New</li>
        <li>Create a new post type (e.g., "Invitations")</li>
        <li>Note down the post type slug - you\'ll need this later</li>
      </ol>
    </div>

    <div class="step">
      <h3>Step 2: Create ACF Fields</h3>
      <p>
        Create the <strong>MUST HAVE</strong> fields in ACF for your post type.
        You can name them as you like, but ensure they match the plugin settings
        later. Here are the recommended fields:
      </p>
      <ul>
        <li><code>first_name</code> (Text) - First name of invitee</li>
        <li><code>last_name</code> (Text) - Last name of invitee</li>
        <li><code>email</code> (Email) - Email address</li>
        <li><code>invitation_code</code> (Text) - Unique invitation code</li>
        <li><code>status</code> (Select) - Status with options: unused/used</li>
        <li><code>uuid</code> (Text) - Unique identifier for URL</li>
        <li><code>invitation_url</code> (Text) - Generated invitation URL</li>
        <li><code>qr_code</code> (Image) - Generated QR code</li>
      </ul>
    </div>

    <div class="step">
      <h3>Step 3: Create Contact Form 7 Form</h3>
      <p>Add these fields to your CF7 form:</p>
      <br />
      <pre><code>[email* user-email]<br>[text* invitation-code]</code></pre>
      <p>You can make the invitation code field hidden using:</p>
      <pre><code>[hidden invitation-code ""]</code></pre>
    </div>

    <div class="step">
      <h3>Step 4: Configure Plugin Settings</h3>
      <ol>
        <li>Go to Contact Form 7 → Your Form → Unique Code Validator tab</li>
        <li>
          Fill in the settings:
          <ul>
            <li>CF7 Email Field: <code>user-email</code></li>
            <li>CF7 Unique Code Field: <code>invitation-code</code></li>
            <li>ACF Post Type: Your post type slug</li>
            <li>ACF Field Names: Match with your ACF field names</li>
            <li>CF7 Page Location: The URL where your form is located</li>
          </ul>
        </li>
        <li>
          For First time use, click <i>Reset All invitation codes</i> to
          generate Invitation Codes and set the status <i>Unused</i> as default.
        </li>
        <li>
          Click <i>Generate URL & QR Codes</i> to create unique URLs and QR
          codes for each invitation
        </li>
        <li>Save the settings</li>
      </ol>
    </div>

    <div class="warning">
      <h3>Important Notes:</h3>
      <ul>
        <li>
          Field names must match exactly between ACF and the plugin settings
        </li>
        <li>The status field must use "unused" and "used" as values</li>
        <li>Generate URL & QR codes after adding new invitations</li>
        <li>Test the form submission process before going live</li>
      </ul>
    </div>

    <div class="info">
      <h3>Available Shortcodes:</h3>
      <p>Use this form to display ACF field values in your forms:</p>
      <code
        >[dynamic_text* first_name placeholder:First%20Name "ucm_acf_field
        key=\'here-goes-the-ACF-Field-you-want-to-assign-to-this-CF7-field\'"]</code
      >
      <p>The <code>%20</code> is used to make spaces</p>
    </div>

    <div class="step">
      <h3>Managing Invitations</h3>
      <ul>
        <li>Use the "Export CSV" button to download invitation data</li>
        <li>
          "Reset All Invitation Codes" generates new codes for all entries
        </li>
        <li>"Generate URL & QR Codes" creates unique URLs and QR codes</li>
        <li>Individual codes can be reset using the "Reset Code" button</li>
      </ul>
    </div>

    <div class="warning">
      <p>
        <strong>Remember:</strong> Always backup your data before performing
        bulk operations like resetting codes or generating new URLs.
      </p>
    </div></div>';

}


// Added tabs in every CF7 form to set per-form email and unique code field names
// Compatibility for CF7 5.3.x and earlier
add_filter('wpcf7_editor_panels', 'ucm_register_cf7_panels_legacy');

// Compatibility for Modern CF7 5.4+ (current versions)
add_filter('wpcf7_contact_form_admin_panels', 'ucm_register_cf7_panels_modern');

// Legacy function
function ucm_register_cf7_panels_legacy($panels) {
    $panels['ucm_unique_code_validator'] = [
    'title' => __('Unique Code Validator', 'unique-code-validator'),
    'callback' => 'ucm_unique_code_validator_panel_content'
    ];
    return $panels;
}

    // Modern function
function ucm_register_cf7_panels_modern($panels) {
    $panels['ucm_unique_code_validator'] = [
    'title' => __('Unique Code Validator', 'unique-code-validator'),
    'callback' => 'ucm_unique_code_validator_panel_content'
    ];
}


    // Panel content for Unique Code Validator
function ucm_unique_code_validator_panel_content($post) {
    $form_id = $post->id();


    // Fields for the Unique INVITATION Check
    $cf7_email_field = get_post_meta($form_id, '_ucm_cf7_email_field', true);
    $cf7_unique_code = get_post_meta($form_id, '_ucm_cf7_unique_code_field', true);

    // Fields for the Unique URL INVITATION GENERATION
    $acf_post_type    = get_post_meta($form_id, '_ucm_acf_post_type', true);
    $acf_first_name = get_post_meta($form_id, '_ucm_acf_first_name_field', true);
    $acf_last_name  = get_post_meta($form_id, '_ucm_acf_last_name_field', true);
    $acf_email      = get_post_meta($form_id, '_ucm_acf_email_field', true);
    $acf_invitation_code       = get_post_meta($form_id, '_ucm_acf_invitation_code_field', true);
    $acf_invitation_status = get_post_meta($form_id, '_ucm_acf_invitation_status_field', true);
    $acf_uuid_code       = get_post_meta($form_id, '_ucm_acf_unique_uuid_code_field', true);
    $acf_invitation_url = get_post_meta($form_id, '_ucm_acf_invitation_url_field', true);
    $acf_qr_code = get_post_meta($form_id, '_ucm_acf_qr_code_field', true);
    $cf7_page       = get_post_meta($form_id, '_ucm_cf7_page_field', true);


    // Render settings fields
    wp_nonce_field('ucm_cf7_settings_save', 'ucm_cf7_settings_nonce');

    echo '<input type="text" id="ucm-search" placeholder="Search..." style="padding:6px; width: 100%; max-width: 200px; margin: 10px;">';

    $nonce = wp_create_nonce('ucm_export_csv_action');
    echo '<button type="button" id="ucm-export-csv" data-nonce="' . esc_attr($nonce) . '" data-form-id="' . esc_attr($form_id) . '" class="button button-danger" style="margin:10px;padding:5px 10px;">Export CSV</button>';


    echo '<button type="button" class="button button-danger" id="ucm-reset-all-button" style="margin:10px;padding:5px 10px;" data-form-id="'. esc_attr($form_id) .'">Reset All Invitation Codes</button>';

    echo '<button type="button" class="button button-danger" id="ucm-generate-url-qr-code-button" data-form-id="'.esc_attr($form_id).'" style="margin:10px;padding:5px 10px;">Generate URL & QR Codes</button>';
    




    echo '<h3>SETTINGS TO CONFIGURE UNIQUE INVITATION USAGE</h3>';
    echo '<h4>This part is used to validate the unique invitation code and email address in Contact Form 7.<br>
    If left empty the validation wont work. Both fields should be filled with the Contact Form 7 field name. <br>
    Example: If this is the email tag [email* user-email placeholder "Enter User Email"] then here write only user-email (tag name).<br>
    Same rule is for the Unique invitation code field too. This can be a hidden field. </h4>';
    echo '<div class="ucm-settings-row">
    <div>
        <label>CF7 Email Field</label>
        <input type="text" name="ucm_cf7_email_field" value="' . esc_attr($cf7_email_field) . '" />
    </div>
    <div>
        <label>CF7 Unique Invitation Code Field</label>
        <input type="text" name="ucm_cf7_unique_code_field" value="' . esc_attr($cf7_unique_code) . '" />
    </div>
</div>';

   
                

    echo '<h3>SETTINGS TO CONFIGURE UNIQUE URL GENERATOR FOR EVERY INVITATION</h3>';
    echo '<h4>This part is used to generate unique URLs and QR codes for each invitation.<br>
    Before configuration, make sure you have created an ACF Post Type with the following fields.<br>
    It must always have the ACF Post Type set and to create all fields, otherwise it wont work.<br>
    The fields below should match the ACF field names you created in your ACF Post Type.<br>
    Example: If you created a field with the name "first_name" then here write only first_name (field name).<br>

    </h4>';
    
   echo '
<div class="ucm-settings-container">

    <div class="ucm-settings-row">
        <div>
            <label>ACF Post Type</label>
            <input type="text" name="ucm_acf_post_type" value="' . esc_attr($acf_post_type) . '" />
        </div>
        <div>
            <label>ACF First Name Field</label>
            <input type="text" name="ucm_acf_first_name_field" value="' . esc_attr($acf_first_name) . '" />
        </div>
        <div>
            <label>ACF Last Name Field</label>
            <input type="text" name="ucm_acf_last_name_field" value="' . esc_attr($acf_last_name) . '" />
        </div>
    </div>

    <div class="ucm-settings-row">
        <div>
            <label>ACF Email Field</label>
            <input type="text" name="ucm_acf_email_field" value="' . esc_attr($acf_email) . '" />
        </div>
        <div>
            <label>ACF Invitation Code</label>
            <input type="text" name="ucm_acf_invitation_code_field" value="' . esc_attr($acf_invitation_code) . '" />
        </div>
        <div>
            <label>ACF Status Field</label>
            <input type="text" name="ucm_acf_invitation_status_field" value="' . esc_attr($acf_invitation_status) . '" />
        </div>
    </div>

    <div class="ucm-settings-row">
        <div>
            <label>ACF Invitation URL</label>
            <input type="text" name="ucm_acf_invitation_url_field" value="' . esc_attr($acf_invitation_url) . '" />
        </div>
        <div>
            <label>ACF UUID Code</label>
            <input type="text" name="ucm_acf_unique_uuid_code_field" value="' . esc_attr($acf_uuid_code) . '" />
        </div>
        <div>
            <label>ACF QR Code</label>
            <input type="text" name="ucm_acf_qr_code_field" value="' . esc_attr($acf_qr_code) . '" />
        </div>
    </div>

    <div class="ucm-settings-row">
        <div>
            <label>Contact Form 7 Location URL Page<br><small>(e.g. /example-url/)</small></label>
            <input type="text" name="ucm_cf7_page_field" value="' . esc_attr($cf7_page) . '" />
        </div>
    </div>

</div>';





    // Stop if no post type configured
    if (empty($acf_post_type)) {
        echo '<p><strong>Please set the ACF Post Type above to load related data.</strong></p>';
        return;
    }

    // Query data from the configured post type
    $query = new WP_Query([
        'post_type'      => $acf_post_type,
        'posts_per_page' => -1,
    ]);

    if (!$query->have_posts()) {
        echo '<p>No entries found for post type: <code>' . esc_html($acf_post_type) . '</code></p>';
        return;
    }

    // Show table
    echo '<h2>Entries from post type: <code>' . esc_html($acf_post_type) . '</code></h2>';
    echo '<table class="widefat striped" id="ucm-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>E-Mail</th>
                <th>Invitation Code</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>';

    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();

        $first = get_post_meta($post_id, $acf_first_name, true);
        $last = get_post_meta($post_id, $acf_last_name, true);
        $email = get_post_meta($post_id, $acf_email, true);
        $code = get_post_meta($post_id, $acf_invitation_code, true);
        $status = get_post_meta($post_id, $acf_invitation_status, true);

        $status_style = ($status === 'unused') ? 'color:red;font-weight:bold;' : 'color:green;font-weight:bold;';
        $status_text = $status ?: 'n/a';

        echo '<tr>
            <td>' . esc_html($post_id) . '</td>
            <td>' . esc_html($first) . '</td>
            <td>' . esc_html($last) . '</td>
            <td>' . esc_html($email) . '</td>
            <td>' . esc_html($code) . '</td>
            <td style="' . esc_attr($status_style) . '">' . esc_html($status_text) . '</td>
            <td>
            <button type="button" class="button button-small ucm-reset-single-invitation-code" data-id="'. esc_attr($post_id) .'" data-form-id="'. esc_attr($form_id) .'">Reset Code</button>
            </td>
            
        </tr>';
    }

    echo '</tbody></table>';
    wp_reset_postdata();
}

add_action('wpcf7_save_contact_form', 'ucm_save_cf7_form_settings');
function ucm_save_cf7_form_settings($cf7) {
    if (!isset($_POST['ucm_cf7_settings_nonce']) || !wp_verify_nonce($_POST['ucm_cf7_settings_nonce'], 'ucm_cf7_settings_save')) {
        return;
    }

    $form_id = $cf7->id();
    // Fields for the Unique INVITATION Check

    update_post_meta($form_id, '_ucm_cf7_email_field', sanitize_text_field($_POST['ucm_cf7_email_field'] ?? ''));
    update_post_meta($form_id, '_ucm_cf7_unique_code_field', sanitize_text_field($_POST['ucm_cf7_unique_code_field'] ?? ''));
    // Fields for the Unique URL INVITATION GENERATION
    update_post_meta($form_id, '_ucm_acf_post_type', sanitize_text_field($_POST['ucm_acf_post_type'] ?? ''));
    update_post_meta($form_id, '_ucm_acf_first_name_field', sanitize_text_field($_POST['ucm_acf_first_name_field'] ?? ''));
    update_post_meta($form_id, '_ucm_acf_last_name_field', sanitize_text_field($_POST['ucm_acf_last_name_field'] ?? ''));
    update_post_meta($form_id, '_ucm_acf_email_field', sanitize_text_field($_POST['ucm_acf_email_field'] ?? ''));
    update_post_meta($form_id, '_ucm_acf_invitation_code_field', sanitize_text_field($_POST['ucm_acf_invitation_code_field'] ?? ''));
    update_post_meta($form_id, '_ucm_acf_invitation_status_field', sanitize_text_field($_POST['ucm_acf_invitation_status_field'] ?? ''));
    update_post_meta($form_id, '_ucm_acf_invitation_url_field', sanitize_text_field($_POST['ucm_acf_invitation_url_field'] ?? ''));
    update_post_meta($form_id, '_ucm_acf_unique_uuid_code_field', sanitize_text_field($_POST['ucm_acf_unique_uuid_code_field'] ?? ''));
    update_post_meta($form_id, '_ucm_acf_qr_code_field', sanitize_text_field($_POST['ucm_acf_qr_code_field'] ?? ''));

    
    update_post_meta($form_id, '_ucm_cf7_page_field', sanitize_text_field($_POST['ucm_cf7_page_field'] ?? ''));
}

// Reset Single Code for Single User
add_action('wp_ajax_ucm_ajax_reset_single_invitation_code', 'ucm_ajax_reset_single_invitation_code');
function ucm_ajax_reset_single_invitation_code() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    $post_id = intval($_POST['post_id'] ?? 0);
    $form_id = intval($_POST['form_id'] ?? 0);

    if (!$post_id || !$form_id) {
        wp_send_json_error(['message' => 'Invalid data']);
    }

    // Load field keys from CF7 form's meta
    $acf_post_type         = get_post_meta($form_id, '_ucm_acf_post_type', true);
    $acf_invitation_code   = get_post_meta($form_id, '_ucm_acf_invitation_code_field', true);
    $acf_invitation_status = get_post_meta($form_id, '_ucm_acf_invitation_status_field', true);

    // Validate post type
    if (get_post_type($post_id) !== $acf_post_type) {
        wp_send_json_error(['message' => 'Post type mismatch']);
    }

    // Function to check if code exists on any post except the current one
    function code_exists_elsewhere($code, $post_type, $meta_key, $exclude_post_id) {
        $query = new WP_Query([
            'post_type'      => $post_type,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'meta_query'     => [
                [
                    'key'     => $meta_key,
                    'value'   => $code,
                    'compare' => '=',
                ]
            ],
            'fields'         => 'ids',
            'exclude'        => [$exclude_post_id],
        ]);
        return $query->have_posts();
    }

    // Generate unique code
    do {
        $new_code = strtoupper(bin2hex(random_bytes(5)));
        $exists = code_exists_elsewhere($new_code, $acf_post_type, $acf_invitation_code, $post_id);
    } while ($exists);

    // Update the current post meta
    update_post_meta($post_id, $acf_invitation_code, $new_code);
    update_post_meta($post_id, $acf_invitation_status, 'unused');

    wp_send_json_success(['message' => 'Code reset', 'new_code' => $new_code]);
}

// Reset Codes for all Users
add_action('wp_ajax_ucm_ajax_reset_all_invitation_codes', 'ucm_ajax_reset_all_invitation_codes');
function ucm_ajax_reset_all_invitation_codes() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    $form_id = intval($_POST['form_id'] ?? 0);

    if (!$form_id) {
        wp_send_json_error(['message' => 'Invalid form ID']);
    }

    // Load field keys from CF7 form's meta
    $acf_post_type         = get_post_meta($form_id, '_ucm_acf_post_type', true);
    $acf_invitation_code   = get_post_meta($form_id, '_ucm_acf_invitation_code_field', true);
    $acf_invitation_status = get_post_meta($form_id, '_ucm_acf_invitation_status_field', true);

    if (!$acf_post_type || !$acf_invitation_code || !$acf_invitation_status) {
        wp_send_json_error(['message' => 'Missing form meta data']);
    }

    // Helper function to check if code exists elsewhere (excluding a specific post)
    function code_exists_elsewhere_all($code, $post_type, $meta_key, $exclude_post_id) {
        $query = new WP_Query([
            'post_type'      => $post_type,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'meta_query'     => [
                [
                    'key'     => $meta_key,
                    'value'   => $code,
                    'compare' => '=',
                ]
            ],
            'fields'         => 'ids',
            'exclude'        => [$exclude_post_id],
        ]);
        return $query->have_posts();
    }

    // Get all posts of this post type
    $posts = get_posts([
        'post_type'      => $acf_post_type,
        'post_status'    => 'any',
        'numberposts'    => -1,
        'fields'         => 'ids',
    ]);

    if (empty($posts)) {
        wp_send_json_error(['message' => 'No posts found for this post type']);
    }

    $updated_posts = [];

    foreach ($posts as $post_id) {
        // Generate a unique code for each post
        do {
            $new_code = strtoupper(bin2hex(random_bytes(5)));
            $exists = code_exists_elsewhere_all($new_code, $acf_post_type, $acf_invitation_code, $post_id);
        } while ($exists);

        // Update post meta with new code and reset status
        update_post_meta($post_id, $acf_invitation_code, $new_code);
        update_post_meta($post_id, $acf_invitation_status, 'unused');

        $updated_posts[$post_id] = $new_code;
    }

    wp_send_json_success([
        'message' => 'All invitation codes reset',
        'updated' => $updated_posts,
    ]);
}

//Export CSV
add_action('admin_post_ucm_export_csv', 'ucm_handle_export_csv');
function ucm_handle_export_csv() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    if (empty($_GET['ucm_nonce']) || !wp_verify_nonce($_GET['ucm_nonce'], 'ucm_export_csv_action')) {
        wp_die('Nonce verification failed or link expired.');
    }

    $form_id = intval($_GET['form_id'] ?? 0);

    if (!$form_id) {
        wp_die('Missing form ID');
    }

    // Load ACF field keys from the form meta
    $acf_post_type         = get_post_meta($form_id, '_ucm_acf_post_type', true);
    $acf_first_name = get_post_meta($form_id, '_ucm_acf_first_name_field', true);
    $acf_last_name  = get_post_meta($form_id, '_ucm_acf_last_name_field', true);
    $acf_email      = get_post_meta($form_id, '_ucm_acf_email_field', true);
    $acf_invitation_code   = get_post_meta($form_id, '_ucm_acf_invitation_code_field', true);
    $acf_invitation_status = get_post_meta($form_id, '_ucm_acf_invitation_status_field', true);



    if (!$acf_post_type || !$acf_invitation_code || !$acf_invitation_status) {
        wp_die('Form metadata is missing or incomplete.');
    }

    // Prepare CSV headers
    $timezone = wp_timezone();
    $datetime = new DateTime('now', $timezone);
    $filename = 'Invitation_Codes_' . $datetime->format('Y-m-d_H-i-s') . '.csv';

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // Write CSV column headers
    fputcsv($output, ['User ID', 'First Name', 'Last Name', 'E-Mail', 'Invitation Code', 'Status']);

    // Query all posts of the ACF-defined post type
    $posts = get_posts([
        'post_type'      => $acf_post_type,
        'post_status'    => 'any',
        'numberposts'    => -1,
        'fields'         => 'ids',
    ]);

    foreach ($posts as $post_id) {
        $user_id = $post_id;
        $first = get_post_meta($post_id, $acf_first_name, true);
        $last = get_post_meta($post_id, $acf_last_name, true);
        $email = get_post_meta($post_id, $acf_email, true);
        $code = get_post_meta($post_id, $acf_invitation_code, true);
        $status = get_post_meta($post_id, $acf_invitation_status, true);

        fputcsv($output, [$user_id, $first, $last, $email, $code, $status]);
    }

    fclose($output);
    exit;
}

add_action('wp_ajax_ucm_generate_all_qr_codes', 'ucm_generate_all_qr_codes');

function ucm_generate_all_qr_codes() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    $form_id = intval($_POST['form_id'] ?? 0);
    if (!$form_id) {
        wp_send_json_error(['message' => 'Missing form ID']);
    }

    // Get all needed meta keys
    $acf_post_type      = get_post_meta($form_id, '_ucm_acf_post_type', true);
    $acf_first_name     = get_post_meta($form_id, '_ucm_acf_first_name_field', true);
    $acf_last_name      = get_post_meta($form_id, '_ucm_acf_last_name_field', true);
    $acf_uuid_code      = get_post_meta($form_id, '_ucm_acf_unique_uuid_code_field', true);
    $acf_invitation_url = get_post_meta($form_id, '_ucm_acf_invitation_url_field', true);
    $acf_qr_code        = get_post_meta($form_id, '_ucm_acf_qr_code_field', true);
    $cf7_page           = get_post_meta($form_id, '_ucm_cf7_page_field', true);

    if (!$acf_post_type || !$acf_uuid_code || !$acf_invitation_url || !$cf7_page) {
        error_log('Form metadata incomplete');
        wp_send_json_error(['message' => 'Form metadata incomplete']);
    }

    $posts = get_posts([
        'post_type'   => $acf_post_type,
        'post_status' => 'any',
        'numberposts' => -1,
        'fields'      => 'ids',
    ]);

    if (empty($posts)) {
        wp_send_json_error(['message' => 'No posts found for post type']);
    }

    // UUID generator
    if (!function_exists('generate_uuid_v4')) {
        function generate_uuid_v4() {
            $data = random_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }
    }

    // QR code generator
    if (!function_exists('generate_qr_code')) {
        function generate_qr_code($post_id, $unique_url, $acf_first_name, $acf_last_name, $acf_qr_code) {
            if (!class_exists('QRcode')) {
                error_log('QRcode class not found. Make sure the QR library is loaded.');
                return;
            }

            $first_name = get_field($acf_first_name, $post_id) ?: 'first';
            $last_name  = get_field($acf_last_name, $post_id) ?: 'last';

            $filename = sanitize_title($first_name) . '-' . sanitize_title($last_name) . '-' . $post_id . '.png';
            $upload_dir = wp_upload_dir();
            $filepath = $upload_dir['path'] . '/' . $filename;

            // Generate QR code PNG file
            QRcode::png($unique_url, $filepath, QR_ECLEVEL_L, 16);

            if (!file_exists($filepath) || filesize($filepath) < 100) {
                error_log("Failed to generate valid QR code image for post $post_id");
                return;
            }
            error_log("QR code saved to: $filepath");

            // Delete old QR code attachment
            $old_id = get_field($acf_qr_code, $post_id);
            if ($old_id) {
                wp_delete_attachment($old_id, true);
            }

            // Attach new QR code image to media library
            $filetype = wp_check_filetype($filename, null);
            if (empty($filetype['type'])) {
                $filetype['type'] = 'image/png'; // Fallback MIME type
            }

            $attachment = [
                'post_mime_type' => $filetype['type'],
                'post_title'     => 'QR Code ' . $post_id,
                'post_content'   => '',
                'post_status'    => 'inherit',
            ];

            $attach_id = wp_insert_attachment($attachment, $filepath, $post_id);

            if (is_wp_error($attach_id)) {
                error_log('Failed to insert attachment for QR code: ' . $attach_id->get_error_message());
                return;
            }

            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
            wp_update_attachment_metadata($attach_id, $attach_data);
            update_field($acf_qr_code, $attach_id, $post_id);

            error_log("QR code attached to post $post_id: ID $attach_id");
        }
    }

    foreach ($posts as $post_id) {
        $uuid = get_post_meta($post_id, $acf_uuid_code, true);

        if (!$uuid) {
            $uuid = generate_uuid_v4();
            update_post_meta($post_id, $acf_uuid_code, $uuid);
           
        }

        $unique_url = add_query_arg('invitation_uuid', $uuid, site_url($cf7_page));
        update_field($acf_invitation_url, $unique_url, $post_id);
        generate_qr_code($post_id, $unique_url, $acf_first_name, $acf_last_name, $acf_qr_code);
    }

    wp_send_json_success(['message' => 'All UUIDs, URLs, and QR codes generated']);
}

// Helper: get invitation post by UUID in URL -- invitation_uuid should be changed, in order to change it from URL.
function ucm_get_invitation_post() {
    if (empty($_GET['invitation_uuid'])) {
        return false;
    }

    $uuid = sanitize_text_field($_GET['invitation_uuid']);

    // Get all CF7 forms
    $forms = get_posts([
        'post_type'   => 'wpcf7_contact_form',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields'      => 'ids',
    ]);

    foreach ($forms as $form_id) {
        $post_type     = get_post_meta($form_id, '_ucm_acf_post_type', true);
        $uuid_field    = get_post_meta($form_id, '_ucm_acf_unique_uuid_code_field', true);

        if (!$post_type || !$uuid_field) {
            continue;
        }

        $matched = get_posts([
            'post_type'   => $post_type,
            'post_status' => 'any',
            'numberposts' => 1,
            'meta_query'  => [
                [
                    'key'     => $uuid_field,
                    'value'   => $uuid,
                    'compare' => '=',
                ],
            ],
        ]);

        if (!empty($matched)) {
            return $matched[0];
        }
    }

    return false;
}

    // Shortcode to get ACF/post meta field value from invitation post
    function ucm_acf_field_shortcode($atts) {
    $atts = shortcode_atts(['key' => ''], $atts);
    $post = ucm_get_invitation_post();
    if (!$post || empty($atts['key'])) {
    return '';
    }

    $value = get_field($atts['key'], $post->ID);
    if (empty($value)) {
    $value = get_post_meta($post->ID, $atts['key'], true);
    }

    return esc_html($value);
    }
    add_shortcode('ucm_acf_field', 'ucm_acf_field_shortcode');
//=======================================================================================


// Validation for unique code and email in Contact Form 7
add_filter('wpcf7_validate', 'ucm_validate_invitation_form', 20, 2);
function ucm_validate_invitation_form($result, $tags) {
    $submission = WPCF7_Submission::get_instance();
    if (!$submission) return $result;

    $data = $submission->get_posted_data();
    $contact_form = $submission->get_contact_form();
    $form_id = $contact_form->id();

    // Get per-form field names
    $cf7_email_field = get_post_meta($form_id, '_ucm_cf7_email_field', true);
    $cf7_code_field  = get_post_meta($form_id, '_ucm_cf7_unique_code_field', true);

    // ACF post settings
    $acf_post_type         = get_post_meta($form_id, '_ucm_acf_post_type', true);
    $acf_email_field       = get_post_meta($form_id, '_ucm_acf_email_field', true);
    $acf_code_field        = get_post_meta($form_id, '_ucm_acf_invitation_code_field', true);
    $acf_status_field      = get_post_meta($form_id, '_ucm_acf_invitation_status_field', true);

    // Sanitize posted data
    $email = isset($data[$cf7_email_field]) ? sanitize_email($data[$cf7_email_field]) : '';
    $code  = isset($data[$cf7_code_field]) ? sanitize_text_field($data[$cf7_code_field]) : '';

    // Bypass validation if both fields are empty
    if (empty($email) && empty($code)) {
        return $result;
    }

    // Validate: Email is required
    if (empty($email)) {
        $result->invalidate($cf7_email_field, wpcf7_get_message('ucm_email_required'));
        return $result;
    }

    // Validate: Email format
    if (!is_email($email)) {
        $result->invalidate($cf7_email_field, wpcf7_get_message('ucm_email_invalid'));
        return $result;
    }

    // Validate: Code is required
    if (empty($code)) {
        $result->invalidate($cf7_code_field, wpcf7_get_message('ucm_code_required'));
        return $result;
    }

    // Query: Check for matching invitation by email
    $email_posts = get_posts([
        'post_type'   => $acf_post_type,
        'numberposts' => 1,
        'meta_query'  => [[
            'key'     => $acf_email_field,
            'value'   => $email,
            'compare' => '='
        ]],
        'fields' => 'ids',
    ]);

    if (empty($email_posts)) {
        $result->invalidate($cf7_email_field, wpcf7_get_message('ucm_email_not_exist'));
        return $result;
    }

    $email_post_id = $email_posts[0];

    // Query: Check for matching code
    $code_posts = get_posts([
        'post_type'   => $acf_post_type,
        'numberposts' => 1,
        'meta_query'  => [[
            'key'     => $acf_code_field,
            'value'   => $code,
            'compare' => '='
        ]],
        'fields' => 'ids',
    ]);

    if (empty($code_posts)) {
        $result->invalidate($cf7_code_field, wpcf7_get_message('ucm_code_invalid'));
        return $result;
    }

    $code_post_id = $code_posts[0];

    // Validate: Code matches email
    if ($code_post_id !== $email_post_id) {
        $result->invalidate($cf7_code_field, wpcf7_get_message('ucm_code_mismatch'));
        return $result;
    }

    // Validate: Code is unused
    $status = get_post_meta($email_post_id, $acf_status_field, true);
    if (strtolower($status) !== 'unused') {
        $result->invalidate($cf7_code_field, wpcf7_get_message('ucm_code_used'));
        return $result;
    }

    return $result;
}


add_action('wpcf7_mail_sent', function($contact_form) {
    $submission = WPCF7_Submission::get_instance();
    if (!$submission) return;

    $data = $submission->get_posted_data();
    $form_id = $contact_form->id();

    $cf7_email_field = get_post_meta($form_id, '_ucm_cf7_email_field', true);
    $cf7_code_field  = get_post_meta($form_id, '_ucm_cf7_unique_code_field', true);

    $acf_post_type    = get_post_meta($form_id, '_ucm_acf_post_type', true);
    $acf_email_field  = get_post_meta($form_id, '_ucm_acf_email_field', true);
    $acf_code_field   = get_post_meta($form_id, '_ucm_acf_invitation_code_field', true);
    $acf_status_field = get_post_meta($form_id, '_ucm_acf_invitation_status_field', true);

    $email = sanitize_email($data[$cf7_email_field] ?? '');
    $code  = sanitize_text_field($data[$cf7_code_field] ?? '');

    if (!$email || !$code) return;

    $posts = get_posts([
        'post_type'   => $acf_post_type,
        'numberposts' => 1,
        'meta_query'  => [
            'relation' => 'AND',
            [
                'key'   => $acf_email_field,
                'value' => $email,
            ],
            [
                'key'   => $acf_code_field,
                'value' => $code,
            ],
        ],
        'fields' => 'ids',
    ]);

    if (!empty($posts)) {
        $post_id = $posts[0];
        update_post_meta($post_id, $acf_status_field, 'used');
    }
});



    // Register custom messages with translations
    add_filter('wpcf7_messages', function($messages) {
        $messages['ucm_email_required'] = [
        'description' => __('Error when email is missing', 'user-code-manager'),
        'default' => __('Email address is required.', 'user-code-manager')
        ];
        $messages['ucm_email_invalid'] = [
        'description' => __('Error when email is invalid', 'user-code-manager'),
        'default' => __('Email address is invalid.', 'user-code-manager')
        ];
        $messages['ucm_email_not_exist'] = [
        'description' => __('Error when email not found in system', 'user-code-manager'),
        'default' => __('Email does not exist.', 'user-code-manager')
        ];
        $messages['ucm_code_required'] = [
        'description' => __('Error when code is missing', 'user-code-manager'),
        'default' => __('Unique code is required.', 'user-code-manager')
        ];
        $messages['ucm_code_invalid'] = [
        'description' => __('Error when code does not exist', 'user-code-manager'),
        'default' => __('Code does not exist.', 'user-code-manager')
        ];
        $messages['ucm_code_mismatch'] = [
        'description' => __('Error when code does not belong to user', 'user-code-manager'),
        'default' => __('Invitation was not sent to this email.', 'user-code-manager')
        ];
        $messages['ucm_code_used'] = [
        'description' => __('Error when code is already used', 'user-code-manager'),
        'default' => __('This code has already been used.', 'user-code-manager')
        ];

        return $messages;
    });

// JS FILE FOR FUNCTIONS ETC 
add_action('admin_enqueue_scripts', 'ucm_enqueue_admin_scripts');
function ucm_enqueue_admin_scripts() {
    wp_enqueue_script('ucm-admin-js', plugin_dir_url(__FILE__) . 'js/scripts.js', ['jquery'], '1.0', true);
    
    wp_localize_script('ucm-admin-js', 'ucm_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ucm_admin_nonce')
    ]);
}
    ?>