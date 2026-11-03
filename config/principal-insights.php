<?php

// Thresholds the principal dashboard uses to decide what is a "problem".
// Each school can tune these without touching code.

return [
    'fees' => [
        'grace_days' => 7,              // days after the due date before a family counts as a defaulter
    ],

    'attendance' => [
        'defaulter_below' => 75,        // % over the last 30 school days
        'streak_days' => 3,             // consecutive absences that trigger a "call the family" alert
        'section_drop_points' => 6,     // section today vs its own usual level
    ],

    'marks' => [
        'pass_mark' => 40,              // % needed to pass a subject
        'low_below' => 40,              // default "low marks" line on the Marks tab
    ],

    'exams' => [
        'look_ahead_days' => 30,        // warn about syllabus gaps this far before an exam
        'marks_entry_due_days' => 3,    // marks should be entered within N days of the paper
    ],
];
