<?php
namespace SabaiApps\Directories\Component\Map\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Exception;

class MarkerHelper
{
    protected static $_defaultImage = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAPAAAAC0CAQAAAAAlWljAAABIklEQVR42u3RAQ0AAAjDMK4c6aAD0klYM116XAADFmABFmABFmABBizAAizAAizAAgxYgAVYgAVYgAVYgAELsAALsAALsAADFmABFmABFmABBizAAizAAizAAizAgAVYgAVYgAVYgAELsAALsAALsAADBgxYgAVYgAVYgAUYsAALsAALsAALMGABFmABFmABFmABBizAAizAAizAAgxYgAVYgAVYgAUYsAALsAALsAALsAADFmABFmABFmABBizAAizAAizAAgzYBMACLMACLMACLMCABViABViABViAAQuwAAuwAAuwAAswYAEWYAEWYAEWYMACLMACLMACLMCABViABViABViABRiwAAuwAAuwAAswYAEWYAEWYAEWYMACrKst65UNXM2XNOgAAAAASUVORK5CYII=';

    public function help(Application $application, Entity\Type\IEntity $entity, array $settings, $container)
    {
        if (!isset($settings['coordinates_field'])) return;

        $markers = [];
        if ($values = $entity->getFieldValue($settings['coordinates_field'])) {
            $icon_color = null;
            $size = isset($settings['marker_size']) ? $this->_getSize($settings['marker_size']) : 'icon';
            $icon_type = isset($settings['view_marker_icon']) ? $settings['view_marker_icon'] : 'image';
            $link_title = !isset($settings['marker_link']) || $settings['marker_link'];
            foreach ($values as $key => $value) {
                if (!$value['lat'] || !$value['lng']) continue;

                if (!isset($image)) {
                    if (!$image = $application->Entity_Image($entity, isset($settings['marker_image_size']) ? $settings['marker_image_size'] : 'thumbnail')) {
                        $image = false;
                    }
                }
                $icon_is_url = $icon_is_full = false;
                if (!isset($icon)) {
                    $icon = false;
                    if (!empty($icon_type)) {
                        if ($icon_type === 'image') {
                            // icon is image
                            if ($_icon = $application->Entity_Image($entity, $size)) {
                                $icon = $_icon;
                                $icon_is_url = true;
                            }
                        } else {
                            // icon is taxonomy term icon
                            if ($terms = $entity->getFieldValue($icon_type)) {
                                foreach ($terms as $term) {
                                    if ($_icon = $term->getCustomProperty('image_src')) {
                                        $icon = $_icon;
                                        $icon_is_url = true;
                                    } elseif ($_icon = $term->getCustomProperty('icon_src')) {
                                        $icon = $_icon;
                                        $icon_is_url = $icon_is_full = true;
                                    } elseif ($_icon = $term->getCustomProperty('icon')) {
                                        $icon = $_icon;
                                        $icon_color = $term->getCustomProperty('color');
                                    }
                                }
                            }
                        }
                    }
                }
                if (!isset($permalink)) {
                    $permalink = $link_title ? $application->Entity_Permalink($entity, ['atts' => ['class' => DRTS_BS_PREFIX . 'text-white']]) : $application->H($entity->getTitle());
                }
                $address = $application->Filter('map_marker_address', $application->H($value['address']), [$entity, $value, $settings]);
                $markers[$key] = array(
                    'index' => $key,
                    'entity_id' => $entity->getId(),
                    'content' => $this->_getContent($application, $entity, $permalink, $image, $address),
                    'lat' => $value['lat'],
                    'lng' => $value['lng'],
                    'icon' => $icon ? [empty($icon_is_url) ? 'icon' : 'url' => $icon, 'icon_color' => $icon_color] : null,
                );
                if (!empty($icon_is_full)) {
                    $markers[$key]['icon']['is_full'] = true;
                }
            }
        }
        return $markers;
    }
    
    protected function _getContent(Application $application, Entity\Type\IEntity $entity, $permalink, $imageSrc, $address)
    {
        if (!$content = $application->Filter('map_marker_content', null, [$entity, $permalink, $imageSrc, $address])) {
            $image_html = '<img class="' . DRTS_BS_PREFIX . 'card-img" src="' . self::$_defaultImage . '" alt="" />';
            if ($imageSrc) {
                $image_html .= '<img class="' . DRTS_BS_PREFIX . 'card-img" src="' . $imageSrc . '" alt="" style="position:absolute;" />';
            }
            $content = sprintf(
                '<div class="%1$scard %1$sborder-0 %1$sbg-dark %1$stext-white drts-location-card">
%2$s
<div class="%1$scard-img-overlay drts-location-card-body %1$sp-2">
<div class="%1$scard-title">%3$s</div>
<address class="%1$scard-text">%4$s</address>
</div></div>',
                DRTS_BS_PREFIX,
                $image_html,
                $permalink,
                $address
            );
        }
        return $content;
    }
    
    public function all(Application $application, Entity\Model\Bundle $bundle, array $entities, array $settings, $container)
    {
        $markers = [];
        foreach ($entities as $entity) {
            $_markers = $this->help($application, $entity, $settings, $container);
            foreach (array_keys($_markers) as $i) {
                $markers[] = $_markers[$i];
            }
        }
        
        return $markers;
    }
    
    protected function _getSize($size)
    {   
        if (!is_numeric($size)) return $size;
        
        if ($size > 54) return 'icon_xl';
        
        return $size <= 38 ? 'icon' : 'icon_lg';
    }

    public function iconOptions(Application $application, Entity\Model\Bundle $bundle)
    {
        $ret = [];
        if (!empty($bundle->info['taxonomies'])) {
            if ($bundle->info['entity_image']) {
                $ret['image'] = __('Show image', 'directories');
            }
            foreach ($bundle->info['taxonomies'] as $taxonomy_bundle_type => $taxonomy) {
                if ($taxonomy_bundle = $application->Entity_Bundle($taxonomy)) {
                    if (!empty($taxonomy_bundle->info['entity_image'])) {
                        $ret[$taxonomy_bundle_type] = __('Show taxonomy image', 'directories')
                            . ' - ' . $taxonomy_bundle->getLabel('singular');
                    } elseif (!empty($taxonomy_bundle->info['entity_icon'])) {
                        $ret[$taxonomy_bundle_type] = __('Show taxonomy icon', 'directories')
                            . ' - ' . $taxonomy_bundle->getLabel('singular');
                    }
                }
            }
        }
        if (!empty($ret)) {
            $ret['default'] = __('Default', 'directories');
        }

        return $ret;
    }
}