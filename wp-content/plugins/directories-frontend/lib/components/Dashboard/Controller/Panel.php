<?php
namespace SabaiApps\Directories\Component\Dashboard\Controller;

use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Context;

class Panel extends Controller
{
    protected function _doExecute(Context $context)
    {
        $context->clearTemplates();
        if ($context->dashboard_panel) {
            $context->content = $this->Dashboard_Panels_impl($context->dashboard_panel)
                ->dashboardPanelContent($context->dashboard_panel_link, $context->getRequest()->getParams());
        } else {
            $context->content = __('No dashboard panels found.', 'directories-frontend');
        }
    }
}