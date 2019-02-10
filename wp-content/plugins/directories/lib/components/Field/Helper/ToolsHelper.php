<?php
namespace SabaiApps\Directories\Component\Field\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\System\Progress;

class ToolsHelper
{
    public function help(Application $application)
    {
        $tools = [];
        $time_fields = [];
        foreach ($application->Entity_Bundles() as $bundle) {
            $time_fields += $application->Entity_Field_options(
                $bundle, ['prefix' => $bundle->getGroupLabel() . ' - ', 'name_prefix' => $bundle->name . ',', 'type' => 'time']
            );
        }
        if (!empty($time_fields)) {
            $hours = [];
            foreach (range(-11, 12) as $hour) {
                if ($hour === 0) continue;

                $hours[$hour] = sprintf(
                    _n('%s hour', '%s hours', $hour, 'directories'),
                    $hour > 0 ? '+' . $hour : $hour
                );
            }
            $tools['field_adjust_time'] = [
                'label' => __('Adjust time', 'directories'),
                'description' => __('This tool will let you bulk adjust time fields values.', 'directories'),
                'weight' => 100,
                'with_progress' => true,
                'form' => [
                    'field' => [
                        '#title' => __('Select field', 'directories'),
                        '#type' => 'select',
                        '#options' => $time_fields,
                        '#horizontal' => true,
                    ],
                    'hours' => [
                        '#title' => __('Adjust time', 'directories'),
                        '#type' => 'select',
                        '#options' => $hours,
                        '#horizontal' => true,
                        '#default_value' => 1,
                    ],
                ],
            ];
        }

        return $tools;
    }

    public function adjustTime(Application $application, Progress $progress, array $values = null)
    {
        if (!empty($values['field'])
            && !empty($values['hours'])
            && ($parts = explode(',', $values['field']))
            && ($bundle = $application->Entity_Bundle($parts[0]))
            && ($field = $application->Entity_Field($bundle, $parts[1]))
            && $field->getFieldType() === 'time'
        ) {
            $adjust = $values['hours'] * 3600;
            $paginator = $application->Entity_Query($bundle->entitytype_name)
                ->fieldIs('bundle_name', $bundle->name)
                ->fieldIsNotNull($field->getFieldName(), 'start')
                ->sortById()
                ->paginate(100);
            $label = $bundle->getGroupLabel() . ' - ' . $bundle->getLabel() . '(' . $field->getFieldName() . ')';
            foreach ($paginator as $page) {
                $paginator->setCurrentPage($page);
                $offset = $paginator->getElementOffset();
                $progress->set(sprintf(
                    'Adjusting time field for %s (%d - %d)',
                    $label,
                    $offset + 1,
                    $offset + $paginator->getElementLimit()
                ));
                foreach ($paginator->getElements() as $entity) {
                    if (!$time_values = $entity->getFieldValue($field->getFieldName())) continue;

                    foreach (array_keys($time_values) as $i) {
                        $time_values[$i]['start'] += $adjust;
                        $time_values[$i]['end'] += $adjust;
                    }
                    $application->Entity_Save($entity, [
                        $field->getFieldName() => $time_values,
                    ]);
                }
            }
        }
    }
}
