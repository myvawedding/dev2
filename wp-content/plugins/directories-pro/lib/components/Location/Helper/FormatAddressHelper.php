<?php
namespace SabaiApps\Directories\Component\Location\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class FormatAddressHelper
{
    public function help(Application $application, array $value, $format, Entity\Type\IEntity $entity, array $locationHierarchy = null)
    {
        $replace = array(
            '{street}' => $application->H($value['street']),
            '{street2}' => $application->H($value['street2']),
            '{city}' => $application->H($value['city']),
            '{province}' => $application->H($value['province']),
            '{zip}' => $application->H($value['zip']),
            '{country}' => $application->H($value['country']),
            '{full_address}' => $application->H($value['address']),
        );
        if (!empty($locationHierarchy)) {
            if (!empty($value['term_id'])) {
                foreach ($entity->getFieldValue('location_location') as $term) {
                    if ($term->getId() === $value['term_id']) {
                        $location_titles = (array)$term->getCustomProperty('parent_titles');
                        $location_titles[$term->getId()] = $term->getTitle();
                        foreach (array_keys($locationHierarchy) as $key) {
                            $replace['{' . $key . '}'] = (string)array_shift($location_titles);
                        }
                    }
                }
            }
        }
        // Replace tags
        $formatted = strtr($format, $replace);
        // Replace multiple columns with single column
        $formatted = preg_replace('/,+/', ',', $formatted);
        // Replace columns with spaces in between
        $formatted = preg_replace('/,\s*,/', ',', $formatted);
        // Replace multiple spacess with single space
        $formatted = preg_replace('/\s+/', ' ', $formatted);
        // Remove starting/trailing spaces/commas
        $formatted = trim($formatted, ' ,');

        return $formatted;
    }

    public function tags(Application $application, Entity\Model\Bundle $bundle)
    {
        $tags = array('{country}', '{province}', '{city}', '{street}', '{street2}', '{zip}', '{full_address}');
        if (($location_bundle = $application->Entity_Bundle('location_location', $bundle->component, $bundle->group))
            && ($location_hierarchy = $application->Location_Hierarchy($location_bundle))
        ) {
            foreach (array_keys($location_hierarchy) as $key) {
                $tags[] = '{' . $key . '}';
            }
        }
        return $tags;
    }
}