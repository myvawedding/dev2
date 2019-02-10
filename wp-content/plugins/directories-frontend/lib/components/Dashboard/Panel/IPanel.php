<?php
namespace SabaiApps\Directories\Component\Dashboard\Panel;

use SabaiApps\Framework\User\AbstractIdentity;

interface IPanel
{
    public function dashboardPanelInfo($key = null);
    public function dashboardPanelOnLoad();
    public function dashboardPanelLabel();
    public function dashboardPanelLinks(AbstractIdentity $identity = null);
    public function dashboardPanelContent($link, array $params, AbstractIdentity $identity = null);
}