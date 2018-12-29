<?php
namespace SabaiApps\Directories\Component\DirectoryPro;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class DirectoryProComponent extends AbstractComponent
    implements Field\IRenderers,
               System\IAdminRouter,
               System\IWidgets,
               Display\ILabels
{
    const VERSION = '1.2.15', PACKAGE = 'directories-pro';
    
    public static function interfaces()
    {
        return ['Payment\IFeatures'];
    }
    
    public static function description()
    {
        return 'Adds features to build a local business directory.';
    }
    
    public function onCorePlatformWordPressInit()
    {
        if ($this->_application->getPlatform()->getName() === 'WordPress') {
            new WordPressHomePage($this->_application);
        }
    }
    
    public function systemAdminRoutes()
    {
        return [
            '/directories/add' => [
                'controller' => 'AddDirectory',
                'access_callback' => true,
                'title_callback' => true,
                'callback_path' => 'add_directory',
                'callback_component' => 'Directory',
                'type' => Application::ROUTE_MENU,
                'priority' => 5,
            ],
            '/directories/:directory_name/export' => [
                'controller' => 'ExportDirectory',
                'title_callback' => true,
                'callback_path' => 'export_directory',
            ],
            '/directories/:directory_name/content_types/:bundle_name/export_bundle' => [
                'controller' => 'ExportBundle',
                'title_callback' => true,
                'callback_path' => 'export_bundle',
            ],
        ];
    }

    public function systemOnAccessAdminRoute(Context $context, $path, $accessType, array &$route){}

    public function systemAdminRouteTitle(Context $context, $path, $titleType, array $route)
    {
        switch ($path) {
            case 'export_directory':
            case 'export_bundle':
                return __('Export', 'directories-pro');
        }
    }
    
    public function systemGetWidgetNames()
    {
        $has_directory = false;
        foreach ($this->_application->Entity_Bundles(null, 'Directory') as $bundle) {
            if (!empty($bundle->info['public'])
                && empty($bundle->info['is_taxonomy'])
                && empty($bundle->info['parent'])
            ) {
                $has_directory = true;
                break;
            }
        }
        return $has_directory ? ['directory_filters'] : [];
    }
    
    public function systemGetWidget($name)
    {
        if ($name === 'directory_filters') {
            return new SystemWidget\FiltersSystemWidget($this->_application, $name);
        }
    }
    
    public function fieldGetRendererNames()
    {
        return ['directory_opening_hours', 'directory_screenshot'];
    }
    
    public function fieldGetRenderer($name)
    {
        switch ($name) {
            case 'directory_opening_hours':
                return new FieldRenderer\OpeningHoursFieldRenderer($this->_application, $name);
            case 'directory_screenshot':
                return new FieldRenderer\ScreenshotFieldRenderer($this->_application, $name);
        }
    }
    
    public function displayGetLabelNames(Entity\Model\Bundle $bundle)
    {
        return ['directory_open_now'];
    }
    
    public function displayGetLabel($name)
    {
        switch ($name) {
            case 'directory_open_now':
                return new DisplayLabel\OpenNowDisplayLabel($this->_application, $name);
        }
    }
    
    public function paymentGetFeatureNames()
    {
        return ['directory_photos'];
    }
    
    public function paymentGetFeature($name)
    {
        switch ($name) {
            case 'directory_photos':
                return new PaymentFeature\PhotosPaymentFeature($this->_application, $name);
        }
    }
    
    public function onDirectoryTypesFilter(&$types)
    {
        $types['directory'] = $this->_name;
    }
    
    public function directoryGetType($name)
    {
        return new DirectoryType\DirectoryType($this->_application, $name);
    }
    
    public function onEntityFieldValuesLoaded($entity, $bundle, $fields, $cache)
    {
        if (!$cache
            || $bundle->type !== 'directory__listing'
            || !$this->_application->isComponentLoaded('Payment')
            || empty($bundle->info['payment_enable'])
            || (!$directory_photos = $entity->getFieldValue('directory_photos'))
        ) return;
        
        $features = $this->_application->Payment_Plan_features($entity);

        if (!empty($features[0]['directory_photos']['unlimited'])) return;
                    
        if (!isset($features[0]['directory_photos']['num'])) {
            $max_num_allowed = 5;
        } else {
            $max_num_allowed = empty($features[0]['directory_photos']['num']) ? 0 : $features[0]['directory_photos']['num'];
        }
        if (!empty($features[1]['directory_photos']['num'])) { // any additional num of photos allowed?
            $max_num_allowed += $features[1]['directory_photos']['num'];
        }
        
        $current_num = count($directory_photos);
        if ($current_num <= $max_num_allowed) return;
                    
        $entity->setFieldValue('directory_photos', array_slice($directory_photos, 0, $max_num_allowed));
    }
    
    public function onDirectoryAdminDirectoryLinksFilter(&$links, $directory)
    {
        $links['settings']['link'][98] = '';
        $links['settings']['link'][99] = $this->_application->LinkTo(
            $title = __('Export', 'directories-pro'),
            $this->_application->Url('/directories/' . $directory->name . '/export'),
            ['btn' => true, 'container' => 'modal'],
            [
                'data-modal-title' => $title . ' - ' . $directory->getLabel(),
                'rel' => 'sabaitooltip',
            ]
        );
    }
    
    public function onDirectoryAdminDirectoryMenusFilter(&$menus, $directory)
    {
        $menus['export'] = [
            'title' => $title = __('Export', 'directories-pro'),
            'url' => '/directories/' . $directory->name . '/export',
            'data' => array(
                'link_options' => ['container' => 'modal'],
                'link_attr' => ['data-modal-title' => $title . ' - ' . $directory->getLabel()],
            ),
            'page' => true,
        ];
    }

    public function onCsvImportFilesFilter(&$files, $bundle)
    {
        if ($bundle->type === 'directory_category') {
            $files[__DIR__ . '/csv/categories.csv'] = __('Demo categories', 'directories-pro');
        } elseif ($bundle->type === 'location_location') {
            $files[__DIR__ . '/csv/locations.csv'] = __('Demo locations (USA states and cities)', 'directories-pro');
        } elseif ($bundle->type === 'directory_tag') {
            $files[__DIR__ . '/csv/tags.csv'] = __('Demo tags', 'directories-pro');
        }
    }

    public function onCsvImportSettingsFormFilter(&$form, $bundle, $csvFile)
    {
        if ($csvFile['type'] === 'existing'
            && $bundle->type === 'location_location'
            && $csvFile['existing'] === __DIR__ . '/csv/locations.csv'
        ) {
            $form['importers']['location_photo']['location']['#default_value'] = 'url';
        }
    }
}
