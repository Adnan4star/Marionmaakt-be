<?php

namespace App\Console\Commands;

use App\Http\Controllers\BlogController;
use App\Models\Session;
use Illuminate\Console\Command;

class BlogCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blog:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users=Session::all();
        foreach ($users as $user) {
            $blogController = new BlogController();
            $blogController->syncBlogs($user);
        }
    }
}
