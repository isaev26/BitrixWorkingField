$(document).ready(function() {
    $("#btn").click(function (e) {
        e.preventDefault();
        let path = $("input[name='pathToTemplate']" ).val();
        $.ajax({
                url: path+'/ajax.php', //url страницы (ajax.php)
                type: "POST", //метод отправки
                data: $("#form").serialize(),  // Сеарилизуем объект
                success: function (response) { //Данные отправлены успешно
                    let result = $.parseJSON(response);
                    $('.result_form').html('Рабочий день: ' + result.calendar);
                },
                error: function (response) { // Данные не отправлены
                    $('.result_form').html('Ошибка. Данные не отправлены.');
                }
            }
        );
    });
});