<?php

namespace App\Filament\Concerns;

trait HasCustomLabels
{
    abstract protected static function getSingularLabel(): string;

    abstract protected static function getPluralLabel(): string;

    public static function getModelLabel(): string
    {
        return static::getSingularLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return static::getPluralLabel();
    }
}
