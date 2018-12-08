<?php
namespace SabaiApps\Directories\Component\CSV\Importer;

use SabaiApps\Directories\Component\Entity;

class VotingImporter extends AbstractImporter
{
    public function csvImporterDoImport(Entity\Model\Field $field, array $settings, $column, $value, &$formStorage)
    {
        if ((!$value = json_decode($value))
            || !is_array($value)
        ) return;
        
        switch ($this->_name) {
            case 'voting_vote':
                $ret = [];
                if (!isset($value[0])) {
                    $value = [$value];
                }
                // Need to convert each to array since json_decode will return object(s)
                foreach ($value as $_value) {
                    $ret[] = (array)$_value;
                }
                return $ret;
        }
    }
}
