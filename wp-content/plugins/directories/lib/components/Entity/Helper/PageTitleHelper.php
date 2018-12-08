<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class PageTitleHelper
{
    public function help(Application $application, Entity\Type\IEntity $entity, $withIcon = true)
    {
        $title = $application->Entity_Title($entity);
        $bundle = $application->Entity_Bundle($entity, null, '', true);
        if ($entity->getParentId()) {
            $title = sprintf(
                $bundle->getLabel('page'),
                $title,
                ($parent_entity = $application->Entity_ParentEntity($entity, false)) ? $application->Entity_Title($parent_entity) : ''
            );
        } elseif (empty($bundle->info['is_primary'])) {
            $title = sprintf($bundle->getLabel('page'), $title);
        }
        $title = $application->H($title);

        // Add icon?
        if ($withIcon
            && ($icon = $application->Entity_Icon($entity, false))
        ) {
            $icon .= ' fa-border';
            $icon .= $application->getPlatform()->isRtl() ? ' fa-pull-left' : ' fa-pull-right';
            if ($color = $application->Entity_Color($entity)) {
                $style = 'background-color:' . $color . ';color:#fff;';
            } else {
                $style = '';
            }
            $title = '<i class="drts-' . $icon . '" style="' . $style . '"></i>' . $title;
        }
        
        return $application->Filter('entity_page_title', $title, [$withIcon]);
    }
}
