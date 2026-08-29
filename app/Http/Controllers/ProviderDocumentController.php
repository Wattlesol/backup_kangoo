<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProviderDocument;
use App\Models\Service;
use App\Http\Requests\ProviderDocumentRequest;
use Yajra\DataTables\DataTables;
use App\Models\User;

class ProviderDocumentController extends Controller
{
    private function scopedDocumentQuery(bool $withTrashed = false)
    {
        $query = $withTrashed ? ProviderDocument::withTrashed() : ProviderDocument::query();

        if (auth()->user()->hasRole('provider')) {
            $query->where('provider_id', auth()->id());
        }

        return $query;
    }

    private function authorizeDocumentActor(bool $adminOnly = false): void
    {
        $roles = $adminOnly ? ['admin', 'demo_admin'] : ['admin', 'demo_admin', 'provider'];
        abort_unless(auth()->check() && auth()->user()->hasAnyRole($roles), 403);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

    }

    public function index_data(DataTables $datatable,Request $request)
    {
        $providerdata = $request->providerdocument;
        $query = ProviderDocument::query()->myDocument()->where('provider_id',$providerdata);

        $filter = $request->filter;

        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('is_verified', $filter['column_status']);
            }
        }
        if (auth()->user()->hasAnyRole(['admin'])) {
            $query;
        }


        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-'.$row->id.'"  name="datatable_ids[]" value="'.$row->id.'" data-type="providerdocument" onclick="dataTableRowCheck('.$row->id.',this)">';
            })
            ->editColumn('is_verified' , function ($query){
                $disabled = $query->trashed() ? 'disabled': '';
                if(auth()->user()->hasAnyRole(['provider','demo_provider'])){
                    if($query->is_verified == 0){
                        $status = '<span class="badge badge-danger">'.__('messages.unverified').'</span>';
                    }else{
                        $status = '<span class="badge badge-success">'.__('messages.verified').'</span>';
                    }
                    return $status;
                }
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input bg-primary change_status" data-type="provider_is_verified" data-name="provider_is_verified" '.($query->is_verified ? "checked" : "").' '.$disabled.'  value="'.$query->id.'" id="'.$query->id.'" data-id="'.$query->id.'">
                        <label class="custom-control-label" for="'.$query->id.'" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';

            })

            ->editColumn('provider_id', function ($query) {
                return view('providerdocument.user', compact('query'));
            })

            ->editColumn('document_id' , function ($query){
                return ($query->document_id != null && isset($query->document)) ? $query->document->localized_name : '';
            })
            ->filterColumn('provider_id',function($query,$keyword){
                $query->whereHas('providers',function ($q) use($keyword){
                    $q->where('first_name','like','%'.$keyword.'%');
                });
            })
            ->addColumn('action', function($provider_document){
                return view('providerdocument.action',compact('provider_document'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['check','provider_id', 'action','is_verified'])
            ->toJson();
    }

    /* bulck action method */
    public function bulk_action(Request $request)
    {
        $this->authorizeDocumentActor(true);
        $ids = explode(',', $request->rowIds);

        $actionType = $request->action_type;

        $message = 'Bulk Action Updated';

        switch ($actionType) {
            case 'change-featured':
                $branches = ProviderDocument::whereIn('id', $ids)->update([
                    'is_verified' => $request->is_verified,
                    'verification_status' => $request->is_verified ? 'approved' : 'pending',
                    'review_reason' => null,
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                ]);
                $message = 'Bulk ProviderDocument Featured Updated';
                break;

            case 'delete':
                ProviderDocument::whereIn('id', $ids)->delete();
                $message = 'Bulk Provider Document Deleted';
                break;

            case 'restore':
                ProviderDocument::whereIn('id', $ids)->restore();
                $message = 'Bulk Provider Document Restored';
                break;

            case 'permanently-delete':
                ProviderDocument::whereIn('id', $ids)->forceDelete();
                $message = 'Bulk Provider Document Permanently Deleted';
                break;

            default:
                return response()->json(['status' => false,'is_verified' => false, 'message' => 'Action Invalid']);
                break;
        }

        return response()->json(['status' => true, 'is_verified' => true, 'message' => $message]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->authorizeDocumentActor();
        $id = $request->id;
        $auth_user = authSession();
        $providerdocument = $request->providerdocument;
        $providerdocument = auth()->user()->hasRole('provider') ? auth()->id() : $providerdocument;
        $providerdata = User::with('providerDocument')->where('user_type','provider')->where('id',$providerdocument)->firstOrFail();
        $provider_document = $id ? $this->scopedDocumentQuery()->findOrFail($id) : null;
        $pageTitle = trans('messages.update_form_title',['form'=>trans('messages.providerdocument')]);

        if( $provider_document == null){
            $pageTitle = trans('messages.add_button_form',['form' => trans('messages.providerdocument')]);
             $provider_document = new ProviderDocument;
        }

        return view('providerdocument.create', compact('pageTitle' ,'provider_document' ,'auth_user','providerdata' ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ProviderDocumentRequest $request)
    {
        $this->authorizeDocumentActor();
        if(demoUserPermission()){
            if(request()->is('api/*')){
                return comman_message_response( __('messages.demo_permission_denied') );
            } else {
                return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
            }
        }
        $data = $request->validated();
        unset($data['id'], $data['provider_document']);
        if (auth()->user()->hasRole('provider')) {
            $data['provider_id'] = auth()->id();
            unset($data['is_verified']);
        }
        $data['provider_id'] = !empty( $data['provider_id'] ) ?  $data['provider_id'] : auth()->user()->id;
        $result = $request->filled('id')
            ? $this->scopedDocumentQuery()->findOrFail($request->id)
            : new ProviderDocument();
        if (!$result->exists || auth()->user()->hasAnyRole(['admin', 'demo_admin'])) {
            $isVerified = (bool) ($data['is_verified'] ?? false);
            $data['is_verified'] = $isVerified;
            $data['verification_status'] = $isVerified ? 'approved' : 'pending';
            $data['review_reason'] = null;
            $data['reviewed_by'] = $isVerified ? auth()->id() : null;
            $data['reviewed_at'] = $isVerified ? now() : null;
        }
        $result->fill($data)->save();
        storeMediaFile($result,$request->provider_document, 'provider_document');

        $message = __('messages.update_form',['form' => __('messages.providerdocument')]);
        if($result->wasRecentlyCreated){
            $message = __('messages.save_form',['form' => __('messages.providerdocument')]);
        }
        if($request->is('api/*')) {
            return comman_message_response($message);
		}
        return redirect(route('providerdocument.show',['providerdocument' => $result->provider_id]))->withSuccess($message);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $this->authorizeDocumentActor();
        if (auth()->user()->hasRole('provider')) {
            abort_unless((int) $id === (int) auth()->id(), 403);
        }
        $auth_user = authSession();
        $providerdata = User::with('providerDocument')->where('user_type','provider')->where('id',$id)->first();
        $filter = [
            'is_verified' => $request->is_verified,
        ];
        $pageTitle = trans('messages.list_form_title',['form' => trans('messages.providerdocument')] );
        $assets = ['datatable'];
        if(empty($providerdata))
        {
            $msg = __('messages.not_found_entry',['name' => __('messages.provider')] );
            return redirect(route('provider.index'))->withError($msg);
        }

        $sanadProviderVerificationSummary = $this->sanadProviderVerificationSummary($providerdata);

        return view('providerdocument.view', compact('pageTitle' ,'providerdata' ,'auth_user','assets','filter','sanadProviderVerificationSummary' ));
    }

    private function sanadProviderVerificationSummary(User $provider)
    {
        $documents = ProviderDocument::where('provider_id', $provider->id);

        return [
            'total_documents' => (clone $documents)->count(),
            'verified_documents' => (clone $documents)->where('is_verified', 1)->count(),
            'pending_documents' => (clone $documents)->where('is_verified', 0)->count(),
            'required_document_types' => \App\Models\Documents::where('status', 1)->where('is_required', 1)->count(),
            'verification_status' => (clone $documents)->where('is_verified', 0)->exists() ? 'Pending Review' : 'Verified',
        ];
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->authorizeDocumentActor();
        if(demoUserPermission()){
            if(request()->is('api/*')){
                return comman_message_response( __('messages.demo_permission_denied') );
            }
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $provider_document = $this->scopedDocumentQuery()->find($id);
        $msg = __('messages.not_found_entry',['name' => __('messages.providerdocument')] );

        if( $provider_document!='') {

            $provider_document->delete();
            $msg= __('messages.msg_deleted',['name' => __('messages.providerdocument')] );
        }
        if(request()->is('api/*')){
            return comman_custom_response(['message'=> $msg , 'status' => true]);
        }
        return comman_custom_response(['message'=> $msg, 'status' => true]);
    }

    public function action(Request $request){
        $this->authorizeDocumentActor(true);
        $id = $request->id;

        $provider_document  = $this->scopedDocumentQuery(true)->where('id',$id)->firstOrFail();
        $msg = __('messages.not_found_entry',['name' => __('messages.providerdocument')] );
        if($request->type == 'restore') {
            $provider_document->restore();
            $msg = __('messages.msg_restored',['name' => __('messages.providerdocument')] );
        }
        if($request->type === 'forcedelete'){
            $provider_document->forceDelete();
            $msg = __('messages.msg_forcedelete',['name' => __('messages.providerdocument')] );
        }
        return comman_custom_response(['message'=> $msg , 'status' => true]);
    }

}
