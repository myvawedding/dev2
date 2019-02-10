<?php
namespace SabaiApps\Directories\Component\Payment\DisplayLabel;

use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;

class PlanDisplayLabel extends Display\Label\AbstractLabel
{
    protected function _displayLabelInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'label' => __('Payment plan label', 'directories-payments'),
            'default_settings' => array(),
            'labellable' => false,
            'colorable' => false,
        );
    }

    public function displayLabelText(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings)
    {
        if (!$plan = $this->_application->Payment_Plan($entity)) return;

        return [
            'label' => $plan->paymentPlanTitle(),
            'attr' => [
                'data-plan-name' => $plan->paymentPlanName(),
            ],
        ];
    }
}
