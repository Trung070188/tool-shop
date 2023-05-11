<template>
    <div class="main-header side-header sticky nav nav-item " style="margin-bottom: -63px;">
        <div class=" main-container container-fluid">
            <div class="main-header-left ">
                <div class="responsive-logo"><a href="index.html" class="header-logo"> <img
                    src="/assets/img/brand/logo.svg" class="mobile-logo logo-1" alt="logo"> <img
                    src="/assets/img/brand/logo.svg" class="mobile-logo dark-logo-1" alt="logo">
                </a></div>
                <div class="app-sidebar__toggle" data-bs-toggle="sidebar">
                    <a class="open-toggle" href="javascript:void(0);" @click.prevent="toggleMinimizedMenu(true)"><i
                        style="color: #0090D0" class="header-icon fe fe-align-left"></i></a>
                    <a class="close-toggle primary" href="javascript:void(0);" @click.prevent="toggleMinimizedMenu(false)">
                        <i class="header-icon fe fe-x" style="color: #0090D0"></i>
                    </a>
                </div>
                <div class="logo-horizontal"><a href="index.html" class="header-logo"> <img
                    src="/assets/img/brand/logo.svg" class="mobile-logo logo-1" alt="logo"> <img
                    src="/assets/img/brand/logo.svg" class="mobile-logo dark-logo-1" alt="logo">
                </a></div>
                <div class="main-header-center ms-4 d-sm-none d-md-none d-lg-block form-group" style="font-size: 20px;font-weight: 500;color: #0090D0">
                    ASOAPP
                </div>
            </div>
            <div class="main-header-right">
                <button class="navbar-toggler navresponsive-toggler d-lg-none ms-auto" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent-4"
                        aria-controls="navbarSupportedContent-4" aria-expanded="false"
                        aria-label="Toggle navigation"><span
                    class="navbar-toggler-icon fe fe-more-vertical "></span></button>
                <div class="mb-0 navbar navbar-expand-lg navbar-nav-right responsive-navbar navbar-dark p-0">
                    <div class="collapse navbar-collapse" id="navbarSupportedContent-4">
                        <ul class="nav nav-item header-icons navbar-nav-right ms-auto">


                            <li class="dropdown main-profile-menu nav nav-item nav-link ps-lg-2"><a
                                class="new nav-link profile-user d-flex" href="javascript:;"
                                data-bs-toggle="dropdown"><img :alt="auth.name" :src="auth.avatar"
                                                               class=""></a>
                                <div class="dropdown-menu">
                                    <div class="menu-header-content p-3 border-bottom">
                                        <div class="d-flex wd-100p">
                                            <div class="main-img-user"><img :alt="auth.name" :src="auth.avatar"
                                                                            class=""></div>
                                            <div class="ms-3 my-auto"><h6
                                                class="tx-15 font-weight-semibold mb-0" v-text="auth.name"></h6>
                                                <span
                                                    class="dropdown-title-text subtext op-6  tx-12"></span>
                                            </div>
                                        </div>
                                    </div>


                                    <a class="dropdown-item" href="/xadmin/logout">
                                        <i class="far fa-arrow-alt-circle-left"></i> Đăng xuất</a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="d-flex"></div>
            </div>
        </div>
    </div>



</template>

<script>
import ModalSelectDB from "../../components/ModalSelectDB";
const storageKey = 'MENU_MINIMIZED';
import {KEY_CURRENT_DB_NAME} from "../../lib/querybuilder/QueryBuilder";

export default {
    name: "NavBar",
    mounted() {
        const value = localStorage.getItem(storageKey);

        if (value === '1') {
            this.toggleMinimizedMenu(true);
        }
    },
    data() {
        return {
            currentDbName: localStorage.getItem(KEY_CURRENT_DB_NAME),
            reportDatabases: window.$reportDatabases,
            auth: window.$auth,
        }
    },
    components: { ModalSelectDB},
    methods: {
        changeDB() {
            this.$refs.modalSelectDB.show();
        },
        toggleMinimizedMenu(value) {
            if (value) {
                localStorage.setItem(storageKey, '1');
                $(document.body).addClass('sidenav-toggled');
            } else {
                localStorage.setItem(storageKey, '0');
                $(document.body).removeClass('sidenav-toggled');
            }

        }
    }
}
</script>

<style scoped>

</style>
