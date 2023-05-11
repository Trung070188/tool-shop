<template>
    <div class="container-fluid">

        <div class="row">
            <div class="col-lg-12">
                <div class="card card-custom card-stretch gutter-b">
                    <div class="card-body d-flex flex-column">
                        <div class="row">
                            <div class="col-lg-12">
                                <input v-model="entry.id" type="hidden" name="id">
                                                                    <div class="form-group">
                                        <label>Code</label>
                                        <input id="f_code" v-model="entry.code" name="name"
                                               class="form-control"
                                               placeholder="code">
                                        <error-label for="f_code" :errors="errors.code"></error-label>
                                    </div>
                                                                    <div class="form-group">
                                        <label>Username</label>
                                        <input id="f_username" v-model="entry.username" name="name"
                                               class="form-control"
                                               placeholder="username">
                                        <error-label for="f_username" :errors="errors.username"></error-label>
                                    </div>
                                                                    <div class="form-group">
                                        <label>Name</label>
                                        <input id="f_name" v-model="entry.name" name="name"
                                               class="form-control"
                                               placeholder="name">
                                        <error-label for="f_name" :errors="errors.name"></error-label>
                                    </div>
                                                                    <div class="form-group">
                                        <label>Birthday</label>
                                        <input id="f_birthday" v-model="entry.birthday" name="name"
                                               class="form-control"
                                               placeholder="birthday">
                                        <error-label for="f_birthday" :errors="errors.birthday"></error-label>
                                    </div>
                                                                    <div class="form-group">
                                        <label>Phone</label>
                                        <input id="f_phone" v-model="entry.phone" name="name"
                                               class="form-control"
                                               placeholder="phone">
                                        <error-label for="f_phone" :errors="errors.phone"></error-label>
                                    </div>
                                                                    <div class="form-group">
                                        <label>Email</label>
                                        <input id="f_email" v-model="entry.email" name="name"
                                               class="form-control"
                                               placeholder="email">
                                        <error-label for="f_email" :errors="errors.email"></error-label>
                                    </div>
                                                                    <div class="form-group">
                                        <label>Address</label>
                                        <input id="f_address" v-model="entry.address" name="name"
                                               class="form-control"
                                               placeholder="address">
                                        <error-label for="f_address" :errors="errors.address"></error-label>
                                    </div>
                                                                    <div class="form-group">
                                        <label>Status</label>
                                        <input id="f_status" v-model="entry.status" name="name"
                                               class="form-control"
                                               placeholder="status">
                                        <error-label for="f_status" :errors="errors.status"></error-label>
                                    </div>
                                                                    <div class="form-group">
                                        <label>Type</label>
                                        <input id="f_type" v-model="entry.type" name="name"
                                               class="form-control"
                                               placeholder="type">
                                        <error-label for="f_type" :errors="errors.type"></error-label>
                                    </div>
                                                                    <div class="form-group">
                                        <label>Created By</label>
                                        <input id="f_created_by" v-model="entry.created_by" name="name"
                                               class="form-control"
                                               placeholder="created_by">
                                        <error-label for="f_created_by" :errors="errors.created_by"></error-label>
                                    </div>
                                                                    <div class="form-group">
                                        <label>Updated By</label>
                                        <input id="f_updated_by" v-model="entry.updated_by" name="name"
                                               class="form-control"
                                               placeholder="updated_by">
                                        <error-label for="f_updated_by" :errors="errors.updated_by"></error-label>
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


    export default {
        name: "UsersForm.vue",
        components: {},
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
                const res = await $post('/xadmin/users/save', {entry: this.entry}, false);
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
                        location.replace('/xadmin/users/edit?id=' + res.id);
                    }
                }
            }
        }
    }
</script>

<style scoped>

</style>
