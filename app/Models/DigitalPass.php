<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\QrCodeService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DigitalPass extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'terrestre.digital_pass';

    protected $fillable = [
        'pass_code',
        'qr_code',
        'pass_type',
        'holder_name',
        'holder_dni',
        'truck_id',
        'valid_from',
        'valid_until',
        'status',
        'created_by',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    /**
     * Boot method to generate QR code automatically
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($digitalPass) {
            $qrService = app(QrCodeService::class);
            
            if (empty($digitalPass->pass_code)) {
                $digitalPass->pass_code = $qrService->generateUniquePassCode(
                    fn($code) => self::where('pass_code', $code)->exists()
                );
            }
            
            if (empty($digitalPass->qr_code)) {
                // Prepare pass data for QR generation
                $passData = [
                    'pass_code' => $digitalPass->pass_code,
                    'pass_type' => $digitalPass->pass_type,
                    'holder_name' => $digitalPass->holder_name,
                    'holder_dni' => $digitalPass->holder_dni,
                    'valid_from' => $digitalPass->valid_from?->toIso8601String() ?? now()->toIso8601String(),
                    'valid_until' => $digitalPass->valid_until?->toIso8601String() ?? now()->addDays(30)->toIso8601String(),
                ];
                
                // Add truck plate if available
                if ($digitalPass->truck_id && $digitalPass->truck) {
                    $passData['truck_placa'] = $digitalPass->truck->placa;
                }
                
                $digitalPass->qr_code = $qrService->generateQrCodeWithPassInfo($passData);
            }
        });
    }

    /**
     * Check if the pass is currently valid
     */
    public function isValid(): bool
    {
        return $this->status === 'ACTIVO' 
            && now()->between($this->valid_from, $this->valid_until);
    }

    /**
     * Revoke the digital pass
     */
    public function revoke(): void
    {
        $this->update(['status' => 'REVOCADO']);
    }

    /**
     * Relationships
     */
    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function accessPermits(): HasMany
    {
        return $this->hasMany(AccessPermit::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVO');
    }

    public function scopeValid($query)
    {
        return $query->where('status', 'ACTIVO')
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('valid_until', '<', now())
            ->where('status', '!=', 'REVOCADO');
    }
}
