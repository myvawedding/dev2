<?php
function SabaiApps_Directories_Component_CSV_WPAllImport_import_attachment($fieldName, $postId, $attachmentId, $imagePath, $importOptions) {
    if (!$attachment_ids = get_post_meta($postId, '_drts_imported_attachments', true)) {
        $attachment_ids = [];
    }
    $attachment_ids[$fieldName][$attachmentId] = $attachmentId;
    update_post_meta($postId, '_drts_imported_attachments', $attachment_ids);
}

function SabaiApps_Directories_Component_CSV_WPAllImport_create_function($fieldName) {
    $func = 'SabaiApps_Directories_Component_CSV_WPAllImport_import_attachment_' . $fieldName;
    if (!function_exists($func)) {
        eval('function ' . $func . '($postId, $attachmentId, $imagePath, $importOptions){
            SabaiApps_Directories_Component_CSV_WPAllImport_import_attachment("' . $fieldName . '", $postId, $attachmentId, $imagePath, $importOptions);
        }');
    }
    return $func;
}