<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting by key, with default value fallback.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            $setting = static::where('key', $key)->first();
            if (! $setting || $setting->value === null) {
                return $default;
            }

            $decoded = json_decode($setting->value, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $setting->value;
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, mixed $value): static
    {
        $serialized = is_array($value) || is_object($value)
            ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            : (string) $value;

        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $serialized]
        );
    }

    /**
     * Default NTTN Provider NOC email directory.
     */
    public static function defaultNttnProviders(): array
    {
        return [
            'Summit Communications' => 'noc@summitcommunications.net',
            'Fiber@Home' => 'noc@fiberathome.net',
            'Bahon Limited' => 'nmasum54@gmail.com',
            'Direct Ring Core' => 'noc@nttn-portal.local',
            'Other NTTN' => '',
        ];
    }

    /**
     * Get configured NTTN Provider emails (merged with defaults).
     */
    public static function getNttnProviderEmails(): array
    {
        $saved = static::get('nttn_provider_emails', []);
        if (! is_array($saved) || empty($saved)) {
            return static::defaultNttnProviders();
        }

        // Merge defaults with saved so standard keys are always present
        return array_merge(static::defaultNttnProviders(), $saved);
    }

    /**
     * Get email for a given provider.
     */
    public static function getEmailForProvider(?string $provider): string
    {
        if (empty($provider)) {
            return 'noc@summitcommunications.net';
        }

        $directory = static::getNttnProviderEmails();

        // Direct exact match
        if (! empty($directory[$provider])) {
            return $directory[$provider];
        }

        // Case-insensitive / fuzzy match
        foreach ($directory as $name => $email) {
            if (empty($email)) {
                continue;
            }
            if (stripos($provider, $name) !== false || stripos($name, $provider) !== false) {
                return $email;
            }
        }

        // Fallbacks based on common company keywords
        $lower = strtolower($provider);
        if (str_contains($lower, 'summit')) {
            return $directory['Summit Communications'] ?? 'noc@summitcommunications.net';
        }
        if (str_contains($lower, 'fiber') || str_contains($lower, 'home')) {
            return $directory['Fiber@Home'] ?? 'noc@fiberathome.net';
        }
        if (str_contains($lower, 'bahon')) {
            return $directory['Bahon Limited'] ?? 'nmasum54@gmail.com';
        }
        if (str_contains($lower, 'direct')) {
            return $directory['Direct Ring Core'] ?? 'noc@nttn-portal.local';
        }

        return reset($directory) ?: 'noc@summitcommunications.net';
    }
}
