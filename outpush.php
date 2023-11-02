<?php
/*
Plugin Name: Push Notifications
Description: Send push notifications using Outpush API.
Version:     1.31
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
			
            if (isset($_POST['push_notifications_test'])) {
                $test_url = sanitize_text_field($_POST['test_url']);
                $test_title = sanitize_text_field($_POST['test_title']);
                $test_thumbnail = esc_url($_POST['test_thumbnail']);

                $test_notification_data = array(
                    'url' => $test_url,
                    'title' => $test_title,
                    'thumb' => $test_thumbnail,
                    'scheduleDate' => '', // Laissez la date vide pour utiliser la date actuelle + 15 minutes
                );

                $test_result = send_push_notification($test_notification_data, $data);

                echo '<div class="updated"><p>Response:</p>' . $test_result . '</div>';
            }
			$last_result = get_option('push_notification_last_result');
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
			<hr/>
			<p>Last Notification Result:</p>
			<p><?php echo $last_result; ?></p>
        </form>
    </div>
    <?php
}


function trimTextRecursive($text, $maxLength = 45) {
	$text = trim($text);
    if (mb_strlen($text) > ($maxLength)) {
        $lastSpacePos = mb_strrpos($text, ' ');
        if ($lastSpacePos) {
            $text = mb_substr($text, 0, $lastSpacePos);
            $text = trimTextRecursive($text, $maxLength);
        }
    }
    return $text;
}


function send_push_notification($notification_data, $config_data) {
    $title = $notification_data['title'];
    $meta = 'Lire l\'article';

    if (strlen($title) <= 45) {
        $meta = 'Lire l\'article';
    } else {
        if (mb_strpos($title, ':')) {
            list($title, $meta) = explode(':', $title, 2);
			if (strlen($title) > 43) {
				$title = trimTextRecursive($title, 43) . '...';
			}
			if ($strlen($meta) > 43) {
				$meta = trimTextRecursive($meta, 43) . '...';
			}
        } else {
            $title = trimTextRecursive($title, 43) . '...';
            $meta = 'Lire l\'article';
        }
    }

    $notification_data['title'] = $title;
    $notification_data['meta'] = $meta;

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
		update_option('push_notification_last_result', '<p>Authentication:<br><pre>' . wp_unslash(json_encode(json_decode($auth_response, true), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . '</pre></p>');
        error_log('<p>Authentication:<br><code>' . $auth_response . '</code></p>');
		return '<p>Authentication:<br><code>' . $auth_response . '</code></p>';
    }
	
    if (isset($auth_data['tokens']['access']['token'])) {
        $access_token = $auth_data['tokens']['access']['token'];

        $schedule_date = empty($notification_data['scheduleDate']) ? date('c', strtotime('+45 minutes')) : $notification_data['scheduleDate'];

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

		update_option('push_notification_last_result', '<p>Authentication:<br><pre>' .  wp_unslash(json_encode(json_decode($auth_response, true), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . '</pre><br><br>Campaign:<br><pre>' . wp_unslash(json_encode(json_decode($campaign_response, true), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . '</pre><br><br>Used data:<br><pre>' . wp_unslash(json_encode($campaign_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . '</pre><br></p>');
		error_log('<p>Authentication:<br><code>' . $auth_response . '</code><br><br>Campaign:<br><code>' . $campaign_response . '</code><br><br>Used data:<br><code>' . var_dump($campaign_data) . '</code><br></p>');
		return '<p>Authentication:<br><code>' . $auth_response . '</code><br><br>Campaign:<br><code>' . $campaign_response . '</code><br><br>Used data:<br><code>' . var_dump($campaign_data) . '</code><br></p>';
    }
}


function send_push_notification_on_publish($new_status, $old_status, $post) {
	$debug = true;
	
	$data = get_option('push_notifications_data');
	
	if (($new_status != 'publish' || $old_status == 'publish') && !$debug) {
		return;
	}
	
    $notification_data = array(
        'url' => get_permalink($post),
        'title' => $post->post_title,
        'thumb' => get_the_post_thumbnail_url($post, 'medium'),
        'scheduleDate' => date('c', strtotime('+45 minutes')),
    );
    
	// update_option('push_notification_last_result', 'triggered ' . intval(rand(0, 100)));
    $notification_successful = send_push_notification($notification_data, $data);
    echo $notification_successful;
}

// ToDo: Mettre 0 au lieu de 45 minutes

add_action('transition_post_status', 'send_push_notification_on_publish', 10, 3);
