<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Entity;

class DisplayHelper
{
    protected $_templatePaths = [];
    
    public function help(Application $application, Entity\Type\IEntity $entity, $display, array $vars, array $options = [], $extension = '.html')
    {
        $bundle_name = $entity->getBundleName();
        $display_name = is_array($display) ? $display['name'] : $display;
        if (!isset($this->_templatePaths[$bundle_name][$display_name])) {
            if ($template_path = $this->hasCustomTemplate($application, $bundle_name, $display_name, $extension)) {
                if ($extension === '.html') {
                    $template_name = basename($template_path, '.html.php');
                    // Load custom CSS for this display if any
                    $css_path = $template_name . '.css';
                    if (file_exists($css_path)) {
                        $application->getPlatform()->addCssFile(
                            $this->_application->FileUrl($css_path),
                            'drts-entity-display-template-name-' . $template_name,
                            array('drts-display-display'),
                            false
                        );
                    }
                }
            } else {
                if (!$template_path = $application->getTemplate()->exists('entity_display', $extension)) {
                    // This shouldn't happen, but just in case
                    throw new Exception\RuntimeException('Template file entity_display does not exist.');
                }
            }
            $this->_templatePaths[$bundle_name][$display_name] = $template_path;
        }
        // Cache summary display if not explicitly disabled
        //if ($display_name === 'summary'
        //    && !isset($options['cache'])
        //) {
        //    $options['cache'] = 3600;
        //}
        $vars = [
            'entity' => $entity,
            'display' => $display, // this may be either string or array
            'display_name' => $display_name,
            'options' => $options,
        ] + $vars;
        $application->getTemplate()->includeFile($this->_templatePaths[$bundle_name][$display_name], $vars);
    }
    
    public function hasCustomTemplate(Application $application, $entityOrBundle, $displayName, $extension = '.html')
    {
        $bundle = $application->Entity_Bundle($entityOrBundle);
        $template_name = $bundle->type . '-display_' . $displayName;
        $templates = $bundle->group ? [$template_name . '-' . $bundle->group] : [];
        $templates[] = $template_name;
        foreach ($templates as $_template_name) {
            if ($template_path = $application->getTemplate()->exists($_template_name, $extension)) {
                return $template_path;
            }
        }
        return false;
    }
    
    public function preRender(Application $application, array $entities, $displayName, $displayType = 'entity')
    {
        $entities_by_bundle = $html = [];
        foreach ($entities as $entity_id => $entity) {
            $entities_by_bundle[$entity->getBundleName()][$entity_id] = $entity;
        }
        foreach (array_keys($entities_by_bundle) as $bundle_name) {
            $pre_rendered = $this->preRenderbyBundle($application, $bundle_name, $entities_by_bundle[$bundle_name], $displayName, $displayType);
            if (!empty($pre_rendered['html'])) {
                foreach (array_keys($pre_rendered['html']) as $k) {
                    $html[$k] = $pre_rendered['html'][$k];
                }
            }
        }
        
        return ['entities' => $entities, 'html' => $html];
    }
    
    public function preRenderbyBundle(Application $application, $bundleName, array $entities, $displayName, $displayType = 'entity')
    {
        $ret = array(
            'entities' => $entities,
            'html' => [],
        );
        if (($display = $application->Display_Display($bundleName, $displayName, $displayType))
            && $display['type'] === 'entity'
            && $display['pre_render']
        ) {
            $application->callHelper(
                'Display_Render_preRender',
                array($display, $application->Entity_Bundle($bundleName), &$ret)
            );
        }
        
        return $ret;
    }
}