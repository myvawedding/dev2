<?php
namespace SabaiApps\Directories\Component\WordPressContent\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\WordPressContent\EntityType\PostEntity;

class DoShortcodeHelper
{
    public function help(Application $application, $atts, $content, $tag)
    {
        if (!is_array($atts)) $atts = [];

        switch ($tag) {
            case 'drts-entity':
                if (empty($atts['id'])) {
                    if (isset($GLOBALS['drts_entity'])) {
                        $entity = $GLOBALS['drts_entity'];
                    } elseif ($post = get_post()) {
                        $post_types = $application->getComponent('WordPressContent')->getPostTypes();
                        if (!isset($post_types[$post->post_type])) return;

                        $entity = new PostEntity($post);
                        $application->Entity_LoadFields($entity);
                    } else {
                        return;
                    }
                } else {
                    if (!$atts['id'] = intval($atts['id'])) return;

                    $type = isset($atts['type']) && in_array($atts['type'], ['term']) ? $atts['type'] : 'post';
                    if (!$entity = $application->Entity_Entity($type, $atts['id'])) return;
                }
                if (!empty($atts['field'])) {
                    if ((!$field = $application->Entity_Field($entity, $atts['field']))
                        || (!$field_type = $application->Field_Type($field->getFieldType(), true))
                    ) {
                        $application->logError('Shortcode [' . $tag . ']: Invalid field "' . $atts['field']) . '" specified using the field parameter.';
                        return;
                    }
                    if (!$field_type instanceof \SabaiApps\Directories\Component\Field\Type\IHumanReadable) {
                        $application->logWarning('Shortcode [' . $tag . ']: Unsupported field type "' . $field->getFieldType() . '" specified using the field parameter.');
                        return;
                    }
                    $separator = isset($atts['separator']) ? $atts['separator'] : null;
                    $key = isset($atts['key']) ? $atts['key'] : null;

                    return $field_type->fieldHumanReadableText($field, $entity, $separator, $key);
                } elseif (!empty($atts['display_element'])) {
                    if ((!$bundle = $application->Entity_Bundle($entity))
                        || (!$display = $application->Display_Display($bundle->name))
                        || (!$element = $application->Display_Display_element($display, $atts['display_element']))
                    ) return;

                    $application->Display_Render_css();
                    // Remove some unwanted options
                    $element['visibility']['hide_on_parent'] = $element['visibility']['globalize'] = false;
                    $rendered = $application->Display_Render_element($bundle, $element, $entity);
                    if ($rendered === null) return;

                    $show_header = !isset($atts['heading']) || $atts['heading'];
                    return sprintf(
                        '<div class="drts%s"><div class="drts-display %s%s" data-display-type="%s" data-display-name="%s">%s</div></div>',
                        $application->getPlatform()->isRtl() ? ' drtsrtl' : '',
                        $display['class'],
                        $show_header ? '' : ' drts-display-no-header',
                        $display['type'],
                        $display['name'],
                        $rendered
                    );
                }

                $cache = $title = null;
                if (isset($atts['title'])) {
                    $title = empty($atts['title']) ? false : $atts['title'];
                    unset($atts['title']);
                }
                if (isset($atts['cache'])) {
                    $cache = !empty($atts['cache']);
                    unset($atts['cache']);
                }

                return $application->getPlatform()->render(
                    $application->Entity_Path($entity),
                    ['settings' => ['display' => isset($atts['display']) ? $atts['display'] : 'detailed']],
                    $cache,
                    $title
                );

            default:
        }
    }
}