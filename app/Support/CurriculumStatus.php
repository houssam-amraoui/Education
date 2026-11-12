<?php

namespace App\Support;

enum CurriculumStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
