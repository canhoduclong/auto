<?php
namespace App\Policies;
use App\Models\User;
use App\Models\Transaction;
class TransactionPolicy
{
    public function viewAny(User $user) { return $user->isAdmin() || $user->hasPermission('transaction.view'); }
    public function view(User $user, Transaction $transaction) { return $user->isAdmin() || $user->hasPermission('transaction.view'); }
    public function create(User $user) { return $user->isAdmin() || $user->hasPermission('transaction.create'); }
    public function refund(User $user, Transaction $transaction) { return $user->isAdmin() || $user->hasPermission('transaction.refund'); }
}
