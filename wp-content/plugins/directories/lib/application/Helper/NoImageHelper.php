<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class NoImageHelper
{
    protected $_src;

    public function help(Application $application, $srcOnly = false)
    {
        if (!isset($this->_src)) {
            // 240x180 px transparent png
            $this->_src = $application->Filter(
                'core_no_image_src',
                'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAPAAAAC0CAQAAAAAlWljAAABH0lEQVR42u3RAQ0AAAzCsOPf9HVAOglrTtPFAsACLMACLMACLMCABViABViABViAAQuwAAuwAAuwAAswYAEWYAEWYAEWYMACLMACLMACLMCABViABViABViABRiwAAuwAAuwAAswYAEWYAEWYAEWYMACLMACLMACLMACDFiABViABViABRiwAAuwAAuwAAuwAAMWYAEWYAEWYAEGLMACLMACLMACDFiABViABViABViAAQuwAAuwAAuwAAMWYAEWYAEWYAEGLMACLMACLMACLMCABViABViABViAAQuwAAuwAAuwAAswYAEWYAEWYAEWYMACLMACLMACLMCABViABViABViABRiwAAuwAAuwAAswYAEWYAEWYAEWYMACrNYe6J4AtdAWxOcAAAAASUVORK5CYII='
            );
        }

        return $srcOnly ? $this->_src : '<img class="drts-no-image" src="' . $application->H($this->_src) . '" alt="" />';
    }
}
