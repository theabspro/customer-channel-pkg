<?php

namespace Abs\CustomerChannelPkg;
use Abs\CustomerChannelPkg\CustomerChannelGroup;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class CustomerChannelGroupController extends Controller {

	public function __construct() {
	}

	public function getCustomerChannelGroupList(Request $request) {
		$customer_channel_groups = CustomerChannelGroup::withTrashed()
			->select(
				'customer_channel_groups.id',
				'customer_channel_groups.name',
				DB::raw('IF(customer_channel_groups.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->where('customer_channel_groups.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('customer_channel_groups.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->email)) {
					$query->where('customer_channel_groups.email', 'LIKE', '%' . $request->email . '%');
				}
			})
			->whereNull('customer_channel_groups.parent_id')
			->orderby('customer_channel_groups.id', 'desc');

		return Datatables::of($customer_channel_groups)
		// ->addColumn('code', function ($customer_channel_group) {
		// 	$status = $customer_channel_group->status == 'Active' ? 'green' : 'red';
		// 	return '<span class="status-indicator ' . $status . '"></span>' . $customer_channel_group->code;
		// })
			->addColumn('action', function ($customer_channel_group) {
				$edit = asset('public/img/content/table/edit-yellow.svg');
				$edit_active = asset('public/img/content/table/edit-yellow-active.svg');
				$delete = asset('/public/img/content/table/delete-default.svg');
				$delete_active = asset('/public/img/content/table/delete-active.svg');

				$action = '';
				if (Entrust::can('edit-customer-channel-group')) {
					$action .= '<a href="#!/customer-channel-pkg/customer-channel-group/edit/' . $customer_channel_group->id . '">
						<img src="' . $edit . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $edit_active . '" onmouseout=this.src="' . $edit . '" >
					</a>';
				}
				if (Entrust::can('delete-customer-channel-group')) {
					$action .= '<a href="javascript:;" data-toggle="modal" data-target="#delete_customer_channel_group"
					onclick="angular.element(this).scope().deleteCustomerChannelGroup(' . $customer_channel_group->id . ')" dusk = "delete-btn" title="Delete">
					<img src="' . $delete . '" alt="Delete" class="img-responsive" onmouseover=this.src="' . $delete_active . '" onmouseout=this.src="' . $delete . '" >
					</a>
					';
				}
				return $action;
			})
			->make(true);
	}

	public function getCustomerChannelGroupFormData($id = NULL) {
		if (!$id) {
			$customer_channel_group = new CustomerChannelGroup;
			$customer_channel_sub_group = [];
			$action = 'Add';
		} else {
			$customer_channel_group = CustomerChannelGroup::withTrashed()->find($id);
			$customer_channel_sub_group = CustomerChannelGroup::withTrashed()->where('parent_id', $id)->get();
			$action = 'Edit';
		}
		$this->data['customer_channel_group'] = $customer_channel_group;
		$this->data['customer_channel_sub_group'] = $customer_channel_sub_group;
		$this->data['action'] = $action;

		return response()->json($this->data);
	}

	public function getSubGroupList($main_group_id) {
		$this->data['sub_group_list'] = CustomerChannelGroup::getSubGroup($main_group_id);
		return response()->json($this->data);
	}

	public function saveCustomerChannelGroup(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'name.required' => 'Customer Channel Group Name is Required',
				'name.unique' => 'Customer Channel Group Name is already taken',
				'name.max' => 'Maximum 255 Characters',
				'name.min' => 'Minimum 3 Characters',
			];
			$validator = Validator::make($request->all(), [
				'name' => [
					'required:true',
					'max:255',
					'min:3',
					'unique:customer_channel_groups,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id . ',parent_id,NULL',
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			$error_messages1 = [
				'sub_group_name.required' => 'Customer Channel Group Name is Required',
				'sub_group_name.unique' => 'Customer Channel Sub Group Name is already taken',
				'sub_group_name.max' => 'Maximum 255 Characters',
				'sub_group_name.min' => 'Minimum 3 Characters',
			];
			if (!empty($request->customer_channel_groups)) {
				foreach ($request->customer_channel_groups as $customer_channel_group) {
					$validator = Validator::make($customer_channel_group, [
						'sub_group_name' => [
							'required:true',
							'max:191',
							'min:3',
							'unique:customer_channel_groups,name,' . $customer_channel_group['id'] . ',id,company_id,' . Auth::user()->company_id . ',parent_id,' . $request->id,
						],
					], $error_messages1);
					if ($validator->fails()) {
						return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
					}
				}
			}

			DB::beginTransaction();
			// dd($request->customer_channel_sub_group_id);
			if (!empty($request->customer_channel_sub_group_id)) {
				$channel_group_removal_id = json_decode($request->customer_channel_sub_group_id, true);
				CustomerChannelGroup::whereIn('id', $channel_group_removal_id)->forceDelete();
			}

			if (!$request->id) {
				$customer_channel_group = new CustomerChannelGroup;
				$customer_channel_group->created_by_id = Auth::user()->id;
				$customer_channel_group->created_at = Carbon::now();
				$customer_channel_group->updated_at = NULL;
			} else {
				$customer_channel_group = CustomerChannelGroup::withTrashed()->find($request->id);
				$customer_channel_group->updated_by_id = Auth::user()->id;
				$customer_channel_group->updated_at = Carbon::now();
			}
			$customer_channel_group->fill($request->all());
			$customer_channel_group->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$customer_channel_group->deleted_at = Carbon::now();
				$customer_channel_group->deleted_by_id = Auth::user()->id;
			} else {
				$customer_channel_group->deleted_by_id = NULL;
				$customer_channel_group->deleted_at = NULL;
			}
			$customer_channel_group->save();

			if (!empty($request->customer_channel_groups)) {
				foreach ($request->customer_channel_groups as $customer_channel_groups) {
					if (!$customer_channel_groups['id']) {
						$customer_channel_sub_group = new CustomerChannelGroup;
						$customer_channel_sub_group->parent_id = $customer_channel_group->id;
						$customer_channel_sub_group->created_by_id = Auth::user()->id;
						$customer_channel_sub_group->created_at = Carbon::now();
						$customer_channel_sub_group->updated_at = NULL;
					} else {
						$customer_channel_sub_group = CustomerChannelGroup::withTrashed()->find($customer_channel_groups['id']);
						$customer_channel_sub_group->updated_by_id = Auth::user()->id;
						$customer_channel_sub_group->updated_at = Carbon::now();
					}
					$customer_channel_sub_group->name = $customer_channel_groups['sub_group_name'];
					$customer_channel_sub_group->company_id = Auth::user()->company_id;
					if ($customer_channel_groups['status'] == 'Inactive') {
						$customer_channel_sub_group->deleted_at = Carbon::now();
						$customer_channel_sub_group->deleted_by_id = Auth::user()->id;
					} else {
						$customer_channel_sub_group->deleted_by_id = NULL;
						$customer_channel_sub_group->deleted_at = NULL;
					}
					$customer_channel_sub_group->save();
				}
			}

			DB::commit();
			if (!($request->id)) {
				return response()->json(['success' => true, 'message' => ['Customer Channel Group Details Added Successfully']]);
			} else {
				return response()->json(['success' => true, 'message' => ['Customer Channel Group Details Updated Successfully']]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
	public function deleteCustomerChannelGroup($id) {
		$sub_group_delete_status = CustomerChannelGroup::withTrashed()->where('parent_id', $id)->forceDelete();
		if ($sub_group_delete_status) {
			$delete_status = CustomerChannelGroup::withTrashed()->where('id', $id)->forceDelete();
			return response()->json(['success' => true]);
		}
	}
}
