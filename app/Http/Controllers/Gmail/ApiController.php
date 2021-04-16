<?php

namespace App\Http\Controllers\Gmail;

use Exception;
use Swift_Message;
use ErrorException;
use Swift_Attachment;
use App\GmailAuthData;
use App\GmailTemplate;

use Illuminate\Http\Request;
use Google_Service_Gmail_Draft;
use Google_Service_Gmail_Label;
use App\Service\GmailApiService;
use Google_Service_Gmail_Message;
use App\Http\Controllers\Controller;
use App\Http\Resources\GmailTemplateResource;
use Illuminate\Validation\ValidationException;
use Dacastro4\LaravelGmail\Facade\LaravelGmail;

class ApiController extends Controller
{
    protected $gmail, $request;

    public function __construct( GmailApiService $gmail, Request $request )
    {
        $this->request = $request;
        config(['global.request' => $request]);
        $this->gmail = $gmail;
    }

    public function checkLogin()
    {
        $status = LaravelGmail::check($this->request);
        return $this->handleSuccessResponse($status, 'data', $status, 'Gmail login status');
    }

    public function login()
    {
        return LaravelGmail::redirect();
    }

    public function logout()
    {
        try
        {
            session()->flush();

            LaravelGmail::logout($this->Request); //It returns exception if fails
            return redirect()->to( '/email' );

        } catch ( Exception $e )
        {

            return $e->getMessage();
        }
    }

    public function gmailCallback()
    {
        $request = $this->request;

        try
        {
            LaravelGmail::makeToken();
            $is_session = $this->gmail->setupSessionData();
        
            if($is_session)
            {
                $data = GmailAuthData::where('user_id', $request->login_user_id)->latest()->first();
                return $this->handleSuccessResponse(true, 'data', $data, 'Data has been fetched successfully.');
            }
        } catch(\Exception $e)
        {
            return $this->handleErrorExceptionResponse($e);
        }
        
        return $this->handleErrorExceptionResponse();
        
    }

    public function dashboard( $type )
    {
        if ( ! LaravelGmail::check($this->request) )
        {
            return $this->handleErrorExceptionResponse();
        }
        $per_page = $this->request->per_page;
        $page = $this->request->page;

        $result = $this->gmail->findLabelByLabel( $type, $this->request->transaction_code, $per_page, $page );

        $data = [
            'messages'       => $result['data'],
            'folders'        => $result['folders'],
            'unread_number'  => $result['unread_number'],
            'current_folder' => $result['current_folder'],
            'meta'           => $result['meta']
        ];

        return $this->handleSuccessResponse(true, 'data', $data, 'Data has been fetched successfully.');
    }

    public function draft($id)
    {
        $data            = [];
        $data['message'] = $this->gmail->readEmailById( $id );
        $data['type']    = 'gmail.draft.send';
    
        return view( 'gmail.forward', compact( 'data' ) );
    }

    public function draftSend(Request $request)
    {
        $this->validate( $request, [ 'to' => 'required' ] );

        $emails  = $this->gmail->validate_email( $request );

        $this->sendEmail( $emails, $request->message_id, $request->subject, $request->message,
            'draft', $request->thread_id );

        return redirect()->route( 'dashboard', [ 'type' => 'INBOX' ] );
    }

    public function singleEmail( $type, $id )
    {
        $message = LaravelGmail::message();
        $metadata_messages = $message->service->users_threads->get('me', $id,['format'=>'METADATA'])['messages'];

        $conversations = [];
        if(count($metadata_messages)>0) {
            foreach ($metadata_messages as $metadata_message){
                $conversations[] = $this->gmail->readEmailById($metadata_message->getId());
            }
        }

        $folders = $this->gmail->getFolders( $message, [ $type, 'DRAFT', 'SENT', 'TRASH' ] );


        $data = [
            'messages'       => $conversations,
            'folders'        => $folders,
            'current_folder' => $type,
            'message_id'     => $id
        ];

        return $this->handleSuccessResponse(true, 'data', $data, 'Data has been fetched successfully.');
    }

    public function composeSend( Request $request )
    {
        $request->validate([ 'to' => 'required' ],
                            [ 'to.required' => 'Recipient address required'] );

        $emails = $this->gmail->validate_email( $request );
        $thread_id = $request->thread_id ? : '';
        $subject = $request->subject;
        $message = $request->body;
        if($request->type === 'reply')
        {
            $subject = "Re: " . $request->subject;
        }
        if($request->type === 'forward')
        {
            $subject = "Fwd: " . $request->subject;
            $data = [];
            $data = $this->gmail->readEmailById( $request->message_id );
            $data['body'] = $request->body;
            $message   = $this->gmail->forwardBody( $data );
        }

        try
        {
            $data = $this->gmail->sendEmail(
                $emails,
                $request->message_id,
                $subject,
                $message,
                $request->headers,
                $request->type,
                $thread_id
            );
            return $this->handleSuccessResponse(true, 'data', $data, 'Email sent successfully.');

        } catch(ErrorException $e)
        {
            $this->error_logs($e);
            return $this->handleErrorExceptionResponse($e);
        }
    }

    public function emailDelete( $type, $id )
    {
        $message = LaravelGmail::message()->preload()->get( $id );

        $labels = $this->gmail->filterLabels( $message, $message->getLabels() );

        if ( in_array( "TRASH", $labels ) )
        {
            $message->removeFromTrash();
            $success = "Conversation deleted from Trash!!!";
        } else
        {
            $message->sendToTrash();
            $success = "Conversation moved to Trash!!!";
        }

        return $this->handleSuccessResponse(true, 'data', $message, $success);
    }

    public function emailRestore( $id )
    {
        $message = LaravelGmail::message()->preload()->get( $id );
        $labels  = $this->gmail->filterLabels( $message, $message->getLabels() );

        if ( in_array( "TRASH", $labels ) )
        {
            $message->removeLabel( 'TRASH' );
        }

        return redirect()->route( 'dashboard', [ 'type' => 'INBOX' ] );
    }

    public function createLabel( Request $request )
    {
        $this->validate( $request, [ 'labelName' => 'required' ] );

        $label = $request->labelName;

        $labelObj = new Google_Service_Gmail_Label;
        $labelObj->setName( $label );
        
        $message = LaravelGmail::message();
        $message->service->users_labels->create( 'me', $labelObj );

        $labels = $message->service->users_labels->listUsersLabels( 'me' )->getLabels();

        GmailAuthData::where('user_id', $request->login_user_id)->latest()->update(['setup_parts' => json_encode(['labels'=>$labels])]);

        return redirect()->route( 'dashboard', [ 'type' => 'INBOX' ] );
    }

    public function moveFolder( Request $request )
    {
        $this->validate( $request, [ 'folder' => 'required' ] );

        $message_id        = $request->message_id;
        $target_folder_id  = $request->folder;
        $current_folder_id = $request->current_folder;

        $messageObj = LaravelGmail::message();
        $folders    = $this->gmail->getFolders( $messageObj, [] );
        $message    = $messageObj->preload()->get( $message_id );

        $message->removeLabel( $current_folder_id );
        $message->addLabel( $target_folder_id );

        return redirect()->route( 'dashboard', [ 'type' => 'INBOX' ] );
    }

    public function composeAttchSave(Request $request)
    {
        try
        {
            $data = $this->gmail->composeAttchSave($request);
            return $this->handleSuccessResponse(true, 'data', $data, 'File(s) attached with email successfully.');
        } catch (ErrorException $e)
        {
            $this->handleErrorExceptionResponse($e);
        }
    }

    public function composeAttchDelete(Request $request)
    {
        try
        {
            $data = $this->gmail->composeAttchDelete($request);
            return $this->handleSuccessResponse(true, 'data', $data, 'File(s) detached with email successfully.');
        } catch (ErrorException $e)
        {
            $this->handleErrorExceptionResponse($e);
        }
    }

    public function searchByTransaction( Request $request )
    {

        $this->validate( $request, [ 'transaction_id' => 'required' ] );

        $result = $this->gmail->searchByTransactionId( $request->transaction_id );

        return view( 'gmail.dashboard' )->with( [
            'data' => [
                'current_folder' => 'INBOX',
                'unreadNumber' => 0,
                'messages' => $result['data'],
                'folders'  => $result['folders']
            ],
        ] );

    }

    public function draftSave( Request $request )
    {
        try
        {
            $to         = $request->to ?? '';
            $subject    = $request->subject ?? '';
            $message    = $request->body ?? '';
            $message_id = $request->message_id ?? '';


            $messageObj  = LaravelGmail::message();
            $user_drafts = $messageObj->service->users_drafts;

            $swiftMessage = new Swift_Message();
            $swiftMessage->setSubject( $subject )
                         ->setBody( $message, 'text/html' );

            if ( ! empty( $to ) )
            {
                $swiftMessage->setTo( $to, '' );
            }

            $attachments = null;
            $attachmentsArr = [];

            if($request->hasFile('attachments'))
            {
                $attachmentsArr = $this->gmail->composeAttchSave($request);
                $attachments = $attachmentsArr['attachments'];
            } else if ( ! empty( $message_id ) )
            {
                $attachments = $this->gmail->fetchAttachments( $message_id );
                $attachmentsArr['message_id'] = $message_id;
                $attachmentsArr['attachments'] = $attachments;
            }

            if ( ! empty( $attachments ) )
            {
                foreach ( $attachments as $file )
                {
                    $swiftMessage->attach( Swift_Attachment::fromPath( $file ) );
                }
            }

            $body = new Google_Service_Gmail_Message();
            $body->setRaw( encode_raw_email( $swiftMessage->toString() ) );

            $gmailDraft = new Google_Service_Gmail_Draft;
            $gmailDraft->setMessage( $body );

            $user_drafts->create( 'me', $gmailDraft );

            // if ( $attachments ) {
            //     // Clear the Junk
            //     $this->deleteAttachments( $message_id, $attachments );
            // }

            return $this->handleSuccessResponse(true, 'data', $attachmentsArr, 'Draft has been saved successfully');

        } catch ( ErrorException $e ) 
        {
            //'Email Field is Required'
            return $this->handleErrorExceptionResponse($e);
        }

    }

    public function fetchGmailTemplates() 
    {
        $per_page = trim($this->request->query('per_page') ? $this->request->query('per_page') : 10);
        $data = GmailTemplate::where('admin_id', $this->request->admin_id)->orderBy( 'id', 'desc' );
        $total = $data->count();
        if($this->request->filled('per_page')) {
            $data = GmailTemplateResource::collection($data->paginate($per_page));
            return $data;
        } else {
            $data = $data->get();
            $meta = ['total' => $total];
            return response()->json(['Status' => 200, 'data' => $data, 'meta' => $meta, 'Message' => 'Data has been fetched successfully.'], 200);
        }
    }

    public function saveGmailTemplates( Request $request )
    {
        try
        {
            $request->validate([
                'name' => 'required|unique:gmail_templates,name,'.$request->id.',id,admin_id,'.$request->admin_id
            ] );

            $data = $request->all();
            $data["user_id"] = $request->login_user_id;
            $template = GmailTemplate::updateOrCreate([
                'id' => $request->id
            ], $data);

            return $this->handleSuccessResponse(true, 'data', $template, 'Template has been saved successfully.');
        }catch(ValidationException $e)
        {
            $this->error_logs($e);
            return $this->handleValidationExceptionResponse($e);
            
        }catch(ErrorException $e)
        {
            $this->error_logs($e);
            return $this->handleErrorExceptionResponse($e);
        }
    }

    public function showTemplate( Request $request )
    {
        $response = [ 'status' => 'error', 'data' => [] ];

        $id = $request->templateId;

        if ( empty( $id ) )
        {
            return response()->json( $response );
        }

        $obj          = GmailTemplate::find( $id );
        $data         = [];
        $data['name'] = $obj->name;
        $data['body'] = $obj->body;

        $response['data']   = $data;
        $response['status'] = 'success';

        return response()->json( $response );
    }

    public function deleteGmailTemplate($id)
    {
        $template = GmailTemplate::findOrFail($id);

        if($template->delete())
        {
            return $this->handleSuccessResponse(true, 'data', $template, 'Template has been deleted successfully.');
        }
        return $this->handleErrorExceptionResponse();
    }

}
