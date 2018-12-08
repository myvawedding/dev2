<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

trait QueryableUserTrait
{
    public function fieldQueryableInfo(IField $field)
    {
        return array(
            'example' => '1,-_current_user_,john,-12',
            'tip' => __('Enter user IDs, usernames, "_current_" (for current post author if any), or "_current_user_" (for current user) separated with commas. Prefix each with "-" to exclude.', 'directories'),
        );
    }
    
    public function fieldQueryableQuery(Query $query, $fieldName, $paramStr, Bundle $bundle = null)
    {
        if (!$ids = explode(',', trim($paramStr, ','))) return;

        $ids = array_map('trim', $ids);
        $exclude = [];
        foreach (array_keys($ids) as $k) {
            if (is_numeric($ids[$k])) {
                $ids[$k] = (int)$ids[$k];
                if ($ids[$k] < 0) {
                    $exclude[] = -1 * $ids[$k]; // removes "-"
                    unset($ids[$k]);
                }
                continue;
            }

            if (false !== $pos = strpos($ids[$k], '_current_user_')) {
                $current_user = $this->_application->getUser();
                if (!$current_user->isAnonymous()) {
                    if ($pos === 1) {
                        $exclude[] = $current_user->id;
                        unset($ids[$k]);
                    } else {
                        $ids[$k] = $current_user->id;
                    }
                } else {
                    unset($ids[$k]);
                }
                continue;
            }
            
            if (in_array($ids[$k], array('_current_', '-_current_'))) {
                if (isset($GLOBALS['drts_entity'])
                    && ($author_id = $GLOBALS['drts_entity']->getAuthorId())
                ) {
                    if ($ids[$k] === '-_current_') {
                        $exclude[] = $author_id;
                    } else {
                        $ids[$k] = $author_id;
                    }
                }
                continue;
            }
            
            if (!$user = $this->_application->getPlatform()->getUserIdentityFetcher()->fetchByUsername($ids[$k])) {
                unset($ids[$k]); // invalid user name
                continue;
            }
            
            $ids[$k] = $user->id;
        }
        if (!empty($ids)) {
            $query->fieldIsIn($fieldName, $ids);
        }
        if (!empty($exclude)) {
            $query->fieldIsNotIn($fieldName, $exclude);
        }
    }
}