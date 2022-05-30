const TabCheck = document.getElementsByClassName('.check-one');
$(function () {
  $(document).on("change", "#select-etat", function () {
    const path = $(this).find(":selected").data("path");
    $.ajax({
      url: path,
      type: "GET",
      dataType: "JSON", // or html or whatever you want
      success: function (data) {
        window.location.href = data;
      },
    });
  });
});


/* $(function () {
  $("#payer-commandes").click(function () {
      //alert(true)
    TabCheck.forEach(element => {
        console.log(element);
    });
  })
}); */
$(function () {
  //button select all or cancel
  $("#check-all").click(function () {
    $(".check-one").each(function (index, item) {
      /*   var all = $("#check-all")[0];
            all.checked = !all.checked
            var checked = all.checked; */
      item.checked = true;
    });
  });
});
$(function () {
  $("#decheck-all").click(function (item) {
    $(".check-one").each(function (index, item) {
      item.checked = false;
    });
  });
});
setTimeout(function () {
  $("#add_commande").fadeOut("fast");
}, 2000); // <-- time in milliseconds
