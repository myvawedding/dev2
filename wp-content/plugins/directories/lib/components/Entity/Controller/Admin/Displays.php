<?php
namespace SabaiApps\Directories\Component\Entity\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Display\Controller\Admin\AbstractDisplays;

class Displays extends AbstractDisplays
{
    protected $_enableCSS = true, $_hideTabsIfSingle = false;
    
    protected function _getDisplays(Context $context)
    {
        return $this->Entity_Displays($context->bundle);
    }
    
    protected function _getDisplayWeight(array $display)
    {
        return $display['name'] === 'detailed' ? -1 : ($display['name'] === 'summary' ? 0 : parent::_getDisplayWeight($display));
    }
    
    protected function _getSettingsForm(Context $context, array &$formStorage)
    {
        $form = parent::_getSettingsForm($context, $formStorage);
        foreach ($form['#displays'] as $display_name) {
            if (!$template = $this->Entity_Display_hasCustomTemplate($context->bundle, $display_name)) continue;
        
            $form[$display_name]['#prefix'] = '<div class="drts-bs-alert drts-bs-alert-warning">'
                . sprintf(
                    $this->H(__('Template file for this display was found at %s. Display settings on this page are ignored.', 'directories')),
                    '<code>' . $this->H($template) . '</code>'
                ) . '</div>';
        }
        
        return $form;
    }
}