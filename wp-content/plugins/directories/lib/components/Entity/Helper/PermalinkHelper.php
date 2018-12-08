<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class PermalinkHelper
{
    public function help(Application $application, IEntity $entity, array $options = [], $fragment = '', $lang = null)
    {
        if (isset($options['atts'])) {
            $atts = $options['atts'];
            unset($options['atts']);
        } else {
            $atts = [];
        }   
        $title = $atts['title'] = isset($options['title']) ? $options['title'] : $application->Entity_Title($entity);
        
        if (!empty($options['icon'])) {
            $icon_class = 'drts-icon';
            if (!empty($options['icon_size'])) {
                $icon_class .= ' drts-icon-' . $options['icon_size'];
            }
            if (!is_bool($options['icon'])) {
                if (!empty($options['icon_is_image'])) {
                    $image_url = !empty($options['icon_is_value']) ? $options['icon'] : $application->Entity_Image($entity, 'icon', $options['icon']);
                    if ($image_url) {
                        $icon = '<img src="' . $image_url . '" alt="" class="' . $icon_class . '" />';
                    } else {
                        $show_default_icon = !empty($options['icon_fallback']);
                    }
                } else {
                    $_icon_class = !empty($options['icon_is_value']) ? $options['icon'] : $application->Entity_Icon($entity, !empty($options['icon_fallback']), $options['icon']);
                    if ($_icon_class) {
                        $style = isset($options['icon_color']) && ($color = $this->_getEntityColor($application, $entity, $options['icon_color']))
                            ? 'background-color:' . $color . ';color:#fff;'
                            : '';
                        $icon = '<i style="' . $style . '" class="' . $icon_class . ' ' . $_icon_class . '"></i>';
                    }
                }
            } else {
                $show_default_icon = true;
            }
            if (!empty($show_default_icon)
                && ($_icon_class = $application->Entity_BundleTypeInfo($entity->getBundleType(), 'icon') )
            ) {
                $bgcolor = isset($options['icon_color']) && ($_bgcolor = $this->_getEntityColor($application, $entity, $options['icon_color']))
                    ? 'background-color:' . $_bgcolor . ';'
                    : '';
                $icon = '<i style="' . $bgcolor . '" class="' . $icon_class . ' ' . $_icon_class . '"></i>';
            }
            if (isset($icon)) {
                if (empty($options['no_escape'])) {
                    $title = $application->H($title);
                    $options['no_escape'] = true;
                }
                $title = $icon . '<span>' . $title . '</span>';
            }
            unset($options['icon']); // prevent being passed to LinkTo helper
        }
        
        if (!empty($options['no_link'])) return $title;
        
        if (!isset($atts['class'])) $atts['class'] = '';
        $atts['class'] .= ' drts-entity-permalink drts-entity-' . $entity->getId();
        $atts['data-type'] = $entity->getType();
        $atts['data-bundle-type'] = $entity->getBundleType();
        $atts['data-bundle-name'] = $entity->getBundleName();
        if (!empty($options['rel'])) $atts['rel'] = $options['rel'];
        
        return $application->LinkTo(
            $title,
            isset($options['script_url'])
                ? $application->createUrl(array('script_url' => $options['script_url']))
                : $application->Entity_PermalinkUrl($entity, $fragment, $lang),
            $options,
            $atts
        );
    }
    
    protected function _getEntityColor(Application $application, IEntity $entity, $color)
    {
        return strpos($color, '#') === 0 ? $color : (string)$entity->getSingleFieldValue($color);
    }
    
    protected function _getEntityTitle(Application $application, IEntity $entity, array $options)
    {
        if (isset($options['title'])) {
            $title = $options['title'];
            if (empty($options['no_escape'])) {
                $title = $application->H($title);
            }
        } else {
            $title = $application->H($application->Entity_Title($entity));
        }
        
        return $title;
    }
}