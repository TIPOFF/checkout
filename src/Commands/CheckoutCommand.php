<?php

namespace Tipoff\Checkout\Commands;

use Illuminate\Console\Command;

class CheckoutCommand extends Command
{
    public $signature = 'checkout';

    public $description = 'My command';

    public function handle()
    {
        $this->comment('All done');
    }
}
