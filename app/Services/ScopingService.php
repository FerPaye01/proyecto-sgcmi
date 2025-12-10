<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ScopingService
{
    /**
     * Apply company scope to a query based on user role.
     * 
     * If the user has the TRANSPORTISTA role, the query will be filtered
     * to only show records belonging to their company.
     * 
     * For now, this implementation assumes users will have a direct company_id field
     * or a many-to-many relationship through user_companies table (to be added in migration).
     * 
     * @param Builder $query The Eloquent query builder
     * @param User $user The authenticated user
     * @return Builder The modified query builder
     */
    public static function applyCompanyScope(Builder $query, User $user): Builder
    {
        // Check if user has TRANSPORTISTA role
        if (!$user->hasRole('TRANSPORTISTA')) {
            // No scoping for other roles
            return $query;
        }
        
        // Check if user has direct company_id field (attribute)
        $attributes = $user->getAttributes();
        if (isset($attributes['company_id']) && $attributes['company_id'] !== null) {
            return $query->where('company_id', $attributes['company_id']);
        }
        
        // Check if the user_companies pivot table exists before trying to use the relationship
        $tableExists = \Illuminate\Support\Facades\Schema::hasTable('admin.user_companies');
        
        if ($tableExists && method_exists($user, 'companies')) {
            $companyIds = $user->companies()->pluck('id');
            
            if ($companyIds->isNotEmpty()) {
                return $query->whereIn('company_id', $companyIds);
            }
        }
        
        // User has no companies, return empty result
        // Use a condition that will never be true
        return $query->where('id', '<', 0);
    }
}
