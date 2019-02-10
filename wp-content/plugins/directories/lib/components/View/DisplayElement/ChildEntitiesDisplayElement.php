<?php
namespace SabaiApps\Directories\Component\View\DisplayElement;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;

class ChildEntitiesDisplayElement extends AbstractEntitiesDisplayElement
{
    protected $_bundleType;

    public function __construct(Application $application, $name)
    {
        parent::__construct($application, $name);
        $this->_bundleType = substr($name, 20); // remove 'view_child_entities_' prefix
    }

    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return parent::_displayElementInfo($bundle) + array(
            'label' => $label = $this->_application->Entity_Bundle($this->_bundleType, $bundle->component, $bundle->group)->getLabel(),
            'description' => sprintf(__('Displays %s of the current content', 'directories'), strtolower($label), $label),
        );
    }

    public function displayElementSupports(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        return parent::displayElementSupports($bundle, $display)
            && $this->_application->Entity_Bundle($this->_bundleType, $bundle->component, $bundle->group); // make sure child bundle exists
    }

    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        if (($child_bundle = $this->_application->Entity_Bundle($this->_bundleType, $bundle->component, $bundle->group))
            && $this->_application->Entity_IsRoutable($child_bundle, 'list', $var)
        ) {
            return parent::displayElementRender($bundle, $element, $var);
        }
    }

    protected function _getEntitiesBundleType($entityOrBundle)
    {
        return $this->_bundleType;
    }

    protected function _getListEntitiesSettings(Entity\Model\Bundle $bundle, array $element, Entity\Type\IEntity $entity)
    {
        if (!$entity->isPublished()) return;

        $settings = parent::_getListEntitiesSettings($bundle, $element, $entity);
        $settings['settings']['query']['fields']['entity_parent'] = $entity->getId();

        return $settings;
    }

    protected function _getListEntitiesPath(Entity\Model\Bundle $bundle, array $element, Entity\Type\IEntity $entity)
    {
        return str_replace(':slug', $entity->getSlug(), $bundle->getPath(true));
    }
}
