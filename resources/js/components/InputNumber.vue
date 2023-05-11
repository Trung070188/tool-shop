<template>
  <input maxlength="20" type="text" :placeholder="placeholder"
         :value="value" @input="updateValue($event)" class="form-control jqnumber">
</template>

<script>
import {numberFormat, parseIntEx} from "../utils";

export default {
  name: "InputNumber",
  props: ['value', 'placeholder'],
  mounted() {
    this.$el.value = numberFormat(parseIntEx(this.value))
  },
  watch: {
    'value': function (newValue) {
      this.$el.value = numberFormat(newValue)
    }
  },
  methods: {
    updateValue: function ($event) {

      var type = this.type || 'int';

      if ($event.target.value === '-') {
        return;
      }

      var numVal, timeID, n = 0;
      if (type === 'float') {
        n = 2;
      }

      var self = this;

      timeID = setTimeout(function () {
        if (timeID) {
          clearTimeout(timeID);
        }

        var val = $event.target.value.replace(/,/g, '');
        numVal = Number(val) || 0.0;
        $event.target.value = numberFormat(numVal, n);
        // console.log(formatNumber(numVal,2))
        self.$emit('input', numberFormat(numVal, n))
      }, 500)
    }
  }
}
</script>

<style scoped>

</style>
