<?php
namespace SabaiApps\Directories\Component\Claiming\DisplayButton;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class ClaimEntityDisplayButton extends Display\Button\AbstractButton
{
    protected function _displayButtonInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'label' => __('Claim listing button', 'directories-pro'),
            'default_settings' => array(
                '_color' => 'outline-warning',
            ),
            'labellable' => false,
            'iconable' => false,
        );
    }

    public function displayButtonLink(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings, $displayName)
    {
        if (!$claim_bundle = $this->_application->Entity_Bundle('claiming_claim', $bundle->component, $bundle->group)) return;

        if ($this->_application->getUser()->isAnonymous()) {
            // We can't use Entity_IsRoutable helper here since it will always return false if guest

            if ($entity->getAuthorId()) return; // already claimed

            return $this->_getLink($bundle, $entity, $settings);
        }
        
        if ($this->_application->Entity_IsRoutable($claim_bundle, 'add', $entity)) {
            return $this->_getLink($bundle, $entity, $settings);
        }
    }

    protected function _getLink(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings)
    {
        $claim_bundle = $this->_application->Entity_Bundle('claiming_claim', $bundle->component, $bundle->group);
        return $this->_application->LinkTo(
            $claim_bundle->getLabel('add'),
            $this->_application->Entity_Url($entity, '/' . $claim_bundle->info['slug'] . '_add'),
            array('icon' => $this->_application->Entity_BundleTypeInfo($claim_bundle, 'icon'), 'btn' => true),
            array('class' => $settings['_class'], 'style' => $settings['_style'])
        );
    }
}
