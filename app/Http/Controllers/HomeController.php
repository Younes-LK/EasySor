<?php

namespace App\Http\Controllers;

use App\Models\Contract; // Ensure Contract model is imported
use App\Models\Maintenance;
use App\Models\MaintenanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    /**
     * Display the home page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $pendingContracts = collect();
        $dueMaintenances = collect();
        $isAdmin = false;
        $isStaff = false;

        if (Auth::check()) {
            $user = Auth::user();
            $isAdmin = $user->isAdmin();
            $isStaff = $user->isStaff();
            $today = Carbon::today();

            // Fetch Pending Contracts
            $contractsQuery = Contract::with(['customer', 'address', 'assignedUser'])
                                      ->where('status', 'active'); // Only show 'active' contracts to be marked done

            if ($isStaff) {
                $contractsQuery->where('assigned_to', $user->id);
            } elseif ($isAdmin) {
                $contractsQuery->where(function ($query) use ($user) {
                    $query->where('assigned_to', $user->id)
                          ->orWhereNull('assigned_to');
                });
            }
            $pendingContracts = $contractsQuery->orderBy('created_at', 'desc')->get();


            // Fetch Due Maintenances
            $maintenancesQuery = Maintenance::with(['customer', 'address', 'user', 'logs'])
                                          ->where('is_active', true)
                                          ->whereRaw('completed_count < duration_in_months');

            if ($isStaff) {
                $maintenancesQuery->where('assigned_to', $user->id);
            } elseif ($isAdmin) {
                $maintenancesQuery->where(function ($query) use ($user) {
                    $query->where('assigned_to', $user->id)
                          ->orWhereNull('assigned_to');
                });
            }

            $allActiveFilteredMaintenances = $maintenancesQuery->get();

            foreach ($allActiveFilteredMaintenances as $maintenance) {
                $startDate = Carbon::parse($maintenance->start_date);
                $isDue = false;

                if ($maintenance->last_completed_at === null) {
                    if ($startDate->lessThanOrEqualTo($today)) {
                        $isDue = true;
                    }
                } else {
                    $nextDueDate = Carbon::parse($maintenance->last_completed_at)->addMonths(1)->startOfDay();
                    if ($nextDueDate->lessThanOrEqualTo($today)) {
                        $isDue = true;
                    }
                }

                if ($isDue) {
                    if ($maintenance->last_completed_at === null) {
                        $maintenance->next_due_display = Jalalian::fromCarbon($startDate)->format('Y/m/d');
                    } else {
                        $maintenance->next_due_display = Jalalian::fromCarbon(Carbon::parse($maintenance->last_completed_at)->addMonths(1))->format('Y/m/d');
                    }
                    $dueMaintenances->push($maintenance);
                }
            }
            $dueMaintenances = $dueMaintenances->sortBy(function ($m) {
                return $m->last_completed_at ? Carbon::parse($m->last_completed_at)->addMonths(1) : Carbon::parse($m->start_date);
            });

        }

        return view('home', compact('pendingContracts', 'dueMaintenances', 'isAdmin', 'isStaff'));
    }

    /**
     * Mark a maintenance service for the current period as done.
     */
    public function markMaintenanceDone(Request $request, $maintenanceId)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $maintenance = Maintenance::find($maintenanceId);
        $user = Auth::user();

        if (!$maintenance) {
            return redirect()->route('home')->withErrors(['general' => 'سرویس دوره‌ای یافت نشد.']);
        }

        if (!$user->isAdmin() && ($user->isStaff() && $maintenance->assigned_to !== $user->id)) {
            return redirect()->route('home')->withErrors(['general' => 'شما اجازه انجام این عملیات را ندارید.']);
        }

        DB::beginTransaction();
        try {
            MaintenanceLog::create([
                'maintenance_service_id' => $maintenance->id,
                'performed_by' => $user->id,
                'performed_at' => Carbon::now(),
                'sms_sent' => false,
                'note' => 'سرویس دوره‌ای ماهانه انجام شد - ثبت از طریق داشبورد.',
            ]);

            $maintenance->increment('completed_count');
            $maintenance->last_completed_at = Carbon::now();

            if ($maintenance->completed_count >= $maintenance->duration_in_months) {
                $maintenance->is_active = false;
            }
            $maintenance->save();

            DB::commit();
            return redirect()->route('home')->with('success', 'سرویس دوره‌ای با موفقیت ثبت شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error marking maintenance done: " . $e->getMessage());
            return redirect()->route('home')->withErrors(['general' => 'خطا در ثبت سرویس دوره‌ای: ' . $e->getMessage()]);
        }
    }

    /**
     * Mark a contract as completed.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Contract  $contract  // Route Model Binding
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markContractDone(Request $request, Contract $contract) // Using Route Model Binding
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $user = Auth::user();

        // Authorization: Admin or assigned staff can mark as done
        if (!$user->isAdmin() && ($user->isStaff() && $contract->assigned_to !== $user->id)) {
            return redirect()->route('home')->withErrors(['general' => 'شما اجازه تکمیل این قرارداد را ندارید.']);
        }

        if ($contract->status === 'active') {
            $contract->status = 'completed';
            $contract->save();
            return redirect()->route('home')->with('success', 'قرارداد با موفقیت به وضعیت "تکمیل شده" تغییر یافت.');
        }

        return redirect()->route('home')->withErrors(['general' => 'این قرارداد در وضعیت فعال برای تکمیل نیست.']);
    }
}
