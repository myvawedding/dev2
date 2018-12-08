<?php
namespace SabaiApps\Directories\Component\CSV\Exporter;

use SabaiApps\Directories\Component\Entity;

class VotingExporter extends AbstractExporter
{
    public function csvExporterDoExport(Entity\Model\Field $field, array $settings, $value, array $columns, array &$formStorage)
    {
        switch ($this->_name) {
            case 'voting_vote':
                if (!isset($value[0])) return;

                $values = [];
                foreach (array_keys($value[0]) as $name) {
                    $values[] = [
                        'name' => (string)$name,
                        'count' => $value[0][$name]['count'],
                        'sum' => $value[0][$name]['sum'],
                        'count_init' => $value[0][$name]['count'],
                        'sum_init' => $value[0][$name]['sum'],
                    ];
                }
                return json_encode($values);
        }
    }
}
