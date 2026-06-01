<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [
        'company_name',
        'logo_path',
        'manager_name',
        'contact_email',
        'contact_phone',
        'contact_whatsapp',
        'address',
    ];

    /**
     * @param array<string, string|null> $defaults
     */
    public static function current(array $defaults = []): self
    {
        return static::query()->first()
            ?? static::query()->create([
                'company_name' => $defaults['name'] ?? 'Teras Rasa Cafe',
                'manager_name' => $defaults['manager'] ?? null,
                'contact_email' => $defaults['contact_email'] ?? null,
                'contact_phone' => $defaults['contact_phone'] ?? null,
                'contact_whatsapp' => $defaults['contact_whatsapp'] ?? null,
                'address' => $defaults['address'] ?? null,
            ]);
    }
}
