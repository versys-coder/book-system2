(function() {
  var form = document.getElementById('aquastart-form');
  var msg = document.getElementById('aquastart-msg');
  var submitBtn = document.getElementById('aquastart-submit-btn');
  var agreement = document.getElementById('aquastart-agreement');
  var agreementError = document.getElementById('aquastart-agreement-error');
  function makeToken() {
    var now = Date.now();
    var rand = Math.floor(Math.random() * 1e8);
    var base = btoa((now + rand).toString());
    return base.split('').reverse().join('');
  }
  form.addEventListener('submit', async function(e) {
    msg.textContent = '';
    msg.style.color = '';
    agreementError.style.display = 'none';
    if (!agreement.checked) {
      agreementError.textContent = 'Необходимо принять политику конфиденциальности и согласие на обработку данных!';
      agreementError.style.display = 'block';
      agreement.focus();
      e.preventDefault();
      return;
    }
    e.preventDefault();
    var child_name = form.child_name.value.trim();
    var child_birthdate = form.child_birthdate.value.trim();
    var parent_name = form.parent_name.value.trim();
    var phone = form.phone.value.trim();
    var email = form.email.value.trim();
    var wishes = form.wishes.value.trim();
    if (child_name.length < 2) { msg.textContent = 'Введите фамилию и имя ребенка.'; msg.style.color = '#d32f2f'; form.child_name.focus(); return; }
    if (!/^\d{2}\.\d{2}\.\d{4}$/.test(child_birthdate)) { msg.textContent = 'Введите корректную дату рождения ребенка (ДД.ММ.ГГГГ).'; msg.style.color = '#d32f2f'; form.child_birthdate.focus(); return; }
    if (parent_name.length < 2) { msg.textContent = 'Введите ФИО родителя.'; msg.style.color = '#d32f2f'; form.parent_name.focus(); return; }
    if (phone.replace(/[^\d]/g, '').length < 11) { msg.textContent = 'Введите корректный мобильный телефон (начиная с +7).'; msg.style.color = '#d32f2f'; form.phone.focus(); return; }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { msg.textContent = 'Некорректный e-mail.'; msg.style.color = '#d32f2f'; form.email.focus(); return; }
    if (wishes.length < 1) { msg.textContent = 'Заполните поле "Пожелания".'; msg.style.color = '#d32f2f'; form.wishes.focus(); return; }
    submitBtn.disabled = true;
    submitBtn.textContent = 'Отправка...';
    var tokInput = document.getElementById('aquastart_js_token');
    if (tokInput) tokInput.value = makeToken();
    try {
      var fd = new FormData(form);
      var resp = await fetch('https://price.dvvs-ekb.ru/aquastart/save-form.php', { method: 'POST', body: fd });
      var txt = await resp.text();
      if ((txt || '').toLowerCase().indexOf('перезвоним') !== -1) {
        msg.textContent = txt;
        msg.style.color = '#237e3b';
        form.reset();
        agreement.checked = true;
        setTimeout(function() { msg.textContent = ''; }, 3000);
      } else {
        msg.textContent = txt || 'Ошибка на сервере';
        msg.style.color = '#d32f2f';
      }
    } catch (err) {
      msg.textContent = 'Ошибка при отправке, попробуйте позже.';
      msg.style.color = '#d32f2f';
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Отправить заявку';
    }
  });
  agreement.addEventListener('change', function() {
    if (!agreement.checked) {
      agreementError.textContent = 'Необходимо принять политику конфиденциальности и согласие на обработку данных!';
      agreementError.style.display = 'block';
    } else {
      agreementError.textContent = '';
      agreementError.style.display = 'none';
    }
  });
})();