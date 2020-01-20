@if(config('custom.PKG_DEV'))
    <?php $customer_channel_pkg_prefix = '/packages/abs/customer-channel-pkg/src';?>
@else
    <?php $customer_channel_pkg_prefix = '';?>
@endif

<script type="text/javascript">
    var customer_channel_group_list_template_url = "{{URL::asset($customer_channel_pkg_prefix.'/public/angular/customer-channel-pkg/pages/customer-channel-group/list.html')}}";
    var customer_channel_group_get_form_data_url = "{{url('customer-channel-pkg/customer-channel-group/get-form-data/')}}";
    var customer_channel_group_form_template_url = "{{URL::asset($customer_channel_pkg_prefix.'/public/angular/customer-channel-pkg/pages/customer-channel-group/form.html')}}";
    var customer_channel_group_delete_data_url = "{{url('customer-channel-pkg/customer-channel-group/delete/')}}";
</script>
<script type="text/javascript" src="{{URL::asset($customer_channel_pkg_prefix.'/public/angular/customer-channel-pkg/pages/customer-channel-group/controller.js?v=2')}}"></script>
