<?php
$filter_form = null;
if (isset($filter['form'])) {
    $filter_form = $this->View_FilterForm_render($filter['form'], null, !empty($filter['is_external']));
}
$html = [];
if (!empty($nav[0])) {
    $html['.drts-view-entities-header'] = $this->View_Nav($CONTEXT, $nav[0]);
}
if (!empty($nav[1])) {
    $html['.drts-view-entities-footer'] = $this->View_Nav($CONTEXT, $nav[1], true);
}
if ($filter_form && empty($filter['is_external'])) {
    $html['.drts-view-entities-filter-form'] = $filter_form;
}
if ((string)$view !== 'map') { // Map view does not need to render entities list
    $html['.drts-location-entities'] = $this->render($settings['map']['template'], $CONTEXT->getAttributes());
}
$options = [
    'link' => true,
    'size' => $settings['map']['marker_size'],
    'icon' => isset($settings['map']['view_marker_icon']) ? $settings['map']['view_marker_icon'] : 'image',
];
echo $this->JsonEncode(array(
    'html' => $html,
    'filter_form' => empty($filter['is_external']) ? null : $filter_form,
    'markers' => $this->Map_Marker_all(
        $bundle,
        $entities,
        $settings['map'] + ['coordinates_field' => 'location_address'],
        $CONTEXT->getContainer()
    ),
    'draw_options' => $this->Location_DrawMapOptions([], $CONTEXT),
));