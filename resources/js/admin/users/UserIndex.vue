<template>
    <div class="main-content app-content"> <!-- container -->
        <div class="main-container container-fluid"> <!-- breadcrumb -->
            <div class="breadcrumb-header justify-content-between">
                <div class="left-content"><span class="main-content-title mg-b-0 mg-b-lg-1">DASHBOARD</span></div>
                <div class="justify-content-center mt-2">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item tx-15"><a href="javascript:void(0);">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Sales</li>
                    </ol>
                </div>
            </div> <!-- /breadcrumb --> <!-- row -->

            <div class="row row-sm">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header pb-0">
                            <div class="d-flex justify-content-between"><h4 class="card-title mg-b-0">SIMPLE
                                    TABLE</h4></div>
                            <p class="tx-12 tx-gray-500 mb-2">Example of Nowa Simple Table. <a href="">Learn
                                    more</a></p></div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table mg-b-0 text-md-nowrap">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                                                                    <th>Code</th>
                                                                                    <th>Username</th>
                                                                                    <th>Name</th>
                                                                                    <th>Birthday</th>
                                                                                    <th>Phone</th>
                                                                                    <th>Email</th>
                                                                                    <th>Address</th>
                                                                                    <th>Status</th>
                                                                                    <th>Type</th>
                                                                                    <th>Created By</th>
                                                                                    <th>Updated By</th>
                                                                                <th>Action</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="entry in entries">
                                        <td>
                                            <a class="edit-link" :href="'/xadmin/users/edit?id='+entry.id"
                                               v-text="entry.id"></a>
                                        </td>
                                                                                    <td v-text="entry.code"></td>
                                                                                    <td v-text="entry.username"></td>
                                                                                    <td v-text="entry.name"></td>
                                                                                    <td v-text="entry.birthday"></td>
                                                                                    <td v-text="entry.phone"></td>
                                                                                    <td v-text="entry.email"></td>
                                                                                    <td v-text="entry.address"></td>
                                                                                    <td v-text="entry.status"></td>
                                                                                    <td v-text="entry.type"></td>
                                                                                    <td v-text="entry.created_by"></td>
                                                                                    <td v-text="entry.updated_by"></td>

                                        <td class="">
                                            <a :href="'/xadmin/users/edit?id='+entry.id" class="btn "><i
                                                    class="fa fa-edit"></i></a>
                                            <a @click="remove(entry)" href="javascript:;" class="btn "><i
                                                    class="fa fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                                <div class="float-right" style="margin-top:10px; ">
                                    <Paginate :value="paginate" :pagechange="onPageChange"></Paginate>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!--/div--> <!--div-->

            </div> <!-- /row -->
        </div>


    </div> <!-- /main-content -->

</template>

<script>
    import {$get, $post, getTimeRangeAll} from "../../utils";
    import $router from '../../lib/SimpleRouter';


    let created = getTimeRangeAll();
    const $q = $router.getQuery();

    export default {
        name: "UsersIndex.vue",
        components: {},
        data() {
            return {
                entries: [],
                filter: {
                    keyword: $q.keyword || '',
                    created: $q.created || created,
                },
                paginate: {
                    currentPage: 1,
                    lastPage: 1
                }
            }
        },
        mounted() {
            $router.on('/', this.load).init();
        },
        methods: {
            async load() {
                let query = $router.getQuery();
                const res = await $get('/xadmin/users/data', query);
                this.paginate = res.paginate;
                this.entries = res.data;
            },
            async remove(entry) {
                if (!confirm('Xóa bản ghi: ' + entry.id)) {
                    return;
                }

                const res = await $post('/xadmin/users/remove', {id: entry.id});

                if (res.code) {
                    toastr.error(res.message);
                } else {
                    toastr.success(res.message);
                }

                $router.updateQuery({page: this.paginate.currentPage, _: Date.now()});
            },
            filterClear() {
                for (var key in app.filter) {
                    app.filter[key] = '';
                }

                $router.setQuery({});
            },
            doFilter(field, value, event) {
                if (event) {
                    event.preventDefault();
                }

                const params = {page: 1};
                params[field] = value;
                $router.setQuery(params)
            },
            async toggleStatus(entry) {
                const res = await $post('/xadmin/users/toggleStatus', {
                    id: entry.id,
                    status: entry.status
                });

                if (res.code === 200) {
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message);
                }
            },
            onPageChange(page) {
                $router.updateQuery({page: page})
            }
        }
    }
</script>

<style scoped>

</style>
