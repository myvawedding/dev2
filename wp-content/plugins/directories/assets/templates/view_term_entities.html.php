<?php
$this->Entity_Display($entity, $display, $vars = $CONTEXT->getAttributes(), ['pre_render' => true, 'is_caching' => $CONTEXT->isCaching()]);
$this->display('view_entities', $vars);