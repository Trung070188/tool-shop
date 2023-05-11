<template>
    <div class="main-footer">
        <div class="col-md-12 col-sm-12 text-center">
            <div class="container-fluid pt-0 ht-100p">
                Copyright © 2022
                <a href="javascript:;" class="text-primary">
                VietnamPost Digital
            </a> ({{LAST_REPORT_AT}})
            </div>
        </div>
    </div> <!-- Footer closed -->
</template>

<script>
export default {
    name: "Footer",
    data() {
        return {
            SERVER_TIME: window.$SERVER_TIME,
            LAST_REPORT_AT: window.$LAST_REPORT_AT,
        }
    }
}
</script>

<style scoped>

</style>
