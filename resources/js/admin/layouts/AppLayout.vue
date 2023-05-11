<template>
    <div class="page ">
        <div>
            <NavBar/>
            <div class="jumps-prevent" style="padding-top: 63px;"></div>
            <SideBar/>
            <div class="jumps-prevent" style="padding-top: 63px;"></div>
        </div>

        <Main/>

        <Footer/>
    </div>
</template>

<script>

import registry from "../../registry";
import {botWarn} from "../../utils";
import ModalPermissionError from "../../components/ModalPermissionError";
import SideBar from "../includes/SideBar";
import NavBar from "../includes/NavBar";
import Footer from "../includes/Footer";
import ModalSelectDB from "../../components/ModalSelectDB";

const componentName = window.$componentName;

if (!componentName) {
    botWarn('You need to set component in controller');
}

const component = registry[componentName];

export default {
    name: "AppLayout.vue",
    components: {ModalSelectDB, Footer, NavBar, SideBar, Main: component, ModalPermissionError},
    mounted() {
        const self = this;
        $('#global-loader').hide();
        window.showModalPermissionError = function (title, requiredPermission, allowClose) {
            self.$refs.modalPermissionError.show(title, requiredPermission, allowClose);
        }

        window.$modalRefs = {
            modalSelectDB: this.$refs.modalSelectDB
        }
    },
    data() {
        return {}
    }
}
</script>

<style scoped>

</style>
