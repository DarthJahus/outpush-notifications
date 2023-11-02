<?php
/*
Plugin Name: Push Notifications
Description: end push notifications using outpush API.
Version:     0.18
Author:      Jahus
Author URI:  https://jahus.net
License:     Unlicense
*/

// Create a menu page for plugin configuration
function push_notifications_menu() {
    add_options_page(
        'Push Notifications',
        'Push Notifications',
        'manage_options',
        'push-notifications-settings',
        'push_notifications_settings_page'
    );
}

add_action('admin_menu', 'push_notifications_menu');


function push_notifications_settings_page() {
    // Retrieve configuration data from WordPress options
    $data = get_option('push_notifications_data');
    ?>

    <div class="wrap">
        <h2>Push Notifications Settings</h2>
        <p>Please provide your Push Notifications API credentials below.</p>

        <form method="post" action="">
            <?php
            // Handle form submission and save configuration data
            if (isset($_POST['push_notifications_submit'])) {
                $data = array(
                    'website' => sanitize_text_field($_POST['website']),
                    'email' => sanitize_email($_POST['email']),
                    'password' => sanitize_text_field($_POST['password']),
                    'name' => sanitize_text_field($_POST['name']),
                    'favicon' => esc_url($_POST['favicon']),
                );
                update_option('push_notifications_data', $data);
                echo '<div class="updated"><p>Settings saved.</p></div>';
            }
			
            // Traitement du test de notification
            if (isset($_POST['push_notifications_test'])) {
                $test_url = sanitize_text_field($_POST['test_url']);
                $test_title = sanitize_text_field($_POST['test_title']);
                $test_thumbnail = esc_url($_POST['test_thumbnail']);

                // Appel à send_push_notification pour le test
                $test_notification_data = array(
                    'url' => $test_url,
                    'title' => $test_title,
                    'thumb' => $test_thumbnail,
                    'scheduleDate' => '', // Laissez la date vide pour utiliser la date actuelle + 15 minutes
                );

                $test_result = send_push_notification($test_notification_data, $data);

                echo '<div class="updated"><p>Response:</p>' . $test_result . '</div>';
            }
            ?>

            <table class="form-table">
                <tr>
                    <th scope="row">Website URL:</th>
                    <td>
                        <input type="text" name="website" value="<?php echo esc_attr($data['website']); ?>" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Email:</th>
                    <td>
                        <input type="email" name="email" value="<?php echo esc_attr($data['email']); ?>" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Password:</th>
                    <td>
                        <input type="password" name="password" value="<?php echo esc_attr($data['password']); ?>" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Campaign Name:</th>
                    <td>
                        <input type="text" name="name" value="<?php echo esc_attr($data['name']); ?>" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Favicon URL:</th>
                    <td>
                        <input type="text" name="favicon" value="<?php echo esc_attr($data['favicon']); ?>">
                    </td>
                </tr>
                <!-- Champs de test -->
                <tr>
                    <th scope="row">Test URL:</th>
                    <td>
                        <input type="text" name="test_url" value="">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Test Title:</th>
                    <td>
                        <input type="text" name="test_title" value="">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Test Thumbnail URL:</th>
                    <td>
                        <input type="text" name="test_thumbnail" value="">
                    </td>
                </tr>
            </table>
            <input type="submit" name="push_notifications_submit" class="button-primary" value="Save Settings">
            <input type="submit" name="push_notifications_test" class="button" value="Test Notification">
        </form>
    </div>
    <?php
}


function trimTextRecursive($text, $maxLength = 45) {
	$text = trim($text);
    if (mb_strlen($text) > ($maxLength - 3)) {
        $lastSpacePos = mb_strrpos($text, ' ');
        if ($lastSpacePos !== false) {
            $text = mb_substr($text, 0, $lastSpacePos);
            $text = trimTextRecursive($text, $maxLength);
        }
    }
    return $text . '...';
}


function send_push_notification($notification_data, $config_data) {
    // Arrange the title and meta data
    $title = $notification_data['title'];
    $meta = 'Lire l\'article';

    if (strlen($title) <= 45) {
        $meta = 'Lire l\'article';
    } else {
        if (mb_strpos($title, ':') !== false) {
            list($title, $meta) = explode(':', $title, 2);
			if (strlen($title) > 45) {
				$title = trimTextRecursive($title);
			}
			if ($strlen($meta) > 45) {
				$meta = trimTextRecursive($meta);
			}
        } else {
            $title = trimTextRecursive($title);
            $meta = 'Lire l\'article';
        }
    }

    // Update the notification data with arranged title and meta
    $notification_data['title'] = $title;
    $notification_data['meta'] = $meta;

    // Authenticate with Outpush to get the access token
    $login_endpoint = 'https://publisher-api.pushmaster-in.xyz/v1/auth/login';
    $login_data = array(
        'email' => $config_data['email'],
        'password' => $config_data['password'],
    );

    $req = wp_safe_remote_post($login_endpoint, array(
        'body' => json_encode($login_data),
        'headers' => array('Content-Type' => 'application/json'),
    ));

    $auth_response = wp_remote_retrieve_body($req);
    $auth_data = json_decode($auth_response, true);
	
	if (is_wp_error($req)) {
        error_log('Error with login: ' . $req->get_error_message());
		return '<p>Authentication:<br><code>' . $auth_response . '</code></p>';
    }
	
    if (isset($auth_data['tokens']['access']['token'])) {
        $access_token = $auth_data['tokens']['access']['token'];

        // Determine the schedule date
        $schedule_date = empty($notification_data['scheduleDate']) ? date('c', strtotime('+45 minutes')) : $notification_data['scheduleDate'];

        // Create the campaign
        $campaign_endpoint = 'https://publisher-api.pushmaster-in.xyz/v1/campaigns/';
        $campaign_data = array(
            'websiteUrl' => $config_data['website'],
            'scheduleDate' => $schedule_date,
            'campaignName' => $config_data['name'],
            'notification' => array(
                'title' => substr($notification_data['title'], 0, 45),
                'body' => substr($notification_data['meta'], 0, 45),
                'url' => $notification_data['url'],
                'imageUrl' => $notification_data['thumb'],
                'iconUrl' => $config_data['favicon'],
            ),
        );

        $req = wp_safe_remote_post($campaign_endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($campaign_data),
        ));
		
		$campaign_response = wp_remote_retrieve_body($req);

        if (is_wp_error($req)) {
            error_log('Error with campaign: ' . $req->get_error_message());
            return '<p>Authentication:<br><code>' . $auth_response . '</code><br><br>Campaign:<br><code>' . $campaign_response . '</code><br><br>Used data:<br><code>' . var_dump($campaign_data) . '</code><br></p>';
        } else {
            return '<p>Authentication:<br><code>' . $auth_response . '</code><br><br>Campaign:<br><code>' . $campaign_response . '</code><br><br>Used data:<br><code>' . var_dump($campaign_data) . '</code><br></p>';
        }
    }
}


function send_push_notification_on_publish($ID, $post) {
    $title = $post->post_title;

    $notification_data = array(
        'url' => get_permalink($post),
        'title' => $title,
        'thumb' => get_the_post_thumbnail_url($post),
        'scheduleDate' => date('c', strtotime('+45 minutes')), // Schedule for 15 minutes from now
    );

    $data = get_option('push_notifications_data');
    $notification_successful = send_push_notification($notification_data, $data);

    echo $notification_successful;
}

// ToDo: Mettre 0 au lieu de 45 minutes

add_action('publish_post', 'send_push_notification_on_publish', 10, 2);
