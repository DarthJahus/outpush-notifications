# Outpush Notifications plugin for WordPress

Outpush Notifications is a WordPress plugin designed to integrate your WordPress website with the Outpush API, allowing you to send push notifications to your subscribers. This plugin simplifies the process of setting up and sending push notifications directly from your WordPress dashboard.

## Features

- **Easy configuration**: Set up your Outpush API credentials and start sending push notifications in just a few steps.
- **Test notifications**: Test your notifications before sending them to ensure they look exactly as you want.
- **Automatic notifications**: Automatically send push notifications when new content is published on your website.

## Installation

1. Download the `outpush-notifications.zip` file from the releases section.
2. Go to your WordPress Dashboard, navigate to **Plugins > Add new**, and click the **Upload plugin** button.
3. Choose the downloaded zip file and click **Install now**.
4. Once installed, activate the plugin.

## Configuration

To configure the plugin, follow these steps:

1. Go to your WordPress Dashboard.
2. Navigate to **Settings > Outpush Notifications**.
3. Fill in the following fields with your Outpush API credentials:
   - **Website URL**: The URL of your website.
   - **Email**: Your Outpush account email.
   - **Password**: Your Outpush account password.
   - **Campaign Name**: A name for your notification campaign.
   - **Favicon URL**: The URL of your website's favicon (optional).
4. Click **Save Settings** to store your configuration.

### Automatic Notifications
1. When a new post is published on your website, the plugin automatically prepares a push notification for that post.
2. This notification includes the post's title, a link to the post and a thumbnail.
3. The plugin schedules this notification to be sent out 5 minutes after the post is published, ensuring timely delivery to your subscribers.

### Testing Notifications
When you test a notification using the Outpush Notifications plugin, it's important to note that the test campaign is scheduled to be sent 1 hour from the current time. This delay allows you to have enough time to review and make any necessary adjustments before the notification is actually sent out to your audience. Here's how it works:

1. In the plugin's settings page, fill in the **Test URL**, **Test Title**, and **Test Thumbnail URL** for your test notification.
2. When you click **Test Notification**, the plugin creates a push notification campaign scheduled to be sent 1 hour from the current time.
3. This 1-hour window provides you with a buffer period to review the notification and ensure everything is set up correctly. This distinction in timing ensures that while testing, you have ample time to review and modify your notifications, whereas the automatic notifications are sent promptly to keep your audience informed about new content in a timely manner.
4. Debug information is displayed at the end of the plugin's settings page.

## Support

If you encounter any issues or have questions, please [open an issue](https://github.com/DarthJahus/outpush-notifications/issues) on GitHub.

---

This plugin is open-source and contributions are welcome. Feel free to submit pull requests or suggest new features.

Please note that this plugin requires an active Outpush account and API access. Ensure your Outpush account is properly set up and you have the necessary permissions to use the API.
