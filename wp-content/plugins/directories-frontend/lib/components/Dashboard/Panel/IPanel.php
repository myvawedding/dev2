<?php
namespace SabaiApps\Directories\Component\Dashboard\Panel;

interface IPanel
{
    public function dashboardPanelInfo();
    public function dashboardPanelOnLoad();
    public function dashboardPanelLabel();
    public function dashboardPanelLinks();
    public function dashboardPanelContent($link, array $params);
}