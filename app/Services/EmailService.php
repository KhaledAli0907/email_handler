<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use App\Models\SentEmail; // Ensure you have a SentEmail model to save email records

class EmailService
{
    /**
     * Send an email with optional attachments.
     *
     * @param array $data Email data including subject, sender, receiver, body, and attachments.
     * @return SentEmail|bool The sent email object or false on failure.
     */
    public function sendEmailFunction(array $data): SentEmail
    {
        // Use Laravel's Mail facade to send an email
        Mail::send([], [], function ($message) use ($data) {
            $message->from($data['sender'])
                ->to($data['receiver'])
                ->subject($data['subject'])
                ->html($data['body']);

            // Attach files if provided
            try {
                if (!empty ($data['attachments']) && is_array($data['attachments'])) {
                    foreach ($data['attachments'] as $attachment) {
                        if (is_array($attachment) && isset ($attachment['filename'], $attachment['content'], $attachment['mime_type'])) {
                            // Decode the base64 encoded content
                            $content = base64_decode($attachment['content']);

                            // Attach the file to the email
                            $message->attachData(
                                $content,
                                $attachment['filename'],
                                ['mime' => $attachment['mime_type']]
                            );
                        } else {
                            // Throw an exception if attachment format is invalid
                            throw new \Exception('Invalid attachment format');
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log the exception message
                return response()->json(array ('Error' => $e->getMessage()), 401);
            }
        });

        // Create a record for the sent email
        $email = $this->createSendEmail($data);
        return $email;
    }

    /**
     * Save the sent email information to the database.
     *
     * @param array $data Email data including subject, sender, receiver, body, and attachments.
     * @return SentEmail The sent email object.
     */
    private function createSendEmail($data): sentEmail
    {
        // Instantiate a new SentEmail model
        $email = new SentEmail();
        $email->sender = $data['sender'];
        $email->receiver = $data['receiver'];
        $email->subject = $data['subject'];
        $email->body = $data['body'];

        // Encode attachments as JSON if they exist
        $email->attachments = $data['attachments'] ? json_encode(array_map(function ($attachment) {
            return [
                'filename' => $attachment['filename'],
                'mime_type' => $attachment['mime_type'],
                'content' => $attachment['content'],
            ];
        }, $data['attachments'])) : null;

        // Save the email record to the database
        $email->save();

        return $email;
    }
}
