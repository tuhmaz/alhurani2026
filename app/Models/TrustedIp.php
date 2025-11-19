<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrustedIp extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'reason',
        'added_at',
    ];

    /**
     * الحقول المحمية من Mass Assignment لأسباب أمنية
     *
     * @var array<int, string>
     */
    protected $guarded = [
        'added_by',  // من قام بالإضافة - يُحدد من المستخدم الحالي فقط
    ];

    protected $casts = [
        'added_at' => 'datetime',
    ];

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
