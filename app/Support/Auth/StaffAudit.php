<?php

declare(strict_types=1);

namespace App\Support\Auth;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class StaffAudit
{
    public static function record(string $event, ?int $staffId = null, ?int $subjectId = null): void
    {
        $request = request();
        $eventId = $request->attributes->get('staff_audit_event_id');
        if (! is_string($eventId)) {
            $eventId = (string) Str::uuid();
            $request->attributes->set('staff_audit_event_id', $eventId);
        }

        $route = $request->route();
        try {
            Log::channel('staff_security')->notice($event, [
                'event_id' => $eventId,
                'staff_id' => $staffId,
                'subject_id' => $subjectId,
                'route' => $route instanceof Route ? $route->getName() : null,
            ]);
        } catch (Throwable) {
            error_log('Could not write the staff security audit event '.$eventId);
        }
    }

    public static function committed(string $event, int $staffId, int $subjectId): void
    {
        DB::afterCommit(static fn () => self::record($event, $staffId, $subjectId));
    }
}
