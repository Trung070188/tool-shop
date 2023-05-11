<template>
    <div class="main-content app-content"> <!-- container -->
        <ActionBar label="Lưu lại" @action="save()" backUrl="/xadmin/campaigns/index"/>
        <div class="main-container container-fluid"> <!-- breadcrumb -->
            <div class="breadcrumb-header justify-content-between">
                <div class="left-content"><span class="main-content-title mg-b-0 mg-b-lg-1">Campaign</span></div>
                <div class="justify-content-center mt-2">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item tx-15"><a href="/xadmin/dashboard/index">HOME</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Campaign</li>
                    </ol>
                </div>
            </div> <!-- /breadcrumb --> <!-- row -->

            <div class="row row-sm">

                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header pb-0">
                            <div class="d-flex justify-content-between">
                                <h4 class="card-title mg-b-0">Campaign Form</h4></div>

                        </div>
                        <div class="card-body">

                            <input v-model="entry.id" type="hidden" name="id">
                                                            <div class="form-group">
                                    <label>Name</label>
                                    <input id="f_name" v-model="entry.name" name="name"
                                           class="form-control"
                                           placeholder="name">
                                    <error-label for="f_name" :errors="errors.name"></error-label>
                                </div>
                                                            <div class="form-group">
                                    <label>Package Id</label>
                                    <input id="f_package_id" v-model="entry.package_id" name="name"
                                           class="form-control"
                                           placeholder="package_id">
                                    <error-label for="f_package_id" :errors="errors.package_id"></error-label>
                                </div>
                                                            <div class="form-group">
                                    <label>Icon</label>
                                    <input id="f_icon" v-model="entry.icon" name="name"
                                           class="form-control"
                                           placeholder="icon">
                                    <error-label for="f_icon" :errors="errors.icon"></error-label>
                                </div>
                                                            <div class="form-group">
                                    <label>Price</label>
                                    <input id="f_price" v-model="entry.price" name="name"
                                           class="form-control"
                                           placeholder="price">
                                    <error-label for="f_price" :errors="errors.price"></error-label>
                                </div>
                                                            <div class="form-group">
                                    <label>Os</label>
                                    <input id="f_os" v-model="entry.os" name="name"
                                           class="form-control"
                                           placeholder="os">
                                    <error-label for="f_os" :errors="errors.os"></error-label>
                                </div>
                                                            <div class="form-group">
                                    <label>Customer Id</label>
                                    <input id="f_customer_id" v-model="entry.customer_id" name="name"
                                           class="form-control"
                                           placeholder="customer_id">
                                    <error-label for="f_customer_id" :errors="errors.customer_id"></error-label>
                                </div>
                                                            <div class="form-group">
                                    <label>Type</label>
                                    <input id="f_type" v-model="entry.type" name="name"
                                           class="form-control"
                                           placeholder="type">
                                    <error-label for="f_type" :errors="errors.type"></error-label>
                                </div>
                                                            <div class="form-group">
                                    <label>Status</label>
                                    <input id="f_status" v-model="entry.status" name="name"
                                           class="form-control"
                                           placeholder="status">
                                    <error-label for="f_status" :errors="errors.status"></error-label>
                                </div>
                                                    </div>
                    </div>
                </div> <!--/div--> <!--div-->

            </div> <!-- /row -->
        </div>


    </div> <!-- /main-content -->

</template>

<script>
    import {$post} from "../../utils";
    import ActionBar from '../../components/ActionBar';

    export default {
        name: "CampaignsForm.vue",
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
                const res = await $post('/xadmin/campaigns/save', {entry: this.entry});
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
                        location.replace('/xadmin/campaigns/edit?id=' + res.id);
                    }
                }
            }
        }
    }
</script>

<style scoped>

</style>
