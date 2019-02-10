<?php
namespace SabaiApps\Directories\Component\Voting\DashboardPanel;

use SabaiApps\Directories\Component\Dashboard;
use SabaiApps\Framework\User\AbstractIdentity;

class VotesDashboardPanel extends Dashboard\Panel\AbstractPanel
{
    protected function _dashboardPanelInfo()
    {
        return [
            'weight' => 5,
            'wp_um_icon' => 'um-faicon-thumbs-up',
        ];
    }

    public function dashboardPanelLabel()
    {
        return __('Votes', 'directories');
    }

    protected function _dashboardPanelLinks(AbstractIdentity $identity = null)
    {
        $ret = [];
        $weight = 0;
        foreach (array_keys($this->_application->Voting_Types()) as $type) {
            if ((!$type_impl = $this->_application->Voting_Types_impl($type))
                || (!$type_info = $type_impl->votingTypeInfo())
            ) continue;

            $ret[$type] = array(
                'title' => $type_info['label'],
                'weight' => ++$weight,
                'icon' => $type_info['icon'],
            );
        }

        return $ret;
    }

    public function dashboardPanelContent($link, array $params, AbstractIdentity $identity = null)
    {
        return $this->_application->getPlatform()->render(
            $this->_application->getComponent('Dashboard')->getPanelUrl('voting_votes', $link, '/votes', [], true, $identity),
            [
                'is_dashboard' => false, // prevent rendering duplicate panel sections on reload panel
                'identity' => $identity,
            ]
        );
    }

    public function dashboardPanelOnLoad()
    {
        $this->_application->getPlatform()->loadJqueryUiJs(array('effects-highlight'));
    }
}
