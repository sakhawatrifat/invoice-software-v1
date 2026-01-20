<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;


// Common Controllers
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TicketController as CommonTicketController;
use App\Http\Controllers\TravelSearchController as CommonTravelSearchController;
use App\Http\Controllers\TicketReminderController as CommonTicketReminderController;
use App\Http\Controllers\HotelInvoiceController as CommonHotelInvoiceController;
use App\Http\Controllers\PaymentController as CommonPaymentController;
use App\Http\Controllers\StaffController as CommonStaffController;

use App\Http\Controllers\IntroductionSourceController as CommonIntroductionSourceController;
use App\Http\Controllers\IssuedSupplierController as CommonIssuedSupplierController;
use App\Http\Controllers\IssuedByController as CommonIssuedByController;
use App\Http\Controllers\TransferToController as CommonTransferToController;
use App\Http\Controllers\PaymentMethodController as CommonPaymentMethodController;
use App\Http\Controllers\IssuedCardTypeController as CommonIssuedCardTypeController;
use App\Http\Controllers\CardOwnerController as CommonCardOwnerController;

// Admin Controllers
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\AirlineController as AdminAirlineController;
use App\Http\Controllers\Admin\HomepageController as AdminHomepageController;
use App\Http\Controllers\Admin\LanguageController as AdminLanguageController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\DepartmentController as AdminDepartmentController;
use App\Http\Controllers\Admin\DesignationController as AdminDesignationController;
use App\Http\Controllers\AttendanceController;

// Admin Controllers
use App\Http\Controllers\User\DashboardController as UserDashboardController;




Route::get('/fresh-migration', function () {
    $dbPassword = env('DB_PASSWORD');

    if (empty($dbPassword)) {
        Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true, // Important for production or non-interactive execution
        ]);

        return 'Migration and seeding done!';
    } else {
        return 'Migration skipped because DB_PASSWORD is set. (This site is lived)';
    }
});

use Database\Seeders\TranslationSeeder;
Route::get('/seed', function () {
    Artisan::call('db:seed', [
        '--class' => TranslationSeeder::class,
        '--force' => true,
    ]);

    return 'Seeding done!';
});

Route::get('/clear-cache', function() {
    Artisan::call('route:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('config:clear');
    Artisan::call('view:clear');

    $route = url('/login');    
    return '
        <div style="font-family: Arial; padding:20px; text-align:center">
            <h3>Cache cleared successfully!</h3>
            <a href="' . $route . '" 
               style="display:inline-block; margin-top:10px; padding:8px 15px; background:#3490dc; color:white; text-decoration:none; border-radius:4px;">
                ← Back
            </a>
        </div>
    ';
})->name('clear-cache');

Route::get('/storage-link', function () {
    Artisan::call('storage:link');
    return 'Storage link created successfully.';
});

Route::get('/seed-country', function () {
    seedCountries();
    return 'Country seed done.';
});

// Home Routes
Route::controller(HomeController::class)->group(function () {
    Route::get('/', 'home')->name('home');

});

// All User Authentication
Route::controller(UserAuthController::class)->group(function () {
    Route::get('login', 'loginForm')->name('login');
    Route::post('login', 'login')->name('login.confirm');
    Route::get('register', 'registerForm')->name('register');
    Route::post('register', 'register')->name('register.confirm');
    
    Route::get('account-verification', 'accountVerifyForm')->name('account.verify');
    Route::get('account-verification/resend', 'accountVerifyCodeResend')->name('account.verify.resend');
    Route::get('account-verify', 'accountVerify')->name('account.verify.confirm');

    Route::get('password/forget', 'forgotPasswordForm')->name('password.forget.form');
    Route::post('password/forget', 'forgotPassword')->name('password.forget');
    Route::get('password/reset/{token}', 'resetPasswordForm')->name('password.reset.form');
    Route::post('password/reset', 'resetPassword')->name('password.reset');
    Route::post('logout', 'logout')->name('logout');
    Route::get('logout', 'logout')->name('logout');
});


Route::group(['middleware' => ['auth', 'activeStatus', 'verificationStatus']], function () {

    // Admin Routes
    Route::group(['middleware' => ['admin']], function () {
        Route::prefix('admin')->as('admin.')->group(function () {
            // Admin Dashboard Routes
            Route::controller(AdminDashboardController::class)->group(function () {
                Route::get('/dashboard', 'dashboard')->name('dashboard');
                Route::get('/permission', 'permission')->name('permission');
            });

            //User Routes
            Route::controller(AdminUserController::class)->group(function () {
                Route::get('/user-list', 'index')->name('user.index');
                Route::get('/user-datatble', 'datatable')->name('user.datatable');
                Route::get('/user/create', 'create')->name('user.create');
                Route::post('/user/store', 'store')->name('user.store');
                Route::get('/user/status/{id}/{status}', 'status')->name('user.status');
                Route::get('/user/show/{id}', 'show')->name('user.show');
                Route::get('/user/edit/{id}', 'edit')->name('user.edit');
                Route::put('/user/update/{id}', 'update')->name('user.update');
                Route::delete('/user/delete/{id}', 'destroy')->name('user.destroy');
            });

            //Airline Routes
            Route::controller(AdminAirlineController::class)->group(function () {
                Route::get('/airline-list', 'index')->name('airline.index');
                Route::get('/airline-datatble', 'datatable')->name('airline.datatable');
                Route::get('/airline/create', 'create')->name('airline.create');
                Route::get('/airline/create/ajax', 'createAjax')->name('airline.create.ajax');
                Route::post('/airline/store', 'store')->name('airline.store');
                Route::get('/airline/status/{id}/{status}', 'status')->name('airline.status');
                Route::get('/airline/edit/{id}', 'edit')->name('airline.edit');
                Route::put('/airline/update/{id}', 'update')->name('airline.update');
                Route::delete('/airline/delete/{id}', 'destroy')->name('airline.destroy');
            });

            //Department Routes
            Route::controller(AdminDepartmentController::class)->group(function () {
                Route::get('/department-list', 'index')->name('department.index');
                Route::get('/department-datatble', 'datatable')->name('department.datatable');
                Route::get('/department/create', 'create')->name('department.create');
                Route::get('/department/create/ajax', 'createAjax')->name('department.create.ajax');
                Route::post('/department/store', 'store')->name('department.store');
                Route::get('/department/status/{id}/{status}', 'status')->name('department.status');
                Route::get('/department/edit/{id}', 'edit')->name('department.edit');
                Route::put('/department/update/{id}', 'update')->name('department.update');
                Route::delete('/department/delete/{id}', 'destroy')->name('department.destroy');
            });

            //Designation Routes
            Route::controller(AdminDesignationController::class)->group(function () {
                Route::get('/designation-list', 'index')->name('designation.index');
                Route::get('/designation-datatble', 'datatable')->name('designation.datatable');
                Route::get('/designation/create', 'create')->name('designation.create');
                Route::get('/designation/create/ajax', 'createAjax')->name('designation.create.ajax');
                Route::post('/designation/store', 'store')->name('designation.store');
                Route::get('/designation/status/{id}/{status}', 'status')->name('designation.status');
                Route::get('/designation/edit/{id}', 'edit')->name('designation.edit');
                Route::put('/designation/update/{id}', 'update')->name('designation.update');
                Route::delete('/designation/delete/{id}', 'destroy')->name('designation.destroy');
            });

            //Homepage CRUD Routes
            Route::controller(AdminHomepageController::class)->group(function () {
                Route::get('/homepage-list', 'index')->name('homepage.index');
                Route::get('/homepage-datatble', 'datatable')->name('homepage.datatable');
                Route::get('/homepage/create', 'create')->name('homepage.create');
                Route::post('/homepage/store', 'store')->name('homepage.store');
                Route::get('/homepage/status/{id}/{status}', 'status')->name('homepage.status');
                Route::get('/homepage/edit/{id}', 'edit')->name('homepage.edit');
                Route::put('/homepage/update/{id}', 'update')->name('homepage.update');
                Route::delete('/homepage/delete/{id}', 'destroy')->name('homepage.destroy');
            });

            //Language CRUD Routes
            Route::controller(AdminLanguageController::class)->group(function () {
                Route::get('/language-list', 'index')->name('language.index');
                Route::get('/language-datatble', 'datatable')->name('language.datatable');
                Route::get('/language/create', 'create')->name('language.create');
                Route::post('/language/store', 'store')->name('language.store');
                Route::get('/language/status/{id}/{status}', 'status')->name('language.status');
                Route::get('/language/edit/{id}', 'edit')->name('language.edit');
                Route::put('/language/update/{id}', 'update')->name('language.update');
                Route::delete('/language/delete/{id}', 'destroy')->name('language.destroy');

                Route::get('/language/translate/{id}', 'translateForm')->name('language.translate.form');
                Route::post('/language/new-translate-key/{id}', 'newTranslateKey')->name('language.translate.key');
                Route::put('/language/translate/{id}', 'translateUpdate')->name('language.translate.update');
                Route::get('/language/translate/delete/{lang_key}', 'translateDelete')->name('language.translate.delete');
            });

            //Admin Report Routes
            Route::controller(AdminReportController::class)->group(function () {
                Route::get('/gross-profit-loss-report', 'grossProfitLossReport')->name('grossProfitLossReport');
            });

            //Attendance Report Routes
            Route::controller(AttendanceController::class)->group(function () {
                Route::get('/attendance/report', 'report')->name('attendance.report');
                Route::get('/attendance/employee-details/{employeeId}', 'employeeAttendanceDetails')->name('attendance.employeeDetails');
                Route::get('/attendance/get-details', 'getAttendanceDetails')->name('attendance.getDetails');
            });

            //Salary Routes
            Route::controller(\App\Http\Controllers\Admin\SalaryController::class)->group(function () {
                Route::get('/salary', 'index')->name('salary.index');
                Route::post('/salary/generate', 'generate')->name('salary.generate');
                Route::post('/salary/check-duplicates', 'checkDuplicates')->name('salary.checkDuplicates');
                Route::get('/salary/list', 'list')->name('salary.list');
                Route::put('/salary/{id}', 'update')->name('salary.update');
                Route::get('/salary/{id}/details', 'getDetails')->name('salary.getDetails');
                Route::delete('/salary/{id}', 'destroy')->name('salary.destroy');
            });
        });
    });

    //Staff Routes
    Route::controller(CommonStaffController::class)->group(function () {
        Route::get('/staff-list', 'index')->name('staff.index');
        Route::get('/staff-datatble', 'datatable')->name('staff.datatable');
        Route::get('/staff/create', 'create')->name('staff.create');
        Route::get('/staff/load-permissions', 'loadPermissions')->name('staff.loadPermissions');
        Route::post('/staff/store', 'store')->name('staff.store');
        Route::get('/staff/status/{id}/{status}', 'status')->name('staff.status');
        Route::get('/staff/show/{id}', 'show')->name('staff.show');
        Route::get('/staff/edit/{id}', 'edit')->name('staff.edit');
        Route::put('/staff/update/{id}', 'update')->name('staff.update');
        Route::delete('/staff/delete/{id}', 'destroy')->name('staff.destroy');
    });

    //Attendance Routes
    Route::controller(AttendanceController::class)->group(function () {
        Route::get('/attendance/status', 'getCurrentStatus')->name('attendance.status');
        Route::post('/attendance/check-in', 'checkIn')->name('attendance.checkIn');
        Route::post('/attendance/check-out', 'checkOut')->name('attendance.checkOut');
        Route::post('/attendance/pause', 'pause')->name('attendance.pause');
        Route::post('/attendance/resume', 'resume')->name('attendance.resume');
    });

    //Staff Attendance Routes (for is_staff == 1)
    Route::controller(AttendanceController::class)->group(function () {
        Route::get('/staff/attendance/report', 'staffAttendanceReport')->name('staff.attendance.report');
    });

    Route::controller(\App\Http\Controllers\Admin\SalaryController::class)->group(function () {
        Route::get('/staff/salary/list', 'staffSalaryList')->name('staff.salary.list');
    });
    
    //Common Ticket Routes
    Route::controller(CommonTicketController::class)->group(function () {
        Route::get('/ticket-list', 'index')->name('ticket.index');
        Route::get('/ticket-datatble', 'datatable')->name('ticket.datatable');

        Route::get('/ticket/create', 'create')->name('ticket.create');

        Route::post('/ticket/store', 'store')->name('ticket.store');
        Route::get('/ticket/status/{id}/{status}', 'status')->name('ticket.status');

        Route::get('/ticket/show/{id}', 'show')->name('ticket.show');
        Route::get('/ticket/mail/{id}', 'mail')->name('ticket.mail');
        Route::post('/ticket/mail-content-load/{id}', 'mailContentLoad')->name('ticket.mailContentLoad');
        Route::put('/ticket/mail/{id}', 'mailSend')->name('ticket.mailSend');
        Route::get('/ticket/download-pdf/{id}', 'downloadPdf')->name('ticket.downloadPdf');
        Route::get('/ticket/duplicate/{id}', 'duplicate')->name('ticket.duplicate');

        Route::get('/ticket/edit/{id}', 'edit')->name('ticket.edit');
        Route::put('/ticket/update/{id}', 'update')->name('ticket.update');
        Route::delete('/ticket/delete/{id}', 'destroy')->name('ticket.destroy');
    });

    //Common Travel Search Routes
    Route::controller(CommonTravelSearchController::class)->group(function () {
        Route::get('/ticket/search', 'ticketSearchForm')->name('ticket.search.form');
        Route::get('/airports/search', 'getAirportSuggestions')->name('airports.search');
        Route::get('/airports/common', 'getCommonAirports')->name('airports.common');
        Route::post('/ticket/search/import', 'ticketSearchImport')->name('ticket.search.import');
        Route::post('/ticket/search/process-flight', 'processFlightData')->name('ticket.search.process.flight');

        Route::get('travel/flights/calendar', 'getFlightCalendar')->name('travel.getFlightCalendar');
        Route::get('travel/destinations/popular', 'getPopularDestinations')->name('travel.getPopularDestinations');
        Route::get('travel/hotels/search', 'searchHotels')->name('travel.searchHotels');
        Route::get('travel/hotels/{hotelId}/prices', 'getHotelPrices')->name('travel.getHotelPrices');
        Route::post('travel/booking-url', 'getBookingUrl')->name('travel.getBookingUrl');
        Route::post('travel/booking-link', 'getBookingLink')->name('travel.getBookingLink');
    });

    //Common Hotel Invoice Routes
    Route::controller(CommonHotelInvoiceController::class)->group(function () {
        Route::get('/hotel-invoice-list', 'index')->name('hotel.invoice.index');
        Route::get('/hotel-invoice-datatble', 'datatable')->name('hotel.invoice.datatable');
        Route::get('/hotel-invoice/create', 'create')->name('hotel.invoice.create');
        Route::post('/hotel-invoice/store', 'store')->name('hotel.invoice.store');
        Route::get('/hotel-invoice/status/{id}/{status}', 'status')->name('hotel.invoice.status');

        Route::get('/hotel-invoice/show/{id}', 'show')->name('hotel.invoice.show');
        Route::get('/hotel-invoice/mail/{id}', 'mail')->name('hotel.invoice.mail');
        Route::post('/hotel-invoice/mail-content-load/{id}', 'mailContentLoad')->name('hotel.invoice.mailContentLoad');
        Route::put('/hotel-invoice/mail/{id}', 'mailSend')->name('hotel.invoice.mailSend');
        Route::get('/hotel-invoice/download-pdf/{id}', 'downloadPdf')->name('hotel.invoice.downloadPdf');
        Route::get('/hotel-invoice/duplicate/{id}', 'duplicate')->name('hotel.invoice.duplicate');

        Route::get('/hotel-invoice/edit/{id}', 'edit')->name('hotel.invoice.edit');
        Route::put('/hotel-invoice/update/{id}', 'update')->name('hotel.invoice.update');
        Route::delete('/hotel-invoice/delete/{id}', 'destroy')->name('hotel.invoice.destroy');
    });


    //Common Payment Routes
    Route::controller(CommonPaymentController::class)->group(function () {
        Route::get('/payment-list', 'index')->name('payment.index');
        Route::get('/payment-datatble', 'datatable')->name('payment.datatable');
        Route::get('/todo-list', 'toDoList')->name('payment.toDoList');
        Route::get('/todo-datatble', 'toDoDatatable')->name('payment.toDoDatatable');
        Route::get('/payment/create', 'create')->name('payment.create');
        Route::get('/payment/ticket-search', 'ticketSearch')->name('payment.ticket.search');
        Route::post('/payment/store', 'store')->name('payment.store');
        Route::get('/payment/status/{id}/{status}', 'status')->name('payment.status');

        Route::get('/payment/show/{id}', 'show')->name('payment.show');
        Route::get('/payment/mail/{id}', 'mail')->name('payment.mail');
        Route::post('/payment/mail-content-load/{id}', 'mailContentLoad')->name('payment.mailContentLoad');
        Route::put('/payment/mail/{id}', 'mailSend')->name('payment.mailSend');
        Route::get('/payment/download-pdf/{id}', 'downloadPdf')->name('payment.downloadPdf');
        Route::get('/payment/duplicate/{id}', 'duplicate')->name('payment.duplicate');

        Route::get('/payment/edit/{id}', 'edit')->name('payment.edit');
        Route::put('/payment/update/{id}', 'update')->name('payment.update');
        Route::delete('/payment/delete/{id}', 'destroy')->name('payment.destroy');


        Route::get('/flight-list', 'flightList')->name('flight.list');
        Route::get('/flight-list-datatable', 'flightListDatatable')->name('flight.list.datatable');
    });

    //Common IntroductionSource Routes
    Route::controller(CommonIntroductionSourceController::class)->group(function () {
        Route::get('/introduction-source-list', 'index')->name('introductionSource.index');
        Route::get('/introduction-source-datatble', 'datatable')->name('introductionSource.datatable');
        Route::get('/introduction-source/create', 'create')->name('introductionSource.create');
        Route::get('/introduction-source/create/ajax', 'createAjax')->name('introductionSource.create.ajax');
        Route::post('/introduction-source/store', 'store')->name('introductionSource.store');
        Route::get('/introduction-source/status/{id}/{status}', 'status')->name('introductionSource.status');
        Route::get('/introduction-source/edit/{id}', 'edit')->name('introductionSource.edit');
        Route::put('/introduction-source/update/{id}', 'update')->name('introductionSource.update');
        Route::delete('/introduction-source/delete/{id}', 'destroy')->name('introductionSource.destroy');
    });

    //Common IssuedSupplier Routes
    Route::controller(CommonIssuedSupplierController::class)->group(function () {
        Route::get('/issued-supplier-list', 'index')->name('issuedSupplier.index');
        Route::get('/issued-supplier-datatble', 'datatable')->name('issuedSupplier.datatable');
        Route::get('/issued-supplier/create', 'create')->name('issuedSupplier.create');
        Route::get('/issued-supplier/create/ajax', 'createAjax')->name('issuedSupplier.create.ajax');
        Route::post('/issued-supplier/store', 'store')->name('issuedSupplier.store');
        Route::get('/issued-supplier/status/{id}/{status}', 'status')->name('issuedSupplier.status');
        Route::get('/issued-supplier/edit/{id}', 'edit')->name('issuedSupplier.edit');
        Route::put('/issued-supplier/update/{id}', 'update')->name('issuedSupplier.update');
        Route::delete('/issued-supplier/delete/{id}', 'destroy')->name('issuedSupplier.destroy');
    });

    //Common IssuedBy Routes
    Route::controller(CommonIssuedByController::class)->group(function () {
        Route::get('/issued-by-list', 'index')->name('issuedBy.index');
        Route::get('/issued-by-datatble', 'datatable')->name('issuedBy.datatable');
        Route::get('/issued-by/create', 'create')->name('issuedBy.create');
        Route::get('/issued-by/create/ajax', 'createAjax')->name('issuedBy.create.ajax');
        Route::post('/issued-by/store', 'store')->name('issuedBy.store');
        Route::get('/issued-by/status/{id}/{status}', 'status')->name('issuedBy.status');
        Route::get('/issued-by/edit/{id}', 'edit')->name('issuedBy.edit');
        Route::put('/issued-by/update/{id}', 'update')->name('issuedBy.update');
        Route::delete('/issued-by/delete/{id}', 'destroy')->name('issuedBy.destroy');
    });

    //Common TransferTo Routes
    Route::controller(CommonTransferToController::class)->group(function () {
        Route::get('/transfer-to-list', 'index')->name('transferTo.index');
        Route::get('/transfer-to-datatble', 'datatable')->name('transferTo.datatable');
        Route::get('/transfer-to/create', 'create')->name('transferTo.create');
        Route::get('/transfer-to/create/ajax', 'createAjax')->name('transferTo.create.ajax');
        Route::post('/transfer-to/store', 'store')->name('transferTo.store');
        Route::get('/transfer-to/status/{id}/{status}', 'status')->name('transferTo.status');
        Route::get('/transfer-to/edit/{id}', 'edit')->name('transferTo.edit');
        Route::put('/transfer-to/update/{id}', 'update')->name('transferTo.update');
        Route::delete('/transfer-to/delete/{id}', 'destroy')->name('transferTo.destroy');
    });

    //Common PaymentMethod Routes
    Route::controller(CommonPaymentMethodController::class)->group(function () {
        Route::get('/payment-method-list', 'index')->name('paymentMethod.index');
        Route::get('/payment-method-datatble', 'datatable')->name('paymentMethod.datatable');
        Route::get('/payment-method/create', 'create')->name('paymentMethod.create');
        Route::get('/payment-method/create/ajax', 'createAjax')->name('paymentMethod.create.ajax');
        Route::post('/payment-method/store', 'store')->name('paymentMethod.store');
        Route::get('/payment-method/status/{id}/{status}', 'status')->name('paymentMethod.status');
        Route::get('/payment-method/edit/{id}', 'edit')->name('paymentMethod.edit');
        Route::put('/payment-method/update/{id}', 'update')->name('paymentMethod.update');
        Route::delete('/payment-method/delete/{id}', 'destroy')->name('paymentMethod.destroy');
    });

    //Common IssuedCardType Routes
    Route::controller(CommonIssuedCardTypeController::class)->group(function () {
        Route::get('/issued-card-type-list', 'index')->name('issuedCardType.index');
        Route::get('/issued-card-type-datatble', 'datatable')->name('issuedCardType.datatable');
        Route::get('/issued-card-type/create', 'create')->name('issuedCardType.create');
        Route::get('/issued-card-type/create/ajax', 'createAjax')->name('issuedCardType.create.ajax');
        Route::post('/issued-card-type/store', 'store')->name('issuedCardType.store');
        Route::get('/issued-card-type/status/{id}/{status}', 'status')->name('issuedCardType.status');
        Route::get('/issued-card-type/edit/{id}', 'edit')->name('issuedCardType.edit');
        Route::put('/issued-card-type/update/{id}', 'update')->name('issuedCardType.update');
        Route::delete('/issued-card-type/delete/{id}', 'destroy')->name('issuedCardType.destroy');
    });

    //Common CardOwner Routes
    Route::controller(CommonCardOwnerController::class)->group(function () {
        Route::get('/card-owner-list', 'index')->name('cardOwner.index');
        Route::get('/card-owner-datatble', 'datatable')->name('cardOwner.datatable');
        Route::get('/card-owner/create', 'create')->name('cardOwner.create');
        Route::get('/card-owner/create/ajax', 'createAjax')->name('cardOwner.create.ajax');
        Route::post('/card-owner/store', 'store')->name('cardOwner.store');
        Route::get('/card-owner/status/{id}/{status}', 'status')->name('cardOwner.status');
        Route::get('/card-owner/edit/{id}', 'edit')->name('cardOwner.edit');
        Route::put('/card-owner/update/{id}', 'update')->name('cardOwner.update');
        Route::delete('/card-owner/delete/{id}', 'destroy')->name('cardOwner.destroy');
    });


    //Common Ticket Routes
    Route::controller(CommonTicketReminderController::class)->group(function () {
        Route::get('/ticket-reminder-list', 'index')->name('ticket.reminder.index');
        Route::get('/ticket-reminder-datatble', 'datatable')->name('ticket.reminder.datatable');
        Route::get('/ticket-reminder/status/{passenger_id}/{status}', 'status')->name('ticket.reminder.status');

        Route::get('/ticket-reminder-mail-form', 'reminderMailForm')->name('ticket.reminder.form');
        Route::post('/ticket-reminder-mail-form', 'saveReminderInformation')->name('ticket.reminder.save');

        //Route::get('/reminder-mail', 'sendReminderMail');
    });

    //Common Notification Routes
    Route::controller(NotificationController::class)->group(function () {
        Route::get('/notification-list', 'index')->name('notification.index');
        Route::get('/notification-datatble', 'datatable')->name('notification.datatable');
        Route::get('/notification/read/{id}', 'read')->name('notification.read');
        Route::get('/notification/read-all', 'readAll')->name('notification.readAll');
        Route::delete('/notification/delete/{id}', 'read')->name('notification.destroy');
    });


    // User Routes
    Route::group(['middleware' => ['user']], function () {
        // User Dashboard Routes
        Route::prefix('user')->as('user.')->group(function () {
            Route::controller(UserDashboardController::class)->group(function () {
                Route::get('/dashboard', 'dashboard')->name('dashboard');

            });
        });
    });


    // Home Routes
    Route::controller(HomeController::class)->group(function () {
        Route::get('/my-profile', 'myProfile')->name('myProfile');
        Route::post('/my-profile', 'myProfileUpdate')->name('myProfile.update');
    });
    
});