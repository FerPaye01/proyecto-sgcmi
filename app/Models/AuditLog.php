<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit.audit_log';
    
    public $timestamps = false;
    
    protected $fillable = [
        'event_ts',
        'actor_user',
        'action',
        'object_schema',
        'object_table',
        'object_id',
        'details',
    ];
    
    protected $casts = [
        'event_ts' => 'datetime',
        'details' => 'array',
    ];
}
