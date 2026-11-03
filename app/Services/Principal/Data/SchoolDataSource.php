<?php

namespace App\Services\Principal\Data;

use Carbon\CarbonImmutable;

/**
 * One contract for every piece of school data the principal dashboard reads.
 *
 * Today it is implemented by DemoSchoolDataSource (generated, date-driven demo data).
 * Later, write EloquentSchoolDataSource that returns the SAME array shapes from the
 * database and change one line in AppServiceProvider. Nothing in the UI changes.
 */
interface SchoolDataSource
{
    /** The moment the dashboard is analysing ("today"). */
    public function now(): CarbonImmutable;

    /** @return list<array{id:string,name:string,class:string,parent_phone:string,subject_marks:array<string,int>}> */
    public function students(): array;

    /**
     * Attendance for the last N school days, newest last.
     *
     * @return array<string, array<string, string>> studentId => [Y-m-d => 'P'|'A'|'L']
     */
    public function attendanceHistory(int $schoolDays = 30): array;

    /**
     * Every fee instalment for the academic year.
     *
     * @return list<array{student_id:string,head:string,amount:int,due_date:string,paid_on:?string}>
     */
    public function feeLedger(): array;

    /** @return list<array{id:string,name:string,designation:string,department:string,subjects:string,classes:string,status:string,employment:string}> */
    public function staff(): array;

    /** @return list<array{time:string,start:string,end:string,class:string,subject:string,teacher:string,teacher_id:string,room:string,status:string}> */
    public function timetableToday(): array;

    /**
     * Timetable for any date (Y-m-d) — same keys as timetableToday() plus
     * 'teacher_on_leave' (bool). Status: past date → Completed, future → Scheduled,
     * today → by the clock. Return [] for holidays / Sundays.
     */
    public function timetableForDate(string $date): array;

    /** @return list<array{class:string,subject:string,chapter:string,topic:string,completion:int,expected:int,status:string,target:string}> */
    public function syllabus(): array;

    /** @return list<array{exam:string,class:string,subject:string,date:string,start:string,end:string,max_marks:int,teacher:string,teacher_id:string,room:string,status:string}> */
    public function examinations(): array;
}
