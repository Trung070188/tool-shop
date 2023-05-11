/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

//require('./bootstrap');

import Vue, { createApp } from 'vue';
import {botInfo, botWarn} from "./utils";
import AppLayout from "./admin/layouts/AppLayout";
import QFileManager from "./components/QFileManager";
import {globalMixin} from "./mixin";

window.Vue = require('vue');
window.toastr = require('toastr');
let app;

if (window.APP_NAME === 'FileManager') {
    app = createApp(QFileManager)
} else {
    app = createApp(AppLayout)
}

app.component('Paginate', require('./components/Paginate').default);
app.component('SwitchButton', require('./components/SwitchButton').default);
app.component('Daterangepicker', require('./components/Daterangepicker').default);
app.component('RichtextEditor', require('./components/RichtextEditor').default);
app.component('ErrorLabel', require('./components/ErrorLabel').default);
app.component('SaveButton', require('./components/SaveButton').default);

function main() {

    const el = document.getElementById('root-app');
    if (!el) {
        botWarn('#root-app not found');
        return;
    }

    app.mixin(globalMixin)
    app.mount(el)
}

try {
    document.addEventListener('DOMContentLoaded', main);
} catch (err) {
    console.error(err);
}
