<?php

namespace App\Providers;

use App\Models\DailyChecklist;
use App\Models\DirectBookingRequest;
use App\Models\Expense;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\OperationalTask;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\StaffMember;
use App\Models\StaffSchedule;
use App\Models\StockItem;
use App\Models\StockMovement;
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
            DailyChecklist::class,
            DirectBookingRequest::class,
            Expense::class,
            Guest::class,
            Invoice::class,
            OperationalTask::class,
            Payment::class,
            Property::class,
            Reservation::class,
            Room::class,
            StaffMember::class,
            StaffSchedule::class,
            StockItem::class,
            StockMovement::class,
            User::class,
        ]);
    }
}
