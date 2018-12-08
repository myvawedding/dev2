<?php
namespace SabaiApps\Directories\Component\Field\Renderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class VideoRenderer extends AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return array(
            'field_types' => array($this->_name),
            'default_settings' => array(
                'columns' => 1,
            ),
            'separatable' => false,
        );
    }

    protected function _fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        return array(
            'columns' => array(
                '#title' => __('Number of columns', 'directories'),
                '#type' => 'slider',
                '#min_value' => 1,
                '#max_value' => 4,
                '#integer' => true,
                '#default_value' => $settings['columns'],
            ),
        );
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $ret = array('<div class="drts-row">');
        $width = 12 / $settings['columns'];
        foreach ($values as $value) {
            $ret[] = '<div class="drts-col-md-' . $width . '"><div class="drts-field-video">';
            switch ($value['provider']) {
                case 'vimeo':
                    $ret[] = $this->_renderVimeoVideo($field, $settings, $value);
                    break;
                default:
                    $ret[] = $this->_renderYouTubeVideo($field, $settings, $value);
            }
            $ret[] = '</div></div>';
        }
        $ret[] = '</div>';
        return implode(PHP_EOL, $ret);
    }
    
    protected function _renderVimeoVideo(IField $field, array $settings, array $value)
    {
        return sprintf('
            <iframe src="//player.vimeo.com/video/%s?api=1&byline=0&portrait=0&title=0&mute=1&loop=1&autoplay=0" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>',
            $this->_application->H($value['id'])
        );
    }
    
    protected function _renderYoutubeVideo(IField $field, array $settings, array $value)
    {
        return sprintf('
            <iframe src="//www.youtube.com/embed/%1$s?enablejsapi=1&controls=0&fs=0&iv_load_policy=3&rel=0&showinfo=0&loop=1&playlist=%1$s&start=0" frameborder="0" allowfullscreen></iframe>',
            $this->_application->H($value['id'])
        );
    }
    
    protected function _fieldRendererReadableSettings(IField $field, array $settings)
    {
        return [
            'columns' => [
                'label' => __('Number of columns', 'directories'),
                'value' => $settings['columns'],
            ],
        ];
    }
}