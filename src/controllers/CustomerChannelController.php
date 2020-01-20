<?php

namespace Abs\CustomerChannelPkg;
use Abs\CustomerChannelGroupPkg\CustomerChannelGroup;
use App\Address;
use App\Country;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
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
				'customer_channel_groups.code',
				'customer_channel_groups.name',
				DB::raw('IF(customer_channel_groups.mobile_no IS NULL,"--",customer_channel_groups.mobile_no) as mobile_no'),
				DB::raw('IF(customer_channel_groups.email IS NULL,"--",customer_channel_groups.email) as email'),
				DB::raw('IF(customer_channel_groups.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->where('customer_channel_groups.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->customer_channel_group_code)) {
					$query->where('customer_channel_groups.code', 'LIKE', '%' . $request->customer_channel_group_code . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->customer_channel_group_name)) {
					$query->where('customer_channel_groups.name', 'LIKE', '%' . $request->customer_channel_group_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->mobile_no)) {
					$query->where('customer_channel_groups.mobile_no', 'LIKE', '%' . $request->mobile_no . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->email)) {
					$query->where('customer_channel_groups.email', 'LIKE', '%' . $request->email . '%');
				}
			})
			->orderby('customer_channel_groups.id', 'desc');

		return Datatables::of($customer_channel_groups)
			->addColumn('code', function ($customer_channel_group) {
				$status = $customer_channel_group->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $customer_channel_group->code;
			})
			->addColumn('action', function ($customer_channel_group) {
				$edit_img = asset('public/theme/img/table/cndn/edit.svg');
				$delete_img = asset('public/theme/img/table/cndn/delete.svg');
				return '
					<a href="#!/customer_channel_group-pkg/customer_channel_group/edit/' . $customer_channel_group->id . '">
						<img src="' . $edit_img . '" alt="View" class="img-responsive">
					</a>
					<a href="javascript:;" data-toggle="modal" data-target="#delete_customer_channel_group"
					onclick="angular.element(this).scope().deleteCustomerChannelGroup(' . $customer_channel_group->id . ')" dusk = "delete-btn" title="Delete">
					<img src="' . $delete_img . '" alt="delete" class="img-responsive">
					</a>
					';
			})
			->make(true);
	}

	public function getCustomerChannelGroupFormData($id = NULL) {
		if (!$id) {
			$customer_channel_group = new CustomerChannelGroup;
			$address = new Address;
			$action = 'Add';
		} else {
			$customer_channel_group = CustomerChannelGroup::withTrashed()->find($id);
			$address = Address::where('address_of_id', 24)->where('entity_id', $id)->first();
			if (!$address) {
				$address = new Address;
			}
			$action = 'Edit';
		}
		$this->data['country_list'] = $country_list = Collect(Country::select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Country']);
		$this->data['customer_channel_group'] = $customer_channel_group;
		$this->data['address'] = $address;
		$this->data['action'] = $action;

		return response()->json($this->data);
	}

	public function saveCustomerChannelGroup(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'CustomerChannelGroup Code is Required',
				'code.max' => 'Maximum 255 Characters',
				'code.min' => 'Minimum 3 Characters',
				'code.unique' => 'CustomerChannelGroup Code is already taken',
				'name.required' => 'CustomerChannelGroup Name is Required',
				'name.max' => 'Maximum 255 Characters',
				'name.min' => 'Minimum 3 Characters',
				'gst_number.required' => 'GST Number is Required',
				'gst_number.max' => 'Maximum 191 Numbers',
				'mobile_no.max' => 'Maximum 25 Numbers',
				// 'email.required' => 'Email is Required',
				'address_line1.required' => 'Address Line 1 is Required',
				'address_line1.max' => 'Maximum 255 Characters',
				'address_line1.min' => 'Minimum 3 Characters',
				'address_line2.max' => 'Maximum 255 Characters',
				// 'pincode.required' => 'Pincode is Required',
				// 'pincode.max' => 'Maximum 6 Characters',
				// 'pincode.min' => 'Minimum 6 Characters',
			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'required:true',
					'max:255',
					'min:3',
					'unique:customer_channel_groups,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => 'required|max:255|min:3',
				'gst_number' => 'required|max:191',
				'mobile_no' => 'nullable|max:25',
				// 'email' => 'nullable',
				'address' => 'required',
				'address_line1' => 'required|max:255|min:3',
				'address_line2' => 'max:255',
				// 'pincode' => 'required|max:6|min:6',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$customer_channel_group = new CustomerChannelGroup;
				$customer_channel_group->created_by_id = Auth::user()->id;
				$customer_channel_group->created_at = Carbon::now();
				$customer_channel_group->updated_at = NULL;
				$address = new Address;
			} else {
				$customer_channel_group = CustomerChannelGroup::withTrashed()->find($request->id);
				$customer_channel_group->updated_by_id = Auth::user()->id;
				$customer_channel_group->updated_at = Carbon::now();
				$address = Address::where('address_of_id', 24)->where('entity_id', $request->id)->first();
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
			$customer_channel_group->gst_number = $request->gst_number;
			$customer_channel_group->axapta_location_id = $request->axapta_location_id;
			$customer_channel_group->save();

			if (!$address) {
				$address = new Address;
			}
			$address->fill($request->all());
			$address->company_id = Auth::user()->company_id;
			$address->address_of_id = 24;
			$address->entity_id = $customer_channel_group->id;
			$address->address_type_id = 40;
			$address->name = 'Primary Address';
			$address->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json(['success' => true, 'message' => ['CustomerChannelGroup Details Added Successfully']]);
			} else {
				return response()->json(['success' => true, 'message' => ['CustomerChannelGroup Details Updated Successfully']]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
	public function deleteCustomerChannelGroup($id) {
		$delete_status = CustomerChannelGroup::withTrashed()->where('id', $id)->forceDelete();
		if ($delete_status) {
			$address_delete = Address::where('address_of_id', 24)->where('entity_id', $id)->forceDelete();
			return response()->json(['success' => true]);
		}
	}
}
