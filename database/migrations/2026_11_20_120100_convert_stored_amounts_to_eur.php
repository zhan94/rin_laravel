<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $mysql = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
            $integerType = $mysql ? 'SIGNED' : 'BIGINT';
            $divide = $mysql ? 'DIV' : '/';
            $rateNumerator = 195583;
            $half = intdiv($rateNumerator, 2);

            foreach ([
                'bookings' => ['sum_paid', 'paid'],
                'booking_payments' => ['sum_paid', 'paid'],
                'services_price' => ['price', 'per_day'],
                'cards_price' => ['price', 'per_day'],
                'equips_price' => ['price', 'per_day'],
                'bonus_price' => ['price', 'per_day'],
                'bonus' => ['price'],
                'payments' => ['sum_service', 'paid_service', 'equip_sum', 'card_sum', 'equip', 'card'],
            ] as $table => $columns) {
                $amounts = [];
                foreach ($columns as $column) {
                    // Convert original cents at 1.95583 per euro with exact half-up rounding.
                    $cents = "(CAST(ROUND(ABS($column) * 100, 0) AS $integerType) * 100000 + $half) $divide $rateNumerator";
                    $amounts[$column] = DB::raw("CASE WHEN $column IS NULL THEN NULL WHEN $column < 0 THEN -($cents) / 100.0 ELSE ($cents) / 100.0 END");
                }

                DB::table($table)->update($amounts);
            }

            DB::table('crm_mutation_receipts')->where('operation', 'booking.payment')
                ->orderBy('user_id')->orderBy('request_key')->chunk(500, function ($receipts): void {
                    foreach ($receipts as $receipt) {
                        $payment = json_decode($receipt->result, true, flags: JSON_THROW_ON_ERROR);
                        $payment = array_intersect_key($payment, array_flip([
                            'mode', 'amount_eur', 'paid_eur', 'sum_paid_eur', 'remaining_eur',
                        ]));
                        $payment['remaining_eur'] = $payment['sum_paid_eur'] === null
                            ? null : max(0, round($payment['sum_paid_eur'] - $payment['paid_eur'], 2));

                        DB::table('crm_mutation_receipts')->where([
                            'user_id' => $receipt->user_id,
                            'operation' => $receipt->operation,
                            'request_key' => $receipt->request_key,
                        ])
                            ->update(['result' => json_encode($payment, JSON_THROW_ON_ERROR)]);
                    }
                });
        });
    }

    public function down(): void
    {
        throw new RuntimeException('The euro cutover cannot be reversed. Restore the database backup taken before the cutover.');
    }
};
