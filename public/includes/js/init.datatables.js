/* global $ */
$(document).ready(function () {
  $('#example').DataTable({
    scrollX: !0,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.10.19/i18n/Dutch.json'
    },
    ordering: !0
  })
})
