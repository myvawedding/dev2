<?php
$filter_form = null;
if ($CONTEXT->filter['form']) {
    if ($filter['is_external']) {
        $filter_form = $this->View_FilterForm_render($CONTEXT->filter['form'], null, true);
        $CONTEXT->filter['form'] = null; // Do not show filter form on main content section.
    }
}
echo $this->JsonEncode(array(
    'html' => $this->render($container_template, $CONTEXT->getAttributes()),
    'filter_form' => $filter_form,
));