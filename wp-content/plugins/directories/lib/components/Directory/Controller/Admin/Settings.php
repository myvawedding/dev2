<?php
namespace SabaiApps\Directories\Component\Directory\Controller\Admin;

use SabaiApps\Directories\Component\System\Controller\Admin\AbstractSettings;
use SabaiApps\Directories\Context;

class Settings extends AbstractSettings
{
    protected function _getSettingsForm(Context $context, array &$formStorage)
    {        
        $form = $this->Filter(
            'directory_admin_settings_form',
            [
                '#tabs' => [],
                '#tab_style' => 'pill_less_margin',
            ]
        );
        if (count($form['#tabs']) <= 1) $form['#tabs'] = [];

        return $form;
    }
}