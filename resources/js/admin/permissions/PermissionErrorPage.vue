<template>
    <div class="container-fluid">
        <ActionBar
                   :title="title"/>
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-custom card-stretch gutter-b">

                    <div class="card-body d-flex flex-column">




                    </div>
                </div>
            </div>


        </div>
        <ModalPermissionError   ref="modal"/>
    </div>

</template>

<script>
    import {$post} from "../../utils";
    import ActionBar from "../includes/ActionBar";
    import ModalPermissionError from "../../components/ModalPermissionError";

    export default {
        name: "PermissionErrorPage",
        components: {ModalPermissionError, ActionBar},
        data() {
            return {
                title: $json.title,
                permissionRequired: $json.permissionRequired,
                isLoading: false,
                errors: {}
            }
        },
        mounted() {

            this.$refs.modal.show(this.title, this.permissionRequired, true)
        },
        methods: {
        }
    }
</script>

<style scoped>

</style>
