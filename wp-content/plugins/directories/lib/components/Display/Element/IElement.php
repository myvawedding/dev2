<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Assets;

interface IElement
{
    public function displayElementInfo(Entity\Model\Bundle $bundle, $key = null);
    public function displayElementSupports(Entity\Model\Bundle $bundle, Display\Model\Display $display);
    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = []);
    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var);
    public function displayElementAdminTitle(Entity\Model\Bundle $bundle, array $element);
    public function displayElementAdminAttr(Entity\Model\Bundle $bundle, array $settings);
    public function displayElementIsEnabled(Entity\Model\Bundle $bundle, array $element, Display\Model\Display $display);
    public function displayElementIsDimmed(Entity\Model\Bundle $bundle, array $settings);
    public function displayElementIsInlineable(Entity\Model\Bundle $bundle, array $settings);
    public function displayElementIsPreRenderable(Entity\Model\Bundle $bundle, array &$element, $displayType);
    public function displayElementPreRender(Entity\Model\Bundle $bundle, array $element, $displayType, &$var);
    public function displayElementOnCreate(Entity\Model\Bundle $bundle, array &$data, $weight);
    public function displayElementOnUpdate(Entity\Model\Bundle $bundle, array &$data, $weight);
    public function displayElementOnExport(Entity\Model\Bundle $bundle, array &$data);
    public function displayElementOnRemoved(Entity\Model\Bundle $bundle, array $settings);
    public function displayElementOnPositioned(Entity\Model\Bundle $bundle, array $settings, $weight);
    public function displayElementOnSaved(Entity\Model\Bundle $bundle, Display\Model\Element $element);
    public function displayElementReadableInfo(Entity\Model\Bundle $bundle, Display\Model\Element $element);
    //public function displayElementCreateChildren(Entity\Model\Bundle $bundle, Display\Model\Display $display, array $settings, $parentId);
}