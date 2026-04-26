<?php

namespace App\Services;

class TrackingLinkService
{
    public function carriers(): array
    {
        return [
            'dhl' => 'DHL',
            'gls' => 'GLS',
            'ups' => 'UPS',
            'fedex' => 'FedEx',
            'poste' => 'Poste Italiane',
            'sda' => 'SDA',
            'brt' => 'BRT',
        ];
    }

    public function carrierName(string $carrier): string
    {
        return $this->carriers()[$carrier] ?? $carrier;
    }

    public function urlFor(string $carrier, string $trackingNumber): string
    {
        $trackingNumber = trim($trackingNumber);
        $encoded = rawurlencode($trackingNumber);

        return match ($carrier) {
            'dhl' => 'https://www.dhl.com/it-it/home/tracking/tracking-express.html?submit=1&tracking-id='.$encoded,
            'gls' => 'https://gls-group.com/IT/it/servizi-online/ricerca-spedizioni?match='.$encoded,
            'ups' => 'https://www.ups.com/track?tracknum='.$encoded,
            'fedex' => 'https://www.fedex.com/fedextrack/?trknbr='.$encoded,
            'poste' => 'https://www.poste.it/cerca/index.html#/risultati-spedizioni/'.$encoded,
            'sda' => 'https://www.sda.it/wps/portal/Servizi_online/dettaglio-spedizione?locale=it&tracing.letteraVettura='.$encoded,
            'brt' => 'https://vas.brt.it/vas/sped_numspe_par.htm?lang=it&spediz='.$encoded,
            default => '',
        };
    }
}
