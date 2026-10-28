<?php

namespace App\Console\Commands;

use App\Models\Measure;
use App\Models\User;
use App\Services\MailSettings;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\Exception;

class SendNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deming:send-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for controls';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        Log::info('SendNotifications - Start.');

        // Need to send notifications today ?
        if ($this->needCheck()) {
            Log::info('SendNotifications - notifications today');

            $measures = Measure::query()
                ->where('status', 0)
                ->where('plan_date', '<=', Carbon::today()
                    ->addDays(intval(config('deming.notification.expire-delay')))->toDateString())
                ->orderBy('plan_date')
                ->count();

            Log::info(
                'SendNotifications - ' .
                $measures .
                ' measure(s) will expire within '.
                config('deming.notification.expire-delay') .
                ' days.'
            );

            // Loop on all users
            $users = User::all();

            foreach ($users as $user) {
                // get measures
                $measures = Measure::query()
                    ->select('measures.*')
                    ->where('status', 0)
                    ->leftJoin('measure_user', 'measures.id', '=', 'measure_user.measure_id')
                    ->leftJoin('measure_user_group', 'measures.id', '=', 'measure_user_group.measure_id')
                    ->leftJoin('user_user_group', 'measure_user_group.user_group_id', '=', 'user_user_group.user_group_id')
                    ->where(function ($query) use ($user) {
                        $query->where('measure_user.user_id', '=', $user->id)
                            ->orWhere('user_user_group.user_id', '=', $user->id);
                    })
                    ->where('plan_date', '<=', Carbon::today()
                        ->addDays(intval(config('deming.notification.expire-delay')))->toDateString())
                    ->orderBy('plan_date')
                    ->distinct()
                    ->with('controls')
                    ->get();

                if ($measures->count() > 0) {
                    App::setlocale($user->language);
                    $txt = '';
                    foreach ($measures as $measure) {
                        // Date
                        $txt .= '<a href="' . url('/bob/show/'. $measure->id) . '">';
                        $txt .= '<b>';
                        if (strtotime($measure->plan_date) >= strtotime('today')) {
                            $txt .= "<font color='green'>" . $measure->plan_date .' </font>';
                        } else {
                            $txt .= "<font color='red'>" . $measure->plan_date . '</font>';
                        }
                        $txt .= '</b>';
                        $txt .= '</a>';
                        // Space
                        $txt .= ' &nbsp; - &nbsp; ';
                        // Clauses (security measures = controls)
                        foreach ($measure->controls as $control) {
                            $txt .= '<a href="' . url('/alice/show/' . $control->id) . '">'. htmlentities($control->clause) . '</a>';
                            // Space
                            $txt .= ' &nbsp; ';
                        }
                        $txt .= ' - &nbsp; ';
                        // Name
                        $txt .= htmlentities($measure->name);
                        $txt .= "<br>\n";
                    }

                    try {
                        // PHPMailer configured with the SMTP settings of the configuration table
                        $mail = MailSettings::mailer();

                        // Recipients
                        MailSettings::setFrom($mail, (string) config('deming.notification.mail-from'));
                        $mail->addAddress($user->email);
                        $mail->Subject = config('deming.notification.mail-subject');

                        // Get message model
                        $message = config('deming.notification.mail-content');
                        if (($message === null) || (strlen($message) === 0)) {
                            $message = trans('cruds.config.notifications.message_default_content');
                        }

                        // Replace %table% in message model
                        $message = str_replace('%table%', $txt, $message);

                        // Content
                        $mail->isHTML(true);
                        $mail->Body = $message;

                        // Send email
                        $mail->send();

                        // Success
                        Log::info("Mail sent to {$user->email}");
                    } catch (Exception $e) {
                        // Log error
                        Log::error("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
                    }
                }
            }
        } else {
            Log::info('SendNotifications - no notifications today');
        }

        Log::info('SendNotifications - DONE.');
    }

    /**
     * return true if check is needed
     *
     * @return bool
     */
    private function needCheck(): bool
    {
        $check_frequency = config('deming.notification.frequency');

        Log::info('SendNotifications - frequency=' . $check_frequency . ' day=' . Carbon::today()->day . ' dayOfWeek=' . Carbon::today()->dayOfWeek);

        return ($check_frequency === '1') ||
            // Weekly
            (($check_frequency === '7') && (Carbon::today()->dayOfWeek === 1)) ||
            // Every two weeks
            (($check_frequency === '15') && ((Carbon::today()->day === 1) || (Carbon::today()->day === 15))) ||
            // Monthly
            (($check_frequency === '30') && (Carbon::today()->day === 1));
    }
}
