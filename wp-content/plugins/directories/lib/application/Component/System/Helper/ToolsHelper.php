<?php
namespace SabaiApps\Directories\Component\System\Helper;

use SabaiApps\Directories\Application;

class ToolsHelper
{
    public function changeCollation(Application $application, $collation)
    {
        $tables = [
            'directory_directory',
            'display_display',
            'display_element',
            'entity_bundle',
            'entity_field',
            'entity_field_choice',
            'entity_field_claiming_status',
            'entity_field_color',
            'entity_field_date',
            'entity_field_email',
            'entity_field_entity_child_count',
            'entity_field_entity_featured',
            'entity_field_entity_term_content_count',
            'entity_field_entity_terms',
            'entity_field_frontendsubmit_guest',
            'entity_field_icon',
            'entity_field_location_address',
            'entity_field_payment_plan',
            'entity_field_phone',
            'entity_field_range',
            'entity_field_review_rating',
            'entity_field_social_accounts',
            'entity_field_time',
            'entity_field_url',
            'entity_field_video',
            'entity_field_voting_vote',
            'entity_field_wp_file',
            'entity_field_wp_image',
            'entity_fieldconfig',
            'payment_feature',
            'payment_featuregroup',
            'system_component',
            'system_route',
            'view_filter',
            'view_view',
            'voting_vote',
        ];
        $prefix = $application->getDB()->getResourcePrefix();
        $charset = in_array($collation, ['utf8_general_ci', 'utf8_unicode_ci']) ? 'utf8' : 'utf8mb4';
        foreach ($application->Filter('system_collate_tables', $tables) as $table_name) {
            $sql = sprintf(
                'ALTER TABLE %s%s CONVERT TO CHARACTER SET %s COLLATE %s;',
                $prefix,
                $table_name,
                $charset,
                $collation
            );
            try {
                $application->getDB()->exec($sql);
            } catch (\Exception $e) {
                $application->logError($e->getMessage());
            }
        }
    }
}