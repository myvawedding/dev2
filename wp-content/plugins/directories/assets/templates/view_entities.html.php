<?php if (empty($entities) && !empty($hide_empty)) return;?>
<?php $this->Action('view_before_entities_container', array($entities, $CONTEXT));?>
<div class="drts-view-entities-container drts-view-entities-container-<?php echo $view;?>">
    <?php $this->display($container_template, $CONTEXT->getAttributes());?>
</div>
<?php $this->Action('view_after_entities_container', array($entities, $CONTEXT));?>