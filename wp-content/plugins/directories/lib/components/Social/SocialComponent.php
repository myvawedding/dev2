<?php
namespace SabaiApps\Directories\Component\Social;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\Field;

class SocialComponent extends AbstractComponent implements
    IMedias,
    Field\ITypes,
    Field\IWidgets,
    Field\IFilters,
    Field\IRenderers
{
    const VERSION = '1.2.23', PACKAGE = 'directories';
    
    public static function description()
    {
        return 'Enables social media account fields and display content fetched from social media sites.';
    }
    
    public function socialMediaNames()
    {
        $names = ['facebook', 'twitter', 'googleplus', 'pinterest', 'tumblr',
            'linkedin', 'flickr', 'youtube', 'instagram', 'rss',
        ];
        if ($custom = $this->_application->Filter('social_medias', [])) {
            $names = array_merge($names, array_keys($custom));
        }
        return $names;
    }
    
    public function socialMediaInfo($name)
    {
        switch ($name) {
            case 'facebook': 
                return array(
                    'type' => 'textfield',
                    'label' => 'Facebook',
                    'icon' => 'fab fa-facebook-square',
                    //'regex' => '/^https?:\/\/((w{3}\.)?)facebook.com\/.*/i',
                    'default' => 'facebook',
                    'placeholder' => __('Enter Facebook username.', 'directories'),
                );
            case 'twitter': 
                return array(
                    'type' => 'textfield',
                    'label' => 'Twitter',
                    'icon' => 'fab fa-twitter-square',
                    //'regex' => '/^https?:\/\/twitter\.com\/(#!\/)?[a-z0-9_]+[\/]?$/i',
                    'default' => 'twitter',
                    'placeholder' => __('Enter Twitter username.', 'directories'),
                );
            case 'googleplus': 
                return array(
                    'type' => 'textfield',
                    'label' => 'Google+',
                    'icon' => 'fab fa-google-plus',
                    'default' => '+googleplus',
                    'placeholder' => __('Enter Google+ username.', 'directories'),
                );
            case 'pinterest': 
                return array(
                    'type' => 'textfield',
                    'label' => 'Pinterest',
                    'icon' => 'fab fa-pinterest',
                    'default' => 'pinterest',
                    'placeholder' => __('Enter Pinterest username.', 'directories'),
                );
            case 'instagram': 
                return array(
                    'type' => 'textfield',
                    'label' => 'Instagram',
                    'icon' => 'fab fa-instagram',
                    'default' => 'instagram',
                    'placeholder' => __('Enter Instagram username. Prefix with "#" if hashtag.', 'directories'),
                );
            case 'youtube': 
                return array(
                    'type' => 'textfield',
                    'label' => 'YouTube',
                    'icon' => 'fab fa-youtube',
                    'default' => 'YouTube',
                    'placeholder' => __('Enter YouTube username.', 'directories'),
                );
            case 'tumblr': 
                return array(
                    'label' => 'Tumblr',
                    'icon' => 'fab fa-tumblr-square',
                    'default' => 'http://staff.tumblr.com/',
                );
            case 'linkedin': 
                return array(
                    'label' => 'LinkedIn',
                    'icon' => 'fab fa-linkedin',
                    'default' => 'https://www.linkedin.com/company/linkedin',
                );
            case 'flickr': 
                return array(
                    'label' => 'Flickr',
                    'icon' => 'fab fa-flickr',
                    'default' => 'https://www.flickr.com/people/flickr',
                );
            case 'rss': 
                return array(
                    'label' => 'RSS',
                    'icon' => 'fas fa-rss-square',
                    'default' => $this->_application->getPlatform()->getSiteUrl(),
                );
            default:
                $custom = $this->_application->Filter('social_medias', []);
                if (isset($custom[$name])) {
                    return $custom[$name];
                }
        }
    }

    public function socialMediaUrl($name, $value)
    {
        if (strpos($value, 'https://') === 0
            || strpos($value, 'http://') === 0
        ) return $value;

        switch ($name) {
            case 'facebook':
                if (strpos($value, '!') === 0) {
                    $value = substr($value, 1);
                }
                return 'https://www.facebook.com/' . rawurlencode($value);
            case 'twitter':
                if (strpos($value, '#') === 0) {
                    return 'https://twitter.com/hashtag/' . rawurlencode(substr($value, 1));
                }
                return 'https://twitter.com/' . rawurlencode($value);
            case 'googleplus':
                if (strpos($value, '#') === 0) {
                    return 'https://plus.google.com/u/0/s/' . rawurlencode($value);
                }
                return 'https://plus.google.com/u/0/' . rawurlencode($value);
            case 'pinterest':
                return 'https://www.pinterest.com/' . rawurlencode($value);
            case 'instagram':
                if (strpos($value, '#') === 0) {
                    return 'https://instagram.com/explore/tags/' . rawurlencode(substr($value, 1));
                }
                return 'https://instagram.com/' . rawurlencode($value);
            case 'youtube':
                return 'https://www.youtube.com/user/' . rawurlencode($value);
            case 'tumblr':
            case 'linkedin':
            case 'flickr':
            case 'rss':
                return $value;
            default:
                return $this->_application->Filter('social_media_url', $value, [$name, $value]);
        }
    }

    public function fieldGetTypeNames()
    {
        return array('social_accounts');
    }

    public function fieldGetType($name)
    {
        return new FieldType\AccountsFieldType($this->_application, $name);
    }

    public function fieldGetWidgetNames()
    {
        return array('social_accounts');
    }

    public function fieldGetWidget($name)
    {
        return new FieldWidget\AccountsFieldWidget($this->_application, $name);
    }
    
    public function fieldGetFilterNames()
    {
        return array('social_accounts');
    }

    public function fieldGetFilter($name)
    {
        return new FieldFilter\AccountsFieldFilter($this->_application, $name);
    }
    
    public function fieldGetRendererNames()
    {
        return ['social_accounts', 'social_twitter_feed', 'social_facebook_page', 'social_facebook_messenger_link'];
    }

    public function fieldGetRenderer($name)
    {
        switch ($name) {
            case 'social_accounts':
                return new FieldRenderer\AccountsFieldRenderer($this->_application, $name);
            case 'social_twitter_feed':
                return new FieldRenderer\TwitterFeedFieldRenderer($this->_application, $name);
            case 'social_facebook_page':
                return new FieldRenderer\FacebookPageFieldRenderer($this->_application, $name);
            case 'social_facebook_messenger_link':
                return new FieldRenderer\FacebookMessengerLinkFieldRenderer($this->_application, $name);
        }
    }
}
