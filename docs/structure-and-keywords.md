# Структура сайта и карта запросов

Основа: 46 приборов с текущей страницы «Mērinstrumenti», запросы из Google Ads (Латвия, средний месяц) и текущая выдача Google. Языки: LV (основной), RU, EN.

## 1. Структура страниц

```
/                     Главная: личный бренд Дмитрия + аренда (два равных входа), услуги, об эксперте, обучение, блог, отзывы
/noma/                Каталог аренды (переключатель «приборы / комплекты»)
  /noma/<категория>/  8 категорий (ниже)
  /noma/<прибор>/     46 страниц приборов
/komplekti/           20 готовых комплектов
/ka-iret/            Как арендовать: залог, время, документ
/kontakti/            Контакты, WhatsApp, реквизиты
/cart, /checkout, /my-account   Бронь и оплата (Фаза 6)
/noderigi/            Полезная информация (уже есть, сохраняем)
/blog-2/              Блог (уже есть, сохраняем)
/apmacibas/…          Обучение (уже есть, сохраняем)
/privatuma-politika/  Политика (есть, 3 языка)
```
RU: префикс `/ru/`, EN: `/en/`. Существующие страницы с позициями в Google (`/ieregulesanas-programma/`, `/category/ventilacija/`, `/apmacibas/…`) не трогаем и не переносим без 301-редиректа.

## 2. Карта запросов: категория → страница

| Категория (LV) | Адрес | Что входит | Приборы | Запросов/мес (сумма по группе, LV+RU) | Главные запросы |
|---|---|---|---|---|---|
| Gaisa plūsma un ventilācija | /noma/gaisa-plusma/ | Анемометры, тяга, вентиляция | Testo 417/420/405i/410i, Fluke 922, Swema 03+, BT-8806 | 760 | anemometrs (140), анемометр (40), plūsmas mērītājs (30), gaisa plūsmas mērītājs (30) |
| Spiediens | /noma/spiediens/ | Манометры, дифманометры | Testo 440 dP/510i/549i/511, DR-S220, GT5101, ST-B168DL | 4790 | spiediena mērītājs (320), manometrs (320), манометр (140), vakuuma manometrs (30) |
| Temperatūra un mitrums | /noma/temperatura-mitrums/ | Термометры, влажность, логгеры | Testo 605i/915i/115i, HTM208, TASI TA612A, Xiaomi, Freshliance, HP-5000T | 1500 | mitruma mērītājs (210), gaisa mitruma mērītājs (90), augsnes ph mērītājs (50), koksnes mitruma mērītājs (40) |
| CO₂ un gaisa kvalitāte | /noma/gaisa-kvalitate/ | CO₂, частицы, качество воздуха | Testo 535, CGP22C, DM502, Nohawk NK800, HZ-011-C | 950 | gaisa kvalitātes mērītājs (320), co2 mērītājs (320), co2 meritajs (20), co2 gaisa kvalitātes mērītājs (20) |
| Gāzes un dūmi | /noma/gazes-dumi/ | Газоанализаторы, дым-генераторы | 4 Gas Detector, NSF-150ADMX, TELESIN | 2590 | tvana gāzes detektors (140), tvana gāzes detektori (140), dūmu ģenerators (110), gāzes noplūdes detektors (70) |
| Termokameras un endoskopi | /noma/termokameras-endoskopi/ | Тепловизоры, эндоскопы | TOOLTOP, эндоскоп с гибкой камерой | 3550 | termokamera (480), endoskops (210), тепловизор (110), єндоскоп (90) |
| Biezums, vibrācija, ultraskaņa | /noma/biezums-vibracija/ | Толщиномеры, вибро, УЗ-расход/тепло | NK-200, GM100, VM6370, TUF-3000L, Ultrasonic Transducer | 2930 | krāsas biezuma mērītājs (110), siltumenerģijas skaitītājs (90), siltuma skaitītājs (90), толщиномеры (40) |
| Skaņa, gaisma, elektrība | /noma/skana-gaisma-elektriba/ | Шум, свет, ток, тахометр, лазер | PCE 318, SK8202, Hanmatek CO601, FNIRSI ERD-10, PEAKMETER PM2019A, HoldPeak, Nohawk NP, AL-NK-200, Refraktometrs | 360 | decibelu mērītājs (70), шумомер (20), trokšņu mērītājs (20), skaņas mērītājs (20) |

**Общие запросы на аренду** (13550/мес): ramirent (3600), storent (2900), ramirent noma (880), instrumentu noma (720), cramo (720), storent sia (590). Идут на главную и `/noma/` (title: «Аренда приборов в Риге»). Названия Ramirent, Storent, Cramo — это конкуренты, на них не оптимизируем.

## 2а. Главная делит аренду и личный бренд
Сайт Дмитрия работает и как визитка. Первый экран: фото и имя, затем два равных входа: «Аренда приборов» и «Услуги эксперта». Ниже аренда (категории, популярные приборы), услуги, «Об эксперте» с соцсетями, обучение и блог, отзывы. Меню: Аренда, Комплекты, Услуги, Обучение, Блог, Контакты. Запросы про аренду ведём на `/noma/`, главная держит имя и экспертность.

## 3. Правила для страниц
- Один запрос — одна страница. Категория отвечает на общий запрос («termokamera noma»), страница прибора на модель («testo 417»).
- H1 — название прибора + «noma Rīgā». Title до 60 знаков, description до 155.
- Ссылка на производителя не ставится: характеристики пишем на странице сами (46 внешних ссылок убираем).
- Каждая страница: цена за сутки/неделю, залог, календарь, кнопка «Rezervēt», WhatsApp.
- Данные для schema.org Product + Offer и hreflang LV/RU/EN в шаблоне.

## 4. Что дальше
Макеты: `docs/mockups/index.html`. Верстка темы — следующая задача фазы.