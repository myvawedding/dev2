<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class TokensHelper
{
    public function help(Application $application, Entity\Model\Bundle $bundle)
    {
        $tokens = [
            '%id%',
            '%author_id%',
            '%author_name%',
            '%timestamp%',
            '%current_user_id%',
            '%current_user_name%',
            '%permalink_url%'
        ];
        return $application->Filter('entity_tokens', $tokens, [$bundle]);
    }

    public function replace(Application $application, $text, Entity\Type\IEntity $entity)
    {
        if (strpos($text, '%') === false) return $text;

        $replacements = [
            '%id%' => $entity->getId(),
            '%author_id%' => $entity->getAuthorId(),
            '%author_name%' => $application->Entity_Author($entity)->username,
            '%timestamp%' => $entity->getTimestamp(),
            '%current_user_id%' => $application->getUser()->id,
            '%current_user_name%' => $application->getUser()->username,
            '%permalink_url%' => $application->Entity_PermalinkUrl($entity),
        ];

        return strtr($text, $application->Filter('entity_tokens_replace', $replacements, [$entity]));
    }
}
