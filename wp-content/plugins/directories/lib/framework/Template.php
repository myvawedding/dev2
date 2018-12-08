<?php
namespace SabaiApps\Framework;

class Template
{
    private $_dirs, $_paths = [];

    public function __construct(array $dirs = [])
    {
        $this->_dirs = $dirs;
    }
    
    public function getDirs()
    {
        return $this->_dirs;
    }
    
    public function display($templateName, array $vars = [], $extension = '.html')
    {
        foreach ((array)$templateName as $template_name) {
            if ($template_path = $this->exists($template_name, $extension)) {
                $this->includeFile($template_path, $vars);
                return;
            }
        }
        if (isset($vars['content'])) {
            echo $vars['content'];
            return;
        }
        throw new Exception(sprintf('No valid template file was found: %s', implode(', ', (array)$templateName)));
    }
    
    public function includeFile($templatePath, array $vars = [])
    {
        $this->_include($templatePath, $vars);
    }

    public function render($templateName, array $vars = [], $extension = '.html')
    {
        ob_start();
        try {
            $this->display($templateName, $vars, $extension);
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }
        return ob_get_clean();
    }
    
    public function exists($templateName, $extension = '.html')
    {
        // Template name is a full template path?
        if (strpos($templateName, '/') !== false) {
            $original_template_name = $templateName; // save for later use
            $templateName = basename($templateName);
        }
        // Already resolved?
        if (isset($this->_paths[$templateName][$extension])) {
            return $this->_paths[$templateName][$extension];
        }
        // Search all template directories
        $template_file = $templateName . $extension . '.php';
        foreach ($this->_dirs as $template_dir) {
            $template_path = $template_dir . '/' . $template_file;
            if (file_exists($template_path)) {
                $this->_paths[$templateName][$extension] = $template_path;
                return $template_path;
            }
        }
        // Search full path if it was originally requested that way
        if (isset($original_template_name)
            && file_exists($template_path = $original_template_name . $extension . '.php')
        ) {
            $this->_paths[$templateName][$extension] = $template_path;
            return $template_path;
        }
        // Template not found
        $this->_paths[$templateName][$extension] = false;
        return false;
    }

    private function _include()
    {
        extract(func_get_arg(1), EXTR_SKIP);
        include func_get_arg(0);
    }
}