<?php

namespace App\Services\Principal\Insights;

use App\Services\Principal\Data\SchoolDataSource;

interface Analyzer
{
    /** @return list<Insight> */
    public function analyze(SchoolDataSource $data): array;
}
