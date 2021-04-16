<?php

namespace App\Service;


use Carbon\Carbon;
use App\GmailAuthData;
use App\GmailAttachment;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Dacastro4\LaravelGmail\Facade\LaravelGmail;
use Dacastro4\LaravelGmail\Services\Message\Mail;

class GmailApiService {

    protected $request;
    private $storageAttachments = "app" . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "gmail" . DIRECTORY_SEPARATOR;
    private $composeAttachments = "gmail" . DIRECTORY_SEPARATOR . "attachments";

    public function __construct(Request $request)
    {
        $this->request = $request;
    }


    public function formatEmailList($emails)
    {
        $all = [];
        $explodedEmails = explode(',', $emails);

        foreach ($explodedEmails as $email)
        {
            $item = [];

            preg_match('/<(.*)>/', $email, $matches);

            $item['email'] = str_replace(' ', '', isset($matches[1]) ? $matches[1] : $email);

            $name = preg_replace('/ <(.*)>/', '', $email);

            if (Str::startsWith($name, ' '))
            {
                $name = substr($name, 1);
            }

            $item['name'] = str_replace("\"", '', $name ?: null);

            $all[] = $item;
        }

        return $all;
    }



    public function validate_email($request)
    {
        $emails=['to'=>[],'cc'=>[], 'bcc'=>[]];

        $to = explode(',', $request->to);
        $cc = explode(',', $request->cc);
        $bcc = explode(',', $request->bcc);

        foreach ($to as $item)
        {
            $i = trim($item);
            if(filter_var($i, FILTER_VALIDATE_EMAIL))
            {
                $emails['to'][]  = trim($item);
            }
        }

        foreach ($cc as $item)
        {
            $i = trim($item);
            if(filter_var($i, FILTER_VALIDATE_EMAIL))
            {
                $emails['cc'][]  = trim($item);
            }
        }

        foreach ($bcc as $item)
        {
            $i = trim($item);
            if(filter_var($i, FILTER_VALIDATE_EMAIL))
            {
                $emails['bcc'][]  = trim($item);
            }
        }

        return $emails;
    }

    public function getFolders( $message, $exclude=[] )
    {
        $request = $this->request;
        $gmailData = GmailAuthData::where('user_id', $request->login_user_id)->latest()->first();

        $labels = json_decode($gmailData->setup_parts, true);
        $labels = $labels['labels'];

        $excluded_list = ["SENT", "INBOX", "TRASH", "DRAFT", "IMPORTANT", "CHAT", "SPAM", "CATEGORY_FORUMS", "CATEGORY_UPDATES", "CATEGORY_PERSONAL", "CATEGORY_PROMOTIONS", "CATEGORY_SOCIAL", "STARRED", "UNREAD",];

        $excluded_list = array_unique((array_merge($excluded_list, $exclude)));

        $boxes = [ "INBOX"=>"INBOX", "DRAFT"=>"DRAFT", "SENT"=>"SENT", "TRASH"=>"TRASH" ];

        foreach ( $labels as $label )
        {
            $name = $label['name'];
            $id = $label['id'];

            if ( ! in_array( $name, $excluded_list ) )
            {
                $boxes[$id] = $label['name'];
            }
        }

        $boxes_with_keys = [];
        foreach ( $boxes as $key => $box )
        {
            if(!in_array($box, $exclude))
            {
                $boxes_with_keys[ $key ] = ucwords( strtolower( $box ) );
            }
        }

        return $boxes_with_keys;
    }

    public function sanitizeText( $text = "", $length = 75 )
    {
        $text = str_replace( "\r\n", " ", $text );
        $text = trim( substr( $text, 0, $length ) );
        $text .= "...";

        return $text;
    }

    public function searchByTransactionId($id)
    {
        $messageObj = LaravelGmail::message();
        $folders    = $this->getFolders( $messageObj );

        $subject = "Transaction #{$id}";

        try
        {
            $messages = $messageObj->subject($subject)->preload()->all();
        } catch ( Exception $e )
        {
            $messages = [];
        }

        $data = [];

        foreach ( $messages as $message )
        {
            $d                    = [];
            $d["id"]              = $message->getId();
            $d["thread_id"]       = $message->getThreadId();
            $d["excerpt"]         = $this->sanitizeText( $message->getPlainTextBody() );
            $d["subject"]         = $message->getSubject();
            $d["from"]            = $this->sanitizeFrom( $message->getFrom() );
            $d["date"]            = $message->getDate()->format( 'M d, Y' );
            $d["is_unread"]       = $this->checkUnread( $message->getLabels() );
            $d["has_attachments"] = $message->hasAttachments();

            $data[] = $d;
        }

        return [ 'data' => $data, 'folders' => $folders ];

    }

    public function checkUnread( $labels = [] )
    {
        if ( in_array( "UNREAD", $labels ) ):
            return true;
        endif;

        return false;
    }

    public function sanitizeFrom( $from )
    {
        if ( empty( $from['name'] ) ):
            return $from['email'];
        endif;

        return $from['name'];
    }

    public function saveAttachmentData( $message_id, $file_name, $type = '' )
    {
        $obj = GmailAttachment::where( 'message_id', $message_id )->where( 'path', $file_name )->where( 'type', $type )->first();

        if( !empty($obj) )
        {
            $file = "public/gmail/{$obj->message_id}/{$obj->path}";
            $file_exists = Storage::disk('local')->exists($file);

            if(!$file_exists)
            {
                return ['save'=>true, 'data'=>$obj];
            }

            return ['save'=>false, 'data'=>$obj];
        }

        $obj             = new GmailAttachment();
        $obj->message_id = $message_id;
        $obj->path       = $file_name;
        $obj->type       = $type;
        $obj->save();

        return ['save'=>true, 'data'=>$obj];
    }

    public function accessStorageAssets($fileObj)
    {
        $file = [];
        $file['name'] = $fileObj->path;
        $file['path'] = asset('storage/gmail/'.$fileObj->message_id.'/'.$fileObj->path);
        $file['type'] = pathinfo($fileObj->path, PATHINFO_EXTENSION);

        return $file;
    }

    public function forwardBody( $data )
    {
        $toString = "";
        foreach ( $data['to'] as $to )
        {
            $toString .= "<span> {$to['name']} &lt;{$to['email']}&gt;</span><br />";
        }

        $template = <<<HTML
        <br /> <br /><span>---------- Forwarded message ---------</span><br />
        <span>From: {$data['from_with_email']['name']} &lt;{$data['from_with_email']['email']}&gt;</span><br />
        <span>Date: {$data['date_time']}</span><br />
        <span>Subject: {$data['subject']}</span><br />
        {$toString}
        <br />
        <br />
HTML;
        return $template . $data['body'];
    }

    public function time_passed( $message )
    {
        $date_now    = Carbon::now();
        $date        = $message->getDate();
        $passedHours = $date->diffInHours( $date_now );

        $time = "";
        if ( $passedHours > 24 )
        {
            $time = $date->diffInDays( $date_now );
            $time .= $time > 1 ? ' days ago' : ' day ago';
        } else
        {
            $time = $passedHours;
            $time .= $time > 1 ? ' hours ago' : ' hour ago';
        }

        return $time;
    }

    public function setupSessionData()
    {
        $request = $this->request;
        $message = LaravelGmail::message();
        $user    = $message->service;
        $profile = $user->users->getProfile( 'me' );

        $labels = $message->service->users_labels->listUsersLabels( 'me' )->getLabels();

        return GmailAuthData::where('user_id', $request->login_user_id)->latest()->update(['setup_parts' => json_encode(['labels'=>$labels, 'profile'=>$profile])]);
    }

    public function findLabelByLabel( $type, $id, $per_page, $page )
    {
        $data = [];

        $messageObj = LaravelGmail::message();

        $folders = $this->getFolders( $messageObj );
        $subject = "#{$id}";

        try
        {
            if($type)
            {
                $messages = $messageObj->in( $folders[$type] );
            }
            if($id)
            {
                $messages = $messageObj->subject( $subject );
            }
            $total = count($messages->all());
            if($total > 0)
            {
                if($per_page)
                {
                    $messages = $messages->take($per_page);
                }

                $messages = $messages->preload()->all($page);
            } else
            {
                $messages = [];
            }
        } catch ( Exception $e )
        {
            $messages = [];
        }

        $unreadNumber = 0;
        foreach ( $messages as $message )
        {
            $d                    = [];
            $d["id"]              = $message->getId();
            $d["thread_id"]       = $message->getThreadId();
            $d["excerpt"]         = utf8_encode($this->sanitizeText( $message->getPlainTextBody() ));
            $d["subject"]         = utf8_encode($message->getSubject());
            $d["from"]            = utf8_encode($this->sanitizeFrom( $message->getFrom() ));
            $d["date"]            = $message->getDate()->format( 'M d, Y' );
            $d["is_unread"]       = $this->checkUnread( $message->getLabels() );
            $d["has_attachments"] = $message->hasAttachments();

            if ( ! empty( $d["is_unread"] ) ):
                ++ $unreadNumber;
            endif;

            $data[] = $d;
        }

        $pageToken = '';
        if($messageObj->pageToken) {
            $pageToken = $messageObj->pageToken;
        }
        return [
            'data'           => $data,
            'unread_number'  => $unreadNumber,
            'folders'        => $folders,
            'current_folder' => $type,
            'meta'           => [
                'total' => $total,
                'current_page' => $page,
                'page' => $pageToken,
                'per_page' => $per_page,
            ],
        ];
    }

    public function readEmailById( $id )
    {
        $message = LaravelGmail::message()->preload()->get( $id );
        $message->markAsRead();

        $data                    = [];
        $data["id"]              = $message->getId();
        $data["thread_id"]       = $message->getThreadId();
        $data["excerpt"]         = utf8_encode($this->sanitizeText( $message->getPlainTextBody() ));
        $data["body"]            = utf8_encode($message->getHtmlBody());
        $data["from"]            = $this->sanitizeFrom( $message->getFrom() );
        $data["date"]            = $message->getDate()->format( 'd M, Y' );
        $data["is_unread"]       = $this->checkUnread( $message->getLabels() );
        $data["labels"]          = $this->filterLabels( $message, $message->getLabels() );
        $data["has_attachments"] = $message->hasAttachments();
        $data["attachments"]     = $this->attachments( $message );
        $data["subject"]         = utf8_encode($message->getSubject());
        $data["from_with_email"] = $message->getFrom();
        $data["to"]              = $message->getTo();
        $data["date_time"]       = $message->getDate()->format( 'd M, Y H:i' );
        $data["time_passed"]     = $this->time_passed( $message );
        $data["reply_to"]        = $message->getReplyTo();
        $data["cc"]              = $message->getCc();
        $data["bcc"]             = $message->getBcc();

        return $data;
    }

    public function filterLabels( $message, $labels )
    {
        $primary_labels = [];
        $exclude        = [ 'CATEGORY_PERSONAL', 'IMPORTANT', 'UNREAD', 'CATEGORY_UPDATES' ];
        
        foreach ( $labels as $key => $label )
        {
            if ( in_array( $label, $exclude ) )
            {
                continue;
            }

            $primary_labels[] = $label;
        }

        $original_primary_labels = [];

        foreach ( $primary_labels as $primary_label )
        {
            try
            {
                $original_primary_labels[] = $message->service->users_labels->get( 'me', $primary_label )->name;
            } catch ( Exception $e )
            {}
        }

        return $original_primary_labels;
    }

    public function attachments( $message )
    {
        $files = [];
        if ( $message->hasAttachments() )
        {
            foreach ( $message->getAttachments() as $attachment )
            {
                $fileObj = $this->saveAttachmentData( $message->getId(), $attachment->getFileName(), $type = 'storage' );

                if ( $fileObj['save'] )
                {
                    $folder = "public/gmail/{$fileObj['data']->message_id}/";
                    $attachment->saveAttachmentTo( $folder, $fileObj['data']->path, $disk = 'local' );
                }

                $files[] = $this->accessStorageAssets( $fileObj['data'] );
            }
        }

        return $files;
    }

    public function fetchAttachments( $message_id, $public = false, $type = '' )
    {
        $attachments = [];

        if ( ! empty( $message_id ) )
        {
            $localFiles = GmailAttachment::where( 'message_id', $message_id )->get();

            if ( ! empty( $localFiles ) )
            {
                foreach ( $localFiles as $key => $file )
                {
                    if ( $type === 'storage' )
                    {
                        $path = storage_path( $this->storageAttachments
                                              . $file->message_id
                                              . DIRECTORY_SEPARATOR
                                              . $file->path );
                        if ( file_exists( $path ) )
                        {
                            $attachments[] = $path;
                        }
                    } else
                    {
                        $path = $this->composeAttachments
                                . DIRECTORY_SEPARATOR
                                . $file->message_id
                                . DIRECTORY_SEPARATOR
                                . $file->path;

                        if ( file_exists( public_path( $path ) ) )
                        {
                            if ( $public )
                            {
                                $attachments[ $file->path ] = asset( $path );
                            } else
                            {
                                $attachments[] = public_path( $path );
                            }
                        }
                    }
                }
            }
        }

        return $attachments;
    }

    public function deleteAttachments( $message_id, $attachments )
    {
        if ( ! empty( $message_id ) )
        {
            GmailAttachment::where( 'message_id', $message_id )->delete();

            foreach ( $attachments as $attachment )
            {
                if ( file_exists( $attachment ) )
                {
                    unlink( $attachment );
                }
            }

            $dir = public_path( $this->composeAttachments
                                . DIRECTORY_SEPARATOR
                                . $message_id );

            if ( file_exists( $dir ) )
            {
                rmdir( $dir );
            }
        }
    }

    public function composeGenerateId( $messageId )
    {
        $gmailData = GmailAuthData::where('user_id', $this->request->login_user_id)->latest()->first();
        $email = $gmailData->email;

        $messageId = empty( $messageId ) ? base64_encode( uniqid( $email ) ) : $messageId;
        $directory = public_path( $this->composeAttachments ) . DIRECTORY_SEPARATOR . $messageId;
        
        if ( ! file_exists( $directory ) )
        {
            mkdir( $directory );
        }

        return $messageId;
    }

    public function sendEmail( $emails, $message_id, $subject, $message, $headers, $type, $thread_id = '' )
    {
        $emails     = empty( $emails ) ? [] : $emails;
        $message_id = empty( $message_id ) ? '' : $message_id;
        $subject    = empty( $subject ) ? '' : $subject;
        $message    = empty( $message ) ? '' : $message;
        $headers    = empty( $headers ) ? '' : $headers;


        $attachments = $this->fetchAttachments( $message_id );

        $delete = true;
        $is_draft = ($type==='draft') ? true : false;

        if ( in_array($type, ['forward', 'draft']) )
        {
            $attachments2 = $this->fetchAttachments( $thread_id, false, 'storage' );
            $attachments  = array_merge( $attachments, $attachments2 );
            $delete       = false;
            $type         = 'send';
        }

        switch ( $type ):
            case 'send':
                $mail = new Mail;
                break;
            case 'reply':
                $mail = LaravelGmail::message()->get( $thread_id );
                break;
        endswitch;

        $mail->subject( $subject );
        if ( ! empty( $emails['to'] ) ): $mail->to( $emails['to'] ); endif;
        if ( ! empty( $emails['cc'] ) ): $mail->cc( $emails['cc'] ); endif;
        if ( ! empty( $emails['bcc'] ) ): $mail->bcc( $emails['bcc'] ); endif;

        if ( ! empty( $attachments ) )
        {
            foreach ( $attachments as $attachment )
            {
                $mail->attach( $attachment );
            }
        }

        
        //$mail->setHeader( 'transaction_id', $headers['transaction_id'] );
        
        $gmailData = GmailAuthData::where('user_id', $this->request->login_user_id)->latest()->first();
        $email = $gmailData->email;
        
        $mail->from($email , 'Repylot' );
        $mail->message( $message );


        switch ( $type ):
            case 'send':
                $mail->send();
                break;
            case 'reply':
                $mail->reply();
                break;
        endswitch;

        if ( $delete ) {
            // Clear the Junk
            $this->deleteAttachments( $message_id, $attachments );
        }

        if($is_draft){
            $draftObj = LaravelGmail::message()->preload()->get( $thread_id );
            $draftObj->sendToTrash();
            $draftObj->removeFromTrash();
        }

        $data = [
            'emails' => $emails,
            'message_id' => $message_id
        ];
        return $data;
    }

    public function composeAttchSave( $request )
    {
        $files     = $request->file( 'attachments' );
        $messageId = $this->composeGenerateId( $request->message_id );

        foreach ( $files as $file ) {

            $file->move( $this->composeAttachments . DIRECTORY_SEPARATOR . $messageId, $file->getClientOriginalName() );

            $fileExists = GmailAttachment::where( 'message_id', $messageId )->where( 'path', $file->getClientOriginalName() )->first();

            if ( ! empty( $fileExists ) )
            {
                continue;
            }

            $this->saveAttachmentData( $messageId, $file->getClientOriginalName() );

        }

        $attachments = $this->fetchAttachments( $messageId );
        $data = [
            'attachments' => $attachments,
            'message_id' => $messageId,
        ];

        return $data;
    }

    public function composeAttchDelete( $request )
    {
        $message_id = $request->message_id;
        $file       = $request->file;

        if ( ! empty( $message_id ) )
        {
            $file = GmailAttachment::where( 'message_id', $message_id )->where( 'path', $file )->first();

            if ( ! empty( $file ) )
            {
                $path = $this->composeAttachments
                        . DIRECTORY_SEPARATOR
                        . $file->message_id
                        . DIRECTORY_SEPARATOR
                        . $file->path;
                $path = public_path( $path );

                if ( file_exists( $path ) )
                {
                    unlink( $path );
                    $file->delete();
                }
            }
        } else {
            abort(422, 'Invalid request!');
            dd($request);
        }

        $data = [
            'file' => $file,
            'message_id' => $message_id
        ];

        return $data;
    }
    
}








