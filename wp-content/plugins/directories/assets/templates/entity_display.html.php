<?php
$options['attr']['id'] = $entity->getUniqueId(substr($CONTEXT->getContainer(), 1));
if ($rendered = $this->Display_Render(
    $entity->getBundleName(),
    $display,
    $entity,
    $options
)):?>
<?php   echo $rendered['html'];?>
<?php   if ($rendered['js']):?>
<?php     echo $rendered['js'];?>
<?php   endif;?>
<?php endif;?>