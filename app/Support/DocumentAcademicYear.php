<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

final class DocumentAcademicYear
{
    /** Keep existing document workflows usable while the additive migration awaits approval. */
    public static function available(): bool
    {
        return Schema::hasColumn('documents', 'academic_year');
    }
}
