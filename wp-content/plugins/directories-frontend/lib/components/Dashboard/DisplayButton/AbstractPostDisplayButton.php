<?php
namespace SabaiApps\Directories\Component\Dashboard\DisplayButton;

use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Application;

abstract class AbstractPostDisplayButton extends Display\Button\AbstractButton
{    
    protected $_route, $_modalDanger, $_allowGuest;
    
    public function __construct(Application $application, $name, $route, $modalDanger = false, $allowGuest = false)
    {
        parent::__construct($application, $name);
        $this->_route = $route;
        $this->_modalDanger = (bool)$modalDanger;
        $this->_allowGuest = (bool)$allowGuest;
    }
    
    public function displayButtonLink(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings, $displayName)
    {
        if (!$this->_application->Entity_IsAuthor($entity) // dashboard should allow own content only
            || !$this->_application->Entity_IsRoutable($bundle, $this->_route, $entity)
        ) return;
        
        if ($this->_application->getUser()->isAnonymous()) {
            if (!$this->_allowGuest) return;
            
            return; // @todo create pages for guest users to edit/delete posts
        }

        $label = $this->_getLabel($bundle, $entity, $settings);
        $options = [
            'icon' => $settings['_icon'],
        ];
        $attr = [
            'class' => $settings['_class'],
            'style' => $settings['_style'],
            'data-modal-title' => $label . ' - ' . $entity->getTitle(),
            'data-modal-danger' => empty($this->_modalDanger) ? 0 : 1,
        ];
        if ($this->_route !== 'edit') {
            $this->_application->getPlatform()->addJsFile('form.min.js', 'drts-form', array('drts')); // for modal form
            $options['container'] = 'modal';
        }
        
        return $this->_application->LinkTo(
            $label,
            $this->_getUrl($bundle, $entity, $settings, $displayName),
            $options,
            $attr
        );
    }
    
    protected function _getUrl(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings, $displayName)
    {
        $params = [];
        if (isset($GLOBALS['drts_entity'])
            && $GLOBALS['drts_entity']->getType() === $entity->getType()
            && $GLOBALS['drts_entity']->getId() === $entity->getId()
        ) {
            $params['redirect'] = 1;
        }

        $path = $this->_route === 'edit' ? '/' : '/' . $this->_route;

        return $this->_application->getComponent('Dashboard')->getPostsPanelUrl($bundle, '/posts/' . $entity->getId() . $path, $params, true);
    }
    
    protected function _getLabel(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings)
    {
        return $settings['_label'];
    }
}