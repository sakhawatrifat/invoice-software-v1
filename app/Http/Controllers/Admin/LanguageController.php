<?php

namespace App\Http\Controllers\Admin;


use Auth;
use File;
use Image;
use Session;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

use App\Models\User;
use App\Models\Language;
use App\Models\Translation;

class LanguageController extends Controller
{
    public function index()
    {
        if (!hasPermission('language.index')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $createRoute = hasPermission('language.create') ? route('admin.language.create') : '';
        $dataTableRoute = hasPermission('language.index') ? route('admin.language.datatable') : '';

        return view('admin.language.index', get_defined_vars());
    }

    public function datatable(Request $request)
    {
        $user = Auth::user();
        $query = Language::with(['creator'])->orderBy('status', 'desc')->latest();

        // Check if DataTables is sending an order by 'name' column
        if ($request->has('order')) {
            foreach ($request->input('order') as $order) {
                $columnIndex = $order['column'];
                $columnName = $request->input("columns.$columnIndex.data");
                $direction = $order['dir'];

                if ($columnName === 'name') {
                    $query->orderBy('name', $direction);
                }
            }
        }

        if($user->user_type != 'admin'){
            $query->where('user_id', $user->id);
        }

        // Properly grouped global search
        if (request()->has('search') && $search = request('search')['value']) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });

            // Similarly for creator, if needed
            $creatorIds = User::where('name', 'like', "%{$search}%")->pluck('id')->toArray();
            if (!empty($creatorIds)) {
                $query->orWhereIn('created_by', $creatorIds);
            }
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('logo', function ($row) {
                if ($row->logo_url) {
                    return '<img src="' . $row->logo_url . '" alt="Logo" height="40">';
                } else {
                    return 'N/A';
                }
            })
            ->addColumn('status', function ($row) {
                // Hide toggle for specific codes like 'en' (you can add more)
                if (in_array($row->code, ['en'])) {
                    return '';
                }

                // If the user does not have permission, just show the status label
                if (!hasPermission('language.status')) {
                    return '
                        <span class="' . ($row->status == 1 ? 'text-success' : 'text-danger') . '">
                            ' . ($row->status == 1 ? 'Active' : 'Inactive') . '
                        </span>';
                }

                // If permission granted, show the toggle
                $newStatus = $row->status == 1 ? 0 : 1;
                $statusUrl = route('admin.language.status', ['id' => $row->id, 'status' => $newStatus]);

                return '
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input toggle-table-data-status"
                            data-id="' . $row->id . '"
                            data-url="' . $statusUrl . '"
                            ' . ($row->status == 1 ? 'checked' : '') . '>
                    </div>';
            })

            ->addColumn('created_by', function ($row) {
                return $row->creator ? $row->creator->name : 'N/A';
            })
            ->addColumn('action', function ($row) {
                $translationUrl = route('admin.language.translate.form', $row->id);
                $editUrl        = route('admin.language.edit', $row->id);
                $deleteUrl      = route('admin.language.destroy', $row->id);

                $buttons = '';

                // Translate button
                if (hasPermission('language.translate')) {
                    $buttons .= '
                        <a href="' . $translationUrl . '" class="btn btn-sm btn-info" title="Translate">
                            <i class="fa-solid fa-language"></i>
                        </a>
                    ';
                }

                // Edit button
                if (hasPermission('language.edit')) {
                    if (in_array($row->code, ['en'])) {
                        $buttons .='';
                    }else{
                        $buttons .= '
                            <a href="' . $editUrl . '" class="btn btn-sm btn-primary" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                        ';
                    }
                }

                // Delete button (not allowed for 'en')
                if (!in_array($row->code, ['en']) && hasPermission('language.delete')) {
                    $buttons .= '
                        <button class="btn btn-sm btn-danger delete-table-data-btn" title="Delete"
                            data-id="' . $row->id . '"
                            data-url="' . $deleteUrl . '">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    ';
                }

                return !empty(trim($buttons)) ? '
                    <div class="d-flex align-items-center gap-2">
                        ' . $buttons . '
                    </div>' : 'N/A';
            })



            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('Y-m-d, H:i');
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function create()
    {
        if (!hasPermission('language.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $listRoute = hasPermission('language.index') ? route('admin.language.index') : '';
        $saveRoute = hasPermission('language.create') ? route('admin.language.store') : '';

        return view('admin.language.addEdit', get_defined_vars());
    }

    public function store(Request $request)
    {
        if (!hasPermission('language.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        return $this->saveData($request);
    }

    public function status($id, $status)
    {
        if (!hasPermission('language.status')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        if(!in_array($status, [0,1])){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['status_is_incorrect'] ?? 'status_is_incorrect'
            ];
        }
        
        $user = Auth::user();
        $language = Language::where('id', $id)->whereNotIn('code', ['en'])->first();
        if(empty($language)){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }

        $language->status = $status;
        $language->updated_by = $user->id;
        $language->save();
        
        $statusName = $status == 1 ? 'Active' : 'Inactive';
        return [
            'is_success' => 1,
            'icon' => 'success',
            'message' => (getCurrentTranslation()['status_updated'] ?? 'status_updated') . ' (' . $statusName . ')',
        ];
    }

    public function edit($id)
    {
        if (!hasPermission('language.edit')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $listRoute = hasPermission('language.index') ? route('admin.language.index') : '';
        $saveRoute = hasPermission('language.edit') ? route('admin.language.update', $id) : '';

        $editData = Language::where('id', $id)->whereNotIn('code', ['en'])->first();
        if(empty($editData)){
            abort(404);
        }
        // if($user->user_type != 'admin' && $editData->user_id != $user->id){
            //     abort(404);
            // }
            
        //dd($editData);
        return view('admin.language.addEdit', get_defined_vars());
    }

    public function update(Request $request, $id)
    {
        if (!hasPermission('language.edit')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }
        
        return $this->saveData($request, $id);
    }

    public function destroy($id)
    {
        if (!hasPermission('language.delete')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $data = Language::where('id', $id)->whereNotIn('code', ['en'])->first();
        if(empty($data)){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }
        $data->save();

        $data->delete();

        return [
            'is_success' => 1,
            'icon' => 'success',
            'message' => getCurrentTranslation()['data_deleted'] ?? 'data_deleted'
        ];
    }


    public function translateForm($id)
    {
        if (!hasPermission('language.translate')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $language = Language::where('id', $id)->first();

        if(empty($language)){
            abort(404);
        }

        $baseTranslations = Translation::where('lang', 'en')->orderBy('id', 'desc')->paginate(50);

        $langKeys = $baseTranslations->pluck('lang_key')->toArray();
        $langTranslations = Translation::where('lang', $language->code)
            ->whereIn('lang_key', $langKeys)
            ->get()
            ->keyBy('lang_key');

        return view('admin.language.translation', get_defined_vars());
    }

    public function newTranslateKey(Request $request, $id)
    {
        if (!hasPermission('language.translate')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        if(!isset($request->lang_key)){
            return back()->with('warning', getCurrentTranslation()['language_key_required_message'] ?? 'language_key_required_message');
        }

        $translation = Translation::where('lang_key', $request->lang_key)->first();
        if(empty($translation)){
            $translation = new Translation();
        }
        $translation->lang = 'en';
        $translation->lang_key = $request->lang_key;
        $translation->lang_value = null;
        $translation->save();

        return redirect(route('admin.language.translate.form', $id))->with('message', getCurrentTranslation()['new_language_key_saved'] ?? 'new_language_key_saved');
    }

    public function translateDelete($key){
        if (!hasPermission('language.translate')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $translation = Translation::where('lang_key', $key)->first();
        if(empty($translation)){
            abort(404);
        }
        $translation->delete();

        return redirect()->back()->with('message', getCurrentTranslation()['language_key_deleted'] ?? 'language_key_deleted');
    }

    public function translateUpdate(Request $request, $id)
    {
        if (!hasPermission('language.translate')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $language = Language::where('id', $id)->first();

        if (empty($language)) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }

        DB::beginTransaction();
        try {
            $translationData = $request->translationData;
            $lang = $language->code;

            // $upserts = [];
            // foreach ($translationData as $langKey => $value) {
            //     $upserts[] = [
            //         'lang' => $lang,
            //         'lang_key' => $langKey,
            //         'lang_value' => $value,
            //         'updated_at' => now(),
            //         'updated_by' => Auth::user()->id,
            //     ];
            // }

            // Translation::upsert(
            //     $upserts,
            //     ['lang', 'lang_key'],
            //     ['lang_key', 'lang_value', 'updated_at', 'updated_by']
            // );

            $translationData = $request->translationData;
            $lang = $language->code;

            foreach ($translationData as $langKey => $value) {
                $existing = Translation::where('lang', $lang)
                    ->where('lang_key', $langKey)
                    ->first();

                if ($existing) {
                    $existing->lang_value = $value;
                    $existing->updated_at = now();
                    $existing->updated_by = Auth::id();
                    $existing->save();
                } else {
                    $translation = new Translation();
                    $translation->lang = $lang;
                    $translation->lang_key = $langKey;
                    $translation->lang_value = $value;
                    $translation->updated_at = now();
                    $translation->updated_by = Auth::id();
                    $translation->save();
                }
            }

            DB::commit();

            return [
                'is_success' => 1,
                'icon' => 'success',
                'message' => getCurrentTranslation()['data_saved'] ?? 'data_saved'
            ];


            DB::commit();
            return [
                'is_success' => 1,
                'icon' => 'success',
                'message' => getCurrentTranslation()['data_saved'] ?? 'data_saved'
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Language store error', ['error' => $e->getMessage()]);

            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_saving_error'] ?? 'data_saving_error'
            ];
        }
    }


    public function saveData(Request $request, $id = null)
    {
        $messages = getCurrentTranslation();

        $rules = [
            'name' => 'required|string|max:255|unique:languages,name,' . ($id ?? 'NULL'),
            'code' => 'required|string|max:255|unique:languages,code,' . ($id ?? 'NULL'),
            'status' => 'required|in:0,1',
        ];

        $validator = Validator::make($request->all(), $rules, [
            'required' => $messages['required_message'] ?? 'This field is required.',
            'unique' => $messages['unique_message'] ?? 'This value has already been taken.',
            'name.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        //dd($request->all());

        $user = Auth::user();
        $language = null;
        if (isset($id)) {
            $language = Language::where('id', $id)->whereNotIn('code', ['en'])->first();
            if(empty($language)){
                return [
                    'is_success' => 0,
                    'icon' => 'error',
                    'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
                ];
            }
        }
        
        $userId = $request->user_id ?? Auth::id();

        if (empty($language)) {
            $language = new Language();
            $language->created_by = Auth::id();
        } else {
            $language->updated_by = Auth::id();
        }
        

        DB::beginTransaction();
        try {
            //$language->user_id = $userId;
            $language->name = $request->name ?? null;
            $language->code = $request->code ?? null;
            $language->status = $request->status ?? 0;

            $language->save();


            DB::commit();
            $response = [
                'is_success' => 1,
                'icon' => 'success',
                'message' => getCurrentTranslation()['data_saved'] ?? 'data_saved'
            ];
            
            // Add redirect_url only when creating new data (not updating)
            if (!isset($id) || empty($id)) {
                $response['redirect_url'] = route('admin.language.index');
            }
            
            return $response;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Language store error', ['error' => $e->getMessage()]);

            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_saving_error'] ?? 'data_saving_error'
            ];
        }
    }
    
}