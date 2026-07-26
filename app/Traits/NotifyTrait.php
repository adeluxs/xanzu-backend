<?php

namespace App\Traits;

use App\Events\NotificationEvent;
use App\Mail\MailSend;
use App\Models\Notification;
use App\Models\Template;
use App\Models\UserDevice;
use Exception;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

trait NotifyTrait
{
    use SmsTrait;

    public function sendNotify($email, $code, $for, $shortcodes, $phone, $userId, $action = '/')
    {
        $template = Template::where('for', $for)->where('code', $code)->first();

        if (!$template) {
            return null;
        }

        try {
            if ($template->email_status) {
                if ($template->code == 'email_verification') {
                    return $this->mailNotify($email, $template, $shortcodes);
                }

                $this->mailNotify($email, $template, $shortcodes);
            }
            if ($template->notification_status) {
                $this->pushNotify($template, $shortcodes, $action, $userId);
            }
            if ($template->sms_status) {
                $this->smsNotify($template, $shortcodes, $phone);
            }
        } catch (\Throwable $th) {

        }
    }

    private function mailNotify($email, $template, $shortcodes = null)
    {

        try {
            if ($template) {
                $find = array_keys($shortcodes);
                $replace = array_values($shortcodes);
                $details = [
                    'subject' => str_replace($find, $replace, $template->subject),
                    'email' => $email,
                    'banner' => asset($template->banner),
                    'title' => str_replace($find, $replace, $template->title),
                    'salutation' => str_replace($find, $replace, $template->salutation),
                    'email_body' => str_replace($find, $replace, $template->email_body),
                    'button_level' => $template->button_level,
                    'button_link' => str_replace($find, $replace, $template->button_link),
                    'footer_status' => $template->footer_status,
                    'footer_body' => str_replace($find, $replace, $template->footer_body),
                    'bottom_status' => $template->bottom_status,
                    'bottom_title' => str_replace($find, $replace, $template->bottom_title),
                    'bottom_body' => str_replace($find, $replace, $template->bottom_body),
                    'site_logo' => asset(setting('site_logo', 'global')),
                    'site_dark_logo' => asset(setting('site_dark_logo', 'global')),
                    'site_title' => setting('site_title', 'global'),
                    'site_link' => route('home'),
                ];

                if ($template->code == 'email_verification') {
                    return (new MailMessage)
                        ->subject($details['subject'])
                        ->view('backend.mail.user-mail-send', ['details' => $details]);
                }

                \Log::info('Sending email notification', ['email' => $email, 'template_code' => $template->code]);

                return Mail::to($email)->send(new MailSend($details));
            }
        } catch (Exception $exception) {
            \Log::error('Mail Notify Error: ' . $exception->getMessage());
        }

        return null;
    }

    private function pushNotify($template, $shortcodes, $action, $userId)
    {
        try {
            if ($template) {
                $find = array_keys($shortcodes);
                $replace = array_values($shortcodes);

                $data = [
                    'icon' => $template->icon,
                    'type' => $template->code,
                    'user_id' => $userId,
                    'for' => Str::snake($template->for),
                    'title' => str_replace($find, $replace, $template->title),
                    'notice' => strip_tags(str_replace($find, $replace, $template->notification_body)),
                    'action_url' => $action,
                ];

                // Create notification record
                $notification = Notification::create($data);

                if (plugin_active('Firebase') && $template->for != 'Admin') {
                    $this->fcmNotify($template, $shortcodes, $action, $userId);
                }

                // Dispatch event
                $userIdForChannel = $template->for == 'Admin' ? '' : $userId;
                event(new NotificationEvent($template->for, $data, $userIdForChannel));
            }
        } catch (Exception $e) {
        }
    }

    private function smsNotify($template, $shortcodes, $phone)
    {
        if (!config('sms.default') && !$phone) {
            return null;
        }

        try {
            if ($template) {
                $find = array_keys($shortcodes);
                $replace = array_values($shortcodes);

                $message = [
                    'sms_body' => str_replace($find, $replace, $template->sms_body),
                ];
                self::sendSms($phone, $message);
            }
        } catch (Exception $exception) {
        }

        return null;
    }

    // ============================= fcm notification template helper ===================================================
    protected function fcmNotify($template, $shortcodes, $action, $userId)
    {
        try {
            $find = array_keys($shortcodes);
            $replace = array_values($shortcodes);

            $title = str_replace($find, $replace, $template->title);
            $body = strip_tags(str_replace($find, $replace, $template->notification_body));

            // Get user device tokens
            $token = UserDevice::where('user_id', $userId)->first()?->fcm_token;

            if ($token == null) {
                return;
            }

            $data = [
                'icon' => $template->icon,
                'user_id' => $userId,
                'for' => strtolower($template->for),
                'title' => $title,
                'notice' => $body,
                'action_url' => $action,
            ];

            $this->sendFcmNotification($token, $title, $body, $data);
        } catch (Exception $e) {
            \Log::error('FCM Notification Error: ' . $e->getMessage());
        }
    }

    protected function sendFcmNotification($token, $title, $body, $data = [])
    {
        try {

            $firebase = plugin_active('Firebase');
            $firebaseData = json_decode($firebase->data, true);

            $json = base_path('assets/' . $firebaseData['upload_account_json']);

            $jsonData = @json_decode(@file_get_contents($json), true);

            if (!$jsonData) {
                \Log::error('FCM Notification Error: Invalid Firebase JSON configuration.');

                return;
            }

            $credentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/firebase.messaging',
                $jsonData
            );

            $bearerToken = $credentials->fetchAuthToken()['access_token'];

            $projectData = json_decode(file_get_contents($json), true);
            $projectId = data_get($projectData, 'project_id');

            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => [
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'id' => '1',
                        'status' => 'done',
                    ],
                ],
            ];

            $response = Http::withToken($bearerToken)->post($url, $payload);

        } catch (Exception $e) {
            \Log::error('FCM Notification Error: ' . $e->getMessage());
        }
    }
}
