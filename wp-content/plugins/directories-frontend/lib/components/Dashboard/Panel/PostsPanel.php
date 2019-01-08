<?php
namespace SabaiApps\Directories\Component\Dashboard\Panel;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Request;

class PostsPanel extends AbstractPanel
{
    protected $_bundleGroup;

    public function __construct(Application $application, $name)
    {
        parent::__construct($application, $name);
        $this->_bundleGroup = substr($name, strlen('dashboard_posts_'));
    }

    protected function _dashboardPanelInfo()
    {
        return [
            'weight' => 1,
            'labellable' => false,
        ];
    }

    public function dashboardPanelOnLoad()
    {
        $this->_application->Form_Scripts();
        $this->_application->getPlatform()->addCssFile('voting-rating.min.css', 'drts-voting-rating', array('drts'));
        $this->_application->getPlatform()->loadJqueryUiJs(array('effects-highlight'));
    }

    public function dashboardPanelLabel()
    {
        if ($directory = $this->_application->getModel('Directory', 'Directory')->name_is($this->_bundleGroup)->fetchOne()) {
            return $directory->getLabel();
        }
    }

    protected function _dashboardPanelLinks()
    {
        if (!$bundles = $this->_application->Entity_Bundles(null, 'Directory', $this->_bundleGroup)) return;

        $ret = [];
        $weight = 0;
        $entity_type = null;
        foreach ($bundles as $bundle) {
            if (!empty($bundle->info['is_taxonomy'])) continue;

            ++$weight;
            $ret[$bundle->name] = array(
                'title' => $bundle->getLabel(),
                'weight' => empty($bundle->info['parent']) ? $weight : 100 + $weight,
                'icon' => $this->_application->Entity_BundleTypeInfo($bundle, 'icon'),
            );
            $entity_type = $bundle->entitytype_name;
        }

        $user_id = $this->_application->getUser()->id;
        $language = $this->_application->getPlatform()->getCurrentLanguage();
        $cache_id = 'dashboard_post_counts_' . $this->_bundleGroup . '_' . $user_id . '_' . $language;
        if (!$counts = $this->_application->getPlatform()->getCache($cache_id, 'content')) {
            if (!empty($ret)) {
                $statuses = array('publish', 'pending', 'draft', 'private');
                foreach (array_keys($statuses) as $key) {
                    $statuses[$key] = $this->_application->Entity_Status($entity_type, $statuses[$key]);
                }
                $counts = $this->_application->Entity_Query($entity_type)
                    ->fieldIsIn('bundle_name', array_keys($ret))
                    ->fieldIsIn('status', $statuses)
                    ->fieldIs('author', $user_id)
                    ->groupByField('bundle_name')
                    ->count();
            } else {
                $counts = [];
            }
            $this->_application->getPlatform()->setCache($counts, $cache_id, 600, 'content'); // cache 10 min
        }
        foreach (array_keys($ret) as $bundle_name) {
            if (empty($counts[$bundle_name])) {
                // No posts, hide if child bundle or no permission to create posts for the bundle
                if (!empty($bundles[$bundle_name]->info['parent'])
                    || !$this->_application->HasPermission('entity_create_' . $bundle_name)
                ) {
                    unset($ret[$bundle_name]);
                }
            } else {
                $ret[$bundle_name]['count'] = $counts[$bundle_name];
            }
        }

        return $ret;
    }

    public function dashboardPanelContent($link, array $params)
    {
        if (!$bundle = $this->_application->Entity_Bundle($link)) {
            throw new Exception\RuntimeException('Invalid bundle: ' . $link);
        }

        $settings = array(
            'mode' => 'dashboard_dashboard',
            'settings' => array(
                'filter' => array(
                    'show' => false,
                ),
                'pagination' => array(
                    'perpage' => 20,
                    'allow_perpage' => true,
                    'perpages' => array(20, 30, 50),
                ),
                'other' => array(
                    'add' => [
                        'show' => empty($bundle->info['parent']),
                        'show_label' => (bool)$this->_application->getComponent('Dashboard')->getConfig('add_show_label'),
                    ],
                    'num' => true,
                ),
                'sort' => array(
                    'default' => $default_sort = $bundle->entitytype_name . '_published',
                    'options' => array(
                        $default_sort,
                        $bundle->entitytype_name . '_title',
                        $bundle->entitytype_name . '_published,asc',
                        'entity_level',
                        'voting_rating',
                        'voting_bookmark',
                        'voting_updown',
                    ),
                ),
            ),
        );
        // Sorting options for non-public bundles can not be configured, therefore force sort by newest/oldest
        if (empty($bundle->info['public'])) {
            $settings['settings']['sort']['options'] = array(
                $default_sort,
                $bundle->entitytype_name . '_published,asc',
            );
        }

        // Set current user ID. No need for non-public bundles as it is done automatically by ViewEntities controller
        if (!empty($bundle->info['public'])) {
            $settings['settings']['query']['fields'][$bundle->entitytype_name . '_author'] = $this->_application->getUser()->id;
        }
        $settings['settings']['query']['status'] = ['publish', 'pending', 'draft', 'private'];

        return $this->_application->getPlatform()->render(
            $this->_application->Entity_BundlePath($bundle),
            ['settings' => $settings]
        );
    }
}
