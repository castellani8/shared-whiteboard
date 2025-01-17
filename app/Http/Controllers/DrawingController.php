<?php

namespace App\Http\Controllers;

use App\Events\DrawingUpdated;
use Illuminate\Http\Request;

class DrawingController extends Controller
{

    public function broadcastDrawing(Request $request)
    {
        \Illuminate\Support\Facades\Broadcast::on('whiteboard')
            ->as('drawing.updated')
            ->with($request->all())
            ->sendNow();
    }

    public function broadcastStrokeEnd(Request $request)
    {
        \Illuminate\Support\Facades\Broadcast::on('whiteboard')
            ->as('stroke.end')
            ->with($request->all())
            ->sendNow();
    }
}
