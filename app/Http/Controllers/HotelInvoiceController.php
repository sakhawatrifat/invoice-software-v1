<?php

namespace App\Http\Controllers;


use Auth;
use File;
use Image;
use Session;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Mail\HotelInvoiceMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

use App\Models\Language;
use App\Models\Currency;
use App\Models\User;
use App\Models\UserCompany;
use App\Models\HotelInvoice;

use PDF;
use App\Services\PdfService;

class HotelInvoiceController extends Controller
{
    public function index()
    {
        if (!hasPermission('hotel.invoice.index')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }
        
        $createRoute = hasPermission('hotel.invoice.create') ? route('hotel.invoice.create') : '';
        $dataTableRoute = hasPermission('hotel.invoice.index') ? route('hotel.invoice.datatable') : '';

        return view('common.hotel-invoice.index', get_defined_vars());
    }

    public function datatable()
    {
        $getCurrentTranslation = getCurrentTranslation();
        
        $user = Auth::user();

        $query = HotelInvoice::with('user', 'creator')->latest();

        if($user->user_type != 'admin'){
            $query->where('user_id', $user->business_id);
        }

        // // Properly grouped global search
        // if (request()->has('search') && $search = request('search')['value']) {
        //     $query->where(function ($q) use ($search) {
        //         $q->where('hotel_name', 'like', "%{$search}%")
        //             ->orWhere('invoice_id', 'like', "%{$search}%")
        //             ->orWhere('pin_number', 'like', "%{$search}%")
        //             ->orWhere('booking_number', 'like', "%{$search}%")
        //             ->orWhere('hotel_phone', 'like', "%{$search}%")
        //             ->orWhere('hotel_phone', 'like', "%{$search}%");
        //     });

        //     // Similarly for creator, if needed
        //     $creatorIds = User::where('name', 'like', "%{$search}%")->pluck('id')->toArray();
        //     if (!empty($creatorIds)) {
        //         $query->orWhereIn('created_by', $creatorIds);
        //     }
        // }

        return DataTables::of($query)
            ->filter(function ($query) {
                $search = request('search')['value'] ?? null;

                if (!empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('hotel_name', 'like', "%{$search}%")
                            ->orWhere('invoice_id', 'like', "%{$search}%")
                            ->orWhere('pin_number', 'like', "%{$search}%")
                            ->orWhere('booking_number', 'like', "%{$search}%")
                            ->orWhere('hotel_phone', 'like', "%{$search}%");
                    });

                    // ✅ Search by creator name as well
                    $creatorIds = User::where('name', 'like', "%{$search}%")->pluck('id')->toArray();
                    if (!empty($creatorIds)) {
                        $query->orWhereIn('created_by', $creatorIds);
                    }
                }
            })

            ->addIndexColumn()

            // ✅ User (relationship with users table)
            ->addColumn('user_id', function ($row) {
                return $row->user ? $row->user->name : 'N/A';
            })

            // ✅ Pin Number
            ->addColumn('pin_number', function ($row) {
                return $row->pin_number ?? 'N/A';
            })

            // ✅ Booking Number
            ->addColumn('booking_number', function ($row) {
                return $row->booking_number ?? 'N/A';
            })

            ->addColumn('hotel_name', function ($row) {
                $image = $row->hotel_image_url
                    ? '<img src="' 
                        . $row->hotel_image_url 
                        . '" alt="' 
                        . e($row->hotel_name) 
                        . '" height="40" style="margin-right: 8px;">'
                    : '';
                    
                return '<div class="text-center">'
                    . $image
                    . '<br><span>' . e($row->hotel_name) . '</span><br>'
                    // . '<small>' . nl2br(e($row->hotel_address)) . '</small>'
                    . '</div>';
            })




            // ✅ Check-in / Check-out
            ->addColumn('check_in_and_checkout', function ($row) use ($getCurrentTranslation) {
                $checkInLabel = $getCurrentTranslation['in'] ?? 'in';
                $checkOutLabel = $getCurrentTranslation['out'] ?? 'out';

                $checkInDate = $row->check_in_date ?? '—';
                $checkInTime = $row->check_in_time ?? '—';
                $checkOutDate = $row->check_out_date ?? '—';
                $checkOutTime = $row->check_out_time ?? '—';

                return "
                    <div>
                        <strong>{$checkInLabel}:</strong> " . e($checkInDate) . " " . e($checkInTime) . "<br>
                        <strong>{$checkOutLabel}:</strong> " . e($checkOutDate) . " " . e($checkOutTime) . "
                    </div>
                ";
            })


            // ✅ Guest Info (from array)
            ->addColumn('guest_info', function ($row) {
                if (!empty($row->guestInfo) && is_array($row->guestInfo)) {
                    return collect($row->guestInfo)
                        ->map(function ($g) {
                            $passport = $g['passport_no'] ?? null;
                            return e($g['name'] ?? '') . ($passport ? ' (' . e($passport) . ')' : '');
                        })
                        ->implode('<br>');
                }
                return 'N/A';
            })



            // // ✅ Total Price
            // ->addColumn('total_price', function ($row) {
            //     return number_format($row->total_price, 2);
            // })

            // // ✅ Payment Status (badge style)
            // ->addColumn('payment_status', function ($row) {
            //     $badgeClass = $row->payment_status === 'Paid' ? 'badge badge-success' : 'badge badge-danger';
            //     return '<span class="' . $badgeClass . '">' . e($row->payment_status) . '</span>';
            // })

            // ✅ Total Price + Payment Status + Invoice Status
            ->addColumn('total_price', function ($row) {
                $currency = Auth::user()->company_data->currency->short_name ?? 'JPY';

                $paymentBadgeClass = $row->payment_status === 'Paid' 
                    ? 'badge badge-success' 
                    : 'badge badge-danger';

                $invoiceBadgeClass = $row->invoice_status === 'Final' 
                    ? 'badge badge-primary' 
                    : 'badge badge-secondary';

                return '<div>
                    <div>' . $currency.number_format($row->total_price, 2) . '</div>
                    <div>
                        <span class="' . $paymentBadgeClass . '">' . e($row->payment_status) . '</span>
                        <span class="' . $invoiceBadgeClass . '">' . e($row->invoice_status) . '</span>
                    </div>
                </div>';
            })



            // ✅ Created At
            ->editColumn('created_at', function ($row) {
                return $row->created_at ? Carbon::parse($row->created_at)->format('Y-m-d, H:i') : 'N/A';
            })

            // ✅ Created By (relationship with users table)
            ->addColumn('created_by', function ($row) {
                return $row->creator ? $row->creator->name : 'N/A';
            })

            // ✅ Actions
            ->addColumn('action', function ($row) {
                $mailUrl = route('hotel.invoice.mail', $row->id);
                $detailsUrl   = route('hotel.invoice.show', $row->id);
                $editUrl      = route('hotel.invoice.edit', $row->id);
                $duplicateUrl = route('hotel.invoice.duplicate', $row->id);
                $deleteUrl    = route('hotel.invoice.destroy', $row->id);

                $buttons = '';

                // Mail button (requires permission)
                if (hasPermission('hotel.invoice.mail')) {
                    $buttons .= '
                        <a href="' . $mailUrl . '" class="btn btn-sm btn-secondary my-1" title="Mail">
                            <i class="fa-solid fa-envelope"></i>
                        </a>
                    ';
                }

                // 👁️ Details
                if (hasPermission('hotel.invoice.show')) {
                    $buttons .= '
                        <a href="' . $detailsUrl . '" class="btn btn-sm btn-info my-1" title="Details">
                            <i class="fa-solid fa-pager"></i>
                        </a>
                    ';
                }

                // ✏️ Edit
                if (hasPermission('hotel.invoice.edit')) {
                    $buttons .= '
                        <a href="' . $editUrl . '" class="btn btn-sm btn-primary my-1" title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                    ';
                }

                // 📑 Duplicate
                if (hasPermission('hotel.invoice.duplicate')) {
                    $buttons .= '
                        <a href="' . $duplicateUrl . '" 
                        class="btn btn-sm btn-warning my-1 data-confirm-button" 
                        title="Duplicate">
                            <i class="fa-solid fa-copy"></i>
                        </a>
                    ';
                }

                // 🗑️ Delete
                if (hasPermission('hotel.invoice.delete')) {
                    $buttons .= '
                        <button class="btn btn-sm btn-danger my-1 delete-table-data-btn"
                            data-id="' . $row->id . '"
                            data-url="' . $deleteUrl . '"
                            title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    ';
                }

                return !empty($buttons) ? $buttons : 'N/A';
            })


            ->rawColumns(['hotel_name', 'check_in_and_checkout', 'guest_info', 'total_price', 'payment_status', 'action'])
            ->make(true);

    }



    public function create()
    {
        if (!hasPermission('hotel.invoice.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $listRoute = hasPermission('hotel.invoice.index') ? route('hotel.invoice.index') : '';
        $saveRoute = hasPermission('hotel.invoice.create') ? route('hotel.invoice.store') : '';

        $currencies = Currency::where('status', 'Active')->orderBy('currency_name', 'asc')->get();
        return view('common.hotel-invoice.invoiceAddEdit', get_defined_vars());
    }

    public function store(Request $request)
    {
        if (!hasPermission('hotel.invoice.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }
        
        return $this->saveData($request);
    }


    public function show($id)
    {
        if (!hasPermission('hotel.invoice.show')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $listRoute = hasPermission('hotel.invoice.index') ? route('hotel.invoice.index') : '';
        $saveRoute = hasPermission('hotel.invoice.edit') ? route('hotel.invoice.update', $id) : '';

        $editData = HotelInvoice::with('user', 'user.company', 'creator')->where('id', $id)->first();
        if(empty($editData)){
            abort(404);
        }
        if($user->user_type != 'admin' && $editData->user_id != $user->business_id){
            abort(404);
        }
        //dd($editData);
        return view('common.hotel-invoice.details', get_defined_vars());
    }

    public function edit($id)
    {
        if (!hasPermission('hotel.invoice.edit')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $listRoute = hasPermission('hotel.invoice.index') ? route('hotel.invoice.index') : '';
        $saveRoute = hasPermission('hotel.invoice.edit') ? route('hotel.invoice.update', $id) : '';

        $query = HotelInvoice::where('id', $id);
        if(Auth::user()->user_type == 'user'){
            $query->where('user_id', $user->business_id);
        }
        $editData = $query->first();
        
        if(empty($editData)){
            abort(404);
        }
        // if($user->user_type != 'admin' && $editData->user_id != $user->id){
            //     abort(404);
            // }
            
        //dd($editData);
        $currencies = Currency::where('status', 'Active')->orderBy('currency_name', 'asc')->get();
        return view('common.hotel-invoice.invoiceAddEdit', get_defined_vars());
    }

    public function update(Request $request, $id)
    {
        if (!hasPermission('hotel.invoice.edit')) {
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
        if (!hasPermission('hotel.invoice.delete')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $query = HotelInvoice::where('id', $id);
        if(Auth::user()->user_type == 'user'){
            $query->where('user_id', $user->business_id);
        }
        $data = $query->first();

        if(empty($data)){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }
        // if($user->user_type != 'admin' && $data->user_id != $user->id){
        //     return [
        //         'is_success' => 0,
        //         'icon' => 'error',
        //         'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
        //     ];
        // }

        if($data->hotel_image != null){
            deleteUploadedFile($data->hotel_image);
        }

        $data->deleted_by = $user->id;
        $data->save();

        $data->delete();

        return [
            'is_success' => 1,
            'icon' => 'success',
            'message' => getCurrentTranslation()['data_deleted'] ?? 'data_deleted'
        ];
    }

    public function downloadPdf(Request $request, $id, PdfService $pdfService)
    {
        if (!hasPermission('hotel.invoice.show')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }
        
        $user = Auth::user();

        $query = HotelInvoice::where('id', $id);
        if(Auth::user()->user_type == 'user'){
            $query->where('user_id', $user->business_id);
        }
        $editData = $query->first();
        
        if(empty($editData)){
            abort(404);
        }

        // Render the Blade view to HTML string
        $blade = 'common.hotel-invoice.includes.invoice';
        $html = view($blade, compact('editData'))->render();

        // Dynamic filename with datetime
        //$filename = 'invoice_' . $editData->id . '_' . Carbon::now()->format('Ymd_His') . '.pdf';
        
        $pdfType = 'hotel-invoice';
        $filename = 'Invoice-' . $editData->booking_number . '.pdf';

        // Generate and return the PDF inline in browser
        // If you want to force download, change 'I' to 'D' in Output()
        $IorD = env('UNDER_DEVELOPMENT') == true ? 'I' : 'D';
        return $pdfService->generatePdf($editData, $html, $filename, 'I', $pdfType);
    }

    public function duplicate($id)
    {
        if (!hasPermission('hotel.invoice.duplicate')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $query = HotelInvoice::where('id', $id);
        if(Auth::user()->user_type == 'user'){
            $query->where('user_id', $user->business_id);
        }
        $data = $query->first();

        if(empty($data)){
            abort(404);
        }
        if($user->user_type != 'admin' && $data->user_id != $user->business_id){
            abort(404);
        }

        DB::beginTransaction();
        try {
            $duplicate = new HotelInvoice();
            $duplicate->created_by = $user->id;
            $duplicate->user_id    = $user->business_id;

            // ✅ Duplicate hotel image (copy old file)
            $hotelImage = null;
            if (!empty($data->hotel_image)) {
                $relativePath = $data->hotel_image; // e.g. "hotel/hotel-image-68aad65d6bc1a.png"

                if (\Storage::disk('public')->exists($relativePath)) {
                    $ext = pathinfo($relativePath, PATHINFO_EXTENSION);

                    // ✅ Generate filename in format "hotel-{uniqueid}.{ext}"
                    $newFileName = 'hotel-image-' . uniqid() . '.' . $ext;
                    $newFilePath = 'hotel/' . $newFileName;

                    // Copy file
                    \Storage::disk('public')->copy($relativePath, $newFilePath);

                    // Save new relative path in DB
                    $hotelImage = $newFilePath;
                } else {
                    \Log::error('Duplicate image failed: file not found', [
                        'given'     => $data->hotel_image,
                        'converted' => $relativePath,
                        'full_path' => storage_path('app/public/' . $relativePath),
                    ]);
                }
            }

            // Assign fields
            $duplicate->website_name        = $data->website_name ?? null;
            $duplicate->pin_number        = $data->pin_number;
            $duplicate->booking_number    = $data->booking_number; // you may append '-COPY' here if needed
            $duplicate->hotel_image       = $hotelImage ?? $data->hotel_image;
            $duplicate->hotel_name        = $data->hotel_name;
            $duplicate->hotel_address     = $data->hotel_address;
            $duplicate->hotel_phone       = $data->hotel_phone;
            $duplicate->hotel_email       = $data->hotel_email;
            $duplicate->check_in_date     = $data->check_in_date;
            $duplicate->check_in_time     = $data->check_in_time;
            $duplicate->check_out_date    = $data->check_out_date;
            $duplicate->check_out_time    = $data->check_out_time;
            $duplicate->room_type         = $data->room_type;
            $duplicate->total_room        = $data->total_room;
            $duplicate->total_night       = $data->total_night;
            $duplicate->guestInfo         = $data->guestInfo;
            $duplicate->occupancy_info    = $data->occupancy_info;
            $duplicate->room_info         = $data->room_info;
            $duplicate->meal_info         = $data->meal_info;
            $duplicate->room_amenities    = $data->room_amenities;
            $duplicate->total_price       = $data->total_price;
            $duplicate->payment_status    = 'Unpaid'; // always reset to Unpaid for duplicates
            $duplicate->invoice_status    = 'Draft'; // always reset to Draft for duplicates
            $duplicate->cancellationPolicy = $data->cancellationPolicy;
            $duplicate->policy_note       = $data->policy_note;
            $duplicate->contact_info      = $data->contact_info;

            $duplicate->save();

            DB::commit();

            $alert = 'success';
            $message= getCurrentTranslation()['data_duplicated'] ?? 'data_duplicated';

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Ticket duplicate error', ['error' => $e->getMessage()]);

            $alert = 'error';
            $message= getCurrentTranslation()['something_went_wrong'] ?? 'something_went_wrong';
        }

        return redirect(route('hotel.invoice.index'))->with($alert, $message);
    }


    public function saveData(Request $request, $id = null)
    {
        $invoiceId = $id; // or however you determine update
        
        $messages = getCurrentTranslation();

        $logoMimes = 'heic,jpg,jpeg,png';
        $maxImageSize = 3072; // in KB

        $validator = Validator::make($request->all(), [
            'website_name'        => 'nullable|string|max:100',
            'pin_number'        => 'required|string|max:100',
            'booking_number'    => 'required|string|max:100',
            'hotel_name'        => 'required|string|max:255',
            'hotel_address'     => 'required|string',
            'check_in_date'     => 'required|date',
            'check_in_time'     => 'required|date_format:H:i',
            'check_out_date'    => 'required|date|after_or_equal:check_in_date',
            'check_out_time'    => 'required|date_format:H:i',
            'room_type'         => 'required|string|max:255',
            'total_room'        => 'required|integer|min:1',
            'total_night'       => 'required|integer|min:1',
            'occupancy_info'    => 'nullable|string',
            'room_info'         => 'nullable|string|max:255',
            'meal_info'         => 'nullable|string|max:255',
            'room_amenities'    => 'nullable|string',
            'total_price'       => 'required|numeric|min:0',
            'payment_status'       => 'required|in:Paid,Unpaid',
            'invoice_status'       => 'required|in:Draft,Final',
            
            // Guest info (array of guests)
            'guest_info'                    => 'required|array|min:1',
            'guest_info.*.name'             => 'required|string|max:255',
            'guest_info.*.passport_number'  => 'nullable|string|max:50',
            
            // Cancellation policy (array of rules)
            'cancellation_policy'               => 'nullable|array',
            'cancellation_policy.*.date_time'   => 'required_with:cancellation_policy|string|max:255',
            'cancellation_policy.*.fee'         => 'required_with:cancellation_policy|string|max:255',
            
            'policy_note'    => 'nullable|string',
            'contact_info'   => 'nullable|string',
            
            // Image upload
            'hotel_image' => ($id ? 'nullable' : 'required') . '|mimes:' . $logoMimes . '|max:' . $maxImageSize,
        ], [
            // Generic
            'required'   => $messages['required_message'] ?? 'This field is required.',
            'unique'     => $messages['unique_message'] ?? 'This value has already been taken.',
            'exists'     => $messages['exists_message'] ?? 'The selected value is invalid.',
            'in'         => $messages['in_message'] ?? 'The selected value is invalid.',
            'confirmed'  => $messages['confirmed_message'] ?? 'The confirmation does not match.',
            'date'       => $messages['date_message'] ?? 'Please enter a valid date.',
            'date_format'=> $messages['date_format_message'] ?? 'The format must be HH:MM.',

            // Min / Max with placeholders
            'string.min' => ($messages['min_string_message'] ?? 'This field allowed minimum character length is: ') . ':min',
            'string.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ':max',
            'numeric.min'=> ($messages['min_numeric_message'] ?? 'This value must be at least :min.'),
            'numeric.max'=> ($messages['max_numeric_message'] ?? 'This value may not be greater than :max.'),
            'integer.min'=> ($messages['min_integer_message'] ?? 'This value must be at least :min.'),

            // File
            'hotel_image.max'   => ($messages['max_file_size_message'] ?? 'The maximum allowed file size for this field is: ') . ($maxImageSize / 1024) . ' MB',
            'hotel_image.image' => $messages['image_message'] ?? 'This must be an image.',
            'hotel_image.mimes' => ($messages['mimes_message'] ?? 'The file must be of type:') . ' ' . $logoMimes,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        //dd($request->all());

        $authUser = Auth::user();
        $invoiceData = null;
        if (isset($id)) {
            $query = HotelInvoice::where('id', $id);
            if(Auth::user()->user_type == 'user'){
                $query->where('user_id', $authUser->business_id);
            }
            $invoiceData = $query->first();
            if(empty($invoiceData)){
                return [
                    'is_success' => 0,
                    'icon' => 'error',
                    'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
                ];
            }
        }

        //dd($invoiceData);
        
        $hotel_image = null;
        if($request->hasFile('hotel_image')){
            $oldFile = $invoiceData->hotel_image ?? null;
            $hotel_image = handleImageUpload($request->hotel_image, 500, 500, $folderName='hotel', 'hotel-image', $oldFile); //$uploadedFile, $resizeMaxWidth, $resizeMaxHeight, $folderName, $fileName ,$oldFile
        }
        if($hotel_image == null && !empty($invoiceData)){
            $hotel_image = $invoiceData->hotel_image;
        }

        if (empty($invoiceData)) {
            $invoiceData = new HotelInvoice();
            $invoiceData->created_by = $authUser->id;
        } else {
            $invoiceData->updated_by = $authUser->id;
        }

        DB::beginTransaction();
        try {
            $invoiceData->user_id = $authUser->business_id;
            $invoiceData->website_name = $request->website_name ?? null;
            $invoiceData->pin_number = $request->pin_number ?? null;
            $invoiceData->booking_number = $request->booking_number ?? null;
            
            $invoiceData->hotel_image = $hotel_image;
            $invoiceData->hotel_name = $request->hotel_name ?? null;
            $invoiceData->hotel_address = $request->hotel_address ?? null;
            $invoiceData->hotel_phone = $request->hotel_phone ?? null;
            $invoiceData->hotel_email = $request->hotel_email ?? null;
            $invoiceData->check_in_date = $request->check_in_date ?? null;
            $invoiceData->check_in_time = $request->check_in_time ?? null;
            $invoiceData->check_out_date = $request->check_out_date ?? null;
            $invoiceData->check_out_time = $request->check_out_time ?? null;
            $invoiceData->room_type = $request->room_type ?? null;
            $invoiceData->total_room = $request->total_room ?? null;
            $invoiceData->total_night = $request->total_night ?? null;
            $invoiceData->guestInfo = $request->guest_info ?? null;
            $invoiceData->occupancy_info = $request->occupancy_info ?? null;
            $invoiceData->room_info = $request->room_info ?? null;
            $invoiceData->meal_info = $request->meal_info ?? null;
            $invoiceData->room_amenities = $request->room_amenities ?? null;
            $invoiceData->total_price = $request->total_price ?? 0;
            $invoiceData->payment_status = $request->payment_status ?? 'Unpaid';
            $invoiceData->invoice_status = $request->invoice_status ?? 'Draft';
            $invoiceData->cancellationPolicy = $request->cancellation_policy ?? null;
            $invoiceData->policy_note = $request->policy_note ?? null;
            $invoiceData->contact_info = $request->contact_info ?? null;
            
            $invoiceData->save();            

            DB::commit();
            return [
                'is_success' => 1,
                'icon' => 'success',
                'message' => getCurrentTranslation()['data_saved'] ?? 'data_saved'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('User store error', ['error' => $e->getMessage()]);

            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_saving_error'] ?? 'data_saving_error'
            ];
        }
    }

    
    public function mail($id)
    {
        if (!hasPermission('hotel.invoice.mail')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();

        $listRoute = hasPermission('hotel.invoice.index') ? route('hotel.invoice.index') : '';
        $saveRoute = hasPermission('hotel.invoice.edit') ? route('hotel.invoice.update', $id) : '';

        $query = HotelInvoice::with('user', 'creator')->where('id', $id);
        if(Auth::user()->user_type == 'user'){
            $query->where('user_id', $user->business_id);
        }
        $editData = $query->first();
        if(empty($editData)){
            abort(404);
        }

        //$airlines = Airline::where('status', 1)->orderBy('name', 'asc')->get();

        //dd($editData);
        return view('common.hotel-invoice.sendMailForm', get_defined_vars());
    }


    public function mailContentLoad(Request $request, $id)
    {
        $user = Auth::user();
        
        //dd($request->all());

        
        $messages = getCurrentTranslation();
        $dateFormat = 'Y-m-d';
        $dateTimeFormat = 'Y-m-d H:i';

        // Base rules for passengers array and id
        $rules = [
            'guest' => 'nullable|array',
            'guest.*.name' => 'nullable',
            'guest.*.email' => 'nullable|email|max:100',
        ];

        $validator = Validator::make($request->all(), $rules, [
            'required' => $messages['required_message'] ?? 'This field is required.',
            'array' => $messages['array_message'] ?? 'This field must be an array.',
            'string' => $messages['string_message'] ?? 'This field must be a string.',
            'integer' => $messages['integer_message'] ?? 'This field must be an integer.',
            'numeric' => $messages['numeric_message'] ?? 'This field must be numeric.',
            'unique' => $messages['unique_message'] ?? 'This value has already been taken.',
            'exists' => $messages['exists_message'] ?? 'The selected value is invalid.',
            'date_format' => $messages['date_format_message'] ?? 'The date format is invalid.',
            'in' => $messages['in_message'] ?? 'The selected value is invalid.',

            // Max length messages
            'passengers.*.email.email' => ($messages['enter_valid_email_address'] ?? 'Please enter a valid email address.'),
            'passengers.*.email.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ':max',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        $request->guest = array_filter($request->guest, function ($guest) {
            return !empty($guest['name']); // keep only if 'name' exists and not empty
        });

        $guests = $request->guest;

        $query = HotelInvoice::with('user', 'creator')->where('id', $id);
        if(Auth::user()->user_type == 'user'){
            $query->where('user_id', $user->business_id);
        }
        $mailData = $query->first();

        if(empty($mailData)){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }
        
        $viewData = view('common.hotel-invoice.sendMailContent', get_defined_vars())->render();

        return [
            'is_success' => 1,
            'icon' => 'success',
            'message' => getCurrentTranslation()['mail_content_updated'] ?? 'mail_content_updated',
            'mail_content' => $viewData
        ];
        //dd($mailData);
        //dd($passengers);
    }



    public function mailSend(Request $request, $id, PdfService $pdfService)
    {
        $user = Auth::user();
        
        $messages = getCurrentTranslation();
        $dateFormat = 'Y-m-d';
        $dateTimeFormat = 'Y-m-d H:i';

        // Base rules for guest array and id
        $rules = [
            'guest' => 'nullable|array',
            'guest.*.id' => 'nullable',
            'guest.*.email' => 'nullable|email|max:100',
        ];

        $validator = Validator::make($request->all(), $rules, [
            'required' => $messages['required_message'] ?? 'This field is required.',
            'array' => $messages['array_message'] ?? 'This field must be an array.',
            'string' => $messages['string_message'] ?? 'This field must be a string.',
            'integer' => $messages['integer_message'] ?? 'This field must be an integer.',
            'numeric' => $messages['numeric_message'] ?? 'This field must be numeric.',
            'unique' => $messages['unique_message'] ?? 'This value has already been taken.',
            'exists' => $messages['exists_message'] ?? 'The selected value is invalid.',
            'date_format' => $messages['date_format_message'] ?? 'The date format is invalid.',
            'in' => $messages['in_message'] ?? 'The selected value is invalid.',

            // Max length messages
            'guest.*.email.email' => ($messages['enter_valid_email_address'] ?? 'Please enter a valid email address.'),
            'guest.*.email.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . ':max',
        ]);

        // Custom rule: At least one guest with both id and email
        $validator->after(function ($validator) use ($request, $messages) {
            $hasValidGuest = false;

            if (isset($request->guest) && is_array($request->guest)) {
                foreach ($request->guest as $guest) {
                    if (!empty($guest['name']) && !empty($guest['email'])) {
                        $hasValidGuest = true;
                        break;
                    }
                }
            }

            if (!$hasValidGuest) {
                // Attach error under first guest's email field
                $validator->errors()->add(
                    'guest.0.email',
                    $messages['at_least_one_guest_and_mail_required'] ?? 'at_least_one_guest_and_mail_required'
                );
            }
        });


        if ($validator->fails()) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        $request->guest = array_filter($request->guest, function ($guest) {
            return !empty($guest['name']); // keep only if 'name' exists and not empty
        });
        
        $guests = $request->guest;

        $query = HotelInvoice::with('user', 'creator')->where('id', $id);
        if(Auth::user()->user_type == 'user'){
            $query->where('user_id', $user->business_id);
        }
        $mailData = $query->first();

        if(empty($mailData)){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }

        $existingGuestInfo = is_array($mailData->guestInfo ?? null) ? $mailData->guestInfo : [];

        $guestInfo = [];
        foreach ($guests as $key => $item) {
            $guestInfo[$key]['name'] = $item['name'] ?? ($existingGuestInfo['name'] ?? null);
            $guestInfo[$key]['email'] = $item['email'] ?? ($existingGuestInfo['email'] ?? null);
            $guestInfo[$key]['phone'] = $item['phone'] ?? ($existingGuestInfo['phone'] ?? null);
            $guestInfo[$key]['passport_number'] = $item['passport_number'] ?? ($existingGuestInfo['passport_number'] ?? null);
        }

        $mailData->guestInfo = $guestInfo;
        $mailData->save();

        $query = HotelInvoice::with('user', 'creator')->where('id', $id);
        if(Auth::user()->user_type == 'user'){
            $query->where('user_id', $user->business_id);
        }
        $mailData = $query->first();
        $editData = $mailData;
        
        $guests = $guests;
        $attachGuests = 1; 
        foreach ($guests as $key => $guestItem) {
            $invoice_guests = (isset($request->send_individually)) && $request->send_individually == 1 ? [$guestItem] : $guests;

            $guests = (isset($request->send_individually)) && $request->send_individually == 1 ? [$guestItem] : $guests;

            $mailContent = $request->mail_content;
            $getGuests = getGuestDataForMail($guests);
            $mailContent = str_replace('{guest_automatic_name_here}', $guestItem['name'] ?? 'N/A', $mailContent);
            $mailContent = str_replace('{guest_automatic_data_here}', $getGuests, $mailContent);


            $attachments = []; // Store file paths for this guest

            // ---------------- Generate Invoice PDF ----------------
            if (isset($request->document_type_invoice) && $request->document_type_invoice == 1) {
                $query = HotelInvoice::with('user', 'creator')->where('id', $id);
                if(Auth::user()->user_type == 'user'){
                    $query->where('user_id', $user->business_id);
                }
                $editData = $query->first();
                $blade = 'common.hotel-invoice.includes.invoice';
                $html = view($blade, compact('editData', 'invoice_guests'))->render();

                // Generate safe filename
                $rawFilename = 'Invoice-' . $editData->invoice_id . '.pdf';
                $filename = preg_replace('/[\/\\\\?%*:|"<>]/', '_', $rawFilename);

                // Ensure temp_pdfs directory exists
                if (!file_exists(public_path('temp_pdfs'))) {
                    mkdir(public_path('temp_pdfs'), 0777, true);
                }

                $filePath = public_path('temp_pdfs/' . $filename);

                $pdfService->generatePdf($editData, $html, $filePath, 'F', 'hotel-invoice');
                $attachments[] = $filePath;
            }


            // ---------------- Send Email ----------------
            $mailPayload = [
                'guests'  => $invoice_guests,
                'mailData'    => $mailData,
                'mailContent' => $mailContent,
            ];

            $filesToAttach = !empty($attachments) ? $attachments : [];
            
            try {
                if (isset($guestItem['email']) && !empty($guestItem['email'])) {
                    $mail = Mail::to($guestItem['email'], $guestItem['name'] ?? 'N/A');

                    // ✅ Add CC if provided
                    if ($request->filled('cc_emails')) {
                        $ccEmails = array_filter($request->cc_emails); // remove empty values
                        if (!empty($ccEmails)) {
                            $mail->cc($ccEmails);
                        }
                    }

                    // ✅ Add BCC if provided
                    if ($request->filled('bcc_emails')) {
                        $bccEmails = array_filter($request->bcc_emails);
                        if (!empty($bccEmails)) {
                            $mail->bcc($bccEmails);
                        }
                    }

                    // ✅ Finally send the mail
                    $mail->send(new HotelInvoiceMail(
                        $mailPayload,
                        $filesToAttach
                    ));

                    // Update cc & bcc emails after mail send
                    $companyData = UserCompany::where('user_id', Auth::user()->business_id)->first();
                    $companyData->cc_emails = $request->cc_emails ?? [];
                    $companyData->bcc_emails = $request->bcc_emails ?? [];
                    $companyData->save();
                }
            } catch (\Throwable $e) {
                \Log::error('Failed to send hotel invoice confirmation email', [
                    'error'     => $e->getMessage(),
                    'guest' => $guestItem['email'] ?? null,
                ]);
            }


            // Delete temp files
            try {
                if (!empty($attachments)) {
                    foreach ($attachments as $filePath) {
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Log::error('Failed to delete temp files', [
                    'error' => $e->getMessage(),
                    'files' => $attachments ?? [],
                ]);
            }

        }


        return [
            'is_success' => 1,
            'icon' => 'success',
            'message' => getCurrentTranslation()['mail_sent_successfully'] ?? 'mail_sent_successfully',
            'mail_content' => $mailContent
        ];

       
    }
}