<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasUserScope
{
    /**
     * Ajouter le global scope pour filtrer par utilisateur connecté
     */
    protected static function bootHasUserScope(): void
    {
        static::addGlobalScope('user', function (Builder $builder) {
            $userId = Auth::id();

            if (! $userId) {
                // Si personne n'est connecté, aucun résultat ne doit être retourné
                $builder->whereRaw('0 = 1');

                return;
            }

            static::applyUserScopeFilter($builder, $userId);
        });
    }

    /**
     * Appliquer le filtre utilisateur - à override dans les modèles
     */
    protected static function applyUserScopeFilter(Builder $builder, int $userId): void
    {
        // Par défaut, filtrer directement sur user_id
        $builder->where($builder->getModel()->getTable().'.user_id', $userId);
    }
}
