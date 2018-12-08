<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Framework\User\AbstractIdentity;

class UserIdentityHtmlHelper
{
    protected $_thumbnails = [];
    
    public function help(Application $application, AbstractIdentity $identity = null, $format = '', $thumbnailOnly = false)
    {
        switch ($format) {
            case 'link':
                return $this->_link($application, $identity);
            case 'link_thumb_s':
                return $this->_linkWithThubmnail($application, $identity, 'sm', $thumbnailOnly);
            case 'link_thumb':
                return $this->_linkWithThubmnail($application, $identity, '', $thumbnailOnly);
            case 'thumb_s':
                return $this->_thumbnail($application, $identity, 'sm');
            case 'thumb':
                return $this->_thumbnail($application, $identity, '');
            default:
                return isset($identity) ? '' : [
                    'link' => __('Link', 'directories'),
                    'thumb_s' => __('Thumbnail (small)', 'directories'),
                    'thumb' => __('Thumbnail', 'directories'),
                    'link_thumb_s' => __('Thumbnail (small) with link', 'directories'),
                    'link_thumb' => __('Thumbnail with link', 'directories'),
                ];
        }
    }
    
    public static function setThumbnailSize(array $size)
    {
        self::$_thumbnailSize = $size + self::$_thumbnailSize;
    }
    
    public function _link(Application $application, AbstractIdentity $identity)
    {
        $name = $identity->name;
        if ($identity->isAnonymous()) {
            if ($name === null) $name = __('Guest', 'drts');
            return $this->_doLink($application, $identity, $application->H($name), 'drts-user drts-user-anonymous', $identity->url, 'nofollow external');
        }

        $class = 'drts-user drts-user-registered drts-user-' . $identity->id;
        return $this->_doLink($application, $identity, $application->H($name), $class, $this->_url($application, $identity), 'nofollow');
    }

    public function _linkWithThubmnail(Application $application, AbstractIdentity $identity, $size, $thumbnailOnly = false)
    {
        $content = $this->_thumbnail($application, $identity, $size);
        if (!$thumbnailOnly) $content .= '<span>' . $application->H($identity->name) . '</span>';
        
        if ($identity->isAnonymous()) {
            return $this->_doLink($application, $identity, $content, 'drts-user drts-user-anonymous', $identity->url, 'nofollow external');
        }

        $class = 'drts-user drts-user-registered drts-user-' . $identity->id;
        return $this->_doLink($application, $identity, $content, $class, $this->_url($application, $identity), 'nofollow');
    }
    
    protected function _url(Application $application, AbstractIdentity $identity)
    {
        return $identity->url ? $identity->url : null;
    }
        
    protected function _doLink(Application $application, AbstractIdentity $identity, $content, $class, $url, $rel = '')
    {
        $class = $application->H($class);
        $attr = [
            'href' => $url,
            'target' => '_blank',
            'rel' => $rel . ' noopener',
            'class' => $class,
        ];
        $attr = $application->Filter('core_user_link_attr', $attr, [$identity]);
        $tag = empty($attr['href']) ? 'span' : 'a';
        return '<' . $tag . $application->Attr($attr) . '>' . $content . '</' . $tag . '>';
    }
    
    protected function _thumbnail(Application $application, AbstractIdentity $identity, $size)
    {        
        if ($identity->isAnonymous()) return $this->_getThumbnail($application, $identity, $size);
        
        $id = $identity->id;
        if (!isset($this->_thumbnails[$id][$size])) {
            $this->_thumbnails[$id][$size] = $this->_getThumbnail($application, $identity, $size);
        }
        return $this->_thumbnails[$id][$size];
    }
    
    protected function _getThumbnail(Application $application, AbstractIdentity $identity, $size)
    {        
        if (!$url = $this->_getGravatarUrl($application, $identity, $size)) return '';

        return sprintf(
            '<img src="%1$s" alt="%2$s" class="drts-user-thumbnail drts-icon %3$s" />',
            $application->H($url),
            $application->H($identity->name),
            $size === 'sm' ? 'drts-icon-sm' : ''
        );
    }
    
    protected function _getGravatarUrl(Application $application, AbstractIdentity $identity, $size)
    {
        if (!$identity->email) return;
   
        return $application->GravatarUrl($identity->email, $size, $identity->gravatar_default, $identity->gravatar_rating);
    }
}