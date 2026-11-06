# Структура сайта и карта запросов

Основа: 46 приборов с текущей страницы «Mērinstrumenti», запросы из Google Ads (Латвия, средний месяц) и текущая выдача Google. Языки: LV (основной), RU, EN.

## 1. Структура страниц

```
/                       Главная (есть, адрес сохраняем)
/noma/                  Каталог аренды (есть как страница магазина, адрес сохраняем)
  /product-category/<категория>/        8 категорий (новые)
  /product-category/komplekti/<задача>/ комплекты по задачам (новые)
  /product/<прибор>/    страницы приборов (адрес уже используется, 4 прибора есть)
/merinstrumenti/        Текущая страница с 46 приборами: адрес живёт до запуска каталога
/noderigi/              Полезная информация (есть)
/apmacibas/…            Обучение, 9 страниц (есть)
/blog-2/                Блог (есть)
/ieregulesanas-programma/, /category/ventilacija/ и др. категории блога: в Google на 1 месте, не трогаем
/eksperta-tehniskais-atbalsts-hvac-sistemu-ekspluatacijai/   (есть)
/pirkuma-veiksana/      Условия покупки (есть)
/privatuma-politika/    Политика (есть, 3 языка)
/cart, /checkout, /my-account   Бронь и оплата (Фаза 6)
```
RU: префикс `/ru/`, EN: `/en/`.

**Правило адресов.** Где есть подходящий адрес, используем его: так Google быстрее проиндексирует страницы. Известные позиции в Google сейчас: `/ieregulesanas-programma/` (1 место), `/category/ventilacija/` (1 место), `/` (2 место). Их адреса и тексты не меняем. Новые адреса делаем только для новых страниц (категории, комплекты, остальные приборы). Если старый адрес всё же уходит, ставим 301-редирект на ближайшую по смыслу страницу. Судьбу `/merinstrumenti/` решим через 4 недели по данным Search Console, редирект на `/noma/` не ставим заранее.

## 2. Карта запросов: категория → страница

| Категория (LV) | Адрес | Что входит | Приборы | Запросов/мес (сумма по группе, LV+RU) | Главные запросы |
|---|---|---|---|---|---|
| Gaisa plūsma un ventilācija | /product-category/gaisa-plusma/ | Анемометры, тяга, вентиляция | Testo 417/420/405i/410i, Fluke 922, Swema 03+, BT-8806 | 760 | anemometrs (140), анемометр (40), plūsmas mērītājs (30), gaisa plūsmas mērītājs (30) |
| Spiediens | /product-category/spiediens/ | Манометры, дифманометры | Testo 440 dP/510i/549i/511, DR-S220, GT5101, ST-B168DL | 4790 | spiediena mērītājs (320), manometrs (320), манометр (140), vakuuma manometrs (30) |
| Temperatūra un mitrums | /product-category/temperatura-mitrums/ | Термометры, влажность, логгеры | Testo 605i/915i/115i, HTM208, TASI TA612A, Xiaomi, Freshliance, HP-5000T | 1500 | mitruma mērītājs (210), gaisa mitruma mērītājs (90), augsnes ph mērītājs (50), koksnes mitruma mērītājs (40) |
| CO₂ un gaisa kvalitāte | /product-category/gaisa-kvalitate/ | CO₂, частицы, качество воздуха | Testo 535, CGP22C, DM502, Nohawk NK800, HZ-011-C | 950 | gaisa kvalitātes mērītājs (320), co2 mērītājs (320), co2 meritajs (20), co2 gaisa kvalitātes mērītājs (20) |
| Gāzes un dūmi | /product-category/gazes-dumi/ | Газоанализаторы, дым-генераторы | 4 Gas Detector, NSF-150ADMX, TELESIN | 2590 | tvana gāzes detektors (140), tvana gāzes detektori (140), dūmu ģenerators (110), gāzes noplūdes detektors (70) |
| Termokameras un endoskopi | /product-category/termokameras-endoskopi/ | Тепловизоры, эндоскопы | TOOLTOP, эндоскоп с гибкой камерой | 3550 | termokamera (480), endoskops (210), тепловизор (110), єндоскоп (90) |
| Biezums, vibrācija, ultraskaņa | /product-category/biezums-vibracija/ | Толщиномеры, вибро, УЗ-расход/тепло | NK-200, GM100, VM6370, TUF-3000L, Ultrasonic Transducer | 2930 | krāsas biezuma mērītājs (110), siltumenerģijas skaitītājs (90), siltuma skaitītājs (90), толщиномеры (40) |
| Skaņa, gaisma, elektrība | /product-category/skana-gaisma-elektriba/ | Шум, свет, ток, тахометр, лазер | PCE 318, SK8202, Hanmatek CO601, FNIRSI ERD-10, PEAKMETER PM2019A, HoldPeak, Nohawk NP, AL-NK-200, Refraktometrs | 360 | decibelu mērītājs (70), шумомер (20), trokšņu mērītājs (20), skaņas mērītājs (20) |

**Общие запросы на аренду** (13550/мес): ramirent (3600), storent (2900), ramirent noma (880), instrumentu noma (720), cramo (720), storent sia (590). Идут на главную и `/noma/` (title: «Аренда приборов в Риге»). Названия Ramirent, Storent, Cramo — это конкуренты, на них не оптимизируем.

## 2а. Главная делит аренду и личный бренд
Сайт Дмитрия работает и как визитка. Всё содержимое главной берём с действующего сайта, ничего нового не придумываем: заголовок «Регулировка, аудиты и экспертизы вентиляционных систем», услуги (регулировка, аудиты, экспертизы, обучение), «Новое: экспертная поддержка HVAC», компетенция и сертификаты, миссия, отзывы, контакты и соцсети. К ним добавляется вход «Аренда приборов». Меню как на сайте сейчас: Mērinstrumenti (аренда), Полезная информация, Обучение, Блог. Запросы про аренду ведём на `/noma/`.

## 3. Правила для страниц
- Один запрос — одна страница. Категория отвечает на общий запрос («termokamera noma»), страница прибора на модель («testo 417»).
- H1 — название прибора + «noma Rīgā». Title до 60 знаков, description до 155.
- Ссылка на производителя не ставится: характеристики пишем на странице сами (46 внешних ссылок убираем).
- Каждая страница: цена за сутки/неделю, залог, календарь, кнопка «Rezervēt», WhatsApp.
- Данные для schema.org Product + Offer и hreflang LV/RU/EN в шаблоне.

## 4. Что дальше
Макеты: `docs/mockups/index.html`. Верстка темы — следующая задача фазы.