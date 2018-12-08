<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class ButtonToolbarHelper
{
    public function help(Application $application, array $links, array $options = [])
    {
        $options += [
            'tooltip' => false,
            'label' => true,
            'separator' => PHP_EOL,
            'class' => '',
        ];
        foreach (array_keys($links) as $i) {
            if (is_string($links[$i])) continue;
            
            $is_first = !isset($is_first);
            $links[$i] = $application->ButtonLinks(
                [$links[$i]],
                ['separator' => PHP_EOL, 'group' => true, 'class' => $is_first ? '' : DRTS_BS_PREFIX . 'ml-2'] + $options
            );
        }
        return '<div class="' . DRTS_BS_PREFIX . 'btn-toolbar ' . $options['class'] . '">' . implode($options['separator'], $links) . '</div>';
    }
}