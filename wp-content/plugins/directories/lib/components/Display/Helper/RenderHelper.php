<?php
namespace SabaiApps\Directories\Component\Display\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Entity;

class RenderHelper
{
    protected static $_current = [], $_cssLoaded, $_defaults = [
        'attr' => [],
        'pre_render' => false,
        'tag' => 'div',
        'element_tag' => 'div',
        'render_empty' => false,
        'cache' => false,
        'html_as_array' => false,
        'wrap_js' => true,
    ];

    public function help(Application $application, $bundle, $displayName, $var, array $options = [])
    {
        if (!$bundle = $application->Entity_Bundle($bundle)) return'';

        if (is_array($displayName)) {
            $display = $displayName;
        } else {
            if (!$display = $application->Display_Display($bundle->name, $displayName)) return '';
        }

        $options += self::$_defaults;

        if ($options['cache'] !== false) {
            $cache_id = $this->_getDisplayCacheId($display, $bundle, $var);
            if (false !== $cached = $application->getPlatform()->getCache($cache_id, 'content')) {
                return $cached;
            }
        }

        switch ($display['type']) {
            case 'entity':
                if (!$var instanceof \SabaiApps\Directories\Component\Entity\Type\IEntity) return [];

                if ($options['pre_render'] && $display['pre_render']) {
                    $_var = array('entities' => array($var->getId() => $var), 'html' => &$display['html']);
                    $this->preRender($application, $display, $bundle, $_var);
                }
                if ($display['name'] === 'detailed') {
                    if (!isset($GLOBALS['drts_display_elements'])) {
                        $GLOBALS['drts_display_elements'] = [];
                    }
                }
                $options['attr'] += [
                    'data-entity-id' => $var->getId(),
                    'class' => $application->Entity_HtmlClass($var),
                    'data-type' => $var->getType(),
                    'data-bundle-type' => $var->getBundleType(),
                    'data-bundle-name' => $var->getBundleName(),
                ];
                break;

            case 'form':
            case 'filters':
                if (!$var instanceof \SabaiApps\Directories\Component\Form\Form) return [];
                break;

            default:
                return [];
        }

        array_push(self::$_current, array($bundle->name, $display['name']));

        // HTML
        $html = [];
        foreach (array_keys($display['elements']) as $element_id) {
            if (!$rendered = $this->element(
                $application,
                $bundle,
                $display['elements'][$element_id],
                $var,
                $options['element_tag']
            )) continue;

            $html[$element_id] = $rendered;
        }
        if (empty($html) && !$options['render_empty']) {
            array_pop(self::$_current);
            if (isset($cache_id)) {
                $application->getPlatform()->setCache ('', $cache_id, null, 'content');
            }
            return '';
        }

        // Attributes
        $options['attr']['data-display-type'] = $display['type'];
        $options['attr']['data-display-name'] = $display['name'];
        // Add CSS class
        $class = 'drts-display ' . $display['class'];
        if (isset($options['attr']['class'])) {
            $options['attr']['class'] .= ' ' . $class;
        } else {
            $options['attr']['class'] = $class;
        }

        // CSS
        $this->css($application, $display);

        // Let others modify output
        $ret = $application->Filter('display_render', array('html' => $html, 'js' => [], 'attr' => $options['attr']), array($display, $bundle, $var, $options));

        // Concatenate and wrap with tags
        if ($options['tag']) {
            $ret['html'] = '<' . $options['tag'] . $application->Attr($ret['attr']) . '>' . implode(PHP_EOL, $ret['html']) . '</' . $options['tag'] . '>';
        } else {
            if (empty($options['html_as_array'])) {
                $ret['html'] = implode(PHP_EOL, $ret['html']);
            }
        }
        if (!empty($ret['js'])) {
            $ret['js'] = implode(PHP_EOL, $ret['js']);
            $ret['js'] = $options['wrap_js'] && strlen($ret['js']) ? '<script type="text/javascript">' .  $ret['js'] . '</script>' : '';
        } else {
            $ret['js'] = '';
        }

        array_pop(self::$_current);

        // Cache?
        if (isset($cache_id)) {
            $application->getPlatform ()->setCache($ret, $cache_id, $options['cache']);
        }

        return $ret;
    }

    public function css(Application $application, array $display = null)
    {
        if (!isset(self::$_cssLoaded)) {
            self::$_cssLoaded = [];

            // Load default CSS stylesheets
            $application->getPlatform()->loadDefaultAssets(false);

            // Load CSS stylesheets once
            $application->getPlatform()->addCssFile('display-display.min.css', 'drts-display-display', array('drts'));
        }
        if (isset($display)
            && !empty($display['css'])
        ) {
            if (!isset(self::$_cssLoaded[$display['bundle_name']][$display['type']][$display['name']])) {
                self::$_cssLoaded[$display['bundle_name']][$display['type']][$display['name']] = true;

                $application->getPlatform()->addCss($display['css'], 'drts-display-display');
            }
        }
    }

    protected function _getDisplayCacheId(array $display, Entity\Model\Bundle $bundle, $var)
    {
        $ret = 'display_rendered_' . $bundle->name . '_' . $display['type'];
        if ($display['type'] === 'entity') {
            $ret .= '_' . $display['name'] . '_' . $var->getId();
        }

        return $ret;
    }

    protected function _getElementCacheId(Entity\Model\Bundle $bundle, $elementId, $entity = null)
    {
        $ret = 'display_rendered_' . $bundle->name . '_element_' . $elementId;
        if ($entity instanceof \SabaiApps\Directories\Component\Entity\Type\IEntity) {
            $ret .= '_' . $entity->getId();
        }

        return $ret;
    }

    public function clearElementCache(Application $application, Entity\Model\Bundle $bundle, $elementId)
    {
        $cache_id = $this->_getElementCacheId($bundle, $elementId);
        $application->getPlatform()->clearCache('content', $cache_id);
    }

    public function element(Application $application, Entity\Model\Bundle $bundle, array $element, $var, $tag = 'div')
    {
        if (!empty($element['visibility']['hide_on_parent'])
            && $var instanceof \SabaiApps\Directories\Component\Entity\Type\IEntity
            && $var->isOnParentPage()
        ) return;

        if (!empty($element['advanced']['cache'])) {
            $cache_id = $this->_getElementCacheId($bundle, $element['id'], $var);
            if (false !== ($cached = $application->getPlatform()->getCache($cache_id, 'content'))
                && is_string($cached)
            ) {
                return $cached;
            }
        }

        if (!$rendered = $this->_renderElement($application, $bundle, $element, $var, $tag)) return;

        if (!empty($element['visibility']['globalize'])) {
            $GLOBALS['drts_display_elements'][$bundle->name][$element['id']] = $rendered;

            // Remove from display?
            if (!empty($element['visibility']['globalize_remove'])) return;
        }

        // Cache?
        if (isset($cache_id)) {
            $application->getPlatform()->setCache($rendered, $cache_id, $element['advanced']['cache'], 'content');
        }

        return $rendered;
    }

    protected function _renderElement(Application $application, Entity\Model\Bundle $bundle, array $element, $var, $tag)
    {
        try {
            $element_impl = $application->Display_Elements_impl($bundle, $element['name']);
            $rendered = $element_impl->displayElementRender($bundle, $element, $var);
        } catch (Exception\IException $e) {
            $application->logError($e);
            return;
        }

        $style = '';
        $class = $element['class'];
        if (isset($element['advanced']['css_class'])) {
            $class .= ' ' . $element['advanced']['css_class'];
        }
        $attr = [];
        if (isset($element['advanced']['css_id'])) {
            $attr['id'] = $element['advanced']['css_id'];
            if (strpos($attr['id'], '%id%')
                && $var instanceof \SabaiApps\Directories\Component\Entity\Type\IEntity
            ) {
                $attr['id'] = str_replace('%id%', $var->getId(), $attr['id']);
            }
        }
        if (is_array($rendered)) {
            if (!empty($rendered['raw'])) {
                return $rendered['raw'];
            }
            if (isset($rendered['style'])) {
                $style = $rendered['style'];
            }
            if (isset($rendered['attr'])) {
                $attr += $rendered['attr'];
            }
            if (isset($rendered['class'])) {
                $class .= ' ' . $rendered['class'];
            }
            $rendered = $rendered['html'];
        } else {
            $rendered = (string)$rendered;
        }

        // Filter rendered element
        $rendered = $application->Filter(
            'display_element_render',
            $rendered,
            array($bundle, $element['name'], $element['settings'], $var)
        );

        if (!strlen($rendered)
            && $tag !== 'td'
        ) return;

        $heading = '';
        if (isset($element['heading']['label'])) {
            $heading = $application->Display_ElementLabelSettingsForm_label(
                $element['heading'],
                $application->Display_Elements_impl($bundle, $element['name'])->displayElementStringId('heading', $element['_element_id'])
            );
            if (strlen($heading)) {
                $heading = '<div class="drts-display-element-header"><span>' . $heading . '</span></div>';
            }
        }

        $attr = empty($attr) ? '' : $application->Attr($attr);
        return '<' . $tag . ' class="' . $class . '" style="' . $style . '" data-name="' . $element['name'] . '"' . $attr . '>' . $heading . $rendered . '</' . $tag . '>';
    }

    public function preRender(Application $application, array $display, Entity\Model\Bundle $bundle, &$var)
    {
        foreach ($display['elements'] as $element) {
            if (empty($element['pre_render'])
                || (!$element_impl = $application->Display_Elements_impl($bundle, $element['name'], true))
            ) continue;

            // Skip if cached
            if (!empty($element['advanced']['cache'])
                && $display['type'] === 'entity'
            ) {
                $_var = $var;
                foreach (array_keys($var['entities']) as $entity_id) {
                    if (false !== $application->getPlatform()->getCache($this->_getElementCacheId($bundle, $element['id'], $var['entities'][$entity_id]), 'content')) {
                        unset($_var['entities'][$entity_id]);
                    }
                }
                if (!empty($_var['entities'])) {
                    $element_impl->displayElementPreRender($bundle, $element, $display['type'], $_var);
                }
            } else {
                $element_impl->displayElementPreRender($bundle, $element, $display['type'], $var);
            }
        }
    }

    public static function isRendering($bundleName = null, $displayName = null)
    {
        if (empty(self::$_current)) return false;

        if (isset($bundleName)) {
            $current = current(self::$_current);
            if ($bundleName !== $current[0]) return false;

            if (isset($displayName)) {
                if ($displayName !== $current[1]) return false;
            }
        }
        return true;
    }
}
