app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //CUSTOMER CHANNEL GROUPS
    when('/customer-channel-pkg/customer-channel-group/list', {
        template: '<customer-channel-group-list></customer-channel-group-list>',
        title: 'Customer Channel Groups',
    }).
    when('/customer-channel-pkg/customer-channel-group/add', {
        template: '<customer-channel-group-form></customer-channel-group-form>',
        title: 'Add Customer Channel Group',
    }).
    when('/customer-channel-pkg/customer-channel-group/edit/:id', {
        template: '<customer-channel-group-form></customer-channel-group-form>',
        title: 'Edit Customer Channel Group',
    });
}]);

app.component('customerChannelGroupList', {
    templateUrl: customer_channel_group_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#customer_channel_groups_list').DataTable({
            "dom": dom_structure,
            "language": {
                "search": "",
                "searchPlaceholder": "Search",
                "lengthMenu": "Rows Per Page _MENU_",
                "paginate": {
                    "next": '<i class="icon ion-ios-arrow-forward"></i>',
                    "previous": '<i class="icon ion-ios-arrow-back"></i>'
                },
            },
            processing: true,
            "ordering": false,
            serverSide: true,
            paging: true,
            stateSave: true,
            ajax: {
                url: laravel_routes['getCustomerChannelGroupList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.mobile_no = $('#mobile_no').val();
                    d.email = $('#email').val();
                },
            },
            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'name', name: 'customer_channel_groups.name' },
                { data: 'sup_groups_count', name: 'sup_groups_count', searchable: false },
                { data: 'status', name: 'status', searchable: false },
            ],
            "initComplete": function(settings, json) {
                $('.dataTables_length select').select2();
            },
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_info').html(max)
            },
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        /* Page Title Appended */
        $('.page-header-content .display-inline-block .data-table-title').html('Customer Channel Groups <span class="badge badge-secondary" id="table_info">0</span>');
        $('.page-header-content .search.display-inline-block .add_close_button').html('<button type="button" class="btn btn-img btn-add-close"><img src="' + image_scr2 + '" class="img-responsive"></button>');
        $('.page-header-content .refresh.display-inline-block').html('<button type="button" class="btn btn-refresh"><img src="' + image_scr3 + '" class="img-responsive"></button>');
        if (self.hasPermission('add-customer-channel-group')) {
            $('.add_new_button').html(
                '<a href="#!/customer-channel-pkg/customer-channel-group/add" type="button" class="btn btn-secondary">' +
                'Add New' +
                '</a>'
            );
        }

        $('.btn-add-close').on("click", function() {
            $('#customer_channel_groups_list').DataTable().search('').draw();
        });

        $('.btn-refresh').on("click", function() {
            $('#customer_channel_groups_list').DataTable().ajax.reload();
        });

        //DELETE
        $scope.deleteCustomerChannelGroup = function($id) {
            $('#customer_channel_group_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#customer_channel_group_id').val();
            $http.get(
                customer_channel_group_delete_data_url + '/' + $id,
            ).then(function(response) {
                if (response.data.success) {
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'CustomerChannelGroup Deleted Successfully',
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 3000);
                    $('#customer_channel_groups_list').DataTable().ajax.reload();
                    $location.path('/customer-channel-pkg/customer-channel-group/list');
                }
            });
        }

        //FOR FILTER
        $('#customer_channel_group_code').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#customer_channel_group_name').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#mobile_no').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#email').on('keyup', function() {
            dataTables.fnFilter();
        });
        $scope.reset_filter = function() {
            $("#customer_channel_group_name").val('');
            $("#customer_channel_group_code").val('');
            $("#mobile_no").val('');
            $("#email").val('');
            dataTables.fnFilter();
        }

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('customerChannelGroupForm', {
    templateUrl: customer_channel_group_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        get_form_data_url = typeof($routeParams.id) == 'undefined' ? customer_channel_group_get_form_data_url : customer_channel_group_get_form_data_url + '/' + $routeParams.id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http.get(
            get_form_data_url
        ).then(function(response) {
            // console.log(response);
            self.customer_channel_group = response.data.customer_channel_group;
            self.customer_channel_sub_group = response.data.customer_channel_sub_group;
            self.action = response.data.action;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                if (self.customer_channel_group.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
            } else {
                $scope.add_sub_groups();
                self.switch_value = 'Active';
            }
        });

        /* Tab Funtion */
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-pills').on("click", function() {
            tabPaneFooter();
        });
        $scope.btnNxt = function() {}
        $scope.prev = function() {}

        $scope.add_sub_groups = function() {
            self.customer_channel_sub_group.push({
                switch_value: 'Active',
            });
        }

        //REMOVE CUSTOMER CHANNEL GROUP 
        self.customer_channel_sub_group_id = [];
        $scope.removeCustomerChannelGroup = function(index, sub_group_id) {
            console.log(index, sub_group_id);
            if (sub_group_id) {
                self.customer_channel_sub_group_id.push(sub_group_id);
                $('#customer_channel_sub_group_id').val(JSON.stringify(self.customer_channel_sub_group_id));
            }
            self.customer_channel_sub_group.splice(index, 1);
        }

        //VALIDATEOR FOR MULTIPLE 
        // $.validator.messages.minlength = 'Minimum of 2 charaters';
        // $.validator.messages.maxlength = 'Maximum of 191 charaters';
        jQuery.validator.addClassRules("sub_group_name", {
            required: true,
            minlength: 2,
            maxlength: 191,
        });

        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'name': {
                    required: true,
                    minlength: 2,
                    maxlength: 191,
                },
            },
            invalidHandler: function(event, validator) {
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'You have errors,Please check all tabs'
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 3000)
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveCustomerChannelGroup'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            $noty = new Noty({
                                type: 'success',
                                layout: 'topRight',
                                text: res.message,
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 3000);
                            $location.path('/customer-channel-pkg/customer-channel-group/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('.submit').button('reset');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                $noty = new Noty({
                                    type: 'error',
                                    layout: 'topRight',
                                    text: errors
                                }).show();
                                setTimeout(function() {
                                    $noty.close();
                                }, 3000);
                            } else {
                                $('.submit').button('reset');
                                $location.path('/customer-channel-pkg/customer-channel-group/list');
                                $scope.$apply();
                            }
                        }
                    })
                    .fail(function(xhr) {
                        $('.submit').button('reset');
                        $noty = new Noty({
                            type: 'error',
                            layout: 'topRight',
                            text: 'Something went wrong at server',
                        }).show();
                        setTimeout(function() {
                            $noty.close();
                        }, 3000);
                    });
            }
        });
    }
});