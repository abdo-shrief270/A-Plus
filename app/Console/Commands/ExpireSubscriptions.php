<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Flip active subscriptions whose ends_at has passed to status=expired. '
        . 'IMPORTANT: this command never touches the wallets table — points remain in '
        . 'the student\'s wallet after a subscription ends.';

    public function handle(): int
    {
        $count = Subscription::where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->update(['status' => 'expired']);

        $this->info("Expired {$count} subscription(s).");
        return self::SUCCESS;
    }
}
