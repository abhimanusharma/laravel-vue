<?php

namespace App\Service;


use App\GmailAttachment;
use Carbon\Carbon;
use Dacastro4\LaravelGmail\Facade\LaravelGmail;
use Illuminate\Support\Str;

class GmailApiService {


    public function __construct() {

    }


    public function formatEmailList($emails)
    {
        $all = [];
        $explodedEmails = explode(',', $emails);

        foreach ($explodedEmails as $email) {

            $item = [];

            preg_match('/<(.*)>/', $email, $matches);

            $item['email'] = str_replace(' ', '', isset($matches[1]) ? $matches[1] : $email);

            $name = preg_replace('/ <(.*)>/', '', $email);

            if (Str::startsWith($name, ' ')) {
                $name = substr($name, 1);
            }

            $item['name'] = str_replace("\"", '', $name ?: null);

            $all[] = $item;

        }

        return $all;
    }



    public function validate_email($request){

        $emails=['to'=>[],'cc'=>[], 'bcc'=>[]];

        $to = explode(',', $request->to);
        $cc = explode(',', $request->cc);
        $bcc = explode(',', $request->bcc);

        foreach ($to as $item){
            $i = trim($item);
            if(filter_var($i, FILTER_VALIDATE_EMAIL)){
                $emails['to'][]  = trim($item);
            }
        }

        foreach ($cc as $item){
            $i = trim($item);
            if(filter_var($i, FILTER_VALIDATE_EMAIL)){
                $emails['cc'][]  = trim($item);
            }
        }

        foreach ($bcc as $item){
            $i = trim($item);
            if(filter_var($i, FILTER_VALIDATE_EMAIL)){
                $emails['bcc'][]  = trim($item);
            }
        }


        return $emails;

    }



    public function getFolders( $message, $exclude=[] ) {

//        $labels = $message->service->users_labels->listUsersLabels( 'me' )->getLabels();
        $labels = session('setup_parts')['labels'];

        $excluded_list = [
            "SENT",
            "INBOX",
            "TRASH",
            "DRAFT",
            "IMPORTANT",
            "CHAT",
            "SPAM",
            "CATEGORY_FORUMS",
            "CATEGORY_UPDATES",
            "CATEGORY_PERSONAL",
            "CATEGORY_PROMOTIONS",
            "CATEGORY_SOCIAL",
            "STARRED",
            "UNREAD",
        ];

        $excluded_list = array_unique((array_merge($excluded_list, $exclude)));

        $boxes = [ "INBOX"=>"INBOX", "DRAFT"=>"DRAFT", "SENT"=>"SENT", "TRASH"=>"TRASH" ];

        foreach ( $labels as $label ) {
            $name = $label->getName();
            $id = $label->getId();

            if ( ! in_array( $name, $excluded_list ) ) {
                $boxes[$id] = $label->getName();
            }
        }

        $boxes_with_keys = [];
        foreach ( $boxes as $key => $box ) {
            if(!in_array($box, $exclude)){
                $boxes_with_keys[ $key ] = ucwords( strtolower( $box ) );
            }
        }

        return $boxes_with_keys;
    }

    public function sanitizeText( $text = "", $length = 75 ) {

        $text = str_replace( "\r\n", " ", $text );
        $text = trim( substr( $text, 0, $length ) );
        $text .= "...";

        return $text;

    }

    public function searchByTransactionId($id){


        $data = [];

        $messageObj = LaravelGmail::message();
        $folders    = $this->getFolders( $messageObj );

        $subject = "Transaction Id: #{$id}";

        try {
            $messages = $messageObj->subject($subject)->preload()->all();
        } catch ( Exception $e ) {
            $messages = [];
        }

        foreach ( $messages as $message ) {

            $d                    = [];
            $d["id"]              = $message->getId();
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

    public function checkUnread( $labels = [] ) {
        if ( in_array( "UNREAD", $labels ) ):
            return true;
        endif;

        return false;
    }

    public function sanitizeFrom( $from ) {
        if ( empty( $from['name'] ) ):
            return $from['email'];
        endif;

        return $from['name'];
    }

    public function saveAttachmentData( $message_id, $file_name, $type = '' ) {

        $obj = GmailAttachment::where( 'message_id', $message_id )->where( 'path', $file_name )->where( 'type', $type )->first();

        if(!empty($obj)){return ['save'=>false, 'data'=>$obj];}

        $obj             = new GmailAttachment;
        $obj->message_id = $message_id;
        $obj->path       = $file_name;
        $obj->type       = $type;
        $obj->save();
        return ['save'=>true, 'data'=>$obj];
    }

    public function accessStorageAssets($fileObj){
        $file = [];
        $file['name']=$fileObj->path;
        $file['path']=asset('storage/'.$fileObj->message_id.'/'.$fileObj->path);
        $file['type']=pathinfo($fileObj->path, PATHINFO_EXTENSION);

        return $file;
    }


    public function forwardBody( $data ) {

        $toString = "";
        foreach ( $data['to'] as $to ) {
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

    public function time_passed( $message ) {
        $date_now    = Carbon::now();
        $date        = $message->getDate();
        $passedHours = $date->diffInHours( $date_now );

        $time = "";
        if ( $passedHours > 24 ) {
            $time = $date->diffInDays( $date_now );
            $time .= $time > 1 ? ' days ago' : ' day ago';
        } else {
            $time = $passedHours;
            $time .= $time > 1 ? ' hours ago' : ' hour ago';
        }

        return $time;
    }


}








