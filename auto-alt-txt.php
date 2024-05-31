<?php
/*
Plugin Name: Auto Alt Text with GPT-4 Vision
Description: Automatically adds alt text to images using GPT-4 Vision API.
Version: 1.0
Author: Paramatma Khadka
*/

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Main function to call GPT-4 Vision API and get alt text
function generate_alt_text($image_url) {
    $api_key = get_option('auto_alt_text_api_key', '');
    $api_endpoint = get_option('auto_alt_text_api_endpoint', '');

    if (empty($api_key) || empty($api_endpoint)) {
        return '';
    }

    $response = wp_remote_post($api_endpoint, [
        'body' => json_encode(['url' => $image_url]),
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
    ]);

    if (is_wp_error($response)) {
        return '';
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['alt_text'])) {
        return sanitize_text_field($data['alt_text']);
    }

    return '';
}

// Hook into the media upload process
add_action('add_attachment', 'auto_add_image_alt_text');

function auto_add_image_alt_text($post_ID) {
    $attachment = get_post($post_ID);

    // Check if the attachment is an image
    if (wp_attachment_is_image($post_ID)) {
        $image_url = wp_get_attachment_url($post_ID);
        $alt_text = generate_alt_text($image_url);

        // Update the image's alt text
        if (!empty($alt_text)) {
            update_post_meta($post_ID, '_wp_attachment_image_alt', $alt_text);
        }
    }
}

// Register settings page
add_action('admin_menu', 'auto_alt_text_settings_page');

function auto_alt_text_settings_page() {
    add_options_page(
        'Auto Alt Text Settings',
        'Auto Alt Text',
        'manage_options',
        'auto-alt-text',
        'auto_alt_text_settings_page_html'
    );
}

// Settings page HTML
function auto_alt_text_settings_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['submit'])) {
        update_option('auto_alt_text_api_key', sanitize_text_field($_POST['api_key']));
        update_option('auto_alt_text_api_endpoint', sanitize_text_field($_POST['api_endpoint']));
    }

    $api_key = get_option('auto_alt_text_api_key', '');
    $api_endpoint = get_option('auto_alt_text_api_endpoint', '');

    ?>
    <div class="wrap">
        <h1>Auto Alt Text Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">API Key</th>
                    <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" size="50"></td>
                </tr>
                <tr valign="top">
                    <th scope="row">API Endpoint</th>
                    <td><input type="text" name="api_endpoint" value="<?php echo esc_attr($api_endpoint); ?>" size="50"></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
?>
