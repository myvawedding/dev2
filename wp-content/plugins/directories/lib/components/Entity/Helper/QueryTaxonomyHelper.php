<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class QueryTaxonomyHelper
{
    public function help(Application $application, $taxonomyBundleType, Field\Query $query, $termId, array $options = [])
    {
        if (empty($termId)) return;
        
        $term_ids = is_array($termId) ? $termId : array($termId);
        
        // Handle previously added criteria if any
        if ($query->hasNamedCriteria($taxonomyBundleType)) {
            if (!empty($options['merge'])) {
                // Merge
                foreach ($query->removeNamedCriteria($taxonomyBundleType, true) as $criteria) {
                    if ($criteria instanceof \SabaiApps\Framework\Criteria\InCriteria) {                   
                        foreach ($criteria->getArray() as $term_id) {
                            $term_ids[] = $term_id;
                        }
                    } elseif ($criteria instanceof \SabaiApps\Framework\Criteria\IsCriteria) {
                        $term_ids[] = $criteria->getValue();
                    }
                }
            } else {
                $query->removeNamedCriteria($taxonomyBundleType);
            }
        }
        
        // Do query
        if (!empty($options['hierarchical'])) {
            // hierarchical, always OR query
            $query->taxonomyTermIdIn($taxonomyBundleType, $term_ids, false);
        } elseif (!isset($options['andor']) || $options['andor'] !== 'AND') {
            // non-hierarchical, OR query
            $query->taxonomyTermIdIn($taxonomyBundleType, $term_ids, true);
        } else {
            // non-hierarchical, AND query
            $query->startCriteriaGroup('AND');
            foreach ($term_ids as $term_id) {
                $query->taxonomyTermIdIs($taxonomyBundleType, $term_id, true);
            }
            $query->finishCriteriaGroup($taxonomyBundleType); // name the criteria group so it can be removed in bulk by other code
        }
    }
}