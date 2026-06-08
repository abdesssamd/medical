<?php

namespace Modules\RIS\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RisReportTemplate extends Model
{
    use HasFactory;

    protected $table = 'ris_report_templates';

    protected $fillable = [
        'category',
        'title',
        'content',
    ];
}
