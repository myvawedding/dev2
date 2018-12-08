<?php
namespace SabaiApps\Directories\Component\WordPressContent\EntityType;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Type\FieldQuery;
use SabaiApps\Framework\Criteria\ContainsCriteria;

class PostFieldQuery extends FieldQuery
{
    protected $_taxonomyCount = 0;
    
    public function taxonomyTermTitleContains($taxonomy, $string)
    {
        $this->addCriteria(new ContainsCriteria($this->_getTaxonomyTermNameTarget($taxonomy), $string));
    }
    
    protected function _getTaxonomyTermNameTarget($taxonomy)
    {
        ++$this->_taxonomyCount;
        $tr = 'tr' . $this->_taxonomyCount;
        $tt = 'tt' . $this->_taxonomyCount;
        $terms = 'terms' . $this->_taxonomyCount;
        return array(
            'tables' => array(
                $GLOBALS['wpdb']->term_relationships => array(
                    'alias' => $tr,
                    'on' => 'object_id = %3$s',
                ),
                $GLOBALS['wpdb']->term_taxonomy => array(
                    'alias' => $tt,
                    'on' => 'term_taxonomy_id = ' . $tr . '.term_taxonomy_id AND %1$s.taxonomy = \'' . esc_sql($taxonomy) . '\'',
                    //'join_type' => 'INNER',
                ),
                $GLOBALS['wpdb']->terms => array(
                    'alias' => $terms,
                    'on' => 'term_id = ' . $tt . '.term_id',
                    //'join_type' => 'INNER',
                ),
            ),
            'column' => $terms . '.name',
            'column_type' => Application::COLUMN_VARCHAR,
        );
    }
}