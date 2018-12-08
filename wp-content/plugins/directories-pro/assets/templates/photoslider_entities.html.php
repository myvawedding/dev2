<?php
$this->Action('view_entities_photo_slider', array($entities, $CONTEXT));
$photos = [];
$tag_term_field = $this->Entity_BundleTypeInfo($bundle, 'entity_tag');
if (!empty($settings['photoslider_link'])) $settings['photoslider_zoom'] = false;
foreach ($entities as $entity) {
    if (!$photo_url = $this->Entity_Image($entity, $settings['photoslider_image_size'], $settings['photoslider_image_field'])) continue;
    
    $photos[] = array(
        'url' => empty($settings['photoslider_link']) ? null : $this->Entity_PermalinkUrl($entity),
        'img' => $photo_url, 
        'tag' => $tag_term_field && ($term = $entity->getSingleFieldValue($tag_term_field)) ? $this->Entity_Title($term) : null,
        'title' => $this->Entity_Title($entity),
        'full_img' => empty($settings['photoslider_link']) ? $this->Entity_Image_url($entity, 'full', $settings['photoslider_image_field']) : null,
    );
}
// Show thumbnails?
if (!empty($settings['photoslider_thumbs'])
    && $settings['photoslider_columns'] === 1
    && $settings['photoslider_image_size'] !== 'thumbnail'
    && $settings['photoslider_image_size'] !== 'thumbnail_scaled'
){
    $settings['photoslider_thumbs'] = [];
    foreach ($entities as $entity) {
        if (!$thumbnail_url = $this->Entity_Image($entity, 'thumbnail', $settings['photoslider_image_field'])) {
            unset($settings['photoslider_thumbs']);
            break;
        }
    
        $settings['photoslider_thumbs'][] = array(
            'img' => $thumbnail_url,      
        );
    }
}
$settings['photoslider_lazyload'] = true;
echo $this->PhotoSlider_Slider($photos, $settings);