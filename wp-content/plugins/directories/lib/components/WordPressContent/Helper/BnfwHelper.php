<?php
namespace SabaiApps\Directories\Component\WordPressContent\Helper;

use SabaiApps\Directories\Application;

class BnfwHelper
{
    public function notificationName(Application $application, $name, $slug)
    {
        if (strpos($slug, 'drts-') === 0
            && ($parts = explode('-', $slug))
            && isset($parts[2])
            && ($post_types = $application->getComponent('WordPressContent')->getPostTypes())
            && isset($post_types[$parts[2]])
            && ($post_type = get_post_type_object($parts[2]))
            && ($notification = $application->WordPressContent_Notifications_impl($parts[1], true))
        ) {
            $name = $post_type->labels->singular_name . ' ' . $notification->wpNotificationInfo('label');
        }
        return $name;
    }

    public function shortcodes(Application $application, $message, $notification, $postId, $engine)
    {
        if (strpos($notification, 'drts-') === 0
            || (!empty($postId) && ($post_type = get_post_type($postId)) && $this->_isValidPostType($application, $post_type))
        ) {
            $message = $application->WordPressContent_Notifications_shortcode($message, $postId, $engine);
        }
        return $message;
    }

    public function afterNotificationOptions(Application $application, $postType, $label, $setting)
    {
        if ($this->_isValidPostType($application, $postType)
            && ($bundle = $application->Entity_Bundle($postType))
            && ($options = $application->WordPressContent_Notifications_options($bundle))
        ) {
            foreach (array_keys($options) as $k) {
                echo '<option value="' . $k . '" ' . selected($k, $setting['notification']) . '>'
                    . $application->H("'" . $label . "' " . $options[$k])
                    . '</option>';
            }
        }
    }

    protected function _isValidPostType(Application $application, $postType)
    {
        $post_types = $application->getComponent('WordPressContent')->getPostTypes();
        return isset($post_types[$postType]);
    }
}