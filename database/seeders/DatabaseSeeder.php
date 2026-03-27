<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Seeder;
use App\Models\ChatGroup;
use App\Models\ChatGroupMember;
use App\Models\User;
use App\Models\UserCompany;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // User::factory(10)->create();

        // Artisan::call('passport:client', [
        //     '--personal' => true,
        //     '--name' => 'Personal Access Client' // Optional: you can specify the client name
        // ]);

        // admin@gmail.com
        $user = User::where('email', 'admin@gmail.com')->first();
        if (is_null($user)) {
            $user = new User();
            $user->user_type = 'admin';
            $user->uid = uniqid();
            $user->image = null;
            $user->name = "Admin";
            $user->email = "admin@gmail.com";
            $user->password = bcrypt('12345678');
            $user->email_verified_at = date('Y-m-d H:i:s');
            $user->device_token = uniqid();
            $user->ip_address = null;
            $user->status = 'Active';
            $user->save();

            $company = new UserCompany();
            $company->user_id = $user->id;
            $company->company_name = 'My Company';
            $company->save();
        }

        // Automation Chatbot user (pinned at top of chat list)
        $chatbot = User::where('email', 'chatbot@system')->first();
        if (is_null($chatbot)) {
            $chatbot = new User();
            $chatbot->user_type = 'admin';
            $chatbot->is_automation_chatbot = 1;
            $chatbot->uid = 'chatbot-' . uniqid();
            $chatbot->image = null;
            $chatbot->name = 'Automation Chatbot';
            $chatbot->email = 'chatbot@system';
            $chatbot->password = bcrypt(str()->random(32));
            $chatbot->email_verified_at = date('Y-m-d H:i:s');
            $chatbot->device_token = null;
            $chatbot->ip_address = null;
            $chatbot->status = 'Active';
            $chatbot->save();
        }

        // Fixed support group: NTT Support Team (cannot be deleted from UI/API)
        $creator = User::where('email', 'admin@gmail.com')->first();
        if (is_null($creator)) {
            $creator = User::where('email', 'chatbot@system')->first();
        }
        if (is_null($creator)) {
            $creator = User::where('user_type', '!=', 'user')->first();
        }

        if (!is_null($creator)) {
            $fixedGroup = ChatGroup::firstOrCreate(
                ['name' => 'NTT Support Team'],
                [
                    'created_by_user_id' => $creator->id,
                    'is_fixed_group' => 1,
                ]
            );

            if ((int) ($fixedGroup->is_fixed_group ?? 0) !== 1) {
                $fixedGroup->is_fixed_group = 1;
                $fixedGroup->save();
            }

            ChatGroupMember::firstOrCreate(
                ['group_id' => $fixedGroup->id, 'user_id' => $creator->id],
                ['role' => 'admin']
            );

            $memberIds = User::where('user_type', '!=', 'user')->pluck('id')->all();
            foreach ($memberIds as $memberId) {
                if ((int) $memberId === (int) $creator->id) {
                    continue;
                }
                ChatGroupMember::firstOrCreate(
                    ['group_id' => $fixedGroup->id, 'user_id' => $memberId],
                    ['role' => 'member']
                );
            }
        }

        $this->call(CurrencySeeder::class);
        $this->call(LanguageSeeder::class);
        $this->call(TranslationSeeder::class);
        $this->call(HRMSeeder::class);
        $this->call(ExpenseSeeder::class);
        $this->call(CRMSeeder::class);
        $this->call(TranslationSeeder::class);
    }
}
