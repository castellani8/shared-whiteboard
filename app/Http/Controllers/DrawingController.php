<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Broadcast;

class DrawingController extends Controller
{
    private const CHUNK_SIZE = 50;

    public function saveStroke(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'pass' => 'required|string|max:50',
            'strokeId' => 'required|string',
            'token' => 'required|string',
            'points' => 'required|array',
            'color' => 'required|string',
            'width' => 'required|numeric',
        ]);

        $key = "whiteboard:{$data['pass']}";

        $strokes = Cache::get($key, []);
        $strokes[$data['strokeId']] = [
            'points' => $data['points'],
            'color' => $data['color'],
            'width' => $data['width'],
            'timestamp' => now()->timestamp,
            'token' => $data['token'],
            'finished' => true
        ];
        Cache::put($key, $strokes, now()->addHours(24));

        $pointChunks = array_chunk($data['points'], self::CHUNK_SIZE);
        $totalChunks = count($pointChunks);

        foreach ($pointChunks as $index => $chunk) {
            Broadcast::on("whiteboard.{$data['pass']}")
                ->as('stroke.chunk')
                ->with([
                    'strokeId' => $data['strokeId'],
                    'token' => $data['token'],
                    'points' => $chunk,
                    'color' => $data['color'],
                    'width' => $data['width'],
                    'chunkIndex' => $index,
                    'totalChunks' => $totalChunks,
                ])
                ->sendNow();
        }

        return response()->json(['status' => 'success']);
    }

    public function getWhiteboardState(Request $request, $pass): \Illuminate\Http\JsonResponse
    {
        $key = "whiteboard:{$pass}";
        $strokes = Cache::get($key, []);
        return response()->json($strokes);
    }

    public function clearWhiteboard(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'pass' => 'required|string|max:50',
            'token' => 'required|string'
        ]);

        $key = "whiteboard:{$data['pass']}";
        Cache::forget($key);

        Broadcast::on("whiteboard.{$data['pass']}")
            ->as('whiteboard.cleared')
            ->with($data)
            ->sendNow();

        return response()->json(['status' => 'success']);
    }
}
