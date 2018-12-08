<?php
namespace SabaiApps\Directories\Component\DirectoryPro\DisplayLabel;

use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;

class OpenNowDisplayLabel extends Display\Label\AbstractLabel
{
    protected function _displayLabelInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'label' => __('Open now label', 'directories-pro'),
            'default_settings' => array(
                '_label' => _x('Open Now', 'featured label', 'directories-pro'),
            ),
            'colorable' => false,
        );
    }

    public function displayLabelSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = array())
    {
        $options = $this->_application->Entity_Field_options(
            $bundle,
            [
                'interface' => 'Field\Type\TimeType',
                'prefix' => __('Field - ', 'directories-pro'),
            ]
        );
        if (count($options) === 1) {
            return [
                'field' => [
                    '#type' => 'hidden',
                    '#value' => current(array_keys($options)),
                ],
            ];
        }

        return [
            '#type' => 'select',
            '#title' => __('Select field', 'directories-pro'),
            '#options' => $options,
            '#horizontal' => true,
        ];
    }

    public function displayLabelText(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings)
    {
        if (empty($settings['field'])
            || (!$values = $entity->getFieldValue($settings['field']))
            || (!$timezone = $entity->getSingleFieldValue('location_address', 'timezone'))
        ) return;

        try {
            $dt = new \DateTime('now', new \DateTimeZone($timezone));
            $current_day = (int)$dt->format('N');
            $current_time = $dt->format('G') * 3600 + (int)$dt->format('i') * 60;
        } catch (\Exception $e) {
            $this->_application->logError('Invalid timezone or error (ID: ' . $entity->getId() . ', timezone: ' . $timezone . ', message: ' . $e->getMessage());
            return;
        }

        $is_open = false;
        foreach ($values as $i => $value) {
            if (!empty($value['day'])
                && $value['day'] === $current_day
                && $value['start'] <= $current_time
                && $value['end'] >= $current_time
            ) {
                $is_open = true;
                break;
            }
        }
        if (!$is_open) return;

        return [
            'label' => $settings['_label'],
            'color' => ['type' => 'success'],
        ];
    }
}
