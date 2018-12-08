<?php
namespace SabaiApps\Directories\Component\DirectoryPro\FieldRenderer;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Field\Renderer\ImageRenderer;

class ScreenshotFieldRenderer extends ImageRenderer
{
    protected function _fieldRendererInfo()
    {
        $info = [
            'label' => __('Screenshot', 'directories-pro'),
            'field_types' => ['url'],
        ] + parent::_fieldRendererInfo();
        $info['default_settings']['link'] = 'url';
        $info['default_settings']['target'] = '_blank';

        return $info;
    }

    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        $form = parent::_fieldRendererSettingsForm($field, $settings, $parents);
        unset($form['link']['#options']['photo'], $form['link_image_size']);
        $form['link']['#options']['url'] = __('URL', 'directories-pro');
        $form['size']['#value'] = 'thumbnail';
        $form['size']['#type'] = 'hidden';
        $form['target'] = [
            '#title' => __('Open link in', 'directories-pro'),
            '#type' => 'select',
            '#options' => $this->_getLinkTargetOptions(),
            '#default_value' => $settings['target'],
            '#states' => [
                'visible' => [
                    sprintf('select[name="%s[link]"]', $this->_application->Form_FieldName($parents)) => ['value' => 'url'],
                ],
            ],
            '#weight' => 7,
        ];

        return $form;
    }

    protected function _getLinkTarget(Field\IField $field, array $settings)
    {
        if ($settings['link'] === 'url') return $settings['target'];

        return parent::_getLinkTarget($field, $settings);
    }

    protected function _getImageLinkUrl(Field\IField $field, array $settings, $value, $permalinkUrl, $imageUrl)
    {
        if ($settings['link'] === 'url') return $value;

        return parent::_getImageLinkUrl($field, $settings, $value, $permalinkUrl, $imageUrl);
    }

    protected function _getImageUrl(Field\IField $field, array $settings, $value, $size)
    {
        try {
            $url = $this->_application->getPlatform()->downloadUrl(
                $this->_getScreenshotUrl($settings, $value),
                function (&$file) use ($settings, $value) {
                    if (!$this->_isScreenshotFileValid($file, $settings, $value)) {
                        $file = false;
                        return false;
                    }
                    return true;
                },
                trim(preg_replace('#^https?://#', '', $value), '/'),
                '.jpeg'
            );
        } catch (\Exception $e) {
            $this->_application->logError($e->getMessage());
            return;
        }

        return $url;
    }

    protected function _getImageTitle(Field\IField $field, array $settings, $value)
    {
        return $value;
    }

    protected function _getScreenshotUrl(array $settings, $value)
    {
        return 'http://s.wordpress.com/mshots/v1/' . urlencode($value) . '?w=320';
    }

    protected function _isScreenshotFileValid($file, array $settings, $value)
    {
        $mime = $this->_application->FileType($file);
        return strpos($mime, 'image/jpeg') !== false;
    }
}
