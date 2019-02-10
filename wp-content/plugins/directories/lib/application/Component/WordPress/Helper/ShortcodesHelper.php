<?php
namespace SabaiApps\Directories\Component\WordPress\Helper;

use SabaiApps\Directories\Application;

class ShortcodesHelper
{
    public function help(Application $application)
    {
        if (!$shortcodes = $application->getPlatform()->getCache('wordpress_shortcodes', false)) {
            $shortcodes = [];
            $slugs = $application->System_Slugs(null, false);
            foreach (array_keys($slugs) as $component_name) {
                foreach ($slugs[$component_name] as $slug_name => $slug_info) {
                    if (isset($slug_info['wp_shortcode'])
                        && !is_array($slug_info['wp_shortcode']) // array meaning using an existing shortcode, so no need to register here
                    ) {
                        $shortcodes[$slug_info['wp_shortcode']] = [
                            'component' => $component_name,
                            'slug' => $slug_name,
                            'path' => null,
                        ];
                    }
                }
            }
            $shortcodes = $application->Filter('wordpress_shortcodes', $shortcodes);
            $application->getPlatform()->setCache($shortcodes, 'wordpress_shortcodes', 0);
        }

        return $shortcodes;
    }
    
    public function doShortcode(Application $application, $atts, $content, $tag)
    {
        if (!is_array($atts)) $atts = [];
        $shortcodes = $this->help($application);
        $shortcode = $shortcodes[$tag];
        if (isset($atts['component'])) {
            $component = $atts['component'];
            unset($atts['component']);
        } else {
            $component = $shortcode['component'];
        }
        if (!$component
            || !$application->isComponentLoaded($component)
        ) return;

        $cache = $title = null;
        if (isset($atts['title'])) {
            $title = empty($atts['title']) ? false : $atts['title'];
            unset($atts['title']);
        }
        if (isset($atts['cache'])) {
            $cache = !empty($atts['cache']);
            unset($atts['cache']);
        }
        try {
            $filtered = $application->Filter(
                'wordpress_do_shortcode',
                ['atts' => (array)$atts] + $shortcode,
                [$tag, $component]
            );
            if (isset($filtered['path'])) {
                $path = $filtered['path'];
            } elseif (isset($filtered['slug'])) {
                $path = '/' . $application->getComponent($component)->getSlug($filtered['slug']);
            } else {
                throw new Exception\RuntimeException('Shortcode [' . $tag . ']: Invalid route or slug.');
            }
            if (isset($filtered['title'])) {
                $title = $filtered['title'];
            }
        } catch (Exception\IException $e) {
            $application->logError($e);
            return;
        }

        $atts_param_name = isset($shortcode['atts_name']) ? $shortcode['atts_name'] : 'settings';
        return $application->getPlatform()->render($path, [$atts_param_name => $filtered['atts']], $cache, $title);
    }
}