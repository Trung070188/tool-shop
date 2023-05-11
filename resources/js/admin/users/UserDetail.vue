<template>
  <div class="container-fluid">
    <ActionBar type="detail" @remove="remove(entry)"
               :typeSync="entry.type"
               :code="entry.id"
               backUrl="/xadmin/users/index"
               :updateUrl="'/xadmin/users/edit?id=' + entry.id"
               title="Chi tiết người dùng"/>
    <div class="row">
      <div class="col-lg-12">
        <div class="card card-custom card-stretch gutter-b">
          <div class="card-body d-flex flex-column">
            <input v-model="entry.id" type="hidden" name="id">
            <div class="row mb-7">
              <label class="col-lg-2 col-4 fw-semibold text-muted">Tên</label>
              <div class="col-lg-10 col-8">
                <span class="fw-bold fs-6 text-gray-800" v-text="entry.name"></span>
              </div>
            </div>
            <div class="row mb-7">
              <label class="col-lg-2 col-4 fw-semibold text-muted">Email</label>
              <div class="col-lg-10 col-8">
                <span class="fw-bold fs-6 text-gray-800" v-text="entry.email"></span>
              </div>
            </div>
            <div class="row mb-7">
              <label class="col-lg-2 col-4 fw-semibold text-muted">Chức danh</label>
              <div class="col-lg-10 col-8">
                <span class="fw-bold fs-6 text-gray-800" v-text="entry.position"></span>
              </div>
            </div>
            <div class="row mb-7">
              <label class="col-lg-2 col-4 fw-semibold text-muted">Đơn vị</label>
              <div class="col-lg-10 col-8">
                <span class="fw-bold fs-6 text-gray-800" v-text="entry.unit_name"></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import ActionBar from "../includes/ActionBar";
import AutoComplete from "../../components/AutoComplete";
import {$post} from "../../utils";
import swal from "sweetalert";
import $router from "../../lib/SimpleRouter";

export default {
  name: "UserDetail",
  components: {ActionBar, AutoComplete},
  data() {
    return {
      entry: $json.entry || {},
      isLoading: false,
      errors: {}
    }
  },

  methods: {
    async remove(entry) {
      swal({
        title: "Cảnh báo",
        text: "Bạn có chắc bạn muốn xóa mục này?",
        icon: "warning",
        buttons: {
          cancel: 'Hủy bỏ',
          confirm: 'Xóa',
        },
        dangerMode: true,
      }).then((isConfirm) => {
        if(isConfirm) {
          const res = $post('/xadmin/users/remove', {id: entry.id});
          if (res.code) {
            toastr.error(res.message);
          } else {
            toastr.success('Đã xóa');
          }

          $router.updateQuery({page: this.paginate.currentPage, _: Date.now()});
        }
      });
    },
  }
}
</script>

<style scoped>

</style>
