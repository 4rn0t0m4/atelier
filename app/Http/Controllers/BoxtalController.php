<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BoxtalController extends Controller
{
    private function auth(): string
    {
        return base64_encode(
            config('shipping.boxtal.v3_access_key').':'.config('shipping.boxtal.v3_secret_key')
        );
    }

    public function mapToken()
    {
        $token = Cache::remember('boxtal_map_token', 3000, function () {
            return $this->fetchToken();
        });

        if (! $token) {
            return response()->json(['error' => 'Unable to get map token'], 500);
        }

        return response()->json(['accessToken' => $token]);
    }

    public function searchParcelPoints(Request $request)
    {
        $request->validate([
            'zipCode' => 'required|string|max:10',
            'city' => 'required|string|max:100',
            'country' => 'nullable|string|size:2',
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Basic '.$this->auth(),
            'Content-Type' => 'application/json',
        ])->post('https://api.boxtal.com/v2/parcel-point', [
            'networks' => config('shipping.boxtal.networks'),
            'address' => [
                'zipCode' => $request->input('zipCode'),
                'city' => $request->input('city'),
                'country' => $request->input('country', 'FR'),
            ],
        ]);

        if (! $response->successful()) {
            return response()->json(['error' => 'Recherche impossible', 'points' => []], 422);
        }

        $points = $response->json('nearbyParcelPoints', []);

        $dayLabels = [
            'MONDAY' => 'Lun', 'TUESDAY' => 'Mar', 'WEDNESDAY' => 'Mer',
            'THURSDAY' => 'Jeu', 'FRIDAY' => 'Ven', 'SATURDAY' => 'Sam', 'SUNDAY' => 'Dim',
        ];

        $results = collect($points)->map(function ($entry) use ($dayLabels) {
            $p = $entry['parcelPoint'] ?? $entry;
            $network = $p['network'] ?? '';

            $networkLabels = [
                'MONR_NETWORK' => 'Mondial Relay',
                'CHRP_NETWORK' => 'Chronopost',
            ];

            $openingDays = collect($p['openingDays'] ?? [])->map(function ($day) use ($dayLabels) {
                $slots = collect($day['openingPeriods'] ?? [])->map(function ($slot) {
                    return ($slot['openingTime'] ?? '').'-'.($slot['closingTime'] ?? '');
                })->join(', ');

                return [
                    'day' => $dayLabels[$day['weekday']] ?? $day['weekday'],
                    'slots' => $slots,
                ];
            })->values()->all();

            return [
                'code' => $p['code'] ?? '',
                'name' => $p['name'] ?? '',
                'network' => $network,
                'networkLabel' => $networkLabels[$network] ?? $network,
                'street' => $p['location']['street'] ?? '',
                'zipCode' => $p['location']['zipCode'] ?? '',
                'city' => $p['location']['city'] ?? '',
                'lat' => (float) ($p['location']['position']['latitude'] ?? 0),
                'lng' => (float) ($p['location']['position']['longitude'] ?? 0),
                'openingDays' => $openingDays,
            ];
        })->take(30)->values();

        return response()->json(['points' => $results]);
    }

    private function fetchToken(): ?string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic '.$this->auth(),
            'Content-Type' => 'application/json',
        ])->post(config('shipping.boxtal.token_url'));

        return $response->json('accessToken');
    }
}
