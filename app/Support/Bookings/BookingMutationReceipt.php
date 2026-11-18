<?php

declare(strict_types=1);

namespace App\Support\Bookings;

use App\Exceptions\ApiException;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;

final class BookingMutationReceipt
{
    /**
     * @template TResult of array<string,mixed>
     *
     * @param  array<string,mixed>  $payload
     * @param  Closure(): TResult  $write
     * @return TResult
     */
    public function run(User $actor, string $operation, ?string $key, array $payload, Closure $write): array
    {
        if ($key === null) {
            return $write();
        }

        $key = strtolower($key);
        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actor, $operation, $key, $hash, $write): array {
            User::query()->lockForUpdate()->findOrFail($actor->id);

            $identity = ['user_id' => $actor->id, 'operation' => $operation, 'request_key' => $key];
            $receipt = DB::table('crm_mutation_receipts')->where($identity)->lockForUpdate()->first();
            if ($receipt !== null) {
                if (! hash_equals($receipt->payload_hash, $hash)) {
                    throw new ApiException('idempotency_key_conflict', 'This request key was already used with different data.', 409);
                }

                /** @var TResult $previous */
                $previous = json_decode($receipt->result, true, flags: JSON_THROW_ON_ERROR);

                return $previous;
            }

            $result = $write();
            DB::table('crm_mutation_receipts')->insert($identity + [
                'payload_hash' => $hash,
                'result' => json_encode($result, JSON_THROW_ON_ERROR),
                'created_at' => now(),
            ]);

            return $result;
        }, 3);
    }
}
