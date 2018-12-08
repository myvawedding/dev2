<?php
namespace SabaiApps\Directories\Component\Form\Helper;

use SabaiApps\Directories\Application;

class LoadTypeAheadHelper
{
    public function help(Application $application)
    {
        $application->getPlatform()->addJsFile('typeahead.bundle.min.js', 'twitter-typeahead', 'jquery', 'directories', true, true)
            ->addCssFile('form-typeahead.min.css', 'drts-form-typeahead', 'drts', 'directories');
    }
}