<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:remind';

    protected $description = 'Send email reminders for upcoming appointments';

    public function handle()
    {
        $tomorrow = now()->addDay()->toDateString();
        
        $appointments = \App\Models\Appointment::with(['child.parent', 'vaccine', 'center'])
            ->where('status', 'booked')
            ->whereDate('appointment_date', $tomorrow)
            ->where('confirmed_by_parent', false)
            ->get();

        foreach ($appointments as $appointment) {
            $parentEmail = $appointment->child->parent->email;
            if ($parentEmail) {
                \Illuminate\Support\Facades\Mail::to($parentEmail)->send(new \App\Mail\AppointmentReminderMail($appointment));
            }
        }

        $this->info("Sent reminders for {$appointments->count()} appointments.");
    }
}
