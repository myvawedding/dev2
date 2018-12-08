<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;

interface IImage
{
    public function fieldImageGetUrl($value, $size);
    public function fieldImageGetIconUrl($value, $size = null);
    public function fieldImageGetFullUrl($value);
    public function fieldImageGetTitle($value);
    public function fieldImageGetAlt($value);
}