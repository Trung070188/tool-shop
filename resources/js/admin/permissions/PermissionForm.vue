<template>
    <div class="container-fluid">
        <ActionBar type="form" @save="save()"
                   :code="entry.id"
                   backUrl="/xadmin/permissions/index"
                   title="PermissionForm"/>
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-custom card-stretch gutter-b">

                    <div class="card-body d-flex flex-column">

                        <div class="row">
                            <div class="col-lg-12">
                                <input v-model="entry.id" type="hidden" name="id">
                                <div class="form-group">
                                    <label>Tên hiển thị</label>
                                    <input id="f_name" v-model="entry.display_name" name="name" class="form-control"
                                           placeholder="name">
                                    <error-label for="f_name" :errors="errors.name"></error-label>

                                </div>
                                <div class="form-group">
                                    <label>Class</label>
                                    <input disabled id="f_class" v-model="entry.class" name="name" class="form-control"
                                           placeholder="class">
                                    <error-label for="f_class" :errors="errors.class"></error-label>

                                </div>
                                <div class="form-group">
                                    <label>Action</label>
                                    <input disabled id="f_action" v-model="entry.action" name="name" class="form-control"
                                           placeholder="action">
                                    <error-label for="f_action" :errors="errors.action"></error-label>

                                </div>

                            </div>


                        </div>


                    </div>
                </div>
            </div>


        </div>
    </div>

</template>

<script>
    import {$post} from "../../utils";
    import ActionBar from "../includes/ActionBar";

    export default {
        name: "PermissionsForm.vue",
        components: {ActionBar},
        data() {
            return {
                entry: $json.entry || {},
                isLoading: false,
                errors: {}
            }
        },
        methods: {
            async save() {
                this.isLoading = true;
                const res = await $post('/xadmin/permissions/save', {entry: this.entry}, false);
                this.isLoading = false;
                if (res.errors) {
                    this.errors = res.errors;
                    return;
                }
                if (res.code) {
                    toastr.error(res.message);
                } else {
                    this.errors = {};
                    toastr.success(res.message);

                    if (!this.entry.id) {
                        location.replace('/xadmin/permissions/edit?id=' + res.id);
                    }

                }
            }
        }
    }
</script>

<style scoped>

</style>
