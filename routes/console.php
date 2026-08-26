<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('broca:expire-subscriptions')->hourly();
