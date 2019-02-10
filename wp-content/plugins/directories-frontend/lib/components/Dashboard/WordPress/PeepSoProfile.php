<?php
namespace SabaiApps\Directories\Component\Dashboard\WordPress;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Context;

class PeepSoProfile extends AbstractProfile
{
    public function __construct(Application $application, array $settings)
    {
        parent::__construct($application, 'PeepSo', $settings);
        $this->_init();
    }

    protected function _init()
    {
        add_filter('peepso_navigation_profile', function($links) {
            if (!isset($links['_user_id'])
                || (!$user_id = (int)$links['_user_id'])
                || ($this->_isOwnProfileOnly() && $user_id !== (int)$this->_application->getUser()->id)
            ) return $links;

            $identity = $this->_getIdentity($user_id);
            $panels = $this->_application->getComponent('Dashboard')->getActivePanels($identity);
            foreach (array_keys($panels) as $panel_name) {
                $link_name = 'drts-' . $panel_name;
                $links[$link_name] = [
                    'href' => $link_name,
                    'label' => $panels[$panel_name]['title'],
                    'icon' => null,
                ];
            }
            return $links;
        }, 1000);

        $panels = $this->_application->getComponent('Dashboard')->getActivePanels();
        foreach (array_keys($panels) as $panel_name) {
            $link_name = 'drts-' . $panel_name;
            add_action('peepso_profile_segment_' . $link_name, function() use ($link_name, $panel_name) {
                $user_id = \PeepSoUrlSegments::get_view_id(\PeepSoProfileShortcode::get_instance()->get_view_user_id());
                $data = [
                    'link_name' => $link_name,
                    'user_id' => $user_id,
                ];
                ob_start();
                $this->_displayPanel($panel_name, $user_id);
                $data['content'] = ob_get_clean();
                \PeepSoTemplate::add_template_directory(__DIR__ . '/peepso');
                echo \PeepSoTemplate::exec_template('drts', 'profile', $data, true);
            });
        }
    }

    public function redirectDashboardAccess(Context $context, array $paths)
    {
        if ($url = \PeepSoUser::get_instance()->get_profileurl()) {
            $this->_redirectDashboardAccess($context, $paths, $url);
        }
    }
}