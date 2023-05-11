<template>
    <div class="main-content app-content"> <!-- container -->
        <ActionBar label="Lưu lại" @action="save()" backUrl="/xadmin/{{$table}}/index"/>
        <div class="main-container container-fluid"> <!-- breadcrumb -->
            <div class="breadcrumb-header justify-content-between">
                <div class="left-content"><span class="main-content-title mg-b-0 mg-b-lg-1">{{$name}}</span></div>
                <div class="justify-content-center mt-2">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item tx-15"><a href="/xadmin/dashboard/index">HOME</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{$name}}</li>
                    </ol>
                </div>
            </div> <!-- /breadcrumb --> <!-- row -->

            <div class="row row-sm">

                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header pb-0">
                            <div class="d-flex justify-content-between">
                                <h4 class="card-title mg-b-0">{{$name}} Form</h4></div>

                        </div>
                        <div class="card-body">

                            <input v-model="entry.id" type="hidden" name="id">
                            @foreach ($fields as $field)
                                <div class="form-group">
                                    <label>{{word_normalized($field)}}</label>
                                    <input id="f_{{$field}}" v-model="entry.{{$field}}" name="name"
                                           class="form-control"
                                           placeholder="{{$field}}">
                                    <error-label for="f_{{$field}}" :errors="errors.{{$field}}"></error-label>
                                </div>
                            @endforeach
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
        name: "{{ucfirst($table)}}Form.vue",
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
                const res = await $post('{{$routePrefix}}/{{$table}}/save', {entry: this.entry});
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
                        location.replace('{{$routePrefix}}/{{$table}}/edit?id=' + res.id);
                    }
                }
            }
        }
    }
</script>

<style scoped>

</style>
