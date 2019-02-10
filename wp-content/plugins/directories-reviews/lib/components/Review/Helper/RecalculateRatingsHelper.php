<?php
namespace SabaiApps\Directories\Component\Review\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\System\Progress;

class RecalculateRatingsHelper
{
    public function help(Application $application, Progress $progress)
    {
        foreach ($application->Entity_Bundles() as $bundle) {
            $this->bundle($application, $bundle, $progress);
        }
    }

    public function bundle(Application $application, $bundle, Progress $progress)
    {
        // Make sure valid bundle
        if (!$bundle = $application->Entity_Bundle($bundle)) return;

        if (empty($bundle->info['review_enable'])) return;

        if (!$review_bundle = $application->Entity_Bundle('review_review', $bundle->component, $bundle->group)) return;

        $voting_model = $application->getModel(null, 'Voting');

        $entity_type_info = $application->Entity_Types_impl($review_bundle->entitytype_name)->entityTypeInfo();

        // Delete votes for trashed reviews
        $voting_model->getGateway('Vote')->deleteEntityVotes(
            $entity_type_info['table_name'],
            $entity_type_info['properties']['id']['column'],
            @$entity_type_info['properties']['bundle_name']['column'],
            $review_bundle->name,
            @$entity_type_info['properties']['status']['column'],
            $application->Entity_Status($review_bundle->entitytype_name, 'trash'),
            true
        );
        $progress->set('Deleting votes for trashed reviews');

        // Fetch reviews without vote entry and cast vote
        $reviews_without_votes = $voting_model->getGateway('Vote')->getMissingEntityIds(
            $entity_type_info['table_name'],
            $entity_type_info['properties']['id']['column'],
            @$entity_type_info['properties']['bundle_name']['column'],
            $review_bundle->name,
            @$entity_type_info['properties']['status']['column'],
            $application->Entity_Status($review_bundle->entitytype_name, 'publish'),
            true
        );
        if (!empty($reviews_without_votes)) {
            foreach ($application->Entity_Entities($review_bundle->entitytype_name, $reviews_without_votes) as $review) {
                if (!$parent_entity = $application->Entity_ParentEntity($review, false)) continue;

                $rating = $review->getSingleFieldValue('review_rating');
                foreach (array_keys($rating) as $rating_name) {
                    $rating[$rating_name] = $rating[$rating_name]['value'];
                }
                $application->Voting_CastVote(
                    $parent_entity,
                    'review_ratings',
                    $rating,
                    array(
                        'reference_id' => $review->getId(),
                        'user_id' => $review->getAuthorId(),
                    )
                );
            }
            $progress->set('Creating missing votes for reviews');
        }

        // Get review IDs
        $review_ids = [];
        $criteria = $voting_model->createCriteria('Vote')
            ->bundleName_is($bundle->name)
            ->fieldName_is('review_ratings');
        $paginator = $voting_model->getRepository('Vote')->paginateByCriteria($criteria, 200);
        foreach ($paginator as $page) {
            $paginator->setCurrentPage($page);
            $offset = $paginator->getElementOffset();
            $progress->set(sprintf(
                'Retrieving reviews for %s (%d - %d)',
                $bundle->getGroupLabel() . ' - ' . $bundle->getLabel(),
                $offset + 1,
                $offset + $paginator->getElementLimit()
            ));
            foreach ($paginator->getElements() as $vote) {
                $review_ids[$vote->entity_id][$vote->reference_id] = $vote->reference_id;
            }
        }

        if (empty($review_ids)) return;

        // Recalculate review ratings
        $paginator = $application->Entity_Query($bundle->entitytype_name)
            ->fieldIsIn('id', array_keys($review_ids))
            ->sortById()
            ->paginate(100);
        foreach ($paginator as $page) {
            $paginator->setCurrentPage($page);
            $offset = $paginator->getElementOffset();
            $progress->set(sprintf(
                'Recalculating review rating for %s (%d - %d)',
                $bundle->getGroupLabel() . ' - ' . $bundle->getLabel(),
                $offset + 1,
                $offset + $paginator->getElementLimit()
            ));
            foreach ($paginator->getElements() as $entity) {
                // Calculate results and update entity
                $application->Voting_RecalculateVotes($entity, 'review_ratings');
            }
        }
    }
}