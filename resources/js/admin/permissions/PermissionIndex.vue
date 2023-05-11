<template>
    <div class="container-fluid">
        <ActionBar type="index"
                   title="PermissionIndex"/>
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-custom card-stretch gutter-b">
                    <div class="card-header border-0 pt-5">

                        <div class="row width-full">
                            <div class="col-lg-12">
                                <form class="form-inline">
                                    <div class="form-group mx-sm-3 mb-4">
                                        <input @keydown.enter="doFilter('keyword', filter.keyword, $event)"
                                               v-model="filter.keyword"
                                               type="text"
                                               class="form-control" placeholder="tìm kiếm">
                                    </div>


                                    <div class="form-group mx-sm-3 mb-2">
                                        <button @click="filterClear()" type="button"
                                                class="btn btn-default btn-sm btn-clear">Xóa
                                        </button>
                                    </div>

                                </form>
                            </div>
                            <div class="col-lg-12">
                                <form class="form-inline">
                                    <template v-if="editMode">
                                        <div class="form-group mx-sm-3 mb-2">
                                            <button @click="saveAll()" type="button"
                                                    class="btn btn-primary btn-sm btn-clear">Lưu lại
                                            </button>
                                        </div>
                                        <div class="form-group mx-sm-3 mb-2">
                                            <button @click="cancelAll()" type="button"
                                                    class="btn btn-default btn-sm btn-clear"> Hủy
                                            </button>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <div class="form-group mx-sm-3 mb-2">
                                            <button @click="renameAll()" type="button"
                                                    class="btn btn-primary btn-sm btn-clear">Đổi tên hiển thị
                                            </button>
                                        </div>
                                    </template>

                                </form>

                            </div>

                        </div>

                    </div>

                    <div class="card-body d-flex flex-column">
                        <table class="table table-head-custom table-head-bg table-vertical-center">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Module</th>
                                <th>Tên</th>
                                <th>Tên hiển thị</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="entry in entries">
                                <td>
                                    <a class="edit-link" :href="'/xadmin/permissions/edit?id='+entry.id"
                                       v-text="entry.id"></a>
                                </td>
                                <td v-text="entry.module"></td>
                                <td v-text="entry.name"></td>
                                <td >
                                    <template v-if="!entry.editMode">
                                        <span v-text="entry.display_name"></span>
                                    </template>
                                    <template v-else>
                                        <input class="form-control" placeholder="Nhập tên hiển thị mới" v-model="entry.display_name_new"/>
                                    </template>
                                </td>

                                <td class="">
                                    <a :href="'/xadmin/permissions/edit?id='+entry.id" class="btn "><i
                                        class="fa fa-edit"></i></a>

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

        </div>
    </div>

</template>

<script>
import {$alert, $get, $post, getTimeRangeAll} from "../../utils";
import $router from '../../lib/SimpleRouter';
import ActionBar from "../includes/ActionBar";

let created = getTimeRangeAll();
const $q = $router.getQuery();

export default {
    name: "PermissionsIndex.vue",
    components: {ActionBar},
    data() {
        return {
            entries: [],
            editMode: false,
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
            const res = await $get('/xadmin/permissions/data', query);
            this.paginate = res.paginate;
            this.entries = res.data;
        },
        async remove(entry) {
            if (!confirm('Xóa bản ghi: ' + entry.id)) {
                return;
            }

            const res = await $post('/xadmin/permissions/remove', {id: entry.id});

            if (res.code) {
                toastr.error(res.message);
            } else {
                toastr.success(res.message);
            }

            $router.updateQuery({page: this.paginate.currentPage, _: Date.now()});
        },
        cancelAll() {
            this.editMode = false;
            this.entries.forEach(e => {
                e.editMode = false;
            })
        },
        async saveAll() {
            if (!confirm('Lưu lại?')) {
                return;
            }

            const entries = this.entries.map(e => {
                return {
                    id: e.id,
                    display_name: e.display_name_new
                }
            });
            const res = await $post('/xadmin/permissions/saveAllName', {
                entries
            });
            $alert(res);
            if (res.code === 200) {
                this.entries.forEach( e => {
                    e.display_name = e.display_name_new;
                    e.editMode = false;
                })
            }
        },
        renameAll() {
            this.editMode = true;
            this.entries.forEach(e => {
                e.display_name_new = e.display_name;
                e.editMode = true;
            })
        },
        filterClear() {
            for (const key in this.filter) {
                this.filter[key] = '';
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
            const res = await $post('/xadmin/permissions/toggleStatus', {
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
