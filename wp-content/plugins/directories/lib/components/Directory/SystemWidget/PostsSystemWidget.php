<?php
namespace SabaiApps\Directories\Component\Directory\SystemWidget;

use SabaiApps\Directories\Component\System\Widget\AbstractWidget;
use SabaiApps\Directories\Application;

class PostsSystemWidget extends AbstractWidget
{
    protected $_bundleType, $_directoryType, $_contentType;

    public function __construct(Application $application, $name, $bundleType)
    {
        parent::__construct($application, $name);
        $this->_bundleType = $bundleType;
        list($this->_directoryType, $this->_contentType) = explode('__', $bundleType);
    }

    protected function _systemWidgetInfo()
    {
        $directory_type_label = $this->_application->Directory_Types_impl($this->_directoryType)->directoryInfo('label');
        $bundle_type_label = $this->_application->Entity_BundleTypeInfo($this->_bundleType, 'label');
        return array(
            'title' => $directory_type_label . ' - ' . $bundle_type_label,
            'summary' => sprintf(__("A list of your site's %s.", 'directories'), $bundle_type_label),
        );
    }

    protected function _getWidgetSettings(array $settings)
    {
        $directory_options = [];
        foreach ($this->_application->getModel('Directory', 'Directory')->type_is($this->_directoryType)->fetch() as $directory) {
            if (!$this->_application->Directory_Types_impl($directory->type, true)) continue;

            $directory_options[$directory->name] = $directory->getLabel();
        }
        if (empty($directory_options)) return;

        $directory_option_keys = array_keys($directory_options);
        $sorts = [];
        $directory_for_sort = isset($settings['directory']) && array_key_exists($settings['directory'], $directory_options) ? $settings['directory'] : current($directory_option_keys);
        if ($bundle_for_sort = $this->_application->Entity_Bundle($this->_bundleType, 'Directory', $directory_for_sort)) {
            foreach ($this->_application->Entity_Sorts($bundle_for_sort) as $sort_name => $sort) {
                $sorts[$sort_name] = $sort['label'];
            }
        }
        $form = array(
            'directory' => array(
                '#title' => __('Select directory', 'directories'),
                '#options' => $directory_options,
                '#type' => count($directory_options) <= 1 ? 'hidden' : 'select',
                '#default_value' => array_shift($directory_option_keys),
            ),
            'limit' => array(
                '#type' => 'textfield',
                '#title' => __('Number of items to show (0 for unlimited)', 'directories'),
                '#integer' => true,
                '#default_value' => 5,
                '#size' => 3,
            ),
            'sort' => array(
                '#type' => 'select',
                '#title' => __('Sort by', 'directories'),
                '#options' => $sorts,
                '#default_value' => 'published',
            ),
            'show_summary' => array(
                '#type' => 'checkbox',
                '#title' => __('Show summary', 'directories'),
                '#default_value' => true,
            ),
            'summary_num_chars' => array(
                '#type' => 'textfield',
                '#title' => __('Number of summary characters', 'directories'),
                '#integer' => true,
                '#default_value' => 100,
                '#size' => 6,
            ),
            'show_published' => array(
                '#type' => 'checkbox',
                '#title' => __('Show published date', 'directories'),
                '#default_value' => true,
            ),
        );
        if ($this->_application->Entity_BundleTypeInfo($this->_bundleType, 'entity_image')) {
            $form['show_thumbnail'] = array(
                '#type' => 'checkbox',
                '#title' => __('Show thumbnail', 'directories'),
                '#default_value' => true,
            );
            $form['thumbnail_size'] = array(
                '#type' => 'textfield',
                '#title' => __('Thumbnail size in pixels', 'directories'),
                '#default_value' => 64,
                '#min_value' => 10,
                '#max_value' => 100,
                '#integer' => true,
            );
        }
        if ($this->_application->Entity_BundleTypeInfo($this->_bundleType, 'featurable')) {
            $form['featured_only'] = array(
                '#type' => 'checkbox',
                '#title' => __('Show featured items only', 'directories'),
                '#default_value' => false,
            );
        }

        return $form;
    }

    protected function _getWidgetContent(array $settings)
    {
        if (!$query = $this->_getQuery($settings)) return;

        $query = $this->_application->Filter('entity_widget_list_query', $query, array($this->_bundleType, $settings, $this->_name));
        $entities = $query->fetch($settings['limit']);
        if (empty($entities)) return;

        $ret = [];
        foreach ($entities as $entity) {
            $ret[] = array(
                'summary' => !empty($settings['show_summary']) ? $this->_application->Summarize($entity->getContent(), $settings['summary_num_chars']) : null,
                'title_link' => $this->_application->Entity_Permalink($entity),
                'meta' => $this->_getEntityMeta($entity, $settings),
                'image' => !empty($settings['show_thumbnail']) && ($src = $this->_application->Entity_Image($entity, 'thumbnail_scaled'))
                    ? array('src' => $src, 'alt' => $entity->getTitle(), 'width' => $settings['thumbnail_size'])
                    : null,
            );
        }
        return $ret;
    }

    protected function _getQuery(array $settings)
    {
        if (empty($settings['directory'])
            || (!$bundle = $this->_application->Entity_Bundle($this->_bundleType, 'Directory', $settings['directory']))
        ) return;

        $query = $this->_application->Entity_Query($bundle->entitytype_name)
            ->fieldIs('status', $this->_application->Entity_Status($bundle->entitytype_name, 'publish'))
            ->fieldIs('bundle_name', $bundle->name)
            ->sort($settings['sort'], $this->_application->Entity_Sorts($bundle), $this->_getCacheId($settings));
        if (!empty($settings['featured_only'])) {
            $query->fieldIsNotNull('entity_featured');
        }

        return $query;
    }

    protected function _getEntityMeta($entity, $settings)
    {
        $meta = [];
        if (!empty($settings['show_published'])) {
            $meta[] = '<i class="far fa-fw fa-calendar"></i> ' . $this->_application->System_Date($entity->getTimestamp(), true);
        }

        return $this->_application->Filter('entity_widget_entity_meta', $meta, array($entity, $settings, 'entity_list'));
    }
}
