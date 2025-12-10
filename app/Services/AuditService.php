<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Log an audit event
     *
     * @param string $action Action performed (CREATE, UPDATE, DELETE, VIEW, EXPORT)
     * @param string $objectSchema Database schema
     * @param string $objectTable Database table
     * @param int|null $objectId Record ID
     * @param array $details Additional details about the action
     * @return AuditLog
     */
    public function log(
        string $action,
        string $objectSchema,
        string $objectTable,
        ?int $objectId = null,
        array $details = []
    ): AuditLog {
        return AuditLog::create([
            'event_ts' => now(),
            'actor_user' => Auth::id() ? (string) Auth::id() : null,
            'action' => $action,
            'object_schema' => $objectSchema,
            'object_table' => $objectTable,
            'object_id' => $objectId,
            'details' => $this->sanitizeDetails($details),
        ]);
    }
    
    /**
     * Sanitize details to remove PII and sensitive data
     * Recursively sanitizes nested arrays
     *
     * @param array $details
     * @return array
     */
    private function sanitizeDetails(array $details): array
    {
        $piiFields = ['placa', 'tramite_ext_id', 'password', 'token', 'secret', 'credentials'];
        
        foreach ($details as $key => $value) {
            if (in_array($key, $piiFields)) {
                $details[$key] = '***MASKED***';
            } elseif (is_array($value)) {
                // Recursively sanitize nested arrays
                $details[$key] = $this->sanitizeDetails($value);
            }
        }
        
        return $details;
    }
}
