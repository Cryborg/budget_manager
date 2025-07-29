<?php

namespace Tests\Unit\Concerns;

use App\Filament\Concerns\HasCustomLabels;
use Tests\TestCase;

class HasCustomLabelsTest extends TestCase
{
    public function test_get_model_label_returns_singular_label(): void
    {
        $traitObject = new class
        {
            use HasCustomLabels;

            protected static function getSingularLabel(): string
            {
                return 'test item';
            }

            protected static function getPluralLabel(): string
            {
                return 'test items';
            }
        };

        $this->assertEquals('test item', $traitObject::getModelLabel());
    }

    public function test_get_plural_model_label_returns_plural_label(): void
    {
        $traitObject = new class
        {
            use HasCustomLabels;

            protected static function getSingularLabel(): string
            {
                return 'test item';
            }

            protected static function getPluralLabel(): string
            {
                return 'test items';
            }
        };

        $this->assertEquals('test items', $traitObject::getPluralModelLabel());
    }

    public function test_trait_works_with_french_labels(): void
    {
        $traitObject = new class
        {
            use HasCustomLabels;

            protected static function getSingularLabel(): string
            {
                return 'revenu';
            }

            protected static function getPluralLabel(): string
            {
                return 'revenus';
            }
        };

        $this->assertEquals('revenu', $traitObject::getModelLabel());
        $this->assertEquals('revenus', $traitObject::getPluralModelLabel());
    }

    public function test_trait_works_with_complex_labels(): void
    {
        $traitObject = new class
        {
            use HasCustomLabels;

            protected static function getSingularLabel(): string
            {
                return 'ajustement de solde';
            }

            protected static function getPluralLabel(): string
            {
                return 'ajustements de solde';
            }
        };

        $this->assertEquals('ajustement de solde', $traitObject::getModelLabel());
        $this->assertEquals('ajustements de solde', $traitObject::getPluralModelLabel());
    }
}
