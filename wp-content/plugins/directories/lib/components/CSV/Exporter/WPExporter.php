<?php
namespace SabaiApps\Directories\Component\CSV\Exporter;

use SabaiApps\Directories\Component\Entity;

class WPExporter extends AbstractExporter
{
    public function csvExporterSettingsForm(Entity\Model\Field $field, array $settings, $column, $enclosure, array $parents = [])
    {
        switch ($this->_name) {
            case 'wp_post_parent':
                return array(
                    'type' => array(
                        '#type' => 'select',
                        '#title' => __('Parent content ID type', 'directories'),
                        '#description' => __('Select the type of data used to specify parent content items.', 'directories'),
                        '#options' => array(
                            'id' => __('ID', 'directories'),
                            'slug' => __('Slug', 'directories'),
                        ),
                        '#default_value' => 'slug',
                        '#horizontal' => true,
                    ),
                );
            case 'wp_image':
            case 'wp_file':
                return $this->_getZipFileSettingsForm() + $this->_acceptMultipleValues($field, $enclosure, $parents);
        }
    }
    
    public function csvExporterDoExport(Entity\Model\Field $field, array $settings, $value, array $columns, array &$formStorage)
    {
        switch ($this->_name) {
            case 'wp_post_content':
            case 'wp_post_status':
            case 'wp_term_description':
                return $value;
            case 'wp_post_parent':
                if ($settings['type'] === 'slug') {
                    return ($parent_entity = $this->_application->Entity_Entity('post', $value, false)) ? $parent_entity->getSlug() : null;
                }
                return $value;
            case 'wp_image':
            case 'wp_file':
                $ret = [];
                $field_name = $field->getFieldName();
                if (!$this->_doZipFile($settings)
                    || (!$zip = $this->_getZipFile($field_name, $formStorage))
                ) {
                    foreach ($value as $_value) {
                        if (!$file_path = get_attached_file($_value['attachment_id'])) continue;
                        
                        $ret[] = basename($file_path);
                    }
                } else {
                    foreach ($value as $_value) {
                        if (!$file_path = get_attached_file($_value['attachment_id'])) continue;
                        
                        $ret[] = $file_name = basename($file_path);
                        $zip->addFile($file_path, $file_name);
                    }
                    if (!empty($ret)
                        && !in_array($zip->filename, $formStorage['files'])
                    ) {
                        $formStorage['files'][] = $zip->filename;
                    }
                    $zip->close();
                }
             
                return isset($settings['_separator']) ? implode($settings['_separator'], $ret) : $ret[0];
        }
    }
}