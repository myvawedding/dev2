<?php
namespace SabaiApps\Directories\Component\Location\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\System\Progress;

class ToolsHelper
{
    public function geocode(Application $application, Progress $progress)
    {
        foreach ($application->Entity_Bundles() as $bundle) {
            if (!empty($bundle->info['is_taxonomy'])
                || (!$field = $application->Entity_Field($bundle, 'location_address'))
            ) continue;

            if ($field->getFieldWidget() === 'location_address') {
                $widget_settings = $field->getFieldWidgetSettings();
            }

            $paginator = $application->Entity_Query($bundle->entitytype_name)
                ->fieldIs('bundle_name', $bundle->name)
                ->sortById()
                ->paginate(50);
            foreach ($paginator as $page) {
                $paginator->setCurrentPage($page);
                $offset = $paginator->getElementOffset();
                $progress->set(sprintf(
                    'Loading geolocation data for %s (%d - %d)',
                    $bundle->getGroupLabel() . ' - ' . $bundle->getLabel(),
                    $offset + 1,
                    $offset + $paginator->getElementLimit()
                ));
                foreach ($paginator->getElements() as $entity) {
                    $values = $entity->getFieldValue('location_address');
                    $save = false;
                    $terms = [];
                    foreach ($entity->getFieldValue('location_location') as $term) {
                        $terms[$term->getId()] = $term->getTitle();
                    }
                    foreach (array_keys($values) as $i) {
                        if (empty($values[$i]['lat']) || empty($values[$i]['lng'])) {
                            $query = []; // string passed to geocoding API

                            if (!empty($values[$i]['address'])) {
                                // Use full address
                                $query[] = $values[$i]['address'];
                            } else {
                                // Use other address components
                                if (!empty($widget_settings['input_country'])) {
                                    $query[] = $widget_settings['input_country'];
                                }
                                // Append location term titles
                                if (!empty($values[$i]['term_id'])
                                    && isset($terms[$values[$i]['term_id']])
                                ) {
                                    foreach ($term->getCustomProperty('parent_titles') as $term_title) {
                                        $query[] = $term_title;
                                    }
                                    $query[] = $term->getTitle();
                                }
                                // Append values of other input fields
                                if (!empty($widget_settings['input_fields']['default'])) {
                                    foreach ($widget_settings['input_fields']['default'] as $input_key) {
                                        if (isset($values[$i][$input_key])) {
                                            $query[] = $values[$i][$input_key];
                                        }
                                    }
                                }
                            }

                            if (empty($query)) continue; // skip since nothing to query

                            try {
                                $result = $application->Location_Api_geocode(implode(' ', $query), false);
                                $values[$i]['lat'] = $result['lat'];
                                $values[$i]['lng'] = $result['lng'];
                                $save = true;
                            } catch (Exception\IException $e) {
                                $application->logError($e);
                                continue;
                            }
                        }
                        if (empty($values[$i]['timezone'])) {
                            try {
                                $values[$i]['timezone'] = $application->Location_Api_timezone([$values[$i]['lat'], $values[$i]['lng']]);
                                $save = true;
                            } catch (Exception\IException $e) {
                                $application->logError($e);
                                continue;
                            }
                        }
                    }
                    if ($save) {
                        $application->Entity_Save($entity, ['location_address' => $values]);
                    }
                }
            }
        }
    }
}
