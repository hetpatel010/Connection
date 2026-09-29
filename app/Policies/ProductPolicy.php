<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return business_context()->userCan('view_products')
            || business_context()->userCan('manage_products');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->belongsToBusiness($product->business)
            && ($this->viewAny($user));
    }

    public function create(User $user): bool
    {
        return business_context()->userCan('manage_products');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->belongsToBusiness($product->business)
            && business_context()->userCan('manage_products');
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }
}
