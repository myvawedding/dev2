<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class PermalinkUrlHelper
{
    public function help(Application $application, Entity\Type\IEntity $entity, $fragment = '', $lang = null)
    {
        return $application->Url(array(
            'route' => $application->Entity_Path($entity, $lang),
            'params' => [],
            'fragment' => $fragment,
            'script' => 'main',
        ));
    }
}