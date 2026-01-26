<?php

namespace App\Http\Controllers\Admin;


use Auth;
use File;
use Image;
use Session;
use Carbon\Carbon;

use App\Models\User;
use App\Models\Airline;

use App\Models\Ticket;
use App\Models\TicketFlight;
use App\Models\TicketPassenger;
use App\Models\TicketPassengerFlight;
use App\Models\TicketFareSummary;

use App\Models\Payment;
use App\Models\PaymentDocument;
use App\Models\Salary;
use App\Models\Expense;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;

class ReportController extends Controller
{
    public function grossProfitLossReport(Request $request){
        if (!hasPermission('admin.grossProfitLossReport')) {
            abort(403, 'Unauthorized action.');
        }
        $user = Auth::user();

        $profitLossQuery = Payment::with('ticket', 'paymentDocuments', 'introductionSource', 'country', 'issuedBy', 'airline', 'transferTo', 'paymentMethod', 'issuedCardType', 'cardOwner');
            if (!empty($request->search)) {
                $search = $request->search;

                $profitLossQuery->where(function ($q) use ($search) {
                    // Search in main table columns
                    $q->where('payment_invoice_id', 'like', "%{$search}%")
                        ->orWhere('client_name', 'like', "%{$search}%")
                        ->orWhere('client_phone', 'like', "%{$search}%")
                        ->orWhere('client_email', 'like', "%{$search}%")
                        ->orWhere('trip_type', 'like', "%{$search}%")
                        ->orWhere('departure', 'like', "%{$search}%")
                        ->orWhere('destination', 'like', "%{$search}%")
                        ->orWhere('flight_route', 'like', "%{$search}%")
                        ->orWhere('seat_confirmation', 'like', "%{$search}%")
                        ->orWhere('mobility_assistance', 'like', "%{$search}%")
                        ->orWhere('transit_visa_application', 'like', "%{$search}%")
                        ->orWhere('halal_meal_request', 'like', "%{$search}%")
                        ->orWhere('transit_hotel', 'like', "%{$search}%")
                        ->orWhere('card_digit', 'like', "%{$search}%")
                        ->orWhere('payment_status', 'like', "%{$search}%");

                    // Search in related ticket invoice_id
                    $q->orWhereHas('ticket', function ($q2) use ($search) {
                        $q2->where('invoice_id', 'like', "%{$search}%");
                    });

                    // Search in related airline name
                    $q->orWhereHas('airline', function ($q3) use ($search) {
                        $q3->where('name', 'like', "%{$search}%");
                    });
                });
            }

            if (!empty($request->introduction_source_id) && $request->introduction_source_id != 0) {
                $profitLossQuery->where('introduction_source_id', $request->introduction_source_id);
            }

            if (!empty($request->customer_country_id) && $request->customer_country_id != 0) {
                $profitLossQuery->where('customer_country_id', $request->customer_country_id);
            }

            if (!empty($request->issued_supplier_ids) && $request->issued_supplier_ids != 0) {
                $ids = (array) $request->issued_supplier_ids;

                $profitLossQuery->where(function ($q) use ($ids) {
                    foreach ($ids as $id) {
                        $q->orWhereJsonContains('issued_supplier_ids', $id);
                    }
                });
            }

            if (!empty($request->issued_by_id) && $request->issued_by_id != 0) {
                $profitLossQuery->where('issued_by_id', $request->issued_by_id);
            }

            if (!empty($request->trip_type) && $request->trip_type != 0) {
                $profitLossQuery->where('trip_type', $request->trip_type);
            }

            if (!empty($request->departure) && $request->departure != 0) {
                $profitLossQuery->where('departure', $request->departure);
            }

            if (!empty($request->destination) && $request->destination != 0) {
                $profitLossQuery->where('destination', $request->destination);
            }

            if (!empty($request->airline_id) && $request->airline_id != 0) {
                $profitLossQuery->where('airline_id', $request->airline_id);
            }

            if (!empty($request->flight_date_range) && $request->flight_date_range != 0) {
                $flightDateRange = $request->flight_date_range;

                // Split the string into start and end dates
                [$start, $end] = explode('-', $flightDateRange);

                // Convert to Carbon instances
                $startDate = Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
                $endDate = Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();

                // Group the date conditions inside a where() closure
                $profitLossQuery->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('departure_date_time', [$startDate, $endDate])
                        ->orWhereBetween('return_date_time', [$startDate, $endDate]);
                });
            } 
            // else {
            //     // Default: last 7 days
            //     $startDate = Carbon::now()->subDays(6)->startOfDay();
            //     $endDate = Carbon::now()->endOfDay();
            // }

            

            if (!empty(request()->invoice_date_range) && request()->invoice_date_range != 0) {
                $invoiceDateRange = request()->invoice_date_range;
                // Split the string into start and end dates
                [$start, $end] = explode('-', $invoiceDateRange);

                // Convert to Carbon instances (optional but safer)
                $startDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
                $endDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();

                $profitLossQuery->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('invoice_date', [$startDate, $endDate]);
                });
            }

            if (!empty(request()->transfer_to) && request()->transfer_to != 0) {
                $profitLossQuery->where('transfer_to_id', request()->transfer_to);
            }

            if (!empty(request()->payment_method) && request()->payment_method != 0) {
                $profitLossQuery->where('payment_method_id', request()->payment_method);
            }

            if (!empty(request()->issued_card_type) && request()->issued_card_type != 0) {
                $profitLossQuery->where('issued_card_type_id', request()->issued_card_type);
            }

            if (!empty(request()->card_owner) && request()->card_owner != 0) {
                $profitLossQuery->where('card_owner_id', request()->card_owner);
            }

            if (!empty(request()->payment_status) && request()->payment_status != 0) {
                $profitLossQuery->where('payment_status', request()->payment_status);
            }

            if (!empty(request()->payment_date_range) && request()->payment_date_range != 0) {
                    $paymentDateRange = request()->payment_date_range;

                    // Split the string into start and end dates
                    [$start, $end] = explode('-', $paymentDateRange);

                    // Convert to Carbon instances
                    $startDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
                    $endDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();

                    $profitLossQuery->whereNotNull('paymentData')
                        ->whereRaw("
                            EXISTS (
                                SELECT 1
                                FROM JSON_TABLE(
                                    paymentData,
                                    '$[*]' COLUMNS (
                                        pay_date DATE PATH '$.date'
                                    )
                                ) AS pd
                                WHERE pd.pay_date BETWEEN ? AND ?
                            )
                        ", [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
                }

            if (!empty(request()->next_payment_date_range) && request()->next_payment_date_range != 0) {
                $invoiceDateRange = request()->next_payment_date_range;
                // Split the string into start and end dates
                [$start, $end] = explode('-', $invoiceDateRange);

                // Convert to Carbon instances (optional but safer)
                $startDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
                $endDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();

                $profitLossQuery->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('next_payment_deadline', [$startDate, $endDate]);
                });
            }
            

            $profitLossData = $profitLossQuery->get();
        
        return view('admin.report.grossProfitLoss', get_defined_vars());
    }

    public function netProfitLossReport(Request $request){
        if (!hasPermission('admin.netProfitLossReport')) {
            abort(403, 'Unauthorized action.');
        }
        $user = Auth::user();

        // --- PAYMENT DATA QUERY (Gross Profit) ---
        $profitLossQuery = Payment::with('ticket', 'paymentDocuments', 'introductionSource', 'country', 'issuedBy', 'airline', 'transferTo', 'paymentMethod', 'issuedCardType', 'cardOwner');
        
        if (!empty($request->date_range) && $request->date_range != 0) {
            $dateRange = $request->date_range;
            [$start, $end] = explode('-', $dateRange);
            $startDate = Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();

            $profitLossQuery->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('invoice_date', [$startDate, $endDate]);
            });
        }

        $profitLossData = $profitLossQuery->get();

        // --- SALARY DATA QUERY (Separate query, not joining) ---
        $salaryQuery = Salary::with('employee');
        
        if (!empty($request->date_range) && $request->date_range != 0) {
            $dateRange = $request->date_range;
            [$start, $end] = explode('-', $dateRange);
            $startDate = Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();

            $salaryQuery->where(function ($q) use ($startDate, $endDate) {
                // Filter by payment_date if available
                $q->where(function($q1) use ($startDate, $endDate) {
                    $q1->whereNotNull('payment_date')
                       ->whereBetween('payment_date', [$startDate, $endDate]);
                })
                // Or filter by year and month for salaries without payment_date
                ->orWhere(function($q2) use ($startDate, $endDate) {
                    $q2->whereNull('payment_date')
                       ->where(function($q3) use ($startDate, $endDate) {
                           // Get all year-month combinations within the date range
                           $current = $startDate->copy();
                           $yearMonths = [];
                           while ($current <= $endDate) {
                               $yearMonths[] = ['year' => $current->year, 'month' => $current->month];
                               $current->addMonth();
                           }
                           
                           $q3->where(function($q4) use ($yearMonths) {
                               foreach ($yearMonths as $ym) {
                                   $q4->orWhere(function($q5) use ($ym) {
                                       $q5->where('year', $ym['year'])->where('month', $ym['month']);
                                   });
                               }
                           });
                       });
                });
            });
        }

        $salaryData = $salaryQuery->get();

        // --- EXPENSE DATA QUERY (Separate query, not joining) ---
        $expenseQuery = Expense::with('category', 'forUser');
        
        if (!empty($request->date_range) && $request->date_range != 0) {
            $dateRange = $request->date_range;
            [$start, $end] = explode('-', $dateRange);
            $startDate = Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();

            $expenseQuery->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('expense_date', [$startDate, $endDate]);
            });
        }

        $expenseData = $expenseQuery->get();
        
        return view('admin.report.netProfitLoss', get_defined_vars());
    }

    /**
     * Export Gross Profit Loss Report as PDF
     */
    public function grossProfitLossReportExportPdf(Request $request, \App\Services\PdfService $pdfService)
    {
        if (!hasPermission('admin.grossProfitLossReport')) {
            abort(403, 'Unauthorized action.');
        }
        $user = Auth::user();

        // Reuse the same logic from grossProfitLossReport() method
        $profitLossQuery = Payment::with('ticket', 'paymentDocuments', 'introductionSource', 'country', 'issuedBy', 'airline', 'transferTo', 'paymentMethod', 'issuedCardType', 'cardOwner');
        
        // Apply all filters (same as report method)
        if (!empty($request->search)) {
            $search = $request->search;
            $profitLossQuery->where(function ($q) use ($search) {
                $q->where('payment_invoice_id', 'like', "%{$search}%")
                    ->orWhere('client_name', 'like', "%{$search}%")
                    ->orWhere('client_phone', 'like', "%{$search}%")
                    ->orWhere('client_email', 'like', "%{$search}%")
                    ->orWhere('trip_type', 'like', "%{$search}%")
                    ->orWhere('departure', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%")
                    ->orWhere('flight_route', 'like', "%{$search}%")
                    ->orWhere('payment_status', 'like', "%{$search}%")
                    ->orWhereHas('ticket', function ($q2) use ($search) {
                        $q2->where('invoice_id', 'like', "%{$search}%");
                    })
                    ->orWhereHas('airline', function ($q3) use ($search) {
                        $q3->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($request->introduction_source_id) && $request->introduction_source_id != 0) {
            $profitLossQuery->where('introduction_source_id', $request->introduction_source_id);
        }
        if (!empty($request->customer_country_id) && $request->customer_country_id != 0) {
            $profitLossQuery->where('customer_country_id', $request->customer_country_id);
        }
        if (!empty($request->issued_by_id) && $request->issued_by_id != 0) {
            $profitLossQuery->where('issued_by_id', $request->issued_by_id);
        }
        if (!empty($request->trip_type) && $request->trip_type != 0) {
            $profitLossQuery->where('trip_type', $request->trip_type);
        }
        if (!empty($request->airline_id) && $request->airline_id != 0) {
            $profitLossQuery->where('airline_id', $request->airline_id);
        }
        if (!empty($request->payment_status) && $request->payment_status != 0) {
            $profitLossQuery->where('payment_status', $request->payment_status);
        }

        if (!empty($request->invoice_date_range) && $request->invoice_date_range != 0) {
            $invoiceDateRange = $request->invoice_date_range;
            [$start, $end] = explode('-', $invoiceDateRange);
            $startDate = Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();
            $profitLossQuery->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('invoice_date', [$startDate, $endDate]);
            });
        }

        $profitLossData = $profitLossQuery->get();
        
        // Calculate paid and due amounts
        $profitLossData = $profitLossData->map(function ($item) {
            if (is_string($item->paymentData)) {
                $payments = json_decode($item->paymentData, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $payments = [];
                }
            } elseif (is_array($item->paymentData)) {
                $payments = $item->paymentData;
            } else {
                $payments = [];
            }

            $totalPaid = is_array($payments) ? collect($payments)->sum('paid_amount') : 0;
            $dueAmount = $item->total_selling_price - $totalPaid;

            $item->total_paid = $totalPaid;
            $item->due_amount = $dueAmount;

            return $item;
        });

        // Calculate summary
        $total_purchase_amount = $profitLossData->sum('total_purchase_price');
        $total_selling_amount = $profitLossData->sum('total_selling_price');
        $total_profit = $total_selling_amount - $total_purchase_amount;
        $total_cancellation_fee = $profitLossData->where('is_refund', 1)->sum('cancellation_fee');
        $total_profit_after_refund = $total_profit - $total_cancellation_fee;
        $total_paid_amount = $profitLossData->sum('total_paid');
        $total_due_amount = $profitLossData->sum('due_amount');
        
        // Payment Status Summary
        $paymentStatusSummary = $profitLossData
            ->groupBy(fn($item) => $item->payment_status ?: 'Unknown')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_purchase_amount' => $group->sum('total_purchase_price'),
                    'total_selling_amount' => $group->sum('total_selling_price'),
                    'total_cancellation_fee' => $group->sum('cancellation_fee'),
                    'total_profit' => $group->sum('total_selling_price') - $group->sum('total_purchase_price'),
                    'total_paid_amount' => $group->sum('total_paid'),
                    'total_due_amount' => $group->sum('due_amount'),
                ];
            });
        
        $getCurrentTranslation = getCurrentTranslation();
        $invoiceDateRangeStr = $request->invoice_date_range ?? 'All Dates';
        
        $html = view('admin.report.gross-profit-loss-pdf', compact('profitLossData', 'invoiceDateRangeStr', 'getCurrentTranslation', 'total_purchase_amount', 'total_selling_amount', 'total_profit', 'total_cancellation_fee', 'total_profit_after_refund', 'total_paid_amount', 'total_due_amount', 'paymentStatusSummary'))->render();
        
        $filename = 'Gross_Profit_Loss_Report_' . date('Y-m-d') . '.pdf';
        
        return $pdfService->generatePdf(null, $html, $filename, 'I');
    }

    /**
     * Export Net Profit Loss Report as PDF
     */
    public function netProfitLossReportExportPdf(Request $request, \App\Services\PdfService $pdfService)
    {
        if (!hasPermission('admin.netProfitLossReport')) {
            abort(403, 'Unauthorized action.');
        }
        $user = Auth::user();

        // Reuse the same logic from netProfitLossReport() method
        $profitLossQuery = Payment::with('ticket', 'paymentDocuments', 'introductionSource', 'country', 'issuedBy', 'airline', 'transferTo', 'paymentMethod', 'issuedCardType', 'cardOwner');
        
        if (!empty($request->date_range) && $request->date_range != 0) {
            $dateRange = $request->date_range;
            [$start, $end] = explode('-', $dateRange);
            $startDate = Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();
            $profitLossQuery->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('invoice_date', [$startDate, $endDate]);
            });
        }

        $profitLossData = $profitLossQuery->get();

        $salaryQuery = Salary::with('employee');
        if (!empty($request->date_range) && $request->date_range != 0) {
            $dateRange = $request->date_range;
            [$start, $end] = explode('-', $dateRange);
            $startDate = Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();

            $salaryQuery->where(function ($q) use ($startDate, $endDate) {
                // Filter by payment_date if available
                $q->where(function($q1) use ($startDate, $endDate) {
                    $q1->whereNotNull('payment_date')
                       ->whereBetween('payment_date', [$startDate, $endDate]);
                })
                // Or filter by year and month for salaries without payment_date
                ->orWhere(function($q2) use ($startDate, $endDate) {
                    $q2->whereNull('payment_date')
                       ->where(function($q3) use ($startDate, $endDate) {
                           // Get all year-month combinations within the date range
                           $current = $startDate->copy();
                           $yearMonths = [];
                           while ($current->lte($endDate)) {
                               $yearMonths[] = [
                                   'year' => $current->year,
                                   'month' => $current->month
                               ];
                               $current->addMonth();
                           }
                           
                           if (!empty($yearMonths)) {
                               $q3->where(function($q4) use ($yearMonths) {
                                   foreach ($yearMonths as $ym) {
                                       $q4->orWhere(function($q5) use ($ym) {
                                           $q5->where('year', $ym['year'])
                                              ->where('month', $ym['month']);
                                       });
                                   }
                               });
                           }
                       });
                });
            });
        }
        $salaryData = $salaryQuery->get();

        $expenseQuery = Expense::with('category', 'forUser');
        if (!empty($request->date_range) && $request->date_range != 0) {
            $dateRange = $request->date_range;
            [$start, $end] = explode('-', $dateRange);
            $startDate = Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();
            $expenseQuery->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('expense_date', [$startDate, $endDate]);
            });
        }
        $expenseData = $expenseQuery->get();
        
        // Calculate paid and due amounts for payments
        $profitLossData = $profitLossData->map(function ($item) {
            if (is_string($item->paymentData)) {
                $payments = json_decode($item->paymentData, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $payments = [];
                }
            } elseif (is_array($item->paymentData)) {
                $payments = $item->paymentData;
            } else {
                $payments = [];
            }

            $totalPaid = is_array($payments) ? collect($payments)->sum('paid_amount') : 0;
            $dueAmount = $item->total_selling_price - $totalPaid;

            $item->total_paid = $totalPaid;
            $item->due_amount = $dueAmount;

            return $item;
        });

        // Gross profit calculations
        $total_purchase_amount = $profitLossData->sum('total_purchase_price');
        $total_selling_amount = $profitLossData->sum('total_selling_price');
        $total_profit = $total_selling_amount - $total_purchase_amount;
        $total_cancellation_fee = $profitLossData->where('is_refund', 1)->sum('cancellation_fee');
        $total_profit_after_refund = $total_profit - $total_cancellation_fee;
        $total_paid_amount = $profitLossData->sum('total_paid');
        $total_due_amount = $profitLossData->sum('due_amount');

        // Salary calculations
        $total_salary_amount = $salaryData->sum('net_salary');
        $total_salary_count = $salaryData->count();
        $total_partial_salary = $salaryData->where('payment_status', 'Partial')->sum('net_salary');
        $total_paid_salary = $salaryData->where('payment_status', 'Paid')->sum('net_salary');
        $total_unpaid_salary = $salaryData->where('payment_status', 'Unpaid')->sum('net_salary');

        // Expense calculations
        $total_expense_amount = $expenseData->sum('amount');
        $total_expense_count = $expenseData->count();
        $total_paid_expense = $expenseData->where('payment_status', 'Paid')->sum('amount');
        $total_unpaid_expense = $expenseData->where('payment_status', 'Unpaid')->sum('amount');

        // Net profit/loss calculation
        $net_profit_loss = $total_profit_after_refund - $total_salary_amount - $total_expense_amount;
        
        $getCurrentTranslation = getCurrentTranslation();
        $dateRangeStr = $request->date_range ?? 'All Dates';
        
        $html = view('admin.report.net-profit-loss-pdf', compact('profitLossData', 'salaryData', 'expenseData', 'dateRangeStr', 'getCurrentTranslation', 'total_purchase_amount', 'total_selling_amount', 'total_profit', 'total_cancellation_fee', 'total_profit_after_refund', 'total_paid_amount', 'total_due_amount', 'total_salary_amount', 'total_salary_count', 'total_partial_salary', 'total_paid_salary', 'total_unpaid_salary', 'total_expense_amount', 'total_expense_count', 'total_paid_expense', 'total_unpaid_expense', 'net_profit_loss'))->render();
        
        $filename = 'Net_Profit_Loss_Report_' . date('Y-m-d') . '.pdf';
        
        return $pdfService->generatePdf(null, $html, $filename, 'I');
    }

}