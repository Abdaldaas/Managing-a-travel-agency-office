<?php

namespace App\Services;

use App\Models\Notification;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Traits\ApiResponseTrait;

class NotificationService
{
    // Removed ApiResponseTrait

    protected $messaging;

    public function __construct()
    {
        $serviceAccountPath = storage_path('app/firebase/travel-agency-office-firebase.json');

        if (!file_exists($serviceAccountPath) || !is_readable($serviceAccountPath)) {
            throw new \InvalidArgumentException('The Firebase credentials file is not readable or does not exist at: ' . $serviceAccountPath);
        }

        $factory = (new Factory)->withServiceAccount($serviceAccountPath);
        $this->messaging = $factory->createMessaging();
    }

    private function successResponse($data, $message, $statusCode)
    {
        return response()->json([
            'data' => $data,
            'message' => $message,
        ], $statusCode);
    }

    private function errorResponse($message, $statusCode, $error = null)
    {
        return response()->json([
            'error' => $error,
            'message' => $message,
        ], $statusCode);
    }

    public function sendToUser($title, $message, $notifiable)
    {
        try {

            if (empty($notifiable->fcm_token )) {
                return $this->errorResponse('no_fcm_token', 400);
            }

            Notification::create([
                'title' => $title,
                'message' => $message,
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id,
                // 'is_read' => false,
            ]);
            $notification = [
                'title' => $title,
                'body' => $message,
                'sound' => 'default',
            ];

            $cloudMessage = CloudMessage::withTarget('token', $notifiable->fcm_token )
                ->withNotification($notification)
                ->withData([
                    'priority' => 'high',
                    'contentAvailable' => true,
                ]);

            $this->messaging->send($cloudMessage);

            return $this->successResponse(null, 'notification_sent_successfully', 200);

        } catch (Exception $e) {
            Log::error('Notification failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('notification_failed', 500, $e->getMessage());
        }
    }

 
       public function sendToMany($title, $message, $notifiables)
{
     try {
            $tokens = [];
              $notificationsData = []; 
        $now = now();
$currentUserId = auth()->id(); 

         foreach ($notifiables as $notifiable) {
   
       if (!empty($notifiable->fcm_token) && $notifiable->id !== $currentUserId) {
        $tokens[] = $notifiable->fcm_token;;

              
                $notificationsData[] = [
                    'title' => $title,
                    'message' => $message,
                    'notifiable_type' => get_class($notifiable),
                    'notifiable_id' => $notifiable->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        

        
        if (!empty($notificationsData)) {
            Notification::insert($notificationsData);
        }

        if (empty($tokens)) {
            return $this->errorResponse('no_valid_fcm_tokens', 400);
        }


            $notification = [
                'title' => $title,
                'body' => $message,
                'sound' => 'default',
            ];

          $cloudMessage = CloudMessage::withTarget('topic', 'users')
           ->withNotification($notification)->withData([
           'priority' => 'high',
           'contentAvailable' => 'true',
            ]);

            $response = $this->messaging->sendMulticast($cloudMessage, $tokens);

            if ($response->hasFailures()) {
                $failedTokens = collect($response->failures())->pluck('target.token');
                return $this->errorResponse('notification_partial_failure', 207, $failedTokens);
            }

            return $this->successResponse(null, 'notification_sent_successfully', 200);

        } catch (Exception $e) {
            Log::error('Bulk Notification failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('notification_failed', 500, $e->getMessage());
        }
    }
}

