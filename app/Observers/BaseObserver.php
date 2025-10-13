<?php

namespace App\Observers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class BaseObserver
{
    public function creating($model)
    {
        $table = $model->getTable();

        if (Auth::check()) {
            if (Schema::hasColumn($table, 'created_by')) {
                $model->created_by = Auth::id();
            }

            if (Schema::hasColumn($table, 'updated_by')) {
                $model->updated_by = Auth::id();
            }
        }
    }

    public function updating($model)
    {
        $table = $model->getTable();
        
        if (Auth::check() && Schema::hasColumn($table, 'updated_by')) {
            if(empty($model->deleted_by)){
                $model->updated_by = Auth::id();
            }
        }
    }

    public function deleting($model)
    {
        $table = $model->getTable();

        if (
            Auth::check() &&
            Schema::hasColumn($table, 'deleted_by') &&
            method_exists($model, 'runSoftDelete') &&
            !$model->isForceDeleting()
        ) {
            $model->deleted_by = Auth::id();
            $model->save();
        }
    }
}
