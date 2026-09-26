<?php

namespace App\Core\Services;

use App\Models\Setting;

class CompanyProfileService
{
    /**
     * About / history / vision / mission / values live in settings so an
     * operator can edit them from the admin without touching code.
     */
    public const ABOUT = [
        'about.description',
        'about.history',
        'about.vision',
        'about.mission',
        'about.values',
    ];

    public const CONTACT = [
        'contact.address',
        'contact.phone',
        'contact.whatsapp',
        'contact.email',
        'contact.map_embed',
        'contact.business_hours',
        'contact.social',
    ];

    public function about(): array
    {
        $settings = app(SettingService::class);
        $out = [];
        foreach (self::ABOUT as $key) {
            $out[$key] = $settings->get($key, '');
        }
        $out['about.values'] = $this->asList($out['about.values']);

        return $out;
    }

    public function contact(): array
    {
        $settings = app(SettingService::class);
        $out = [];
        foreach (self::CONTACT as $key) {
            $out[$key] = $settings->get($key, '');
        }
        $social = is_string($out['contact.social']) ? json_decode($out['contact.social'], true) : $out['contact.social'];
        $out['contact.social'] = is_array($social) ? $social : [];

        return $out;
    }

    public function socialLinks(): array
    {
        return $this->contact()['contact.social'] ?: [];
    }

    protected function asList($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (! $value) {
            return [];
        }
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value))));
    }

    public function saveAbout(array $data): void
    {
        $service = app(SettingService::class);
        foreach (self::ABOUT as $key) {
            if (array_key_exists($key, $data)) {
                $service->set($key, $data[$key], 'json', 'company');
            }
        }
    }

    public function saveContact(array $data): void
    {
        $service = app(SettingService::class);
        foreach (self::CONTACT as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $value = $data[$key];
            $isSocial = $key === 'contact.social';
            $service->set(
                $key,
                $isSocial && is_array($value) ? json_encode($value) : $value,
                $isSocial ? 'json' : 'text',
                'company'
            );
        }
    }
}
