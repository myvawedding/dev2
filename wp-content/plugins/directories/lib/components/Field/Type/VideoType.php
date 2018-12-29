<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class VideoType extends AbstractType implements IHumanReadable, IVideo
{
    protected static $_videoData = [];
    
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Video', 'directories'),
            'default_widget' => 'video',
            'default_renderer' => 'video',
            'default_settings' => [],
            'icon' => 'fas fa-video',
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'id' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'notnull' => true,
                    'was' => 'id',
                    'length' => 20,
                ),
                'provider' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 20,
                    'notnull' => true,
                    'was' => 'provider',
                ),
                'thumbnail_url' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 255,
                    'notnull' => true,
                    'was' => 'thumbnail_url',
                ),
                'title' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 255,
                    'notnull' => true,
                    'was' => 'title',
                ),
            ),
            'indexes' => array(
                'id' => array(
                    'fields' => array('id' => array('sorting' => 'ascending')),
                    'was' => 'id',
                ),
            ),
        );
    }
    
    public function fieldTypeOnSave(IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        foreach (array_keys($values) as $i) {
            if (!is_array($values[$i])
                || !is_string($values[$i]['id'])
                || strlen($values[$i]['id']) === 0
                || empty($values[$i]['provider'])
            ) {
                unset($values[$i]);
                continue;
            }

            if (empty($values[$i]['thumbnail_url'])
                || empty($values[$i]['title'])
            ) {
                try {
                    $video = $this->_getVideoData($values[$i]['provider'], $values[$i]['id']);
                    $values[$i]['thumbnail_url'] = $video['thumbnail_url'];
                    $values[$i]['title'] = $video['title'];
                } catch (\Exception $e) {
                    $this->_application->LogError($e->getMessage());
                }
            }
        }

        return array_values($values);
    }
    
    public function fieldHumanReadableText(IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return '';
        
        $ret = [];
        foreach ($values as $value) {
            switch ($value['provider']) {
                case 'youtube':
                    $ret[] = 'https://youtu.be/' . $value['id'];
                    break;
                case 'vimeo':
                    $ret[] = 'https://vimeo.com/' . $value['id'];
                    break;
            }
        }
        return implode(isset($separator) ? $separator : PHP_EOL, $ret);
    }
    
    protected function _getVideoData($provider, $id)
    {
        if (isset(self::$_videoData[$provider][$id])) return self::$_videoData[$provider][$id];
        
        switch ($provider) {
            case 'youtube':
                $url = 'http://www.youtube.com/oembed?url=' . rawurlencode('http://www.youtube.com/watch?v=' . $id) . '&format=json';
                break;
            case 'vimeo':
                $url = 'http://vimeo.com/api/oembed.json?url=' . rawurlencode('http://vimeo.com/' . $id);
                break;
            default:
                throw new Exception\RuntimeException('Invalid video provider.');
        }
        
        $result = $this->_application->getPlatform()->remoteGet($url);
        if (!$result = json_decode($result, true)) {
            throw new Exception\RuntimeException('Failed decoding video JSON return from URL: ' . $url);
        }
        self::$_videoData[$provider][$id] = $result;
        
        return self::$_videoData[$provider][$id];
    }
}