<?php

namespace App\Providers;

use App\Models\DailyChecklist;
use App\Models\CashClosure;
use App\Models\DamageCharge;
use App\Models\DirectBookingRequest;
use App\Models\Expense;
use App\Models\FeedbackEntry;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\KnowledgeGuide;
use App\Models\MaintenanceReport;
use App\Models\OperationalTask;
use App\Models\OperationalAlert;
use App\Models\OwnerDailyReport;
use App\Models\Payment;
use App\Models\ProductRequisition;
use App\Models\Property;
use App\Models\Receipt;
use App\Models\RemoteApproval;
use App\Models\Reservation;
use App\Models\RoomInventory;
use App\Models\Room;
use App\Models\SaasPlan;
use App\Models\StaffAttendance;
use App\Models\StaffLeave;
use App\Models\StaffMember;
use App\Models\StaffSchedule;
use App\Models\StockCount;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\Subscription;
use App\Models\TenantAccount;
use App\Models\UtilityReading;
use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.locale') === 'pt') {
            App::setLocale('pt_PT');
        }

        Vite::prefetch(concurrency: 3);

        AuditTrail::register([
            CashClosure::class,
            DailyChecklist::class,
            DamageCharge::class,
            DirectBookingRequest::class,
            Expense::class,
            FeedbackEntry::class,
            Guest::class,
            Invoice::class,
            InvoiceLine::class,
            KnowledgeGuide::class,
            MaintenanceReport::class,
            OperationalAlert::class,
            OperationalTask::class,
            OwnerDailyReport::class,
            Payment::class,
            ProductRequisition::class,
            Property::class,
            Receipt::class,
            RemoteApproval::class,
            Reservation::class,
            Room::class,
            RoomInventory::class,
            SaasPlan::class,
            StaffAttendance::class,
            StaffLeave::class,
            StaffMember::class,
            StaffSchedule::class,
            StockCount::class,
            StockItem::class,
            StockMovement::class,
            Subscription::class,
            TenantAccount::class,
            UtilityReading::class,
            User::class,
        ]);
    }
}
