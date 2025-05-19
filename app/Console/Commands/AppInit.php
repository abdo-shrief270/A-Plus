<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class AppInit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This Command used to initialize the project';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Admin::create([
            'name'=>'Abdo Shrief',
            'user_name'=>'abdo_shrief',
            'email'=>'abdo.shrief270@gmail.com',
            'password'=>Hash::make('12345678')
        ]);
    }
}
