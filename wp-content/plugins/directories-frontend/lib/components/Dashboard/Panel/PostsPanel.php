<?php
namespace SabaiApps\Directories\Component\Dashboard\Panel;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Framework\User\AbstractIdentity;

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

    protected function _dashboardPanelLinks(AbstractIdentity $identity = null)
    {
        if (!$bundles = $this->_application->Entity_Bundles(null, 'Directory', $this->_bundleGroup)) return;

        $ret = [];
        $weight = 0;
        $entity_type = null;
        foreach ($bundles as $bundle) {
            if (!empty($bundle->info['is_taxonomy'])
                || (isset($identity) && empty($bundle->info['public']))
            ) continue;

            ++$weight;
            $ret[$bundle->name] = array(
                'title' => $bundle->getLabel(),
                'weight' => empty($bundle->info['parent']) ? $weight : 100 + $weight,
                'icon' => $this->_application->Entity_BundleTypeInfo($bundle, 'icon'),
            );
            $entity_type = $bundle->entitytype_name;
        }

        $user_id = isset($identity) ? $identity->id : $this->_application->getUser()->id;
        $language = $this->_application->getPlatform()->getCurrentLanguage();
        $cache_id = 'dashboard_post_counts_' . $this->_bundleGroup . '_' . $user_id . '_' . $language;
        $show_others = false;
        if (!isset($identity)
            && ($show_others = $this->_application->getComponent('Dashboard')->getConfig('show_others'))
        ) {
            $cache_id .= '_o' . (int)$show_others;
        }
        if (!$counts = $this->_application->getPlatform()->getCache($cache_id, 'content')) {
            $counts = [];
            if (!empty($ret)) {
                $statuses = [];
                $valid_status_keys = isset($identity) ? ['publish'] : ['publish', 'pending', 'draft', 'private'];
                foreach ($valid_status_keys as $status_key) {
                    $statuses[$status_key] = $this->_application->Entity_Status($entity_type, $status_key);
                }

                foreach (array_keys($ret) as $bundle_name) {
                    if ($show_others
                        && ($this->_application->HasPermission('entity_edit_others_' . $bundle_name)
                            || $this->_application->HasPermission('entity_delete_others_' . $bundle_name))
                    ) {
                        $other_statuses = $statuses;
                        if (!$this->_application->HasPermission('entity_edit_published_' . $bundle_name)
                            && !$this->_application->HasPermission('entity_delete_published_' . $bundle_name)
                        ) {
                            unset($other_statuses['publish']);
                        }
                        if (!$this->_application->HasPermission('entity_edit_private_' . $bundle_name)
                            && !$this->_application->HasPermission('entity_delete_private_' . $bundle_name)
                        ) {
                            unset($other_statuses['private']);
                        }
                        $counts[$bundle_name] = $this->_application->Entity_Query($entity_type)
                            ->fieldIs('bundle_name', $bundle_name)
                            ->startCriteriaGroup('OR')
                                ->startCriteriaGroup('AND')
                                    ->fieldIsIn('status', $statuses)
                                    ->fieldIs('author', $user_id)
                                ->finishCriteriaGroup()
                                ->startCriteriaGroup('AND')
                                    ->fieldIsIn('status', $other_statuses)
                                    ->fieldIsNot('author', $user_id)
                                ->finishCriteriaGroup()
                            ->finishCriteriaGroup()
                            ->count();
                    } else {
                        $counts[$bundle_name] = $this->_application->Entity_Query($entity_type)
                            ->fieldIs('bundle_name', $bundle_name)
                            ->fieldIsIn('status', $statuses)
                            ->fieldIs('author', $user_id)
                            ->count();
                    }
                }
            }
            $this->_application->getPlatform()->setCache($counts, $cache_id, 3600, 'content'); // cache 1 hour
        }
        foreach (array_keys($ret) as $bundle_name) {
            if (empty($counts[$bundle_name])) {
                // No posts, hide if child bundle, public profile, or no permission to create posts for the bundle
                if (!empty($bundles[$bundle_name]->info['parent'])
                    || isset($identity)
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

    public function dashboardPanelContent($link, array $params, AbstractIdentity $identity = null)
    {
        if (!$bundle = $this->_application->Entity_Bundle($link)) {
            throw new Exception\RuntimeException('Invalid bundle: ' . $link);
        }

        $dashboard_config = $this->_application->getComponent('Dashboard')->getConfig();

        $settings = [
            'mode' => 'dashboard_dashboard',
            'settings' => [
                'filter' => [
                    'show' => false,
                ],
                'pagination' => [
                    'perpage' => 20,
                    'allow_perpage' => true,
                    'perpages' => [20, 30, 50],
                ],
                'other' => [
                    'add' => [
                        'show' => empty($bundle->info['parent']),
                        'show_label' => !empty($dashboard_config['add_show_label']),
                    ],
                    'num' => true,
                ],
                'sort' => [
                    'default' => $default_sort = $bundle->entitytype_name . '_published',
                    'options' => [
                        $default_sort,
                        $bundle->entitytype_name . '_title',
                        $bundle->entitytype_name . '_published,asc',
                        'entity_level',
                        'voting_rating',
                        'voting_bookmark',
                        'voting_updown',
                    ],
                ],
                'query' => [
                    'user_id' => isset($identity) ? $identity->id : null,
                ],
            ],
        ];
        // Sorting options for non-public bundles can not be configured, therefore force sort by newest/oldest
        if (empty($bundle->info['public'])) {
            if (isset($identity)) return; // this should not happen but just in case

            $settings['settings']['sort']['options'] = [
                $default_sort,
                $bundle->entitytype_name . '_published,asc',
            ];
        } else {
            if (!isset($identity)) {
                $settings['settings']['query']['status'] = ['publish', 'pending', 'draft', 'private'];

                if (!empty($dashboard_config['show_others'])) {
                    // Set statuses for other user posts if permitted
                    if ($this->_application->HasPermission('entity_edit_others_' . $bundle->name)
                        || $this->_application->HasPermission('entity_delete_others_' . $bundle->name)
                    ) {
                        $status_others = ['pending', 'draft'];
                        if ($this->_application->HasPermission('entity_edit_published_' . $bundle->name)
                            || $this->_application->HasPermission('entity_delete_published_' . $bundle->name)
                        ) {
                            $status_others[] = 'publish';
                        }
                        if ($this->_application->HasPermission('entity_edit_private_' . $bundle->name)
                            || $this->_application->HasPermission('entity_delete_private_' . $bundle->name)
                        ) {
                            $status_others[] = 'private';
                        }
                        $settings['settings']['query']['status_others'] = $status_others;
                    }
                }
            }
        }

        return $this->_application->getPlatform()->render(
            $bundle->getPath(),
            ['settings' => $settings]
        );
    }
}
