(function(){
  // Grab elements
  var form = document.getElementById('adaptive-form');
  if (!form) { console.warn('adaptive-form not found'); return; }

  var inner = document.getElementById('adaptive-inner');
  var msg = document.getElementById('adaptive-msg');
  var submitBtn = document.getElementById('adaptive-submit-btn');
  var agreement = document.getElementById('adaptive-agreement');
  var agreementError = document.getElementById('adaptive-agreement-error');
  var thankYou = document.getElementById('adaptive-thankyou');

  function cssVar(name, fallback) {
    try { return getComputedStyle(document.documentElement).getPropertyValue(name) || fallback; }
    catch(e){ return fallback; }
  }
  var ERROR_COLOR = cssVar('--error', '#d32f2f');

  function makeToken() {
    var now = Date.now();
    var rand = Math.floor(Math.random() * 1e8);
    var base = btoa((now + rand).toString());
    return base.split('').reverse().join('');
  }

  function showError(text, elToFocus) {
    if (!msg) return;
    msg.textContent = text;
    msg.style.color = ERROR_COLOR;
    try {
      if (elToFocus && typeof elToFocus.focus === 'function') elToFocus.focus();
      msg.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } catch(e){}
  }

  function normalizeDigits(s) {
    return (s || '').replace(/[^\d]/g, '');
  }

  // --- АВТОЗАПОЛНЕНИЕ ДЛЯ ТЕСТА ---
  // Удалите или закомментируйте этот блок перед публикацией!
  if (form && !form.child_name.value) {
    form.child_name.value = "Иванов Иван";
    form.child_birthdate.value = "12.03.2018";
    form.parent_name.value = "Иванова Мария Сергеевна";
    form.phone.value = "+7 (922) 123-4567";
    form.email.value = "s.volchkov@dvvs-ekb.ru";
    form.health_notes.value = "Имеется справка от невролога, требуется индивидуальный подход.";
    form.wishes.value = "Удобно после 18:00. Желательно будние дни.";
  }
  // --- КОНЕЦ БЛОКА АВТОЗАПОЛНЕНИЯ ---

  form.addEventListener('submit', async function(e){
    e.preventDefault();

    if (msg) { msg.textContent = ''; msg.style.color = ''; }
    if (agreementError) { agreementError.textContent = ''; agreementError.style.display = 'none'; }

    // Read values
    var child_name = (form.child_name?.value || '').trim();
    var child_birthdate = (form.child_birthdate?.value || '').trim();
    var parent_name = (form.parent_name?.value || '').trim();
    var phone = (form.phone?.value || '').trim();
    var email = (form.email?.value || '').trim();
    var health_notes = (form.health_notes?.value || '').trim();
    var wishes = (form.wishes?.value || '').trim();

    // Validations
    if (agreement && !agreement.checked) {
      if (agreementError) {
        agreementError.textContent = 'Необходимо принять политику конфиденциальности и согласие на обработку данных!';
        agreementError.style.display = 'block';
      }
      try { agreement.focus(); } catch(e){}
      return;
    }
    if (child_name.length < 2) { showError('Введите фамилию и имя ребёнка.', form.child_name); return; }

    if (!/^\d{2}\.\d{2}\.\d{4}$/.test(child_birthdate)) {
      showError('Введите корректную дату рождения (ДД.ММ.ГГГГ).', form.child_birthdate);
      return;
    }

    if (parent_name.length < 2) { showError('Введите ФИО родителя.', form.parent_name); return; }

    if (normalizeDigits(phone).length < 11) {
      showError('Введите корректный мобильный телефон (начиная с +7).', form.phone);
      return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showError('Некорректный e-mail.', form.email);
      return;
    }

    if (health_notes.length < 1) {
      showError('Пожалуйста, заполните поле "Особенности здоровья / рекомендации врача".', form.health_notes);
      return;
    }

    if (wishes.length < 1) {
      showError('Пожалуйста, заполните поле "Пожелания".', form.wishes);
      return;
    }

    // Disable submit
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.dataset.prevText = submitBtn.textContent;
      submitBtn.textContent = 'Отправка...';
    }

    // Token
    var tokInput = document.getElementById('adaptive_js_token');
    if (tokInput) tokInput.value = makeToken();

    try {
      var fd = new FormData(form);
      // Ensure backend receives field as "diagnosis"
      if (!fd.has('diagnosis')) fd.append('diagnosis', health_notes);

      var resp = await fetch('https://dvvs.online/adaptive/save-form.php', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        cache: 'no-cache'
      });

      var text = await resp.text();
      var isSuccess = resp.ok || (/перезвон/i.test(text) || /спасиб/i.test(text));

      if (isSuccess) {
        if (inner) inner.style.display = 'none';
        if (thankYou) thankYou.style.display = 'block';
        try { form.reset(); } catch(e){}
        if (agreement) agreement.checked = true;
        if (msg) msg.textContent = '';
      } else {
        showError(text || 'Ошибка на сервере');
      }
    } catch (err) {
      showError('Ошибка при отправке, попробуйте позже.');
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = submitBtn.dataset.prevText || 'Отправить заявку';
      }
    }
  });

  if (agreement) {
    agreement.addEventListener('change', function(){
      if (!agreement.checked) {
        if (agreementError) {
          agreementError.textContent = 'Необходимо принять политику конфиденциальности и согласие на обработку данных!';
          agreementError.style.display = 'block';
        }
      } else {
        if (agreementError) {
          agreementError.textContent = '';
          agreementError.style.display = 'none';
        }
      }
    });
  }
})();