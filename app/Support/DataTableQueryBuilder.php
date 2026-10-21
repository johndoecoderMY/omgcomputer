<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DataTableQueryBuilder
{
    /**
     * @param array<string, AllowedFilter|string> $filters
     */
    public static function for(Builder|Relation $query, Request $request, array $filters): Builder
    {
        $filterValues = $request->query('filter', []);
        if (! is_array($filterValues)) {
            $filterValues = [];
        }

        foreach (array_keys($filters) as $name) {
            if (! array_key_exists($name, $filterValues) && $request->query->has($name)) {
                $filterValues[$name] = $request->query($name);
            }
        }

        $filterValues = Validator::make(
            ['filter' => $filterValues],
            ['filter' => ['array'], 'filter.*' => ['nullable', 'string', 'max:255']],
        )->validate()['filter'] ?? [];

        $queryRequest = Request::createFrom($request);
        $queryRequest->query->set('filter', $filterValues);

        return QueryBuilder::for($query, $queryRequest)
            ->allowedFilters(...array_values($filters))
            ->getEloquentBuilder();
    }
}
