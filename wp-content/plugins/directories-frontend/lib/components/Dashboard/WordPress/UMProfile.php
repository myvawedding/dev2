<?php
namespace SabaiApps\Directories\Component\Dashboard\WordPress;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Context;

class UMProfile extends AbstractProfile
{
    public function __construct(Application $application, array $settings)
    {
        parent::__construct($application, 'Ultimate Member', $settings);
        add_filter('um_profile_tabs', [$this, 'profile'], 1000);
    }

    public function redirectDashboardAccess(Context $context, array $paths)
    {
        if ($url = um_user_profile_url()) {
            $this->_redirectDashboardAccess($context, $paths, $url, 'profiletab');
        }
    }
    
    public function profile($tabs)
    {
        if (!um_get_requested_user() // Not rendering profile tabs yet
            || (!$user_id = (int)um_profile_id())
            || ($this->_isOwnProfileOnly() && $user_id !== (int)$this->_application->getUser()->id)
        ) return $tabs;

        $identity = $this->_getIdentity($user_id);
        $panels = $this->_application->getComponent('Dashboard')->getActivePanels($identity);
        foreach (array_keys($panels) as $panel_name) {
            $tab_name = 'drts-' . $panel_name;
            $tabs[$tab_name] = array(
                'name' => $panels[$panel_name]['title'],
                'icon' => isset($panels[$panel_name]['wp_um_icon']) ? $panels[$panel_name]['wp_um_icon'] : 'um-faicon-pencil',
                'custom' => true
            );
            add_action('um_profile_content_' . $tab_name . '_default', function($args) use ($identity, $panel_name) {
                $this->_displayPanel($panel_name, $identity);
            });
        }

        return $tabs;
    }
}