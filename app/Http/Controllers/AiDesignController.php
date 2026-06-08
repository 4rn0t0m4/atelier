<?php

namespace App\Http\Controllers;

use App\Services\AiDesignService;
use Illuminate\Http\Request;

class AiDesignController extends Controller
{
    public function generate(Request $request, AiDesignService $service)
    {
        $request->validate([
            'description' => 'required|string|max:500',
            'product_id' => 'required|exists:products,id',
        ]);

        $sessionKey = 'ai_design_count_' . $request->product_id;
        $count = session($sessionKey, 0);

        if ($count >= 3) {
            return response()->json([
                'error' => 'Vous avez atteint le nombre maximum de générations (3). Veuillez choisir parmi les propositions déjà générées.',
            ], 429);
        }

        try {
            $result = $service->generate($request->description);

            session([$sessionKey => $count + 1]);

            session()->push('ai_designs_' . $request->product_id, [
                'image_url' => $result['image_url'],
                'image_path' => $result['image_path'],
            ]);

            return response()->json([
                'image_url' => $result['image_url'],
                'remaining' => 3 - ($count + 1),
            ]);
        } catch (\RuntimeException $e) {
            report($e);

            return response()->json([
                'error' => 'La génération a échoué. Veuillez réessayer.',
            ], 500);
        }
    }
}
