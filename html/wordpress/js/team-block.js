(function(){
console.log('== Тест: скрипт тренеров загружен ==');   
 var trainers = {
        tab1: [
            {
                name: 'Егоров Алексей Владимирович',
                title: 'Старший тренер-преподаватель отделения "Плавание"',
                img: '/storage/teammembers/28-02-2025/N5WLAvnZ4IaFNlF.webp',
                info: `<span><strong>Уровень образования:</strong> высшее</span>
<span><strong>Квалификация:</strong> физическая культура и спорт. Тренер-преподаватель</span>
<span><strong>Направление подготовки:</strong> физическая культура и спорт</span>
<span><strong>Звание:</strong> мастер спорта России международного класса</span>
<span><strong>Категория:</strong> высшая квалификационная категория</span>
<span><strong>Повышение квалификации:</strong> "Физическая культура и спорт. Тренер-преподаватель"</span>
<span><strong>Стаж работы по специальности:</strong> 28 лет</span>`
            },
            {
                name: 'Давыдов Евгений Андреевич',
                title: 'Тренер-преподаватель отделения "Плавание"',
                img: '/storage/teammembers/20-05-2025/f6zRe71rZj6QzoE.webp',
                info: `<span><strong>Уровень образования:</strong> высшее</span>
<span><strong>Квалификация:</strong> физическая культура и спорт. Тренер-преподаватель</span>
<span><strong>Направление подготовки:</strong> физическая культура и спорт</span>
<span><strong>Категория:</strong> высшая квалификационная категория</span>
<span><strong>Повышение квалификации:</strong> "Физическая культура и спорт. Тренер-преподаватель"</span>
<span><strong>Стаж работы по специальности:</strong> 8 лет</span>`
            },
            {
                name: 'Чучкалов Денис Васильевич',
                title: 'Тренер-преподаватель отделения "Плавание"',
                img: '/storage/teammembers/03-03-2025/DBMp9RpHzf5SqfY.webp',
                info: `<span><strong>Уровень образования:</strong> высшее</span>
<span><strong>Квалификация:</strong> физическая культура и спорт. Тренер-преподаватель</span>
<span><strong>Категория:</strong> высшая квалификационная категория</span>
<span><strong>Направление подготовки:</strong> физическое воспитание и спорт</span>
<span><strong>Повышение квалификации:</strong> "Физическая культура и спорт. Тренер-преподаватель"</span>
<span><strong>Стаж работы по специальности:</strong> 13 лет</span>`
            },
            {
                name: 'Силина Ирина Владимировна',
                title: 'Тренер-преподаватель отделения "Плавание"',
                img: '/storage/teammembers/03-03-2025/Wm16AUlwrXvkjsd.webp',
                info: `<span><strong>Уровень образования:</strong> высшее</span>
<span><strong>Квалификация:</strong> педагог по физической культуре</span>
<span><strong>Направление подготовки:</strong> физическая культура</span>
<span><strong>Звание:</strong> мастер спорта СССР</span>
<span><strong>Категория:</strong> высшая квалификационная категория</span>
<span><strong>Повышение квалификации:</strong> "Физическая культура и спорт. Тренер-преподаватель"</span>
<span><strong>Стаж работы по специальности:</strong> 27 лет</span>`
            },
            {
                name: 'Силин Сергей Дмитриевич',
                title: 'Тренер-преподаватель отделения "Плавание"',
                img: '/storage/teammembers/03-03-2025/HEihFU03PRRqgUR.webp',
                info: `<span><strong>Уровень образования:</strong> высшее</span>
<span><strong>Квалификация:</strong> бакалавр</span>
<span><strong>Направление подготовки:</strong> физическая культура</span>
<span><strong>Звание:</strong> мастер спорта России международного класса</span>
<span><strong>Категория:</strong> первая квалификационная категория</span>
<span><strong>Повышение квалификации:</strong> "Физическая культура и спорт. Тренер-преподаватель"</span>
<span><strong>Стаж работы по специальности:</strong> 6 лет</span>`
            },
            {
                name: 'Гузеев Павел Валерьевич',
                title: 'Тренер-преподаватель отделения "Плавание"',
                img: 'https://дввс.рф/storage/teammembers/04-03-2025/XRZ4z6Ipi29jNwI.webp',
                info: `<span><strong>Уровень образования:</strong> высшее</span>
<span><strong>Квалификация:</strong> педагог по физической культуре</span>
<span><strong>Направление подготовки:</strong> физическая культура</span>
<span><strong>Категория:</strong> первая квалификационная категория</span>
<span><strong>Стаж работы по специальности:</strong> 14 лет</span>`
            },
            {
                name: 'Надеев Максим Викторович',
                title: 'Тренер-преподаватель отделения "Плавание"',
                img: 'https://дввс.рф/storage/teammembers/03-03-2025/FgMedzoNbyTgiAB.webp',
                info: `<span><strong>Уровень образования:</strong> высшее</span>
<span><strong>Квалификация:</strong> педагог по физической культуре</span>
<span><strong>Направление подготовки:</strong> физическая культура</span>
<span><strong>Категория:</strong> первая квалификационная категория</span>
<span><strong>Стаж работы по специальности:</strong> 12 лет</span>`
            }
        ],
        tab2: [
            {
                name: 'Макаренко Максим Евгеньевич',
                title: 'Старший тренер-преподаватель отделения "Прыжки в воду"',
                img: '/storage/teammembers/1730317420/Макаренко.jpg',
                info: `<span><strong>Уровень образования:</strong> среднее профессиональное</span>
<span><strong>Квалификация:</strong> педагог по физической культуре и спорту</span>
<span><strong>Направление подготовки:</strong> физическая культура</span>
<span><strong>Профессиональная переподготовка:</strong> «Физическая культура и спорт»</span>
<span><strong>Повышение квалификации:</strong> "Физическая культура и спорт. Тренер-преподаватель"</span>
<span><strong>Звание:</strong> мастер спорта России</span>
<span><strong>Категория:</strong> высшая квалификационная категория</span>
<span><strong>Стаж работы по специальности:</strong> 12 лет</span>`
            },
            {
                name: 'Курохтина Алена Станиславовна',
                title: 'Тренер-преподаватель отделения "Прыжки в воду"',
                img: '/storage/teammembers/13-03-2025/fpUK4w4QkcIbCxD.webp',
                info: `<span><strong>Уровень образования:</strong> высшее</span>
<span><strong>Квалификация:</strong> бакалавр</span>
<span><strong>Направление подготовки:</strong> педагогическое образование</span>
<span><strong>Стаж работы по специальности:</strong> 11 лет</span>`
            },
            {
                name: 'Хохрякова Яна Сергеевна',
                title: 'Тренер-преподаватель отделения "Прыжки в воду"',
                img: '/storage/teammembers/13-03-2025/hCIsuxaS0p70joT.webp',
                info: `<span><strong>Уровень образования:</strong> высшее</span>
<span><strong>Квалификация:</strong> бакалавр</span>
<span><strong>Направление подготовки:</strong> педагогическое образование</span>
<span><strong>Звание:</strong> мастер спорта России</span>
<span><strong>Стаж работы по специальности:</strong> 6 лет</span>`
            },
            {
                name: 'Лобанова Елизавета Алексеевна',
                title: 'Тренер-преподаватель отделения "Прыжки в воду"',
                img: 'https://дввс.рф/storage/teammembers/28-02-2025/n1Ei6hDS1RpWQIG.webp',
                info: `<span><strong>Уровень образования:</strong> высшее</span>
<span><strong>Квалификация:</strong> бакалавр</span>
<span><strong>Направление подготовки:</strong> педагогическое образование</span>
<span><strong>Стаж работы по специальности:</strong> 3 года</span>`
            },
            {
                name: 'Мишарина Дарья Александровна',
                title: 'Тренер-преподаватель отделения "Прыжки в воду"',
                img: 'https://дввс.рф/storage/teammembers/03-03-2025/7ZD8ffUnRHyGkYe.webp',
                info: `<span><strong>Уровень образования:</strong> высшее</span>
<span><strong>Квалификация:</strong> бакалавр</span>
<span><strong>Направление подготовки:</strong> педагогическое образование</span>
<span><strong>Стаж работы по специальности:</strong> 2 года</span>`
            }
        ],
        tab3: [
            {
                name: 'Матисон Людмила Борисовна',
                title: 'Тренер-преподаватель отделения "Синхронное плавание"',
                img: '/storage/teammembers/20-03-2025/dAwSU3epJquX240.webp',
                info: `<span><strong>Уровень образования:</strong> высшее</span>
<span><strong>Квалификация:</strong> руководитель творческого коллектива, преподаватель</span>
<span><strong>Направление подготовки:</strong> социально-культурная деятельность и народное художественное творчество</span>
<span><strong>Стаж работы по специальности:</strong> 15 лет</span>`
            },
            {
                name: 'Маслова Анастасия Геннадьевна',
                title: 'Тренер-преподаватель отделения "Синхронное плавание"',
                img: '/storage/teammembers/27-06-2025/FjqmoQ6gvyBBIZR.webp',
                info: `<span><strong>Уровень образования:</strong> высшее</span>
<span><strong>Квалификация:</strong> бакалавр</span>
<span><strong>Направление подготовки:</strong> физическая культура</span>
<span><strong>Звание:</strong> мастер спорта России</span>
<span><strong>Стаж работы по специальности:</strong> 4 года</span>`
            },
            {
                name: 'Кириллова Дарья Юрьевна',
                title: 'Старший тренер-преподаватель отделения "Синхронное плавание"',
                img: '/storage/teammembers/28-02-2025/KrgLey0odtxtJZc.webp',
                info: `<span><strong>Уровень образования:</strong> высшее</span>
<span><strong>Квалификация:</strong> магистр</span>
<span><strong>Направление подготовки:</strong> организация работы с молодёжью</span>
<span><strong>Звание:</strong> мастер спорта России</span>
<span><strong>Стаж работы по специальности:</strong> 8 лет</span>`
            },
            {
                name: 'Донченко Екатерина Сергеевна',
                title: 'Тренер-преподаватель отделения "Синхронное плавание"',
                img: 'https://дввс.рф/storage/teammembers/1730271800/%D0%94%D0%BE%D0%BD%D1%87%D0%B5%D0%BA%D0%BE.jpeg',
                info: `<span><strong>Уровень образования:</strong> высшее</span>
<span><strong>Квалификация:</strong> магистр</span>
<span><strong>Направление подготовки:</strong> физическая культура</span>
<span><strong>Стаж работы по специальности:</strong> 5 лет</span>`
            }
        ]
    };

    var html = `
<div class="team p-59">
    <div class="container-fluid tab-links-container-top" style="margin-bottom:0;">
        <div class="tab-header tab-header__desktop">
            <button class="tab-link active" data-tab="tab1">ОТДЕЛЕНИЕ "ПЛАВАНИЕ"</button>
            <button class="tab-link" data-tab="tab2">ОТДЕЛЕНИЕ "ПРЫЖКИ В ВОДУ"</button>
            <button class="tab-link" data-tab="tab3">ОТДЕЛЕНИЕ "СИНХРОННОЕ ПЛАВАНИЕ"</button>
        </div>
        <div class="tab-underline-bar"></div>
    </div>
    <div class="tabs-content-area">
        ${['tab1','tab2','tab3'].map((tab,tabIdx)=>`
        <div class="tab-content" id="${tab}" style="${tabIdx===0?'':'display:none;'}">
            <div class="card-row">
                <div class="trainer-main-photo">
                    <img src="${trainers[tab][0].img}" alt="" class="trainer-photo-big">
                </div>
                <div class="trainer-main-info">
                    <div class="info-content-wrapper">
                        <p class="tabs-slider-content__name">${trainers[tab][0].name}</p>
                        <p class="tabs-slider-content__title">${trainers[tab][0].title}</p>
                        <p class="tabs-slider-content__description">${trainers[tab][0].info}</p>
                    </div>
                    <div class="trainer-thumbs-row">
                        ${trainers[tab].map((t,i)=>`
                        <div class="trainer-thumb${i===0?' active':''}" data-tab="${tab}" data-trainer="${i}">
                            <img src="${t.img}" alt="" class="trainer-photo-thumb">
                        </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        </div>
        `).join('')}
    </div>
</div>
`;

    function renderBlock() {
        document.querySelectorAll('.spoiler-block-item').forEach(acc => {
            var trigger = acc.querySelector('.spoiler-trigger p');
            if (trigger) {
                // скрыть "1111"
                if (trigger.textContent.trim() === '1111') {
                    acc.style.display = 'none';
                }
                // вставить блок тренеров
                if (trigger.textContent.trim().toLowerCase().includes('педагогический состав')) {
                    var content = acc.querySelector('.spoiler-block-content');
                    if (content && !content.querySelector('.tab-header.tab-header__desktop')) {
                        content.innerHTML = html;
                        setTimeout(updateTabUnderline, 50);
                    }
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', renderBlock);

    var observer = new MutationObserver(renderBlock);
    observer.observe(document.body, {childList: true, subtree: true});

    document.body.addEventListener('click', function(e) {
        let trg = e.target.closest('.spoiler-trigger');
        if (trg && trg.querySelector('p') && trg.querySelector('p').textContent.trim().toLowerCase().includes('педагогический состав')) {
            setTimeout(renderBlock, 100);
        }
    });

    function updateTabUnderline() {
        const active = document.querySelector('.tab-link.active');
        const underline = document.querySelector('.tab-underline-bar');
        if (!active || !underline) return;
        const rect = active.getBoundingClientRect();
        const parentRect = active.parentElement.getBoundingClientRect();
        underline.style.left = (rect.left - parentRect.left) + 'px';
        underline.style.width = rect.width + 'px';
        underline.style.top = (rect.bottom - parentRect.top) + 'px';
    }

    function showTrainer(tab, idx) {
        var mainRow = document.querySelector(`#${tab} .card-row`);
        if (!mainRow) return;
        var t = trainers[tab][idx];
        var bigImg = mainRow.querySelector('.trainer-photo-big');
        var infoDiv = mainRow.querySelector('.trainer-main-info');
        bigImg.src = t.img;
        infoDiv.innerHTML = `
            <div class="info-content-wrapper">
                <p class="tabs-slider-content__name">${t.name}</p>
                <p class="tabs-slider-content__title">${t.title}</p>
                <p class="tabs-slider-content__description">${t.info}</p>
            </div>
            <div class="trainer-thumbs-row">
                ${trainers[tab].map((trainer,i)=>`
                <div class="trainer-thumb${i===idx?' active':''}" data-tab="${tab}" data-trainer="${i}">
                    <img src="${trainer.img}" alt="" class="trainer-photo-thumb">
                </div>
                `).join('')}
            </div>
        `;
    }

    document.body.addEventListener('click', function(e){
        if (e.target.classList.contains('tab-link')) {
            document.querySelectorAll('.tab-link').forEach(b=>b.classList.remove('active'));
            e.target.classList.add('active');
            var tab = e.target.dataset.tab;
            document.querySelectorAll('.tab-content').forEach(tc=>tc.style.display='none');
            document.getElementById(tab).style.display = '';
            showTrainer(tab, 0);
            updateTabUnderline();
        }
    });

    document.body.addEventListener('click', function(e){
        var thumb = e.target.closest('.trainer-thumb');
        if (!thumb) return;
        var tab = thumb.dataset.tab;
        var idx = +thumb.dataset.trainer;
        showTrainer(tab, idx);
    });

    window.addEventListener('resize', updateTabUnderline);

    var style = document.createElement('style');
    style.innerHTML = `
    .team.p-59 { font-family: 'Roboto Condensed', Arial, sans-serif; padding-top:0 !important; }
    .tab-header__desktop {
        font-family: 'Roboto', Arial, sans-serif;
        font-size:16px;
        margin-bottom:0 !important;
        gap:48px;
        justify-content:center;
        display:flex;
        font-weight:400;
        letter-spacing: 0px;
        position:relative;
    }
    .tab-link {
        font-family:'Roboto', Arial, sans-serif;
        font-size:16px;
        background:none;
        border:none;
        cursor:pointer;
        color:#222;
        padding:0 24px 0 24px;
        outline:none;
        transition:color 0.2s;
        font-weight:400;
        letter-spacing: 0px;
        position:relative;
        z-index:1;
    }
    .tab-link.active {
        font-weight:400;
        color: #222;
    }
    .tab-underline-bar {
        position:absolute;
        height:8px;
        background: linear-gradient(90deg,#b13cff 0%,#4d68ff 100%);
        border-radius:4px;
        transition: left 0.2s, width 0.2s;
        z-index:0;
        width:120px;
        left:0; 
        top:38px;
    }
    .card-row {
        width:100%;
        max-width:1280px;
        display:flex;
        flex-direction:row;
        align-items:flex-start;
        justify-content:flex-start;
        gap:16px;
        margin:0 auto;
        min-height:340px;
    }
    .trainer-main-photo {
        flex-shrink:0;
    }
    .trainer-main-photo img.trainer-photo-big {
        width:400px;
        height:400px;
        object-fit:cover;
        border-radius:40px;
        transition: opacity 0.15s;
        box-shadow: 0 8px 32px rgba(150,90,200,0.09);
        display:block;
    }
    .trainer-main-info { 
        font-family: 'Roboto Condensed', Arial, sans-serif;
        font-size:18px; 
        margin-left:0;
        margin-right:0;
        transition: opacity 0.15s;
        color: #3e4e5e;
        text-align:left;
        width:100%;
        max-width:620px;
        min-height:160px;
        display:flex;
        flex-direction:column;
        justify-content:flex-start;
        align-items:flex-start;
    }
    .info-content-wrapper {
        margin-bottom:24px;
        width:100%;
        min-height:80px;
    }
    .tabs-slider-content__name {
        font-family:'Bebas Neue', Arial, sans-serif !important;
        font-size:32px !important;
        color:#3e4e5e !important;
        margin:0 0 20px 0 !important;
        font-weight:400 !important;
        letter-spacing: 0.5px !important;
        text-align:left;
        line-height:1.1;
    }
    .tabs-slider-content__title {
        font-family:'Roboto Condensed', Arial, sans-serif;
        font-size:20px;
        color:#3e4e5e;
        margin:0 0 20px 0;
        font-weight:400;
        text-align:left;
    }
    .tabs-slider-content__description {
        font-family:'Roboto Condensed', Arial, sans-serif;
        font-size:18px;
        color:#3e4e5e;
        margin:0 0 20px 0;
        font-weight:300;
        text-align:left;
    }
    .tabs-slider-content__description span {
        display:block;
        margin-bottom:6px;
    }
    .trainer-thumbs-row {
        gap:16px !important;
        display:flex;
        justify-content:flex-start;
        width:100%;
        min-height:100px;
        margin-top:0;
    }
    .trainer-thumb {
        border: none;
        box-shadow: none;
        cursor: pointer;
        border-radius:20px;
        transition: box-shadow 0.2s, border 0.2s;
        background: none;
        position:relative;
    }
    .trainer-thumb.active {
        box-shadow: 0 0 0 4px #b13cff;
        border-radius:20px;
    }
    .trainer-thumb img.trainer-photo-thumb {
        border-radius: 20px;
        display:block;
        width:100px;
        height:100px;
        object-fit:cover;
        transition: box-shadow 0.2s, border 0.2s;
        box-shadow: none;
        border: none;
    }
    .trainer-thumb:not(.active):hover img.trainer-photo-thumb {
        box-shadow: 0 0 0 2px #bbb;
    }
    `;
    document.head.appendChild(style);
})();
