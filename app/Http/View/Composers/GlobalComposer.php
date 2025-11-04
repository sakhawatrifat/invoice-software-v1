<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Homepage;
use App\Models\User;
use App\Models\Notification;

class GlobalComposer
{
    public function compose(View $view)
    {
        if (Auth::check()) {
            $globalData = User::with('company')->find(Auth::id());
        } else {
            $globalData = User::with('company')->where('user_type', 'admin')->first();
        }

        if($globalData->id == 1 && $globalData->user_type == 'admin' && $globalData->is_staff != 1){
            $userType = $globalData->user_type;
            $allPermissions = getPermissionList();
            $permissions = collect($allPermissions)->filter(function ($item) use ($userType) {
                return $userType === 'admin' || $item['for'] === 'all_user';
            });

            $permissions = $permissions
                ->pluck('permissions')
                ->flatten(1)
                ->pluck('key')
                ->toArray();
            
            if($globalData->id == 1){
                $newPermission = [
                    'user.index', 'user.create', 'user.edit', 'user.status', 'user.delete'
                ];

                $permissions = array_merge($permissions, $newPermission);
            }
                
            $globalData->permissions = $permissions;
            $globalData->save();
        }

        generateNotifications();

        $globalHomepageData = Homepage::where('lang', 'en')->first();

        $view->with([
            'globalData' => $globalData,
            'globalHomepageData' => $globalHomepageData
        ]);
    }
}
