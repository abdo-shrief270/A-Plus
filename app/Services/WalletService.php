<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function getBalance(Student $student): int
    {
        $wallet = $student->wallet ?? $this->createWallet($student);
        return $wallet->balance;
    }

    public function createWallet(Student $student): Wallet
    {
        return Wallet::firstOrCreate(['student_id' => $student->id]);
    }

    public function deposit(Student $student, int $amount, string $type, ?Model $reference = null): WalletTransaction
    {
        return DB::transaction(function () use ($student, $amount, $type, $reference) {
            $wallet = $student->wallet ?? $this->createWallet($student);

            $wallet->increment('balance', $amount);

            return $wallet->transactions()->create([
                'amount' => $amount,
                'type' => $type,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference ? $reference->id : null,
            ]);
        });
    }

    public function withdraw(Student $student, int $amount, string $type, ?Model $reference = null): WalletTransaction
    {
        return DB::transaction(function () use ($student, $amount, $type, $reference) {
            $wallet = $student->wallet ?? $this->createWallet($student);

            if ($wallet->balance < $amount) {
                throw new \Exception("Insufficient funds");
            }

            $wallet->decrement('balance', $amount);

            return $wallet->transactions()->create([
                'amount' => -$amount, // Negative for withdrawal
                'type' => $type,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference ? $reference->id : null,
            ]);
        });
    }

    public function payForContent(Student $student, Model $content, int $cost, string $type = 'content_view'): bool
    {
        if ($cost <= 0) {
            return true;
        }

        // Check if already paid
        $alreadyPaid = $student->wallet?->transactions()
            ->where('reference_type', get_class($content))
            ->where('reference_id', $content->id)
            ->where('amount', '<', 0) // Debit
            ->exists();

        if ($alreadyPaid) {
            return true;
        }

        try {
            $this->withdraw($student, $cost, $type, $content);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
