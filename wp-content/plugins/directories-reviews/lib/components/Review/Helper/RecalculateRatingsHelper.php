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

        $voting_model = $application->getModel(null, 'Voting');

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

        // Make sure votes for non-published reviews do not exist in votes table
        $reviews_delete_vote = [];
        foreach (array_keys($review_ids) as $entity_id) {
            foreach ($application->Entity_Entities($bundle->entitytype_name, $review_ids[$entity_id], false) as $review) {
                if (!$review->isPublished()) {
                    // delete vote for this review
                    $reviews_delete_vote[] = $review->getId();
                }
            }
        }
        if (!empty($reviews_delete_vote)) {
            $criteria = $voting_model->createCriteria('Vote')->referenceId_in($reviews_delete_vote);
            $voting_model->getGateway('Vote')->deleteByCriteria($criteria);
        }

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