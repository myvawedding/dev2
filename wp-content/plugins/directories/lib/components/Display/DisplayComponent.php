<?php
namespace SabaiApps\Directories\Component\Display;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Context;

class DisplayComponent extends AbstractComponent implements
    IElements,
    IButtons,
    ILabels,
    System\IWidgets,
    System\IAdminRouter,
    Form\IFields
{
    const VERSION = '1.2.15', PACKAGE = 'directories';

    protected $_system = true;

    public static function description()
    {
        return 'Enables customizing display of content without writing any code.';
    }

    public function displayGetElementNames(Entity\Model\Bundle $bundle)
    {
        return array('text', 'columns', 'column', 'separator', 'tabs', 'tab', 'group', 'html', 'javascript',
            'labels', 'button', 'statistics', 'fieldlist', 'template', 'fieldtemplate',
        );
    }

    public function displayGetElement($name)
    {
        switch ($name) {
            case 'text':
                return new Element\TextElement($this->_application, $name);
            case 'columns':
                return new Element\ColumnsElement($this->_application, $name);
            case 'column':
                return new Element\ColumnElement($this->_application, $name);
            case 'tabs':
                return new Element\TabsElement($this->_application, $name);
            case 'tab':
                return new Element\TabElement($this->_application, $name);
            case 'group':
                return new Element\GroupElement($this->_application, $name);
            case 'separator':
                return new Element\SeparatorElement($this->_application, $name);
            case 'html':
                return new Element\HtmlElement($this->_application, $name);
            case 'javascript':
                return new Element\JavaScriptElement($this->_application, $name);
            case 'template':
                return new Element\TemplateElement($this->_application, $name);
            case 'labels':
                return new Element\LabelsElement($this->_application, $name);
            case 'button':
                return new Element\ButtonElement($this->_application, $name);
            case 'statistics':
                return new Element\StatisticsElement($this->_application, $name);
            case 'author':
                return new Element\AuthorElement($this->_application, $name);
        }
    }

    public function displayGetButtonNames(Entity\Model\Bundle $bundle)
    {
        return array('custom');
    }

    public function displayGetButton($name)
    {
        return new Button\CustomButton($this->_application, $name);
    }

    public function displayGetLabelNames(Entity\Model\Bundle $bundle)
    {
        return array('custom');
    }

    public function displayGetLabel($name)
    {
        return new Label\CustomLabel($this->_application, $name);
    }

    public function systemGetWidgetNames()
    {
        return array('display_element');
    }

    public function systemGetWidget($name)
    {
        return new SystemWidget\ElementSystemWidget($this->_application, $name);
    }

    public function systemAdminRoutes()
    {
        $routes = [];
        foreach (array_keys($this->_application->Entity_BundleTypes()) as $bundle_type) {
            if ((!$admin_path = $this->_application->Entity_BundleTypeInfo($bundle_type, 'admin_path'))
                || isset($routes[$admin_path . '/displays/list_elements']) // path added already
            ) continue;

            $routes += array(
                $admin_path . '/displays/list_elements' => array(
                    'controller' => 'ListElements',
                ),
                $admin_path . '/displays/add_element' => array(
                    'controller' => 'AddElement',
                ),
                $admin_path . '/displays/edit_element' => array(
                    'controller' => 'EditElement',
                ),
                $admin_path . '/displays/add_display' => array(
                    'controller' => 'AddDisplay',
                    'access_callback' => true,
                    'callback_path' => 'add_display',
                ),
                $admin_path . '/displays/delete_display' => array(
                    'controller' => 'DeleteDisplay',
                    'access_callback' => true,
                    'callback_path' => 'delete_display',
                ),
            );
        }

        return $routes;
    }

    public function systemOnAccessAdminRoute(Context $context, $path, $accessType, array &$route)
    {
        switch ($path) {
            case 'add_display':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    $type = $context->getRequest()->asStr('display_type');
                    $name = $context->getRequest()->asStr('display_name');
                    return $this->_application->Filter(
                        'display_is_creatable',
                        $type === 'entity' && $name === 'summary',
                        [$type, $name]
                    );
                }
                return true;
            case 'delete_display':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    $name = $context->getRequest()->asStr('display_name');
                    if (!$pos = strpos($name, '-')) return false; // can not delete default display

                    $name = substr($name, 0, $pos);
                    $type = $context->getRequest()->asStr('display_type');
                    return $this->_application->Filter(
                        'display_is_creatable',
                        $type === 'entity' && $name === 'summary',
                        [$type, $name]
                    );
                }
                return true;
        }
    }

    public function systemAdminRouteTitle(Context $context, $path, $titleType, array $route){}

    public function onEntityDeleteBundlesCommitted(array $bundles, $deleteContent)
    {
        foreach ($this->getModel('Display')->bundleName_in(array_keys($bundles))->fetch() as $display) {
            $display->markRemoved();
        }
        $this->getModel()->commit();
    }

    public function formGetFieldTypes()
    {
        return ['display_elements'];
    }

    public function formGetField($type)
    {
        return new FormField\ElementsFormField($this->_application);
    }
}
