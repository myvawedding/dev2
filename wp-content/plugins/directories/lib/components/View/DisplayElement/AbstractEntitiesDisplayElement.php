<?php
namespace SabaiApps\Directories\Component\View\DisplayElement;

use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;

abstract class AbstractEntitiesDisplayElement extends Display\Element\AbstractElement
{    
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'type' => 'content',
            'default_settings' => array(
                'view' => null,
            ),
            'icon' => 'far fa-list-alt',
            'cacheable' => true,
        );
    }
    
    protected function _getEntitiesBundleType($entityOrBundle)
    {
        return $entityOrBundle instanceof \SabaiApps\Directories\Component\Entity\Type\IEntity ? $entityOrBundle->getBundleType() : $entityOrBundle->type;
    }
    
    protected function _getEntitiesComponent($entityOrBundle)
    {
        return $this->_application->Entity_Bundle($entityOrBundle)->component;
    }
    
    protected function _getEntitiesBundleGroup($entityOrBundle)
    {
        return $this->_application->Entity_Bundle($entityOrBundle)->group;
    }
    
    protected function _getEntitiesBundle($entityOrBundle)
    {
        return $this->_application->Entity_Bundle(
            $this->_getEntitiesBundleType($entityOrBundle),
            $this->_getEntitiesComponent($entityOrBundle),
            $this->_getEntitiesBundleGroup($entityOrBundle)
        );
    }
    
    protected function _displayElementSupports(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        return $display->type === 'entity' && $display->name === 'detailed';
    }
    
    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        $form = array(
            'view' => array(
                '#title' => __('Select view', 'directories'),
                '#type' => 'select',
                '#horizontal' => true,
                '#options' => $this->_getViewOptions($bundle),
                '#default_value' => $settings['view'],
            ),
        );
        
        return $form;
    }

    protected function _getViewOptions(Entity\Model\Bundle $bundle)
    {
        $views = [];
        foreach ($this->_application->getModel('View', 'View')->bundleName_is($this->_getEntitiesBundle($bundle)->name)->fetch() as $view) {
            $views[$view->name] = $view->getLabel();
        }
        return $views;
    }
    
    protected function _getListEntitiesSettings(Entity\Model\Bundle $bundle, array $element, Entity\Type\IEntity $entity)
    {
        return array(
            'view' => $element['settings']['view'],
        );
    }
    
    protected function _getListEntitiesPath(Entity\Model\Bundle $bundle, array $element, Entity\Type\IEntity $entity)
    {
        return $this->_application->Entity_BundlePath($bundle);
    }
    
    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        if (empty($element['settings']['view'])) return;
        
        // Get bundle of entities to list
        $_bundle = $this->_getEntitiesBundle($bundle);
        
        if (!$list_entities_settings = $this->_getListEntitiesSettings($_bundle, $element, $var)) return;
        
        if (empty($_bundle->info['parent'])
            && empty($_bundle->info['is_taxonomy'])
        ) {
            // @todo: See why filter button does not work with top level bundles when shown in a display. Until then, disable it.
            $list_entities_settings['settings']['filter']['show'] = false;
        }
        
        return $this->_application->getPlatform()->render(
            $this->_getListEntitiesPath($_bundle, $element, $var),
            ['settings' => ['hide_empty' => true] + $list_entities_settings],
            false, // cache
            false, // title
            null, // container
            false // renderAssets
        );
    }

    protected function _displayElementReadableInfo(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        $settings = $element->data['settings'];
        $ret = [
            'view' => [
                'label' => __('Select view', 'directories'),
                'value' => $this->_getViewOptions($bundle)[$settings['view']],
            ],
        ];
        return ['settings' => ['value' => $ret]];
    }
}
