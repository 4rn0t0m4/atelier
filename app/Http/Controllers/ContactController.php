<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageAdmin;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function show()
    {
        return view('contact');
    }

    public function send(Request $request)
    {
        // Honeypot anti-spam
        if ($request->filled('website')) {
            return back()->with('status', 'Votre message a bien été envoyé. Nous vous répondrons dans les meilleurs délais.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $contact = ContactMessage::create($data);

        Mail::to(config('mail.admin_address'))->send(new ContactMessageAdmin($contact));

        return back()->with('status', 'Votre message a bien été envoyé. Nous vous répondrons dans les meilleurs délais.');
    }
}
