

document.querySelector('.amoCRMForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Останавливает отправку формы по умолчанию

    // Собираем данные формы
    const formData = new FormData(this);
    const name = formData.get("name").trim();
    const email = formData.get("email").trim();
    const phone = formData.get("phone").trim();
    const price = formData.get("price").trim();
    formData.set("name", name);
    formData.set("email", email);
    formData.set("phone", phone);
    formData.set("price", price);

    // Отправляем данные через fetch
    fetch('src/process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text(); // или response.json(), если PHP возвращает JSON
    })
    .then(data => {
        console.log('Success:', data);
        // this.reset();
        // Здесь можно добавить действия по обработке успешного ответа от сервера
    })
    .catch(error => {
        console.error('Error:', error);
        // Здесь можно добавить действия по обработке ошибок
    });
});