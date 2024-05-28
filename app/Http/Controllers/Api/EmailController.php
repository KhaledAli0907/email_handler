<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailStoreRequest;
use App\Models\Email;
use App\Models\SentEmail;
use App\Services\EmailService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    private $service;

    // Inject the email service
    public function __construct(EmailService $service)
    {
        $this->service = $service;
    }

    /**
     * Get all emails.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $emails = Email::all();
        return response()->json($emails);
    }

    /**
     * Get a specific email by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $email = Email::findOrFail($id);
        return response()->json($email);
    }

    /**
     * Send an email.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendEmail(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'sender' => 'required|email',
            'receiver' => 'required|email',
            'body' => 'required|string',
            'attachments' => 'sometimes|array'
        ]);

        $email = $this->service->sendEmailFunction($data);
        if (!$email)
            return response()->json(['message' => 'Can not send email']);

        return response()->json(['message' => 'Email sent successfully.'], 200);

    }

    /**
     * Reply to an email.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function replyEmail(Request $request, $id)
    {
        $originalEmail = Email::findOrFail($id);
        $request->merge([
            'subject' => 'Re: ' . $originalEmail->subject,
            'sender' => $originalEmail->receiver,
            'receiver' => $originalEmail->sender,
            'body' => $request->body,
        ]);
        return $this->sendEmail($request);
    }

    /**
     * Forward an email.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function forwardEmail(Request $request, $id)
    {
        $originalEmail = Email::findOrFail($id);
        $request->merge([
            'subject' => 'Fwd: ' . $originalEmail->subject,
            'sender' => $originalEmail->receiver,
            'body' => $request->body . '<br><br>---Original Message---<br>' . $originalEmail->body,
        ]);
        return $this->sendEmail($request);
    }

}
