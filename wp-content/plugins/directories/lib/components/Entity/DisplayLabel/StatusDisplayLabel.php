<?php
namespace SabaiApps\Directories\Component\Entity\DisplayLabel;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class StatusDisplayLabel extends Display\Label\AbstractLabel
{
    protected function _displayLabelInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'label' => __('Content status label', 'directories'),
            'default_settings' => array(),
            'labellable' => false,
        );
    }

    public function displayLabelText(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings)
    {
        if (!$status = $entity->getStatus()) return;

        if ($entity->isPublished()) {
            $color = 'success';
        } elseif ($entity->isPending()) {
            $color = 'warning';
        } else {
            $color = 'secondary';
        }

        return array(
            'label' => $this->_application->Entity_Types_impl($entity->getType())->entityTypeEntityStatusLabel($status),
            'color' => ['type' => $color],
        );
    }
}
