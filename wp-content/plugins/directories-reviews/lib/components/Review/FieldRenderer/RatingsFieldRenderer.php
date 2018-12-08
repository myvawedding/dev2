<?php
namespace SabaiApps\Directories\Component\Review\FieldRenderer;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class RatingsFieldRenderer extends RatingFieldRenderer
{    
    protected function _fieldRendererInfo()
    {
        return array(
            'field_types' => array('voting_vote'),
            'default_settings' => array(
                'format' => 'stars',
                'color' => 'warning',
                'bar_height' => 12,
                'decimals' => 1,
                'inline' => false,
                'show_count' => true,
            ),
            'accept_multiple' => true,
            'inlineable' => true,
        );
    }
    
    public function fieldRendererSupports(Field\IField $field)
    {
        return $field->getFieldName() === 'review_ratings';
    }
    
    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = array())
    {
        $ret = parent::_fieldRendererSettingsForm($field, $settings, $parents) + [
            'show_count' => [
                '#title' => __('Show review count', 'directories-reviews'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['show_count']),
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        sprintf('select[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['format']))) => ['value' => 'stars'],
                    ],
                ],
            ],
        ];
        $ret['decimals']['#states']['invisible'] = [
            sprintf('select[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, array('format')))) => array('value' => 'bars_level'),
        ];
        return $ret;
    }
    
    protected function _fieldRendererRenderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        if ($settings['format'] === 'bars_level') {
            $options = array(
                'color' => $settings['color'],
                'height' => $settings['bar_height'],
                'inline' => $settings['inline'],
            );
            return $this->_application->Voting_RenderRating_barsByLevel($field->Bundle->name, $entity->getId(), $field->getFieldName(), '_all', $options);
        }
        return parent::_fieldRendererRenderField($field, $settings, $entity, $values, $more);
    }
    
    protected function _getFormatOptions()
    {
        return parent::_getFormatOptions() + [
            'bars_level' => __('Rating bars (by number of stars)', 'directories-reviews'),
        ];
    }
    
    protected function _getReviewBundle(Field\IField $field)
    {
        if ($bundle = $field->Bundle) {        
            return $this->_application->Entity_Bundle('review_review', $bundle->component, $bundle->group);
        }
    }
    
    protected function _getRatingValue(Field\IField $field, array $settings, array $values, $slug = null)
    {
        if (!isset($slug)) $slug = '_all';
        return isset($values[0][$slug]['average']) ? $values[0][$slug]['average'] : null;
    }
    
    protected function _getRatingCount(Field\IField $field, array $settings, array $values, $slug = null)
    {
        if (empty($settings['show_count'])
            || (!$review_bundle = $this->_getReviewBundle($field))
        ) return;
        
        if (!isset($slug)) $slug = '_all';
        $count = isset($values[0][$slug]['count']) ? $values[0][$slug]['count'] : 0;
        return $count;
        return sprintf(
            _n($review_bundle->getLabel('count'), $review_bundle->getLabel('count2'), $count, 'directories-reviews'),
            $count
        );
    }
    
    public function fieldRendererReadableSettings(Field\IField $field, array $settings)
    {
        $ret = parent::fieldRendererReadableSettings($field, $settings);
        if ($settings['format'] === 'stars') {
            $ret['show_count'] = [
                'label' => __('Show review count', 'directories-reviews'),
                'value' => !empty($settings['show_count']),
                'is_bool' => true,
            ];
        } elseif ($settings['format'] === 'bars_levels') {
            unset($ret['decimals']);
        }
        return $ret;
    }
}