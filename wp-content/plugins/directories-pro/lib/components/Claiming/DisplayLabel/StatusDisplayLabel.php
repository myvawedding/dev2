<?php
namespace SabaiApps\Directories\Component\Claiming\DisplayLabel;

use SabaiApps\Directories\Component\Display\Label\AbstractLabel;
use SabaiApps\Directories\Component\Entity;

class StatusDisplayLabel extends AbstractLabel
{
    protected function _displayLabelInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'label' => __('Claim status label', 'directories-pro'),
            'default_settings' => array(
                '_icon' => '',
            ),
            'labellable' => false,
            'colorable' => false,
        );
    }

    public function displayLabelText(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings)
    {
        switch ($entity->getSingleFieldValue('claiming_status')) {
            case 'approved':
                $label = __('Approved', 'directories-pro');
                $color = 'success';
                break;
            case 'rejected':
                $label = __('Rejected', 'directories-pro');
                $color = 'danger';
                break;
            default:
                $label = __('Pending', 'directories-pro');
                $color = 'warning';
        }

        return array(
            'label' => $label,
            'color' => ['type' => $color],
        );
    }
}
