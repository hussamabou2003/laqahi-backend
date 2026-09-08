<?php

use Illuminate\Support\Facades\Schedule;

// تنبيه تلقائي يومي الساعة 08:00 صباحاً لولي الأمر قبل الموعد بيوم واحد
Schedule::command('app:send-reminders')->dailyAt('08:00');