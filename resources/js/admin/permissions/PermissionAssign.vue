<template>
    <div class="container-fluid">
        <ActionBar type="form" @save="save()"
                   :backUrl="backUrl"
                   title="Phân quyền"/>
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-custom card-stretch gutter-b">

                    <div class="card-body d-flex flex-column">
                        <div class="row">
                           <h3> Phần quyền cho {{assignType}} : {{entry.name}}</h3>
                        </div>
                        <q-tree-select ref="tree" v-model="model.values"  :items="permissionTree"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import QTreeSelect from "../../components/qtreeselect/QTreeSelect";
    import {$alert, $post, clone} from "../../utils";
    import ActionBar from "../includes/ActionBar";

    export default {
        name: "PermissionAssign",
        components: {QTreeSelect, ActionBar},
        data() {
            const requestData = $json.requestData;

            let backUrl = '/xadmin/permissions/index';
            if (requestData.type === 'user') {
                backUrl = '/xadmin/users/index'
            } else if (requestData.type === 'role') {
                backUrl = '/xadmin/roles/index'
            }


            return {
                backUrl,
                assignType: requestData.type,
                model: clone($json.model),
                entry: clone($json.entry),
                permissionTree: clone($json.tree)
            }
        },
        mounted() {
        },
        methods: {
            async save() {
                const res  = await $post('/xadmin/permissions/assigns', this.model);
                $alert(res);
            }
        }
    }
</script>

<style scoped>

</style>
