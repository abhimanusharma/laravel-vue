<?php

namespace App\Http\Controllers\Gmail;

use App\EmailTemplate;
use App\GmailAttachment;
use App\Service\GmailApiService;
use App\Http\Controllers\Controller;

use Exception;
use Swift_Message;
use Swift_Attachment;
use Google_Service_Gmail_Draft;
use Google_Service_Gmail_Label;
use Google_Service_Gmail_Message;
use Illuminate\Http\Request;
use Dacastro4\LaravelGmail\Facade\LaravelGmail;
use Dacastro4\LaravelGmail\Services\Message\Mail;

class WebController extends Controller {


    private $storageAttachments = "app" . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR;
    private $composeAttachments = "composeAttachments";

    protected $gmail;

    public function __construct( GmailApiService $gmail ) {
        $this->gmail = $gmail;
    }

    public function index() {

        return view( 'gmail.index' );
    }

    public function login() {

        return LaravelGmail::redirect();
    }

    public function logout() {
        try {

            session()->forget( 'gmail.me' );

            LaravelGmail::logout(); //It returns exception if fails

            return redirect()->to( '/' );
        } catch ( Exception $e ) {

            return $e->getMessage();
        }
    }

    public function gmailCallback() {
        LaravelGmail::makeToken();
        $this->setupSessionData();



        return redirect()->route( 'dashboard', [ 'type' => 'INBOX' ] );
    }

    private function setupSessionData() {
        $message = LaravelGmail::message();
        $user    = $message->service;
        $profile = $user->users->getProfile( 'me' );

        $labels = $message->service->users_labels->listUsersLabels( 'me' )->getLabels();

        session( [ 'gmail.me' => $profile, 'setup_parts'=> ['labels'=>$labels] ] );
    }


    private function findLabelByLabel( $type ) {
        $data = [];

        $messageObj = LaravelGmail::message();

        $folders = $this->gmail->getFolders( $messageObj );

        try {
            $messages = $messageObj->preload()->in( $folders[$type] )->all();
        } catch ( Exception $e ) {
            $messages = [];
        }


        $unreadNumber = 0;
        foreach ( $messages as $message ) {

            $d                    = [];
            $d["id"]              = $message->getId();
            $d["excerpt"]         = $this->gmail->sanitizeText( $message->getPlainTextBody() );
            $d["subject"]         = $message->getSubject();
            $d["from"]            = $this->gmail->sanitizeFrom( $message->getFrom() );
            $d["date"]            = $message->getDate()->format( 'M d, Y' );
            $d["is_unread"]       = $this->gmail->checkUnread( $message->getLabels() );
            $d["has_attachments"] = $message->hasAttachments();

            if ( ! empty( $d["is_unread"] ) ):
                ++ $unreadNumber;
            endif;


            $data[] = $d;
        }


        return [
            'data'           => $data,
            'unread_number'  => $unreadNumber,
            'folders'        => $folders,
            'current_folder' => $type
        ];
    }



    public function dashboard( $type ) {

        if ( ! LaravelGmail::check() ) {
            return redirect()->route( 'home' );
        }


        $result = $this->findLabelByLabel( $type );

        $data = [
            'messages'       => $result['data'],
            'folders'        => $result['folders'],
            'unreadNumber'   => $result['unread_number'],
            'current_folder' => $result['current_folder']

        ];


        return view( 'gmail.dashboard', compact( 'data' ) );
    }

    public function draft($id){

        $data            = [];
        $data['message'] = $this->readEmailById( $id );
        $data['type']    = 'gmail.draft.send';


        return view( 'gmail.forward', compact( 'data' ) );

    }

    public function draftSend(Request $request){
        $this->validate( $request, [ 'to' => 'required' ] );

        $emails  = $this->gmail->validate_email( $request );


        $this->sendEmail( $emails, $request->message_id, $request->subject, $request->message,
            'draft', $request->thread_id );


        return redirect()->route( 'dashboard', [ 'type' => 'INBOX' ] );

    }


    private function readEmailById( $id ) {
        $message = LaravelGmail::message()->preload()->get( $id );

//        $messageObj->preload()->in( $folders[$type] )->all();
//        $conversations_array= $message->service->users_threads->get('me', $message->getId());
//        dd($conversations_array);

//        if(count($conversations_array)>0){
//
//            foreach ($conversations_array as $key => $item){
//
//                dd(get_class_methods($item->getPayload()->getBody()));
//
//                dd(get_class_methods($item));
//                dd(get_class_methods($item));
//            }
//
//        }


        $message->markAsRead();


        $data                    = [];
        $data["id"]              = $message->getId();
        $data["excerpt"]         = $this->gmail->sanitizeText( $message->getPlainTextBody() );
        $data["body"]            = $message->getHtmlBody();
        $data["from"]            = $this->gmail->sanitizeFrom( $message->getFrom() );
        $data["date"]            = $message->getDate()->format( 'd M, Y' );
        $data["is_unread"]       = $this->gmail->checkUnread( $message->getLabels() );
        $data["labels"]          = $this->filterLabels( $message, $message->getLabels() );
        $data["has_attachments"] = $message->hasAttachments();
        $data["attachments"]     = $this->attachments( $message );

        $data["subject"]         = $message->getSubject();
        $data["from_with_email"] = $message->getFrom();
        $data["to"]              = $message->getTo();
        $data["date_time"]       = $message->getDate()->format( 'd M, Y H:i' );
        $data["time_passed"]     = $this->gmail->time_passed( $message );
        $data["reply_to"]        = $message->getReplyTo();
        $data["cc"]              = $message->getCc();
        $data["bcc"]             = $message->getBcc();

        return $data;
    }



    public function singleEmail( $type, $id ) {

//        $result = $this->readEmailById( $id );

        $message = LaravelGmail::message();
        $metadata_messages = $message->service->users_threads->get('me', $id,['format'=>'METADATA'])['messages'];

        $conversations = [];
        if(count($metadata_messages)>0){
            foreach ($metadata_messages as $metadata_message){
                $conversations[] = $this->readEmailById($metadata_message->getId());
            }
        }



        $folders = $this->gmail->getFolders( $message, [ $type, 'DRAFT', 'SENT', 'TRASH' ] );


        $data = [
            'messages'       => $conversations,
            'folders'        => $folders,
            'current_folder' => $type,
            'message_id'     => $id
        ];

        return view( 'gmail.single-email', compact( 'data' ) );
    }

    public function composeNew() {

        $data              = [];
        $data['templates'] = EmailTemplate::orderBy( 'id' )->get();
        $data['type']      = 'gmail.send';

        return view( 'gmail.compose.email-form', compact( 'data' ) );
    }

    public function composeSend( Request $request ) {

        $this->validate( $request, [ 'to' => 'required' ] );

        $emails = $this->gmail->validate_email( $request );

        $this->sendEmail( $emails, $request->message_id, $request->subject, $request->message, 'send' );

        return redirect()->route( 'dashboard', [ 'type' => 'INBOX' ] );
    }


    public function replyEmail( $id ) {

        $message = $this->readEmailById( $id );

        $data            = [];
        $data['message'] = $message;
        $data['type']    = 'gmail.reply';

        return view( 'gmail.reply-to', compact( 'data' ) );
    }

    public function forwardEmail( $id ) {

        $data            = [];
        $data['message'] = $this->readEmailById( $id );
        $data['body']    = $this->gmail->forwardBody( $data['message'] );
        $data['type']    = 'gmail.forward';

        return view( 'gmail.forward', compact( 'data' ) );
    }


    public function filterLabels( $message, $labels ) {
        $primary_labels = [];
        $exclude        = [ 'CATEGORY_PERSONAL', 'IMPORTANT', 'UNREAD', 'CATEGORY_UPDATES' ];
        foreach ( $labels as $key => $label ) {
            if ( in_array( $label, $exclude ) ) {
                continue;
            }

            $primary_labels[] = $label;
        }


        $original_primary_labels = [];


        foreach ( $primary_labels as $primary_label ) {

            try {
                $original_primary_labels[] = $message->service->users_labels->get( 'me', $primary_label )->name;
            } catch ( Exception $e ) {

            }

        }

        return $original_primary_labels;
    }

    public function attachments( $message ) {
        $files = [];
        if ( $message->hasAttachments() ) {


            foreach ( $message->getAttachments() as $attachment ) {

                $fileObj = $this->gmail->saveAttachmentData( $message->getId(), $attachment->getFileName(), $type =
                    'storage' );

                if ( $fileObj['save'] ) {
                    $folder = "public/{$fileObj['data']->message_id}/";
                    $attachment->saveAttachmentTo( $folder, $fileObj['data']->path, $disk = 'local' );
                }

                $files[] = $this->gmail->accessStorageAssets( $fileObj['data'] );


            }


        }

        return $files;
    }


    public function emailDelete( $id ) {

        $message = LaravelGmail::message()->preload()->get( $id );

        $labels = $this->filterLabels( $message, $message->getLabels() );

        if ( in_array( "TRASH", $labels ) ) {
            $message->removeFromTrash();
            $success = "Conversation deleted from Trash!!!";
        } else {
            $message->sendToTrash();
            $success = "Conversation moved to Trash!!!";
        }

        session()->flash( 'success', $success );

        return redirect()->route( 'dashboard', [ 'type' => 'INBOX' ] );

    }

    public function emailRestore( $id ) {
        $message = LaravelGmail::message()->preload()->get( $id );
        $labels  = $this->filterLabels( $message, $message->getLabels() );

        if ( in_array( "TRASH", $labels ) ) {
            $message->removeLabel( 'TRASH' );
        }

        session()->flash( 'success', 'Conversation Restored!!!' );

        return redirect()->route( 'dashboard', [ 'type' => 'INBOX' ] );

    }

    public function createLabel( Request $request ) {

        $this->validate( $request, [ 'labelName' => 'required' ] );

        $label = $request->labelName;

        $message = LaravelGmail::message();

        $labelObj = new Google_Service_Gmail_Label;
        $labelObj->setName( $label );

        $message->service->users_labels->create( 'me', $labelObj );

        $labels = $message->service->users_labels->listUsersLabels( 'me' )->getLabels();

        session( ['setup_parts'=> ['labels'=>$labels] ] );

        session()->flash( 'success', 'Label created Successfully!!!' );

        return redirect()->route( 'dashboard', [ 'type' => 'INBOX' ] );

    }

    public function moveFolder( Request $request ) {

        $this->validate( $request, [ 'folder' => 'required' ] );

        $message_id     = $request->message_id;
        $target_folder_id  = $request->folder;
        $current_folder_id = $request->current_folder;

        $messageObj = LaravelGmail::message();
        $message    = $messageObj->preload()->get( $message_id );
        $folders = $this->gmail->getFolders( $messageObj, [] );


        $message->removeLabel( $current_folder_id );
        $message->addLabel( $target_folder_id );

        session()->flash( 'success', "Email moved from '{$folders[$current_folder_id]}' to '{$folders[$target_folder_id]}'" );

        return redirect()->route( 'dashboard', [ 'type' => 'INBOX' ] );

    }


    public function replySend( Request $request ) {

        $this->validate( $request, [ 'to' => 'required' ] );

        $subject = "Re: " . $request->subject;
        $emails  = $this->gmail->validate_email( $request );


        $this->sendEmail( $emails, $request->message_id, $subject, $request->message,
            'reply', $request->thread_id );


        return redirect()->route( 'dashboard', [ 'type' => 'INBOX' ] );

    }

    public function forwardSend( Request $request ) {

        $this->validate( $request, [ 'to' => 'required' ] );

        $subject = "Fwd: " . $request->subject;
        $emails  = $this->gmail->validate_email( $request );

        $this->sendEmail( $emails, $request->message_id, $subject, $request->message,
            'forward', $request->thread_id );


        return redirect()->route( 'dashboard', [ 'type' => 'INBOX' ] );

    }

    private function sendEmail( $emails, $message_id, $subject, $message, $type, $thread_id = '' ) {
        $emails     = empty( $emails ) ? [] : $emails;
        $message_id = empty( $message_id ) ? '' : $message_id;
        $subject    = empty( $subject ) ? '' : $subject;
        $message    = empty( $message ) ? '' : $message;


        $attachments = $this->fetchAttachments( $message_id );

        $delete = true;
        $is_draft = ($type==='draft')?true:false;
        if ( in_array($type, ['forward', 'draft']) ) {
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


        if ( ! empty( $attachments ) ) {
            foreach ( $attachments as $attachment ) {
                $mail->attach( $attachment );
            }
        }

        $mail->from( session( 'gmail.me' )->emailAddress, 'ragib softobiz 02' );
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

        session()->flash( 'success', 'Email Send to: ' . implode( ', ', $emails['to'] ) );
    }


    private function fetchAttachments( $message_id, $public = false, $type = '' ) {
        $attachments = [];


        if ( ! empty( $message_id ) ) {
            $localFiles = GmailAttachment::where( 'message_id', $message_id )->get();


            if ( ! empty( $localFiles ) ) {
                foreach ( $localFiles as $file ) {

                    if ( $type === 'storage' ) {

                        $path = storage_path( $this->storageAttachments
                                              . $file->message_id
                                              . DIRECTORY_SEPARATOR
                                              . $file->path );
                        if ( file_exists( $path ) ) {
                            $attachments[] = $path;
                        }

                    } else {
                        $path = $this->composeAttachments
                                . DIRECTORY_SEPARATOR
                                . $file->message_id
                                . DIRECTORY_SEPARATOR
                                . $file->path;

                        if ( file_exists( public_path( $path ) ) ) {

                            if ( $public ) {
                                $attachments[ $file->path ] = asset( $path );
                            } else {
                                $attachments[] = public_path( $path );
                            }

                        }
                    }

                }
            }

        }


        return $attachments;

    }

    private function deleteAttachments( $message_id, $attachments ) {
        if ( ! empty( $message_id ) ) {
            GmailAttachment::where( 'message_id', $message_id )->delete();

            foreach ( $attachments as $attachment ) {
                if ( file_exists( $attachment ) ) {
                    unlink( $attachment );
                }
            }

            $dir = public_path( $this->composeAttachments
                                . DIRECTORY_SEPARATOR
                                . $message_id );

            if ( file_exists( $dir ) ) {
                rmdir( $dir );
            }


        }
    }

    private function composeGenerateId( $messageId ) {
        $userID    = session( 'gmail.me' )->emailAddress;
        $messageId = empty( $messageId ) ? base64_encode( uniqid( $userID ) ) : $messageId;
        $directory = public_path( $this->composeAttachments ) . DIRECTORY_SEPARATOR . $messageId;
        if ( ! file_exists( $directory ) ) {
            mkdir( $directory );
        }

        return $messageId;
    }

    public function composeAttchSave( Request $request ) {

        $files     = $request->file( 'attachments' );
        $messageId = $this->composeGenerateId( $request->message_id );


        foreach ( $files as $file ) {

            $file->move( $this->composeAttachments . DIRECTORY_SEPARATOR . $messageId, $file->getClientOriginalName() );

            $fileExists = GmailAttachment::where( 'message_id', $messageId )->where( 'path', $file->getClientOriginalName() )->first();

            if ( ! empty( $fileExists ) ) {
                continue;
            }

            $this->gmail->saveAttachmentData( $messageId, $file->getClientOriginalName() );

        }

        $attachments = $this->fetchAttachments( $messageId, true );


        return response()->json( [ 'status' => 'success', 'message_id' => $messageId, 'attachments' => $attachments ] );

    }

    public function composeAttchDelete( Request $request ) {
        $message_id = $request->message_id;
        $file       = $request->file;
        if ( ! empty( $message_id ) ) {
            $file = GmailAttachment::where( 'message_id', $message_id )->where( 'path', $file )->first();

            if ( ! empty( $file ) ) {
                $path = $this->composeAttachments
                        . DIRECTORY_SEPARATOR
                        . $file->message_id
                        . DIRECTORY_SEPARATOR
                        . $file->path;
                $path = public_path( $path );

                if ( file_exists( $path ) ) {
                    unlink( $path );
                    $file->delete();
                }
            }

        }

        return response()->json( [ 'status' => 'success' ] );
    }

    public function searchByTransaction( Request $request ) {

        $this->validate( $request, [ 'transaction_id' => 'required' ] );

        $result = $this->gmail->searchByTransactionId( $request->transaction_id );

        return view( 'gmail.dashboard' )->with( [
            'messages' => $result['data'],
            'folders'  => $result['folders']
        ] );

    }

    public function draftSave( Request $request ) {

        try {
            $to         = $request->to ?? '';
            $subject    = $request->subject ?? '';
            $message    = $request->message ?? '';
            $message_id = $request->message_id ?? '';


            $messageObj  = LaravelGmail::message();
            $user_drafts = $messageObj->service->users_drafts;

            $swiftMessage = new Swift_Message();
            $swiftMessage->setSubject( $subject )
                         ->setBody( $message, 'text/html' );

            if ( ! empty( $to ) ) {
                $swiftMessage->setTo( $to, '' );
            }


            $attachments = null;
            if ( ! empty( $message_id ) ) {
                $attachments = $this->fetchAttachments( $message_id );

                if ( ! empty( $attachments ) ) {
                    foreach ( $attachments as $file ) {
                        $swiftMessage->attach( Swift_Attachment::fromPath( $file ) );
                    }
                }

            }


            $body = new Google_Service_Gmail_Message();
            $body->setRaw( $this->base64_encode( $swiftMessage->toString() ) );

            $gmailDraft = new Google_Service_Gmail_Draft;
            $gmailDraft->setMessage( $body );


            $user_drafts->create( 'me', $gmailDraft );

            if ( $attachments ) {
                // Clear the Junk
                $this->deleteAttachments( $message_id, $attachments );
            }


            session()->flash( 'success', 'message saved to draft' );

            return response()->json( [ 'status' => 'success', 'route' => route( 'dashboard' ) ] );

        } catch ( Exception $e ) {
//            'Email Field is Required'
            return response()->json( [ 'status' => 'error', 'message' => $e->getMessage() ] );
        }

    }

    private function base64_encode( $data ) {
        return rtrim( strtr( base64_encode( $data ), [ '+' => '-', '/' => '_' ] ), '=' );
    }


    public function createETemplate() {

        $templates = EmailTemplate::orderBy( 'id', 'desc' )->get();

        return view( 'gmail.email-template-new', compact( 'templates' ) );
    }

    public function saveETemplate( Request $request ) {

        $this->validate( $request, [ 'template_name' => 'required', 'template' => 'required' ] );


        $template       = new EmailTemplate;
        $template->name = $request->template_name;
        $template->body = $request->template;
        $template->save();

        session()->flash( 'success', 'Template Saved Successfully!!!' );

        return redirect()->route( 'gmail.createETemplate' );

    }

    public function loadETemplate( Request $request ) {

        $response = [ 'status' => 'error', 'data' => [] ];

        $id = $request->templateId;

        if ( empty( $id ) ) {
            return response()->json( $response );
        }

        $obj          = EmailTemplate::find( $id );
        $data         = [];
        $data['name'] = $obj->name;
        $data['body'] = $obj->body;

        $response['data']   = $data;
        $response['status'] = 'success';

        return response()->json( $response );
    }


}
