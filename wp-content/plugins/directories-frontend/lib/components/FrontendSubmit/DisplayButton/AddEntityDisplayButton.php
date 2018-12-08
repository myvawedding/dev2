<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\DisplayButton;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class AddEntityDisplayButton extends Display\Button\AbstractButton
{
    protected $_bundleType;

    public function __construct(Application $application, $name)
    {
        parent::__construct($application, $name);
        $this->_bundleType = substr($name, 19); // remove 'frontendsubmit_add_' prefix
    }

    protected function _displayButtonInfo(Entity\Model\Bundle $bundle)
    {
        if ($child_bundle = $this->_application->Entity_Bundle($this->_bundleType, $bundle->component, $bundle->group)) {
            $label = $child_bundle->getLabel('singular');
        } else {
            $this->_application->LogError('Failed fetching child bundle: ' . $this->_bundleType . '; ' . $this->_application->backtrace());
            $label = 'N/A';
        }
        return array(
            'label' => sprintf(__('Add %s button', 'directories-frontend'), strtolower($label), $label),
            'default_settings' => array(
                '_color' => 'outline-primary',
            ),
            'labellable' => false,
            'iconable' => false,
        );
    }

    public function displayButtonLink(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings, $displayName)
    {
        if ($this->_application->getUser()->isAnonymous()) {
            return $this->_getLink($bundle, $entity, $settings);
        }
        if (($child_bundle = $this->_application->Entity_Bundle($this->_bundleType, $bundle->component, $bundle->group))
            && $this->_application->Entity_IsRoutable($child_bundle, 'add', $entity)
        ) {
            return $this->_getLink($bundle, $entity, $settings);
        }
    }

    protected function _getLink(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings)
    {
        if (!$child_bundle = $this->_application->Entity_Bundle($this->_bundleType, $bundle->component, $bundle->group)) {
            return;
        }
        
        return $this->_application->LinkTo(
            $child_bundle->getLabel('add'),
            $this->_application->Entity_Url($entity, '/' . $child_bundle->info['slug'] . '/add'),
            array('icon' => $this->_application->Entity_BundleTypeInfo($child_bundle, 'icon'), 'btn' => true),
            array('class' => $settings['_class'], 'style' => $settings['_style'])
        );
    }
}
