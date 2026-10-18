<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function show(Link $link)
    {
        $room = ChatRoom::firstOrCreate(['link_id' => $link->id]);

        $messages = ChatMessage::where('chat_room_id', $room->id)
            ->with(['sender.role'])
            ->orderBy('created_at', 'asc')
            ->get();

        return view('chat.show', compact('link', 'room', 'messages'));
    }

    public function send(Request $request, Link $link)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:5120'], // Max 5MB
        ]);

        $room = ChatRoom::firstOrCreate(['link_id' => $link->id]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('chat_attachments', 'public');
        }

        $message = ChatMessage::create([
            'chat_room_id' => $room->id,
            'sender_id' => Auth::id(),
            'message' => $validated['message'],
            'attachment_path' => $attachmentPath,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message->load('sender.role'),
            ]);
        }

        return back()->with('success', 'Message sent.');
    }

    public function messages(Link $link)
    {
        $room = ChatRoom::firstOrCreate(['link_id' => $link->id]);

        $messages = ChatMessage::where('chat_room_id', $room->id)
            ->with(['sender.role'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }
}
