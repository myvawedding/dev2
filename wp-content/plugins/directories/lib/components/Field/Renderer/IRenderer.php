<?php
namespace SabaiApps\Directories\Component\Field\Renderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

interface IRenderer
{
    public function fieldRendererInfo($key = null);
    public function fieldRendererSettingsForm(IField $field, array $settings, array $parents = []);
    public function fieldRendererRenderField(IField $field, array &$settings, Entity\Type\IEntity $entity, array $values = null);
    public function fieldRendererIsPreRenderable(IField $field, array $settings);
    public function fieldRendererPreRender(IField $field, array $settings, array $entities);
    public function fieldRendererReadableSettings(IField $field, array $settings);
    public function fieldRendererSupports(IField $field);
    public function fieldRendererSupportsAmp(Entity\Model\Bundle $bundle);
    public function fieldRendererAmpSettingsForm(IField $field, array $settings, array $parents = []);
}