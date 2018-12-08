<?php
namespace SabaiApps\Directories\Component\Voting\Type;

use SabaiApps\Directories\Component\Voting\Model\Vote;

interface IType
{
    public function votingTypeInfo($name);
    public function votingTypeFormat(array $value, $format = null);
    public function votingTypeTableRow(Vote $vote, array $tableHeaders);
}