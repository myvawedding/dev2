<?php
namespace SabaiApps\Directories\Component\Voting\Type;

class BookmarkType extends AbstractType
{
    protected function _votingTypeInfo()
    {
        return array(
            'label' => __('Bookmarks', 'directories'),
            'label_button' => __('Bookmark button', 'directories'),
            'label_action' => _x('Bookmark', 'action', 'directories'),
            'label_unaction' => _x('Unbookmark', 'action', 'directories'),
            'label_statistic' => __('Bookmark count', 'directories'),
            'icon' => 'far fa-heart',
            'icon_active' => 'fas fa-heart',
            'min' => 1,
            'max' => 1,
            'step' => 1,
            'allow_empty' => false,
            'allow_anonymous' => false,
            'require_permission' => false,
            'entity_button' => true,
            'entity_statistic' => true,
            'table_headers' => array(
                'author' => __('User', 'directories'),
                'created' => __('Date Added', 'directories'),
            ),
        );
    }
    
    public function votingTypeFormat(array $value, $format = null)
    {
        switch ($format) {
            case 'num':
            case 'column':
                return $value['count'];
            default:
                return _n('%d bookmark', '%d bookmarks', $value['count'], 'directories');
        }
    }
}