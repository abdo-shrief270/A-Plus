<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\WalletTransaction;
use Illuminate\Auth\Access\HandlesAuthorization;

class WalletTransactionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the admin can view any models.
     */
    public function viewAny(Admin $admin): bool
    {
        return $admin->can('view_any_wallet::transaction');
    }

    /**
     * Determine whether the admin can view the model.
     */
    public function view(Admin $admin, WalletTransaction $walletTransaction): bool
    {
        return $admin->can('view_wallet::transaction');
    }

    /**
     * Determine whether the admin can create models.
     */
    public function create(Admin $admin): bool
    {
        return $admin->can('create_wallet::transaction');
    }

    /**
     * Determine whether the admin can update the model.
     */
    public function update(Admin $admin, WalletTransaction $walletTransaction): bool
    {
        return $admin->can('update_wallet::transaction');
    }

    /**
     * Determine whether the admin can delete the model.
     */
    public function delete(Admin $admin, WalletTransaction $walletTransaction): bool
    {
        return $admin->can('delete_wallet::transaction');
    }

    /**
     * Determine whether the admin can bulk delete.
     */
    public function deleteAny(Admin $admin): bool
    {
        return $admin->can('delete_any_wallet::transaction');
    }

    /**
     * Determine whether the admin can permanently delete.
     */
    public function forceDelete(Admin $admin, WalletTransaction $walletTransaction): bool
    {
        return $admin->can('{{ ForceDelete }}');
    }

    /**
     * Determine whether the admin can permanently bulk delete.
     */
    public function forceDeleteAny(Admin $admin): bool
    {
        return $admin->can('{{ ForceDeleteAny }}');
    }

    /**
     * Determine whether the admin can restore.
     */
    public function restore(Admin $admin, WalletTransaction $walletTransaction): bool
    {
        return $admin->can('{{ Restore }}');
    }

    /**
     * Determine whether the admin can bulk restore.
     */
    public function restoreAny(Admin $admin): bool
    {
        return $admin->can('{{ RestoreAny }}');
    }

    /**
     * Determine whether the admin can replicate.
     */
    public function replicate(Admin $admin, WalletTransaction $walletTransaction): bool
    {
        return $admin->can('{{ Replicate }}');
    }

    /**
     * Determine whether the admin can reorder.
     */
    public function reorder(Admin $admin): bool
    {
        return $admin->can('{{ Reorder }}');
    }
}
