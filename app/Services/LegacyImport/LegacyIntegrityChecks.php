<?php

declare(strict_types=1);

namespace App\Services\LegacyImport;

use Illuminate\Database\Connection;

final class LegacyIntegrityChecks
{
    public function check(Connection $db, LegacyImportReport $report, bool $source): void
    {
        $relations = [
            ['users', 'role_id', 'roles'], ['locations', 'service_id', 'services', 0],
            ['cards', 'service', 'services'], ['equips', 'service', 'services'],
            ['cards_price', 'card_id', 'cards'], ['equips_price', 'equip_id', 'equips'], ['bonus_price', 'bonus_id', 'bonus'],
            ['service_slots', 'service_id', 'services'], ['service_dates', 'slot_id', 'service_slots'],
            ['bookings', 'customer_id', 'customers'], ['bookings', 'location', 'locations', 0],
            ['booking_dates', 'booking_id', 'bookings'], ['booking_dates', 'service_date_id', 'service_dates'],
            ['booking_payments', 'booking_id', 'bookings'], ['booking_winter_details', 'booking_id', 'bookings'],
            ['booking_winter_details', 'skill_id', 'skills'], ['booking_winter_details', 'equip_id', 'equips'],
            ['booking_winter_details', 'card_id', 'cards'], ['logs', 'user', 'users'],
            ['issue_reports', 'created_by', 'users'], ['issue_reports', 'assigned_to', 'users'], ['issue_reports', 'resolved_by', 'users'],
            ['issue_report_comments', 'issue_id', 'issue_reports'], ['issue_report_comments', 'user_id', 'users'],
            ['issue_report_attachments', 'issue_id', 'issue_reports'], ['user_notifications', 'user_id', 'users'],
            ['user_notifications', 'actor_user_id', 'users'],
            [$source ? 'mutation_requests' : 'legacy_mutation_receipts', 'actor_id', 'users'],
        ];

        foreach ($relations as $relation) {
            [$table, $column, $parent] = $relation;
            $query = $db->table($table.' as child')->leftJoin($parent.' as parent', 'child.'.$column, '=', 'parent.id')
                ->whereNotNull('child.'.$column)->whereNull('parent.id');
            if (isset($relation[3])) {
                $query->where('child.'.$column, '<>', $relation[3]);
            }
            $this->record($report, $source, $table.'.'.$column.' orphan', $query->count());
        }

        foreach ([1 => 'services', 2 => 'bonus'] as $type => $parent) {
            $this->record($report, $source, 'bookings without matching '.$parent,
                $db->table('bookings as b')->leftJoin($parent.' as p', 'b.service', '=', 'p.id')
                    ->where('b.service_type', $type)->whereNull('p.id')->count());
        }

        foreach (['bookings', 'services', 'issue_reports', 'customers', 'locations', 'cards', 'equips', 'bonus'] as $parent) {
            $this->record($report, $source, 'notifications with missing '.$parent,
                $db->table('user_notifications as n')->leftJoin($parent.' as p', 'n.entity_id', '=', 'p.id')
                    ->where('n.entity_type', $parent)->whereNotNull('n.entity_id')->whereNull('p.id')->count());
        }

        $checks = [
            'booking location belongs to the wrong service' => 'SELECT COUNT(*) FROM bookings b JOIN locations l ON l.id = b.location WHERE b.service_type <> 1 OR (l.service_id <> 0 AND b.service <> l.service_id)',
            'booking date belongs to the wrong service' => 'SELECT COUNT(*) FROM booking_dates bd JOIN bookings b ON b.id = bd.booking_id JOIN service_dates d ON d.id = bd.service_date_id JOIN service_slots s ON s.id = d.slot_id WHERE b.service_type <> 1 OR b.service <> s.service_id',
            'booking payment mirror disagrees' => 'SELECT COUNT(*) FROM bookings b LEFT JOIN booking_payments p ON p.booking_id = b.id WHERE p.booking_id IS NULL OR NOT COALESCE(p.sum_paid = b.sum_paid, p.sum_paid IS NULL AND b.sum_paid IS NULL) OR NOT COALESCE(p.paid = b.paid, p.paid IS NULL AND b.paid IS NULL)',
            'booking card or equipment belongs to the wrong service' => 'SELECT COUNT(*) FROM booking_winter_details w JOIN bookings b ON b.id = w.booking_id LEFT JOIN cards c ON c.id = w.card_id LEFT JOIN equips e ON e.id = w.equip_id WHERE (c.id IS NOT NULL AND c.service <> b.service) OR (e.id IS NOT NULL AND e.service <> b.service)',
            'duplicate active customer/service/date bookings' => 'SELECT COUNT(*) FROM (SELECT b.customer_id FROM bookings b JOIN booking_dates bd ON bd.booking_id = b.id JOIN service_dates sd ON sd.id = bd.service_date_id WHERE b.active = 1 AND b.service_type = 1 GROUP BY b.customer_id, b.service, sd.slot_date HAVING COUNT(*) > 1) duplicates',
        ];

        if ($source) {
            $checks['duplicated winter detail fields disagree'] = 'SELECT COUNT(*) FROM bookings b LEFT JOIN booking_winter_details w ON w.booking_id = b.id WHERE (w.booking_id IS NOT NULL AND (NOT COALESCE(w.customer_id = b.customer_id, w.customer_id IS NULL AND b.customer_id IS NULL) OR NOT COALESCE(w.service_id = b.service, w.service_id IS NULL AND b.service IS NULL) OR NOT COALESCE(w.service_type = b.service_type, w.service_type IS NULL AND b.service_type IS NULL))) OR NOT COALESCE(w.skill_id = b.skill_id, w.skill_id IS NULL AND b.skill_id IS NULL) OR NOT COALESCE(w.equip_id = b.equip_id, w.equip_id IS NULL AND b.equip_id IS NULL) OR NOT COALESCE(w.card_id = b.card_id, w.card_id IS NULL AND b.card_id IS NULL)';
            $checks['legacy receipt keys collide under the destination collation'] = 'SELECT COUNT(*) FROM (SELECT actor_id FROM mutation_requests GROUP BY LOWER(operation), actor_id, LOWER(request_key) HAVING COUNT(*) > 1) duplicates';
        }

        foreach ($checks as $label => $sql) {
            $this->record($report, $source, $label, (int) $db->scalar($sql));
        }
    }

    private function record(LegacyImportReport $report, bool $source, string $label, int $count): void
    {
        $scope = $source ? 'source' : 'destination';
        $report->checks[$scope.' / '.$label] = $count;
        if ($count > 0) {
            $report->error($scope, $label, $count);
        }
    }
}
