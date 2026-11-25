#!/usr/bin/env python3
"""Собирает RU и EN версии главной в старом дизайне из латышской (data/home-lv.html).

Меняем только тексты (заголовки, ценности, миссия, подписи, описания работ). Структура, картинки,
слайдер, карты, ссылки и названия объектов остаются как в латышской версии (объекты берём дословно).
Результат: src/wp-content/themes/ivancovs/i18n/pages/home.ru.html и home.en.html
"""
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SRC = ROOT / "data" / "home-lv.html"
OUT = ROOT / "src/wp-content/themes/ivancovs/i18n/pages"

# латышский текст => (RU, EN)
T = {
    "Jaunums: Eksperta tehniskais atbalsts HVAC sistēmu ekspluatācijai": (
        "Новинка: экспертная техническая поддержка при эксплуатации систем HVAC",
        "New: expert technical support for HVAC system operation"),
    "NOSŪTĪT ZIŅU": ("НАПИСАТЬ СООБЩЕНИЕ", "SEND A MESSAGE"),
    "PAKALPOJUMI": ("УСЛУГИ", "SERVICES"),
    "REGULĒŠANA": ("НАЛАДКА", "BALANCING"),
    "AUDITI": ("АУДИТЫ", "AUDITS"),
    "EKSPERTĪZES": ("ЭКСПЕРТИЗЫ", "EXPERT REVIEWS"),
    "APMACĪBAS": ("ОБУЧЕНИЕ", "TRAINING"),
    "KOMPETENCE": ("КОМПЕТЕНЦИИ", "COMPETENCE"),
    "PRAKSES DATI (BIS)": ("ДАННЫЕ ПРАКТИКИ (BIS)", "PRACTICE RECORD (BIS)"),
    "VĒRTĪBAS": ("ЦЕННОСТИ", "VALUES"),
    "MISIJA": ("МИССИЯ", "MISSION"),
    "25 GADU PIEREDZE": ("25 ЛЕТ ОПЫТА", "25 YEARS OF EXPERIENCE"),
    "BŪVUZRAUDZĪBA": ("СТРОИТЕЛЬНЫЙ НАДЗОР", "SITE SUPERVISION"),
    "PROJEKTĒŠANA": ("ПРОЕКТИРОВАНИЕ", "DESIGN"),
    "MONTĀŽA": ("МОНТАЖ", "INSTALLATION"),
    "KONTAKTI": ("КОНТАКТЫ", "CONTACTS"),
    "ATSAUKSMES": ("ОТЗЫВЫ", "TESTIMONIALS"),
    "Darba pieredze": ("Опыт работы", "Work experience"),
    "Objekti": ("Объекты", "Projects"),
    # Компетенции
    "Mg.sc.ing. Dmitrijs Ivancovs": ("Mg.sc.ing. Дмитрий Иванцов", "Mg.sc.ing. Dmitrijs Ivancovs"),
    "Būvekspertīzes sert.nr.:": ("Сертификат строительной экспертизы №:", "Construction expertise certificate no.:"),
    "Būvuzraudzības sert.nr.:": ("Сертификат строительного надзора №:", "Construction supervision certificate no.:"),
    "Būvkomersanta reg.nr.:": ("Рег. № строительного коммерсанта:", "Construction merchant reg. no.:"),
    # Ценности
    "Kompetence: Spēja risināt vissarežģītākās situācijas.": (
        "Компетентность: умение решать самые сложные ситуации.",
        "Competence: the ability to resolve the most complex situations."),
    "Godīgums: Atklāta un taisnīga pieeja katram klientam.": (
        "Честность: открытый и справедливый подход к каждому клиенту.",
        "Integrity: an open and fair approach to every client."),
    "Ātrums un precizitāte: Efektīva un kvalitatīva darbu izpilde.": (
        "Скорость и точность: эффективное и качественное выполнение работ.",
        "Speed and precision: efficient, high-quality work."),
    "Tehnoloģijas: Izmantoju mūsdienīgas mēriekārtas un metodikas.": (
        "Технологии: использую современные измерительные приборы и методики.",
        "Technology: I use modern measuring equipment and methods."),
    "Rezultāts: Ventilācijas sistēmas kalpo cilvēka komfortam un veselībai.": (
        "Результат: вентиляционные системы служат комфорту и здоровью человека.",
        "Result: ventilation systems that serve human comfort and health."),
    # Миссия
    "Ja ventilācijas sistēma ir uzstādīta, tai ir jādarbojas nevainojami.": (
        "Если вентиляционная система установлена, она должна работать безупречно.",
        "If a ventilation system is installed, it must work flawlessly."),
    "Iztērētajiem resursiem uzstādīšanai un ekspluatācijai jāatmaksājas ar komfortu.": (
        "Ресурсы, потраченные на установку и эксплуатацию, должны окупаться комфортом.",
        "The resources spent on installation and operation must pay off in comfort."),
    "Mana misija ir nodrošināt ventilācijas sistēmu efektīvu darbību, piedāvājot profesionālu ekspertīzi, precīzu regulēšanu un uzticamus risinājumus vissarežģītākajās situācijās.": (
        "Моя миссия — обеспечить эффективную работу вентиляционных систем, предлагая профессиональную экспертизу, точную наладку и надёжные решения в самых сложных ситуациях.",
        "My mission is to ensure ventilation systems work efficiently by offering professional expertise, precise balancing and reliable solutions in the most difficult situations."),
    # Контакты
    "PVN reģistrācijas numurs:": ("Регистрационный номер плательщика PVN:", "VAT registration number:"),
    "Reģistrācijas numurs:": ("Регистрационный номер:", "Registration number:"),
    "Juridiskā adrese:": ("Юридический адрес:", "Registered address:"),
    "Biroja adrese:": ("Адрес офиса:", "Office address:"),
    "Banka:": ("Банк:", "Bank:"),
    "Kods:": ("Код (SWIFT):", "Code (SWIFT):"),
    "Konts:": ("Счёт:", "Account:"),
    "Valdes loceklis Dmitrijs Ivancovs": ("Член правления Дмитрий Иванцов", "Board member Dmitrijs Ivancovs"),
    "Tel.nr.:": ("Тел.:", "Tel.:"),
    "E-pasts:": ("Эл. почта:", "Email:"),
    # Цитата
    "Pretēji izplatītajam uzskatam, ventilācija var un tai ir jādarbojas labi, un mans pieredzes bagātais darbs tam pierādījums.": (
        "Вопреки распространённому мнению, вентиляция может и должна работать хорошо, и моя многолетняя работа — тому доказательство.",
        "Contrary to popular belief, ventilation can and must work well, and my many years of work are proof of it."),
    # Описания работ
    "Aukstuma apgādes sistēmu audits": ("Аудит систем холодоснабжения", "Audit of cooling supply systems"),
    "Ventilācijas sistēmu ieregulēšanas darbi": ("Работы по наладке вентиляционных систем", "Ventilation system balancing works"),
    "Ventilācijas sistēmu audits": ("Аудит вентиляционных систем", "Ventilation system audit"),
    "Ventilācijas sistēmu palaišanas un ieregulēšanas darbi": ("Пуск и наладка вентиляционных систем", "Ventilation system start-up and balancing works"),
    "Ventilācijas sistēmu regulēšanas darbi": ("Работы по регулировке вентиляционных систем", "Ventilation system adjustment works"),
    "Ēkas tehnisko sistēmu apsekošana un uzlabošanas priekšlikumu sastādīšana": (
        "Обследование технических систем здания и подготовка предложений по улучшению",
        "Survey of the building's technical systems and drafting of improvement proposals"),
    "Ventilācijas sistēmu pārbaudes, palaišanas, ieregulēšanas un automātiskas darbības iestatīšanas darbi": (
        "Проверка, пуск, наладка вентиляционных систем и настройка автоматического режима работы",
        "Inspection, start-up, balancing and automatic operation setup of ventilation systems"),
    "“Stora Enso” kokmateriālu žāvēšanas kameru tehniskas izpētes darbi": (
        "Техническое исследование сушильных камер для древесины «Stora Enso»",
        "Technical study of the “Stora Enso” timber drying chambers"),
    "Ventilācijas un gaisa kondicionēšanas sistēmu tehniska apsekošana": (
        "Техническое обследование систем вентиляции и кондиционирования",
        "Technical survey of ventilation and air conditioning systems"),
    "AVK un SM sistēmu projekta un montāža darbu ekspertīze": (
        "Экспертиза проекта и монтажных работ систем ОВК и SM",
        "Expert review of the design and installation works of HVAC and SM systems"),
    "AVK būvprojekta ekspertize": ("Экспертиза строительного проекта ОВК", "Expert review of the HVAC construction design"),
    "AVK sistēmu būvuzraudzība": ("Строительный надзор за системами ОВК", "Site supervision of HVAC systems"),
    "Apkures, ventilācijas un gaisa kondicionēšanas sistēmu ekspertīze": (
        "Экспертиза систем отопления, вентиляции и кондиционирования",
        "Expert review of heating, ventilation and air conditioning systems"),
    "Apkures un ventilācijas sistēmu tehniskā projekta izstrāde": (
        "Разработка технического проекта систем отопления и вентиляции",
        "Development of the technical design of heating and ventilation systems"),
    "AVK sistēmu tehniskās shēmas izstrāde": ("Разработка технической схемы систем ОВК", "Development of the technical scheme of HVAC systems"),
    "Ventilācijas sistēmu projektēšanas un regulēšanas darbi": (
        "Проектирование и регулировка вентиляционных систем", "Design and adjustment of ventilation systems"),
    "Ventilācijas sistēmu ekspertīzes un ieregulēšanas darbi": (
        "Экспертиза и наладка вентиляционных систем", "Expert review and balancing of ventilation systems"),
    "Ventilācijas sistēmu montāžnieks, skārdnieks": (
        "Монтажник вентиляционных систем, жестянщик", "Ventilation system installer, sheet-metal worker"),
    "Tehnoloģisko iekārtu testēšana un datu centra konceptuāla izstrāde.": (
        "Тестирование технологического оборудования и разработка концепции дата-центра.",
        "Testing of technological equipment and conceptual design of the data centre."),
    "1.vieta nominācijā Industriālā būve": ("1 место в номинации «Промышленное строительство»", "1st place in the Industrial Construction category"),
    "Latvijas Būvniecības Gada balva 2019": ("Ежегодная строительная премия Латвии 2019", "Latvian Construction Annual Award 2019"),
    "zvērtēt būvprojekta apkures, ventilācijas un gaisa kondicionēšanas risinājumu atbilstību būvniecības reglamentējošo normatīvo aktu un tehnisko noteikumu prasībām.": (
        "Оценить соответствие решений по отоплению, вентиляции и кондиционированию в строительном проекте требованиям нормативных актов, регулирующих строительство, и технических условий.",
        "Assess the compliance of the heating, ventilation and air conditioning solutions in the construction design with the requirements of construction regulations and technical specifications."),
    "Modulārs gaisa dzesēšanas 9,6MW datu centrs": ("Модульный дата-центр с воздушным охлаждением 9,6 МВт", "Modular air-cooled 9.6 MW data centre"),
}


def build(lang_idx):
    c = SRC.read_text(encoding="utf-8")
    for lv in sorted(T, key=len, reverse=True):
        c = c.replace(lv, T[lv][lang_idx])
    return c


def main():
    left = []
    for idx, lang in ((0, "ru"), (1, "en")):
        out = build(idx)
        (OUT / f"home.{lang}.html").write_text(out, encoding="utf-8")
        print(f"home.{lang}.html: {len(out)} знаков")
    # какие латышские слова остались вне названий объектов (для ручной проверки)
    import re
    ru = (OUT / "home.ru.html").read_text(encoding="utf-8")
    plain = re.sub(r"\[vc_raw_html\].*?\[/vc_raw_html\]|<[^>]+>|\[[^\]]+\]", " ", ru, flags=re.S)
    for m in sorted(set(re.findall(r"\b\w*[āēīūšžčņļķģ]\w*", plain))):
        left.append(m)
    print("латышские слова с диакритикой остались (это названия объектов):", len(left))


if __name__ == "__main__":
    main()
