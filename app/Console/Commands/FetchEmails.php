<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Webklex\IMAP\Facades\Client;
use App\Models\Email;
use App\Models\Category;
use App\Models\Keyword;
use Carbon\Carbon;

class FetchEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch emails from IMAP server';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $client = Client::account('default');
        $client->connect();

        $folder = $client->getFolder('INBOX');
        $messages = $folder->messages()->all()->get();

        foreach ($messages as $message) {
            $email = new Email();
            $email->subject = $message->getSubject();
            $email->sender = $message->getFrom()[0]->mail;
            $email->receiver = $message->getTo()[0]->mail;
            $email->body = $message->getTextBody();
            $email->date_received = Carbon::parse($message->getDate());
            // $email->is_read = $message->hasFlag('SEEN');
            $flags = (array) $message->getFlags();
            $email->is_read = in_array('SEEN', $flags);
            $email->category_id = $this->detectCategory($message->getTextBody());
            $email->save();
        }

        $this->info('Emails fetched successfully.');
    }

    private function detectCategory($body)
    {
        $keywords = Keyword::all();
        foreach ($keywords as $keyword) {
            if (stripos($body, $keyword->keyword) !== false) {
                return $keyword->category_id;
            }
        }
        return null;
    }
}
