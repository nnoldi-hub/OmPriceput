<?php

return [
    // din cat in cat minute se ofera un slot (09:00, 09:30, ...)
    'slot_step_minutes' => 30,

    // marja de deplasare dupa fiecare lucrare
    'buffer_minutes' => 30,

    // cu cate ore inainte trebuie facuta o programare
    'min_notice_hours' => 12,

    // cat de departe in viitor se poate programa
    'max_days_ahead' => 30,

    // statusurile care ocupa un slot
    'blocking_statuses' => ['scheduled', 'in_progress'],
];