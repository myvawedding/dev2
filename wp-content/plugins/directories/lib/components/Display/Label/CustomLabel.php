<?php
namespace SabaiApps\Directories\Component\Display\Label;

use SabaiApps\Directories\Component\Entity;

class CustomLabel extends AbstractLabel
{
    protected function _displayLabelInfo(Entity\Model\Bundle $bundle)
    {
        $info = [
            'label' => __('Custom label', 'directories'),
            'default_settings' => [],
        ];
        foreach ($this->_application->Filter('entity_label_custom_label_num', range(1, 3), [$bundle]) as $num) {
            $info['multiple'][$num] = [
                'default_checked' => $num === 1,
                'label' => sprintf(__('Custom label #%d', 'directories'), $num)
            ];
        }
        return $info;
    }

    public function displayLabelSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = array())
    {
        return [];
    }

    public function displayLabelText(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings)
    {
        return [
            'label' => $settings['_label'],
            'color' => $settings['_color'],
        ];
    }
}
