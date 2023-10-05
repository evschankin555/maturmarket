function getCookie(name) {
    let matches = document.cookie.match(
      new RegExp(
        "(?:^|; )" +
          name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, "\\$1") +
          "=([^;]*)"
      )
    );
    return matches ? decodeURIComponent(matches[1]) : undefined;
}

function initByCurrentLocation() {
  $.ajax({
    url: "index.php?route=api/city",
    type: "GET",
    success: function (response) {
      let fiasId = response.fiasId;            

      let oldFiasId = getCookie("fiasId"); // Местоположение пользователя, которое было в прошлое посещение

      // Если зашли впервые или текущее местоположение не совпадает с местоположением прошлого посещения
      // то покажем всплывашку автоопределения города
      if (oldFiasId !== fiasId) {
        $(".modal-ask").attr("city_id", response.city_id);
        $(".dev_modal_ask_city_name").text(response.city_name);
        $(".modal-ask").addClass("modal-show");
      } else {
        $(".modal-ask").removeClass("modal-show");
      }
      document.cookie = "fiasId=" + fiasId + "; domain=" + document.domain;
    },
  });
}

initByCurrentLocation();

// Открытие формы выбора городов
$(document).on('click', '.dev_selected_city', function(){
  $('.modal-city').addClass("modal-show");
});

// Кнопки на всплывашке-плодсказке по городу
// Да
$(document).on('click', '.modal-ask__button_yes', function(){
  $('.modal-ask').removeClass("modal-show");

  if (getCookie("selectedCityId") != $(".modal-ask").attr("city_id")) {
    $(".dev_selected_city").text($(".dev_modal_ask_city_name").text());
    document.cookie = "selectedCityId=" + encodeURIComponent($(".modal-ask").attr("city_id")) + "; domain=" + document.domain;  
    location.reload();
  }
});
// Нет
$(document).on('click', '.modal-ask__button_no', function(){
  $('.modal-ask').removeClass("modal-show");
  $('.modal-city').addClass("modal-show");
});

// Кнопка Отмена в форме выбора города
$(document).on('click', '.modal-city__button, .popup__close', function(){
  $('.modal-city').removeClass("modal-show");
});
// Кнопка Esc также закрывает форму выбора города
document.addEventListener("keydown", (evt) => {
  if (evt.key === "Escape" || evt.key === "Esc") {
    evt.preventDefault();
    $('.modal-city').removeClass("modal-show");
  }
});
// Кнопка конкретного города в форме выбора города
$(document).on('click', '.dev_city_choose', function(){
  document.cookie = "selectedCityId=" + encodeURIComponent($(this).attr("city_id")) + "; domain=" + document.domain;
  location.reload();
});
