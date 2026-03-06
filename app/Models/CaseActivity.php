<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseActivity extends Model
{
    use HasFactory;

    protected $table = 'case_activity'; // schema + table

    protected $primaryKey = 'id';

    public $timestamps = false; // no created_at / updated_at columns

    protected $fillable = [
        'case_id',
        'created_by',
        'subject',
        'notes',
        'case_file',
        'next_follow_up_date',
        'created_date'
    ];

    protected $casts = [
        'case_file' => 'array',
        'next_follow_up_date' => 'date',
        'created_date' => 'date',
    ];

    /**
     * Relationship with Core Case
     */
    public function case()
    {
        return $this->belongsTo(CourtCase::class, 'case_id'); // ensure model name is correct
    }

    /**
     * Relationship with User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
