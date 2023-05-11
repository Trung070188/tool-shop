<template>
    <select
        @change="onChange"
        class="form-control form-select" style="min-width: 154px" v-model="value" >
        <option :value="y" v-for="y in reportYears" :key="y">{{ y }}</option>
    </select>
</template>

<script>
import moment from "moment";
import {range} from "../lib/collection";

export default {
    props: ['loading', 'text', 'icon', 'modelValue'],
    name: "SelectYear",
    data() {
        const endYear = moment().year()
        return {
            reportYears: [endYear, endYear - 1],
            isLoading: this.loading || false,
            label: this.text || 'Lưu lại',
            fa: this.icon || 'fa fa-check',
            value: this.modelValue || moment().year()
        }
    },
    watch: {
        modelValue: function (newValue) {
            if (newValue !== this.value) {
                this.value = newValue;
            }
        }
    },
    mounted() {
    },
    methods: {
        onChange() {
            this.$emit('update:modelValue', this.value)
        }
    }
}


</script>

<style scoped>

</style>
