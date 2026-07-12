<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssociatedLocation extends Model
{
    use HasFactory;

    protected $table = 'associated_locations';

    public $timestamps = false;

    protected $fillable = [
        'case_id',
        'name',
        'address_line_1',
        'address_line_2',
        'city',
        'state_code',
        'zip_code',
        'country_code',
        'is_active',
        'created_by',
        'created_date',
        'modified_by',
        'last_modified_date',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_date' => 'datetime',
        'last_modified_date' => 'datetime',
    ];

    public function courtCase()
    {
        return $this->belongsTo(CourtCase::class, 'case_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function formattedAddress(): string
    {
        return collect([
            $this->address_line_1,
            $this->address_line_2,
            collect([$this->city, $this->state_code])->filter()->join(', '),
            $this->zip_code,
            $this->country_code,
        ])->filter()->join(', ');
    }

    public function mapsUrl(): ?string
    {
        $address = $this->formattedAddress();

        return $address !== ''
            ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($address)
            : null;
    }
}
