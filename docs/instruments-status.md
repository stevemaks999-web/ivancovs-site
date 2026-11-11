# Данные о приборах (Фаза 12)

Состояние: полные данные 17, частичные 29 (всего 46). Не нашли: 0. Шильдиков от Дмитрия не будет.

- **Полные**: данные со страницы производителя.
- **Частичные**: данные из описаний продавцов и сторонних руководств, паспорта нет; в колонке «Проверить» что уточнить.
- **Частичные из досье**: часть данных из папки `instruments/` и описаний продавцов, сверены поиском выборочно.

Файл с данными: `data/instruments.json`. Числа сверять с паспортом прибора.

| № | Прибор | Тип | Шт. | Данные | Проверить |
|---|---|---|---|---|---|
| 1 | Testo 420 | Plūsmas mērītājs | 1 | полные |  |
| 2 | Testo 417 | Plūsmas mērītājs | 2 | полные |  |
| 3 | Testo 535 | CO₂ mērītājs | 1 | полные |  |
| 4 | Testo 440 dP | Spiediena starpības mērītājs | 1 | полные |  |
| 5 | testo 410i | anemometrs ar viedtālruņa vadību | 1 | полные |  |
| 6 | testo 510i | Difspiediena mērītājs ar viedtālruņa kontroli | 4 | полные |  |
| 7 | testo 605i | Termohigrometrs ar viedtālruņa vadību | 4 | полные |  |
| 8 | testo 405i | Termoanemometrs ar viedtālruņa vadību | 2 | полные |  |
| 9 | Testo 915i | Termometrs ar viedtālruņa vadību | 1 | полные |  |
| 10 | testo 549i | Augstspiediena mērītājs ar viedtālruņa vadību | 6 | полные |  |
| 11 | testo 115i | Skavu termometrs ar viedtālruņa vadību | 2 | полные |  |
| 12 | testo 511 | Absolūtais spiediena mērītājs | 1 | полные |  |
| 13 | Fluke 922 | Gaisa plūsmas mērītājs | 1 | полные |  |
| 14 | PCE 318 | Skaņas līmeņa datu reģistrators | 1 | полные |  |
| 15 | NSF-150ADMX | Dūmu ģenerators | 1 | полные |  |
| 16 | HoldPeak | Stroboskopiskais tachometrs | 1 | частичные | Данные из досье и описаний продавцов одной и той же модели под другими брендами; паспорта HoldPeak нет |
| 17 | NK-200 | Krāsas biezuma mērītājs | 1 | полные |  |
| 18 | HTM208 | 8-kanālu temperatūras reģistrators | 1 | частичные | Точность и частоту записи уточнить по руководству; уточнить модель (B или C) |
| 19 | AL-NK-200 | Analogais spēka mērītājs | 1 | частичные | Описания продавцов расходятся по цене деления (0,1 Н или 1 Н); сверить по прибору |
| 20 | Nohawk NP | Lāzera tālmērs | 1 | частичные | Проверить, подходит ли для замеров внутри комнат: это дальномер для больших дистанций |
| 21 | Nohawk NK800 | Putekļu daļiņu skaitītājs | 1 | частичные | Данные взяты по близкому прибору SISCO MKS-800, у Nohawk NK800 паспорта не нашли; сверить |
| 22 | TASI TA612A | 4-kanālu еemperatūras reģistrators | 1 | частичные | Название TA612A, по характеристикам TA612C: проверить на приборе |
| 23 | BTMETER BT-8806-WM-APP | Anemometrs ar Bluetooth | 1 | частичные | Данные продавцов расходятся, официального паспорта нет |
| 24 | DR-S220 | Diferenciālā spiediena mērītājs | 1 | частичные | Диапазон и питание сверить с наклейкой на приборе (модели MD-S220 и MD-S2201 отличаются) |
| 25 | Hanmatek CO601 | Elektrisko parametru reģistrators | 3 | частичные | Напряжение у производителя указано 600 В или 1000 В в разных источниках: сверить по руководству |
| 26 | FNIRSI ERD-10 | Elektromagnētiskā starojuma detektors | 1 | частичные | Для оценки, не для сертифицированных замеров |
| 27 | DM502 | Gaisa kvalitātes mērītājs | 1 | частичные | Уточнить вариант: бывает с формальдегидом (HCHO) |
| 28 | ST-B168DL | Digitālais manometrs | 1 | частичные | Данные из досье, сверить по руководству |
| 29 | XIAOMI | Temperatūras reģistrators | 6 | частичные | Данные из досье |
| 30 | HOLDPEAK HP-5000T | Bezkontakta mitruma mērītājs | 1 | частичные | Паспорта нет; шкала относительная, не проценты влажности |
| 31 | Freshliance | Temperatūras un mitruma datu reģistrators | 20 | частичные | Диапазон влажности уточнить по руководству; стандарты EN12830, CE |
| 32 | CGP22C | CO₂ reģistrators | 3 | частичные | Данные из досье |
| 33 | TELESIN | Dūmu ģenerators | 2 | частичные | Время работы от батареи расходится в источниках (от 5 до 28 минут): проверить |
| 34 | SK8202 | Apgaismojuma mērītājs | 1 | частичные | Числа из досье, сверить по руководству |
| 35 | TOOLTOP | Mobilā termiskā kamera | 1 | частичные | Нужно уточнить модель: T3 или T7 |
| 36 | Endoskops | Endoskops ar locāmo kameru | 1 | частичные | Модель и длину зонда сверить |
| 37 | PEAKMETER PM2019A | Skavu ampērmetrs | 1 | частичные | В описаниях расходятся: True RMS и измерение напряжения; уточнить по руководству |
| 38 | Swema 03+ | Termo-anemometer | 1 | полные |  |
| 39 | Refraktometrs | antifrīza koncentrācijas mērītājs | 1 | частичные | Данные из досье |
| 40 | Quality Monitor HZ-011-C | Daliņu skaitītājs | 1 | частичные | Сверить поток и интервал с полным руководством |
| 41 | Portable 4 Gas Detector | Gas Meter O2 CO H2S Analyzer Rechargeable | 1 | частичные | Модель BH-4S, диапазоны сверить по паспорту |
| 42 | Manometer GT5101 | Differential pressure meter ±500Pa | 1 | частичные | Таблица в руководстве читается плохо: сверить по прибору |
| 43 | TUF-3000L Heat Water Flowmeter | Portatīvais ultraskaņas plūsmas mērītājs | 1 | частичные | В комплектах указан как TUF-3000L, у нас TUF-2000H: уточнить |
| 44 | Ultrasonic Transducer | Portatīvais ultraskaņas siltuma skaitītājs | 1 | частичные | Сверить комплектацию датчиков |
| 45 | GM100 Thickness gauge ultrasonic | Ultraskaņas biezuma mērītājs | 1 | частичные | Производитель GM100 может отличаться: на рынке есть другие модели |
| 46 | VM6370 Vibrometer | Pjezoelektriskais vibrācijas mērītājs | 1 | частичные | Частотный диапазон и точность не подтверждены |
