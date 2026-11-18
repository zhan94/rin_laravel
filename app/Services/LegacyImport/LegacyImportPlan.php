<?php

declare(strict_types=1);

namespace App\Services\LegacyImport;

use App\Enums\RoleKey;
use App\Enums\SeasonType;
use DomainException;
use stdClass;

final class LegacyImportPlan
{
    public const EXCLUDED = [
        'schema_migrations' => 'Legacy migration ledger',
        'sessions' => 'Session credentials and transient runtime state',
        'password_reset_tokens' => 'Legacy reset credentials',
        'webauthn_challenges' => 'One-time WebAuthn challenges',
        'user_passkeys' => 'WebAuthn credentials require enrollment on the destination relying party',
    ];

    /** @return list<LegacyTable> */
    public function tables(): array
    {
        return [
            new LegacyTable('roles', 'id role_key title_bg title_en sort_order is_active', unique: [['role_key']]),
            new LegacyTable('users', 'id username password firstname surname lastname email role_id active', renames: ['active' => 'is_active'], unique: [['username'], ['email']]),
            new LegacyTable('season_defaults', 'id start_month start_day end_month end_day'),
            new LegacyTable('services', 'id title_bg title_en title_key max active', renames: ['title_key' => 'legacy_title_key']),
            new LegacyTable('skills', 'id title'),
            new LegacyTable('locations', 'id service_id location_from location_to from_time to_time active'),
            new LegacyTable('cards', 'id title title_bg title_en service active'),
            new LegacyTable('equips', 'id title title_bg title_en service active'),
            new LegacyTable('bonus', 'id title_bg title_en title_key location_from location_to date_from date_to active price description', renames: ['title_key' => 'legacy_title_key']),
            new LegacyTable('cards_price', 'id card_id days price per_day'),
            new LegacyTable('equips_price', 'id equip_id days price per_day'),
            new LegacyTable('bonus_price', 'id bonus_id days price per_day'),
            new LegacyTable('service_slots', 'id service_id name max date_start date_end season_type active'),
            new LegacyTable('service_dates', 'id slot_id slot_date is_active', unique: [['slot_id', 'slot_date']]),
            new LegacyTable('customers', 'id firstname surname lastname date_birth age parent_name parent_phone email active'),
            new LegacyTable('bookings', 'id customer_id service service_type sum_paid paid location skill_id equip_id card_id approved active lunch note', checkedInRelationships: ['skill_id', 'equip_id', 'card_id']),
            new LegacyTable('booking_dates', 'id booking_id service_date_id', unique: [['booking_id', 'service_date_id']]),
            new LegacyTable('booking_payments', 'booking_id sum_paid paid', keys: ['booking_id']),
            new LegacyTable('booking_winter_details', 'booking_id customer_id service_id service_type skill_id shoe_size height equip_enabled equip_id card_id created_at updated_at', keys: ['booking_id'], checkedInRelationships: ['customer_id', 'service_id', 'service_type']),
            new LegacyTable('logs', 'id user page ip location date'),
            new LegacyTable('issue_reports', 'id created_by module_key issue_key priority status title description page_url resolved_by assigned_to assigned_at resolved_at created_at updated_at'),
            new LegacyTable('issue_report_comments', 'id issue_id user_id comment_text created_at'),
            new LegacyTable('issue_report_attachments', 'id issue_id stored_name original_name mime_type file_size created_at'),
            new LegacyTable('user_notifications', 'id user_id actor_user_id notification_type module_key action_key entity_type entity_id title message metadata link_path status priority delivery_channel is_sent sent_at read_at deleted_at created_at updated_at', renames: ['link_path' => 'legacy_link_path']),
            new LegacyTable('mutation_requests', 'operation actor_id request_key request_hash result_json created_at', 'legacy_mutation_receipts', ['operation', 'actor_id', 'request_key']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function map(LegacyTable $table, stdClass $source): array
    {
        $row = [];
        foreach ($table->sourceColumns() as $column) {
            if (! in_array($column, $table->checkedInRelationships, true)) {
                $row[$table->renames[$column] ?? $column] = $source->{$column};
            }
        }

        foreach (['active', 'is_active', 'approved', 'lunch', 'is_sent', 'equip_enabled'] as $flag) {
            if (isset($row[$flag]) && ! in_array((string) $row[$flag], ['0', '1'], true)) {
                throw new DomainException($flag.' must be 0, 1 or null');
            }
        }

        foreach (['date_birth', 'date_from', 'date_to'] as $date) {
            if (array_key_exists($date, $row)) {
                $row[$date] = $this->legacyDate($row[$date], $date);
            }
        }

        if (in_array($table->source, ['roles', 'services', 'bonus', 'cards', 'equips'], true)) {
            $row = $this->titles($row);
        }

        if (array_key_exists('legacy_title_key', $row)) {
            $key = $row['legacy_title_key'];
            $row['title_key'] = is_string($key) && ctype_digit($key) && strlen($key) <= 10 && (int) $key <= 4294967295
                ? (int) $key : null;
        }

        return match ($table->source) {
            'roles' => $this->role($row),
            'users' => $this->user($row),
            'season_defaults' => $this->season($row),
            'service_slots' => $this->slot($row),
            'bookings' => $this->booking($row),
            'booking_payments' => $row + ['created_at' => null, 'updated_at' => null],
            'user_notifications' => $row + ['link_path' => $this->notificationPath($row['legacy_link_path'])],
            default => $row,
        };
    }

    private function legacyDate(?string $value, string $column): ?string
    {
        if ($value === null || $value === '' || $value === '0000-00-00') {
            return null;
        }

        foreach (['Y-m-d', 'd.m.Y', 'd/m/Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!'.$format, $value);
            if ($date !== false && $date->format($format) === $value && (int) $date->format('Y') >= 1000) {
                return $date->format('Y-m-d');
            }
        }

        throw new DomainException($column.' has an unrecognized or invalid date');
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function titles(array $row): array
    {
        $titles = array_filter(['bg' => $row['title_bg'], 'en' => $row['title_en']], fn ($value) => $value !== null && trim($value) !== '');
        $fallback = ($row['title'] ?? null) ?: ($titles['en'] ?? $titles['bg'] ?? '');
        if (! isset($titles['en']) && $fallback !== '') {
            $titles['en'] = $fallback;
        }

        if (! isset($row['role_key'])) {
            $row['title'] = $row['title'] ?? (mb_strlen($fallback) <= 100 ? $fallback : '');
        }
        $row['title_translations'] = $titles === [] ? null : json_encode($titles, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        return $row;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function role(array $row): array
    {
        if (RoleKey::tryFrom($row['role_key']) === null) {
            throw new DomainException('role_key is unsupported by Laravel authorization');
        }

        return $row + ['created_at' => null, 'updated_at' => null];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function user(array $row): array
    {
        if (password_get_info($row['password'])['algo'] === null) {
            throw new DomainException('password requires a supported password hash or a reviewed reset plan');
        }

        return $row;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function season(array $row): array
    {
        if ((int) $row['id'] !== 1 || ! checkdate((int) $row['start_month'], (int) $row['start_day'], 2000)
            || ! checkdate((int) $row['end_month'], (int) $row['end_day'], 2000)) {
            throw new DomainException('season_defaults must have the singleton ID and valid calendar boundaries');
        }

        return $row + ['updated_at' => null];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function slot(array $row): array
    {
        if ($row['season_type'] !== null && SeasonType::tryFrom($row['season_type']) === null) {
            throw new DomainException('season_type is unsupported');
        }

        return $row + ['created_at' => null];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function booking(array $row): array
    {
        if (! in_array((int) $row['service_type'], [1, 2], true)) {
            throw new DomainException('service_type must refer to a service or bonus');
        }
        if ($row['location'] !== null && (int) $row['location'] === 0) {
            $row['location'] = null;
        }

        return $row;
    }

    private function notificationPath(?string $path): string
    {
        if ($path !== null && preg_match('/^customer_view\.php\?id=([1-9][0-9]*)$/D', $path, $matches) === 1) {
            return '/crm/customers?customer='.$matches[1];
        }

        return match ($path) {
            'reports.php' => '/crm/reports',
            'unpay.php' => '/crm/customers?unpaid=1',
            'services.php' => '/crm/services',
            'issues.php' => '/crm/issues',
            default => '',
        };
    }
}
