<?php

namespace App\Services;

use App\Models\Order;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

class SalesByCountryReport
{
    public const METRICS = [
        'gross' => 'Lordo ordini pagati',
        'net' => 'Netto dopo rimborsi',
    ];

    public function build(string $dateFrom, string $dateTo, string $metric = 'gross'): array
    {
        $from = CarbonImmutable::parse($dateFrom)->startOfDay();
        $to = CarbonImmutable::parse($dateTo)->startOfDay();
        $metric = array_key_exists($metric, self::METRICS) ? $metric : 'gross';
        $countryLabels = [];
        $amounts = [];
        $ordersCount = 0;

        foreach (CarbonPeriod::create($from, $to) as $day) {
            $amounts[$day->toDateString()] = [];
        }

        Order::query()
            ->whereIn('payment_status', ['paid', 'refunded'])
            ->whereDate('paid_at', '>=', $from->toDateString())
            ->whereDate('paid_at', '<=', $to->toDateString())
            ->orderBy('paid_at')
            ->chunk(200, function ($orders) use (&$amounts, &$countryLabels, &$ordersCount, $metric) {
                foreach ($orders as $order) {
                    $ordersCount++;

                    $date = $order->paid_at->toDateString();
                    $country = $this->countryCode($order);
                    $countryLabels[$country] = $this->countryLabel($country);
                    $amounts[$date][$country] = ($amounts[$date][$country] ?? 0) + $this->amountCents($order, $metric);
                }
            });

        asort($countryLabels, SORT_NATURAL | SORT_FLAG_CASE);

        $rows = collect($amounts)
            ->map(function (array $countries, string $date) use ($countryLabels) {
                $normalizedCountries = [];

                foreach (array_keys($countryLabels) as $country) {
                    $normalizedCountries[$country] = $countries[$country] ?? 0;
                }

                return [
                    'date' => $date,
                    'countries' => $normalizedCountries,
                    'total_cents' => array_sum($normalizedCountries),
                ];
            })
            ->values()
            ->all();

        return [
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
            'metric' => $metric,
            'metric_label' => self::METRICS[$metric],
            'countries' => $countryLabels,
            'rows' => $rows,
            'total_cents' => array_sum(array_column($rows, 'total_cents')),
            'orders_count' => $ordersCount,
        ];
    }

    public function countryLabel(string $country): string
    {
        return [
            'IT' => 'Italia',
            'DE' => 'Germania',
            'ES' => 'Spagna',
            'FR' => 'Francia',
            'NL' => 'Paesi Bassi',
            'BE' => 'Belgio',
            'AT' => 'Austria',
            'CH' => 'Svizzera',
            'PT' => 'Portogallo',
            'GB' => 'Regno Unito',
            'UK' => 'Regno Unito',
            'US' => 'Stati Uniti',
            'XX' => 'Non indicato',
        ][$country] ?? $country;
    }

    private function countryCode(Order $order): string
    {
        $country = strtoupper(trim((string) data_get($order->shipping_address, 'country')));

        return $country !== '' ? $country : 'XX';
    }

    private function amountCents(Order $order, string $metric): int
    {
        if ($metric === 'net') {
            return max(0, (int) $order->total_cents - (int) $order->refunded_cents);
        }

        return (int) $order->total_cents;
    }
}
