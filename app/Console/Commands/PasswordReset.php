<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use App\Services\XlsxService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'PasswordReset {action?} {--gen}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws \Exception
     */
    private function generateDefaultUsers()
    {
        $emails = [
            'anhntn1@vnpost.vn',
            'dieultx@vnpost.vn',
      /*      'anhnv@vnpost.vn',
            'quantm@vnpost.vn',
            'cannv@vnpost.vn',
            'binhnt@vnpost.vn',
            'hoadq@vnpost.vn',
            'hangnt3@vnpost.vn',
            'tuanda@vnpost.vn',
            'thaodp@vnpost.vn',
            'thuanln@vnpost.vn',
            'haivt@vnpost.vn',
            'lenp@vnpost.vn',
            'ngocmn@vnpost.vn',
            'huyenntt@vnpost.vn'*/
        ];

        $toExports = [
            'email' => 'EMAIL',
            'password' => 'PASSWORD',
        ];


        $entries = [];
        foreach ($emails as $email) {
            $password = base64_encode(random_bytes(16));
            $entries[] = compact('email', 'password');
            $this->generateFor($email, $password);
        }

        $time = uniqid();

        XlsxService::exportZip($toExports, $entries, storage_path("reports/default_users-$time.xlsx"));
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->option('gen')) {
            $this->generateDefaultUsers();
            return;
        }

        $adminEmail = 'admin@localhost';
        $action  = $this->argument('action');
        if ($action === 'rand') {
            $password = Str::random();
        } else {
            $this->info("Enter new password for admin: {$adminEmail}");
            $password = $this->ask('Enter password');
        }

        $this->generateFor($adminEmail, $password);
        $this->info("New password for $adminEmail is: {$password}");
    }

    private function generateFor(string $adminEmail, string $password)
    {
        $user = User::query()->where('email', $adminEmail)->first();
        #$action  = $this->argument('action');

        if (!$user) {
            list($name) = explode('@', $adminEmail);
            $user = new User();
            $user->name = $name;
            $user->username = $adminEmail;
            $user->email = $adminEmail;
            $user->status = 1;
        }

        /*if ($action === 'rand') {
            $password = Str::random();
        } else {
            $this->info("Enter new password for admin: {$adminEmail}");
            $password = $this->ask('Enter password');
        }*/

        $user->password = Hash::make($password);
        $user->save();
        $userRole = DB::selectOne('SELECT * FROM user_roles WHERE user_id=? AND role_id=?', [
            $user->id, Role::SUPER_USER,
        ]);

        if (!$userRole) {
            DB::table('user_roles')->insert([
                'user_id' => $user->id,
                'role_id' => Role::SUPER_USER,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

}
