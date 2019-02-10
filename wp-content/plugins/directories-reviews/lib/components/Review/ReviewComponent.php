<?php
namespace SabaiApps\Directories\Component\Review;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\CSV;
use SabaiApps\Directories\Application;

class ReviewComponent extends AbstractComponent implements
    Entity\IBundleTypes,
    Field\ITypes,
    Field\IWidgets,
    Field\IRenderers,
    Field\IFilters,
    CSV\IExporters,
    CSV\IImporters
{
    const VERSION = '1.2.23', PACKAGE = 'directories-reviews';
    
    public static function interfaces()
    {
        return array(
            'Faker\IGenerators',
            //'WordPressContent\INotifications'
        );
    }
    
    public static function description()
    {
        return 'Allows users to submit reviews.';
    }
    
    public function onCoreComponentsLoaded()
    {
        $this->_application->setHelper('Review_Criteria', function (Application $application, Entity\Model\Bundle $bundle) {
            return empty($bundle->info['review_criteria']) ? array('_all' => __('Overall rating', 'directories-reviews')) : $bundle->info['review_criteria'];
        });
    }

    public function fieldGetTypeNames()
    {
        return array('review_rating');
    }
    
    public function fieldGetType($name)
    {
        return new FieldType\RatingFieldType($this->_application, $name);
    }
    
    public function fieldGetWidgetNames()
    {
        return array('review_rating');
    }
    
    public function fieldGetWidget($name)
    {
        return new FieldWidget\RatingFieldWidget($this->_application, $name);
    } 
    
    public function fieldGetRendererNames()
    {
        return array('review_rating', 'review_ratings');
    }
    
    public function fieldGetRenderer($name)
    {
        switch ($name) {
            case 'review_rating':
                return new FieldRenderer\RatingFieldRenderer($this->_application, $name);
            case 'review_ratings':
                return new FieldRenderer\RatingsFieldRenderer($this->_application, $name);
        }
    }
    
    public function fieldGetFilterNames()
    {
        return array('review_rating', 'review_ratings');
    }
    
    public function fieldGetFilter($name)
    {
        switch ($name) {
            case 'review_rating':
                return new FieldFilter\RatingFieldFilter($this->_application, $name);
            case 'review_ratings':
                return new FieldFilter\RatingsFieldFilter($this->_application, $name);
        }
    }
    
    protected function _isBundleReviewable($bundleType)
    {
        $info = $this->_application->Entity_BundleTypeInfo($bundleType);
        return !empty($info['review_enable'])
            && empty($info['is_taxonomy'])
            && empty($info['parent']);
    }
    
    public function entityGetBundleTypeNames()
    {        
        return array('review_review');
    }
    
    public function entityGetBundleType($name)
    {
        return new EntityBundleType\ReviewEntityBundleType($this->_application, $name);
    }
    
    public function onDirectoryContentTypeSettingsFormFilter(&$form, $directoryType, $contentType, $info, $settings, $parents, $submitValues)
    {
        if (!isset($info['review_enable'])
            || !$info['review_enable']
            || !empty($info['parent'])
            || !empty($info['is_taxonomy'])
        ) return;
        
        $form['review_enable'] = array(
            '#type' => 'checkbox',
            '#title' => __('Enable reviews', 'directories-reviews'),
            '#default_value' => !empty($settings['review_enable']) || is_null($settings),
            '#horizontal' => true,
        );
    }
    
    public function onDirectoryContentTypeInfoFilter(&$info, $contentType, $settings = null)
    {        
        if (!isset($info['review_enable'])) return;
        
        if (!$info['review_enable']
            || !empty($info['is_taxonomy'])
            || !empty($info['parent'])
        ) {
            unset($info['review_enable']);
        }
        
        if (isset($settings['review_enable']) && !$settings['review_enable']) {
            $info['review_enable'] = false;
        }
    }
    
    public function onEntityBundlesInfoFilter(&$bundles, $componentName, $group)
    {
        foreach (array_keys($bundles) as $bundle_type) {
            $info =& $bundles[$bundle_type];
            
            if (empty($info['review_enable'])
                || !empty($info['is_taxonomy'])
                || !empty($info['parent'])
            ) continue;

            // Add review_review bundle
            if (!isset($bundles['review_review'])) { // may already set if updating or importing
                $bundles['review_review'] = [];
            }
            $bundles['review_review']['parent'] = $bundle_type; // must be bundle type for Entity component to create parent field
            $bundles['review_review'] += $this->entityGetBundleType('review_review')->entityBundleTypeInfo();
            $bundles['review_review']['properties']['parent']['label'] = $info['label_singular'];
            
            return; // there should be only one bundle with review enabled in a group
        }
        
        // No bundle with reviews enabled found, so make sure the review_review bundle is not assigned
        unset($bundles['review_review']);
    }
    
    public function onEntityBundleInfoFilter(&$info, $componentName, $group)
    {
        if (empty($info['review_enable'])
            || !empty($info['is_taxonomy'])
            || !empty($info['parent'])
        ) return;
            
        // Add a field to reviewable bundle that holds overall review ratings 
        $info['fields']['review_ratings'] = array(
            'label' => __('Review Rating', 'directories-reviews'),
            'type' => 'voting_vote',
            'settings' => [],
            'max_num_items' => 0,
        );
    }
    
    public function onEntityBundleInfoKeysFilter(&$keys)
    {
        $keys[] = 'review_enable';
    }

    public function onEntityBundleInfoUserKeysFilter(&$keys)
    {
        $keys[] = 'review_criteria';
    }
    
    public function onEntityCreateEntitySuccess($bundle, $entity, $values, $extraArgs)
    {
        if ($bundle->type !== 'review_review'
            || !$entity->isPublished()
            || (!$parent_entity = $this->_application->Entity_ParentEntity($entity, false))
        ) return;
        
        $this->_castVote($parent_entity, $entity);
    }
    
    public function onEntityUpdateEntitySuccess($bundle, $entity, $oldEntity, $values, $extraArgs)
    {
        if ($bundle->type !== 'review_review'
             || (!$parent_entity = $this->_application->Entity_ParentEntity($entity, false))
        ) return;
        
        if ($entity->isPublished()) {
            if (isset($values['review_rating']) // rating changed
                || isset($values['status']) // review was just published
            ) {
                $this->_castVote($parent_entity, $entity, true);
            }
        } else {
            if ($oldEntity->isPublished()) {
                $this->_castVote($parent_entity, $entity, false, true);
            }
        }
    }
    
    protected function _castVote($entity, $review, $isEdit = false, $isDelete = false)
    {
        $rating = $review->getSingleFieldValue('review_rating');
        foreach (array_keys($rating) as $rating_name) {
            $rating[$rating_name] = $rating[$rating_name]['value'];
        }
        // Cast vote
        $this->_application->Voting_CastVote(
            $entity,
            'review_ratings',
            $rating,
            array(
                'reference_id' => $review->getId(),
                'user_id' => $review->getAuthorId(),
                'edit' => $isEdit,
                'delete' => $isDelete,
            )
        );
    }
    
    public function fakerGetGeneratorNames()
    {
        return array('review_rating');
    }
    
    public function fakerGetGenerator($name)
    {
        return new FakerGenerator\ReviewFakerGenerator($this->_application, $name);
    }
    
    public function onEntitySchemaorgJsonldFilter(&$json, $entity, $settings)
    {
        if (!$this->_isBundleReviewable($entity->getBundleType())
            || (!$ratings = $entity->getSingleFieldValue('review_ratings'))
            || empty($ratings['_all']['count'])
        ) return;
        
        $json['aggregateRating'] = array(
            '@type' => 'AggregateRating',
            'ratingValue' => $ratings['_all']['average'],
            'reviewCount' => $ratings['_all']['count'],
        );
    }

    public function onSystemAdminSystemToolsFilter(&$tools)
    {
        $tools['review_recalculate'] = [
            'label' => __('Recalculate review ratings', 'directories-reviews'),
            'description' => __('This tool will recalculate review ratings for each content item.', 'directories-reviews'),
            'with_progress' => true,
            'weight' => 60,
        ];
    }

    public function onSystemAdminRunTool($tool, $progress, $values)
    {
        if ($tool === 'review_recalculate') {
            $this->_application->Review_RecalculateRatings($progress);
        }
    }

    public function csvGetImporterNames()
    {
        return ['review_rating'];
    }

    public function csvGetImporter($name)
    {
        return new CSVImporter\ReviewCSVImporter($this->_application, $name);
    }

    public function csvGetExporterNames()
    {
        return ['review_rating'];
    }

    public function csvGetExporter($name)
    {
        return new CSVExporter\ReviewCSVExporter($this->_application, $name);
    }
}