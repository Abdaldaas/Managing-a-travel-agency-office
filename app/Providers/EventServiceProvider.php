<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

use App\Events\PassportRequested;
use App\Events\PassportStatusUpdated;
use App\Events\VisaRequested;
use App\Events\VisaStatusUpdated;
use App\Events\TicketRequested;
use App\Events\TicketStatusUpdated;

use App\Listeners\LogPassportRequest;
use App\Listeners\LogPassportStatusUpdate;
use App\Listeners\LogVisaRequest;
use App\Listeners\LogVisaStatusUpdate;
use App\Listeners\LogTicketRequest;
use App\Listeners\LogTicketStatusUpdate;
use App\Listeners\SendNewRequestNotification;
use App\Listeners\SendStatusUpdateNotification;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        
        PassportRequested::class => [
            LogPassportRequest::class,
            SendNewRequestNotification::class,
        ],
        
        PassportStatusUpdated::class => [
            LogPassportStatusUpdate::class,
            SendStatusUpdateNotification::class,
        ],
        
        VisaRequested::class => [
            LogVisaRequest::class,
            SendNewRequestNotification::class,
        ],
        
        VisaStatusUpdated::class => [
            LogVisaStatusUpdate::class,
            SendStatusUpdateNotification::class,
        ],
        
        TicketRequested::class => [
            LogTicketRequest::class,
            SendNewRequestNotification::class,
        ],
        
        TicketStatusUpdated::class => [
            LogTicketStatusUpdate::class,
            SendStatusUpdateNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
