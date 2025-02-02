<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Добролап");
?><nav class="navbar navbar-expand-lg navbar-dark ftco_navbar ftco-navbar-light site-navbar-target" id="ftco-navbar">
        <div class="container">
            <button class="navbar-toggler js-fh5co-nav-toggle fh5co-nav-toggle" type="button" data-toggle="collapse" data-target="#ftco-nav" aria-controls="ftco-nav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="oi oi-menu"></span>
            </button>

            <div class="collapse navbar-collapse" id="ftco-nav">
                <ul class="navbar-nav nav ml-auto">
                    <li class="nav-item"><a href="#needs" class="nav-link">Помогаем вместе</a></li>
                    <li class="nav-item"><a href="#shelter" class="nav-link">Приюты-участники</a></li>
                    <!--<li class="nav-item"><a href="#how_get" class="nav-link"><span>Принять участие</span></a></li>
                    <li class="nav-item"><a href="#thanks" class="nav-link"><span>Добрые сюрпризы</span></a></li>
                    <li class="nav-item"><a href="#little" class="nav-link"><span>Маленькие друзья</span></a></li>
                    <li class="nav-item"><a href="#challenge" class="nav-link"><span>Челлендж</span></a></li>-->
                    <li class="nav-item"><a href="#photos" class="nav-link">Фотоотчеты</a></li>
                    <li class="nav-item"><a href="#raise" class="nav-link">Добрые поездки</a></li>
                </ul>
            </div>
        </div>
    </nav>


    <section class="ftco-about img ftco-section ftco-no-pb" id="about-section">
        <div class="container">
            <div class="row d-flex">
                <div class="col-md-6 col-lg-5 d-flex">
                    <div class="img-about img d-flex align-items-stretch">
                        <div class="overlay"></div>
                        <div class="img d-flex align-self-stretch align-items-center" style="background-image:url(dobrolap/images/dobrolap_logo.png); background-size: contain; background-position: center bottom;">
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-7 pb-5">
                    <div class="row justify-content-start pb-3">
                        <div class="col-md-12 heading-section ftco-animate">
                            <span class="subheading">VI ЕЖЕГОДНАЯ БЛАГОТВОРИТЕЛЬНАЯ АКЦИЯ «ДОБРОЛАП»</span>
                            <h1 class="mb-4 mt-3">На Рождество</h1>
<div class="harvest_icon">
                    <a href="https://4lapy.ru/shares/dobrolap-pomogi-pitomtsam-iz-priyuta.html" class="btn btn-primary py-3 px-4" target="_blank">КАК ПОМОЧЬ</a>
                </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <? /*$APPLICATION->IncludeComponent('articul:dobrolap.form', '', []);*/ ?>

    <section class="ftco-section" id="needs">
        <div class="container">
            <div class="row justify-content-center pb-5">
                <div class="col-md-12 heading-section text-center ftco-animate">
                    <h2>ПОМОГАЕМ ВМЕСТЕ</h2>
                    <h5 class="mb-4">Компания «Четыре Лапы» помогает временно бездомным животным.</h5>
                    <hr>
                    <div class="harvest_icon read_more_btn">
                        <a href="javascript:void(0);" class="btn btn-primary-filled py-3 px-4">УЗНАТЬ ПРО ДОБРОЛАП</a>
                    </div>
                    <div class="needs_note">
                        <h5 class="mb-4">1 декабря стартовала VI&nbsp;ежегодная благотворительная акция «Добролап НА РОЖДЕСТВО»&nbsp;— ПОД ТАКИМ ЛОЗУНГОМ
                            в&nbsp;зоомагазинах «Четыре&nbsp;Лапы» проводится акция помощи приютам для
                            бездомных животных.<br>
                            Благотворительная инициатива «Добролап» уже в&nbsp;шестой раз проходит в&nbsp;сети
                            «Четыре&nbsp;Лапы». Совместно с&nbsp;27 благотворительными
                            организациями в&nbsp;этом году «Четыре&nbsp;лапы» объединяет всех, кто не&nbsp;равнодушен
                            к&nbsp;питомцам без семьи, с&nbsp;целью помочь животным
                            найти родителей и&nbsp;поддержать временно бездомных друзей.</h5>
                        <h5 class="mb-4">ПРИНЯТЬ УЧАСТИЕ МОЖНО НА&nbsp;САЙТЕ «ЧЕТЫРЕ&nbsp;ЛАПЫ» ИЛИ В&nbsp;ЛЮБОМ ЗООМАГАЗИНЕ «ЧЕТЫРЕ&nbsp;ЛАПЫ».<br>
                            ПРИСОЕДИНЯЙТЕСЬ К&nbsp;КОМАНДЕ «ДОБРОЛАП», УЗНАВАЙТЕ ПОДРОБНОСТИ НА&nbsp;САЙТЕ И&nbsp;СЛЕДИТЕ
                            ЗА&nbsp;НОВОСТЯМИ В&nbsp;СОЦИАЛЬНЫХ СЕТЯХ.</h5>
                    </div>
                </div>
            </div>
            <div class="row">
                <?$APPLICATION->IncludeComponent(
	"articul:dobrolap.necessary",
	"template1",
Array()
);?>
                <!--<div class="harvest_icon">
                    <a href="#how_get" class="btn btn-primary py-3 px-4" target="_blank">КАК ПОМОЧЬ</a>
                </div>-->
            </div>
        </div>
    </section>

    <section class="ftco-section ftco-counter img" id="helps">
        <div class="container">

            <div class="col-md-12 heading-section text-center ftco-animate">
                <h2>Мы помогаем</h2>
                <hr>
            </div>
            <div class="row d-md-flex align-items-center">
                <div class="col-md d-flex justify-content-center counter-wrap ftco-animate">
                    <div class="block-18">
                        <div class="text">
                            <strong class="number" data-number="14755">0</strong>
                            Питомцам
                        </div>
                    </div>
                </div>
                <div class="col-md d-flex justify-content-center counter-wrap ftco-animate">
                    <div class="block-18">
                        <div class="text">
                            <span class="free_place">из</span>
                            <strong class="number" data-number="27">0</strong>
                            приютов
                        </div>
                    </div>
                </div>
                <div class="col-md d-flex justify-content-center counter-wrap ftco-animate">
                    <div class="block-18">
                        <div class="text">
                            <span class="free_place">в</span>
                            <strong class="number" data-number="20">0</strong>
                            городах
                        </div>
                    </div>
                </div>
                <div class="cat_dog">
                    <img src="/dobrolap/images/help_bg_3.png" alt="">
                </div>
                <div class="harvest_icon">
                    <a href="https://4lapy.ru/shares/dobrolap-pomogi-pitomtsam-iz-priyuta.html" class="btn btn-primary py-3 px-4" target="_blank">КАК ПОМОЧЬ</a>
                </div>
            </div>
        </div>
    </section>

    <section class="ftco-section" id="shelter">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-12 heading-section text-center ftco-animate">
                    <h2>Приюты участники</h2>
                    <hr>
                </div>
            </div>
            <div class="row">
                <?$APPLICATION->IncludeComponent(
	"articul:dobrolap.shelters",
	"template1",
Array()
);?>
            </div>
        </div>
        <div class="read_more">
            <div class="btn btn-primary py-3 px-4 see_more" data-read-more-shelter="true">Показать больше ▼</div>
        </div>
    </section>

    <?/*<section class="ftco-section" id="how_get">
        <div class="container">
            <div class="row justify-content-center pb-5">
                <div class="col-md-12 heading-section text-center ftco-animate">
                    <h2 class="">Принять участие легко</h2>
                    <hr/>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <h4 class="subheader">в магазине «Четыре лапы»</h4>

                    <div class="how-get__shop">
                        <div class="col-md-12 animate-box">
                            <div class="rule_wrap">

                                <div class="rule_number">
                                    <img src="/dobrolap/images/01.png" alt="01"/>
                                </div>
                                <div class="rule_icon">
                                    <img src="/dobrolap/images/icon_1.png" alt="купи подарок"/>
                                </div>
                                <div class="rule_note">
                                    <span><strong>купи подарок</strong><br/>для питомцев из приюта</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 animate-box">
                            <div class="rule_wrap">

                                <div class="rule_number">
                                    <img src="/dobrolap/images/02.png" alt="02"/>
                                </div>
                                <div class="rule_icon">
                                    <img src="/dobrolap/images/icon_2.png" alt="положи в корзину"/>
                                </div>
                                <div class="rule_note">
                                    <span><strong>положи его</strong><br/>в корзину #добролап</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 animate-box">
                            <div class="rule_wrap">

                                <div class="rule_number">
                                    <img src="/dobrolap/images/03.png" alt="03"/>
                                </div>
                                <div class="rule_icon">
                                    <img src="/dobrolap/images/icon_3.png" alt="получи сюрприз"/>
                                </div>
                                <div class="rule_note">
                                    <span><strong>ПОЛУЧИ СЮРПРИЗ</strong><br/>И МАГНИТ #ДОБРОЛАП НА КАССЕ</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 animate-box">
                            <div class="rule_wrap">

                                <div class="rule_number">
                                    <img src="/dobrolap/images/04.png" alt="04"/>
                                </div>
                                <div class="rule_icon">
                                    <img src="/dobrolap/images/icon_4.png" alt="следи за итогами"/>
                                </div>
                                <div class="rule_note">
                                    <span><strong>СЛЕДИ</strong><br/>ЗА ИТОГАМИ И ОТЧЕТАМИ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 white-col">
                    <h4 class="subheader">на сайте  <a href="https://4lapy.ru/" target="_blank"><img
                                    src="/dobrolap/images/4lapy.png" alt=""/></a></h4>

                    <div class="how-get__site">
                        <div class="col-md-12 animate-box">
                            <div class="rule_wrap">

                                <div class="rule_number">
                                    <img src="/dobrolap/images/01.png" alt="01"/>
                                </div>
                                <div class="rule_icon">
                                    <img src="/dobrolap/images/icon_5.png" alt="выбери товары"/>
                                </div>
                                <div class="rule_note">
                                    <span><strong>ВЫБЕРИ ТОВАРЫ</strong><br/>И ПОЛОЖИ В КОРЗИНУ</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 animate-box">
                            <div class="rule_wrap">

                                <div class="rule_number">
                                    <img src="/dobrolap/images/02.png" alt="02"/>
                                </div>
                                <div class="rule_icon">
                                    <img src="/dobrolap/images/icon_6.png" alt="ВЫБЕРИ ПРИЮТ"/>
                                </div>
                                <div class="rule_note">
                                    <span><strong>ВЫБЕРИ ПРИЮТ</strong><br/>ПРИ ОФОРМЛЕНИИ ЗАКАЗА</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 animate-box">
                            <div class="rule_wrap">

                                <div class="rule_number">
                                    <img src="/dobrolap/images/03.png" alt="03"/>
                                </div>
                                <div class="rule_icon">
                                    <img src="/dobrolap/images/icon_3.png" alt="получи сюрприз"/>
                                </div>
                                <div class="rule_note">
                                    <span>ОПЛАТИ ЗАКАЗ,<br/><strong>ПОЛУЧИ СЮРПРИЗ</strong><br/>И МАГНИТ #ДОБРОЛАП</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 animate-box">
                            <div class="rule_wrap">

                                <div class="rule_number">
                                    <img src="/dobrolap/images/04.png" alt="04"/>
                                </div>
                                <div class="rule_icon">
                                    <img src="/dobrolap/images/icon_4.png" alt="следи за итогами"/>
                                </div>
                                <div class="rule_note">
                                    <span><strong>СЛЕДИ</strong><br/>ЗА ИТОГАМИ И ОТЧЕТАМИ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="harvest_icon">
                <a href="https://4lapy.ru/shares/blagotvoritelnaya-aktsiya-dobrolap-dlya-zhivotnykh-ikh-priyutov2.html"
                   class="btn btn-primary-filled py-3 px-4" target="blank">ХОЧУ ПОМОЧЬ</a>
            </div>
        </div>
    </section>*/?>

    <?/*<section class="ftco-section" id="thanks">
        <div class="container">
            <div class="row">

                <div class="col-md-6 col-md-6-mobile">
                    <div class="row justify-content-center">
                        <div class="col-md-12 heading-section text-center ftco-animate">
                            <h2 class="">ДОБРЫЕ<br/>СЮРПРИЗЫ</h2>
                            <hr/>
                            <h5 class="mb-4">Каждому человеку под силу совершить доброе дело и сделать счастливым
                                маленького пушистого друга. Тем более, что для этого надо совсем немного.</h5>
                            <h5 class="mb-4">На память о добром поступке каждый участник «Добролап» получит памятный
                                магнит и ОДИН ИЗ ДОБРЫХ СЮРПРИЗОВ: СКИДКУ НА АКСЕССУАРЫ ИЛИ ЛАКОМСТВА, БОНУСЫ НА ПОКУПКУ
                                ПРАВИЛЬНОГО КОРМА ИЛИ ОДИН ИЗ 2000 ФАН-БОНУСОВ ДЛЯ УЧАСТИЯ В РОЗЫГРЫШЕ ПРИЗОВ. Весь
                                август в магазинах «Четыре лапы» и на сайте «4lapy.ru».</h5>
                            <div class="thanks__btns">
                                <a href="javascript:void(0);"
                                   class="btn btn-primary-filled py-3 px-4 <?= ($USER->IsAuthorized()) ? 'js-show-fan-form' : 'js-open-popup' ?>"
                                   data-popup-id="authorization">ЗАРЕГИСТРИРОВАТЬ ФАН</a>
                                <a href="javascript:void(0);" class="btn btn-primary py-3 px-4 js-open-popup"
                                   data-popup-id="dobrolap_more_info_popup">ПОДРОБНЕЕ</a>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="col-md-3">
                    <div class="col-md-12 animate-box">
                        <div class="rule_wrap">
                            <div class="rule_icon">
                                <img src="/dobrolap/images/icon_7.png" alt=""/>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12 animate-box">
                        <div class="rule_wrap">
                            <div class="rule_icon">
                                <img src="/dobrolap/images/icon_9.png" alt=""/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="col-md-12 animate-box">
                        <div class="rule_wrap">
                            <div class="rule_icon">
                                <img src="/dobrolap/images/icon_8.png" alt=""/>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12 animate-box">
                        <div class="rule_wrap">
                            <div class="rule_icon">
                                <img src="/dobrolap/images/icon_10.png" alt=""/>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-md-6-desktop">
                    <div class="row justify-content-center">
                        <div class="col-md-12 heading-section text-center ftco-animate">
                            <h2 class="">ДОБРЫЕ<br/>СЮРПРИЗЫ</h2>
                            <hr/>
                            <h5 class="mb-4">Каждому человеку под силу совершить доброе дело и сделать счастливым
                                маленького пушистого друга. Тем более, что для этого надо совсем немного.</h5>
                            <h5 class="mb-4">На память о добром поступке каждый участник «Добролап» получит памятный
                                магнит и ОДИН ИЗ ДОБРЫХ СЮРПРИЗОВ: СКИДКУ НА АКСЕССУАРЫ ИЛИ ЛАКОМСТВА, БОНУСЫ НА ПОКУПКУ
                                ПРАВИЛЬНОГО КОРМА ИЛИ ОДИН ИЗ 2000 ФАН-БОНУСОВ ДЛЯ УЧАСТИЯ В РОЗЫГРЫШЕ ПРИЗОВ. Весь
                                август в магазинах «Четыре лапы» и на сайте «4lapy.ru».</h5>
                            <div class="thanks__btns">
                                <a href="javascript:void(0);"
                                   class="btn btn-primary-filled py-3 px-4 <?= ($USER->IsAuthorized()) ? 'js-show-fan-form' : 'js-open-popup' ?>"
                                   data-popup-id="authorization">ЗАРЕГИСТРИРОВАТЬ ФАН-БОНУС</a>
                                <a href="javascript:void(0);" class="btn btn-primary py-3 px-4 js-open-popup"
                                   data-popup-id="dobrolap_more_info_popup">ПОДРОБНЕЕ</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>*/?>

    <?/*<section class="ftco-section ftco-no-pb ftco-no-pt" id="little">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <div class="row justify-content-center little__description">
                        <div class="col-md-12 heading-section text-center ftco-animate">
                            <h2 class="">большая помощь<br/>для маленького друга</h2>
                            <hr/>
                            <h5 class="mb-4">Для самых маленьких «Четыре лапы» подготовили удобные подарочные коробочки,
                                в которые малыши могут положить подарок для питомца из приюта прямо в магазине и
                                подписать адресата, чтобы потом увидеть на сайте счастливые мордочки питомцев в рубрике
                                «Фотоотчет».</h5>
                            <h5 class="mb-4">Обязательно присоединяйтесь вместе с детьми: помощь маленького друга - это
                                большое доброе сердце и счастье научиться делать чудеса своими руками</h5>
                        </div>
                        <div class="harvest_icon">
                            <a href="#how_get" class="btn btn-primary py-3 px-4" target="_blank">КАК ПОМОЧЬ</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="col-md-12 animate-box">
                        <div class="rule_wrap">
                            <div class="rule_icon">
                                <img src="/dobrolap/images/little_boy.jpg" alt=""/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>*/?>

    <?/*<section class="ftco-section" id="challenge">
        <div class="col-md-12">
            <div class="row justify-content-center">
                <div class="col-md-12 heading-section text-center ftco-animate">
                    <h2 class="">Челлендж #командадобролап</h2>
                    <hr/>
                    <h5 class="mb-4">Стань частью команды – включайся в челлендж : расскажи своим подписчикам о том, как
                        помочь питомцам, у которых пока нет дома. Запиши видео или прикрепи фотографию. Обязательно
                        поставь хештег #командадобролап. Делись и собирай «лайки»: авторы 10 самых популярных историй
                        смогут превратить свои «лайки» в бонусные баллы!</h5>
                    <h5 class="mb-4">Присоединяйтесь к команде и следите за новостями в социальных сетях.</h5>
                </div>
            </div>
        </div>
        <!--<div class="home-slider  owl-carousel">
          <div class="slider-item ">
              <div class="overlay"></div>
            <div class="container">
              <div class="row d-md-flex no-gutters slider-text align-items-end justify-content-end" data-scrollax-parent="true">
                      <video controls poster="/dobrolap/images/29184619-preview.jpg">
                      <source src="/dobrolap/video/29184619-preview.mp4" type="video/mp4">
                    </video>
                </div>
            </div>
          </div>
          <div class="slider-item ">
              <div class="overlay"></div>
            <div class="container">
              <div class="row d-md-flex no-gutters slider-text align-items-end justify-content-end" data-scrollax-parent="true">
                      <video controls poster="/dobrolap/images/1014142868-preview.jpg">
                      <source src="/dobrolap/video/1014142868-preview.mp4" type="video/mp4">
                    </video>
                </div>
            </div>
          </div>
          <div class="slider-item ">
              <div class="overlay"></div>
            <div class="container">
              <div class="row d-md-flex no-gutters slider-text align-items-end justify-content-end" data-scrollax-parent="true">
                      <video controls poster="/dobrolap/images/1012398863-preview.jpg">
                      <source src="/dobrolap/video/1012398863-preview.mp4" type="video/mp4">
                    </video>

                    <span class='ion-ios-arrow-left'></span>
                </div>
            </div>
          </div>
        </div>-->

        <img src="/dobrolap/images/story.png?v=1" style="margin: 20px auto; display: block;" alt=""/>
    </section>*/?>

    <section class="ftco-section" id="photos">
        <div class="col-md-12">
            <div class="row justify-content-center">
                <div class="col-md-12 heading-section text-center ftco-animate">
                    <h2>фотоотчеты</h2>
                    <hr>
                </div>
            </div>
        </div>
        <div class="b-container">
            <section class="b-common-section">
                <div class="b-common-section__title-box b-common-section__title-box--sale">
                    <h2 class="b-title b-title--sale">&nbsp;</h2>
                </div>
                <div class="b-common-section__content b-common-section__content--sale b-common-section__content--main-sale js-popular-product">

                	<div class="b-common-item">
                        <a href="dobrolap/images/IMG_3864.JPG" data-lightbox="photos" class="photos__link" style="background-image: url('dobrolap/images/IMG_3864.JPG')" data-title="наша покупательница с добрым сердцем">
                        </a>
                        <div class="carousel-note"><p class="mb-4">Наша покупательница с добрым сердцем</p></div>
                    </div>

                    <div class="b-common-item">
                        <a href="dobrolap/images/IMG_3865.JPG" data-lightbox="photos" class="photos__link" style="background-image: url('dobrolap/images/IMG_3865.JPG')" data-title="маленький подарок для большого друга из приюта">
                        </a>
                        <div class="carousel-note"><p class="mb-4">Маленький подарок<br />для большого друга из приюта</p></div>
                    </div>

                    <div class="b-common-item">
                        <a href="dobrolap/images/IMG_3866.JPG" data-lightbox="photos" class="photos__link" style="background-image: url('dobrolap/images/IMG_3866.JPG')" data-title="подарки питомцам из приютов от наших покупателей">
                        </a>
                        <div class="carousel-note"><p class="mb-4">Подарки питомцам<br />из приютов от наших покупателей</p></div>
                    </div>

                    <div class="b-common-item">
                        <a href="dobrolap/images/IMG_3868.JPG" data-lightbox="photos" class="photos__link" style="background-image: url('dobrolap/images/IMG_3868.JPG')" data-title="сотрудники магазина всегда участвуют в акции Добролап">
                        </a>
                        <div class="carousel-note"><p class="mb-4">Сотрудники магазина<br />всегда участвуют в акции &laquo;Добролап&raquo;</p></div>
                    </div>

                    <div class="b-common-item">
                        <a href="dobrolap/images/IMG_3870.JPG" data-lightbox="photos" class="photos__link" style="background-image: url('dobrolap/images/IMG_3870.JPG')" data-title="питомцам из приютов ценна любая помощь и подарок">
                        </a>
                        <div class="carousel-note"><p class="mb-4">Питомцам из приютов ценна любая помощь и подарок</p></div>
                    </div>

                    <div class="b-common-item">
                        <a href="dobrolap/images/IMG_3871.JPG" data-lightbox="photos" class="photos__link" style="background-image: url('dobrolap/images/IMG_3871.JPG')" data-title="добрый поступок, совершенный вместе с мамой">
                        </a>
                        <div class="carousel-note"><p class="mb-4">Добрый поступок, совершенный вместе с мамой</p></div>
                    </div>

                    <div class="b-common-item">
                        <a href="dobrolap/images/IMG_3880.JPG" data-lightbox="photos" class="photos__link" style="background-image: url('dobrolap/images/IMG_3880.JPG')" data-title="котики будут очень рады подарку нашей покупательницы">
                        </a>
                        <div class="carousel-note"><p class="mb-4">Котики будут очень рады подарку нашей покупательницы</p></div>
                    </div>

                    <div class="b-common-item">
                        <a href="dobrolap/images/IMG_3885.JPG" data-lightbox="photos" class="photos__link" style="background-image: url('dobrolap/images/IMG_3885.JPG')" data-title="добрые дела с улыбкой на устах">
                        </a>
                        <div class="carousel-note"><p class="mb-4">Добрые дела с улыбкой на устах</p></div>
                    </div>


                    <div class="b-common-item">
                        <a href="/dobrolap/images/report/0209/1.jpg" data-lightbox="photos" class="photos__link" style="background-image: url('/dobrolap/images/report/0209/1.jpg')" data-title="Долгожданную помощь получил приют МУРЛЫКА, все его кошачие обитатели были очень довольны столь разнообразной и щедрой помощи">
                        </a>
                        <div class="carousel-note">
                            <p class="mb-4">
                                Долгожданную помощь получил приют МУРЛЫКА, все его кошачие обитатели были очень довольны столь разнообразной и щедрой помощи
                            </p>
                        </div>
                    </div>

                    <div class="b-common-item">
                        <a href="/dobrolap/images/report/2608/DSCN4338.JPG" data-lightbox="photos" class="photos__link" style="background-image: url('/dobrolap/images/report/2608/DSCN4338.JPG')" data-title="Корзинка «Добролап» никогда не бывает пустой">
                        </a>
                        <div class="carousel-note">
                            <p class="mb-4">
                                Корзинка «Добролап» никогда не бывает пустой
                            </p>
                        </div>
                    </div>

                    <div class="b-common-item">
                        <a href="/dobrolap/images/report/0209/2.jpg" data-lightbox="photos" class="photos__link" style="background-image: url('/dobrolap/images/report/0209/2.jpg')" data-title="Маленький подарок для большого друга от юного участника Добролап Кости из Москвы">
                        </a>
                        <div class="carousel-note">
                            <p class="mb-4">
                                Маленький подарок для большого друга от юного участника Добролап Кости из Москвы
                            </p>
                        </div>
                    </div>

                    <div class="b-common-item">
                        <a href="/dobrolap/images/report/2608/IMG_7423-13-08-19-10-34.jpeg" data-lightbox="photos" class="photos__link" style="background-image: url('/dobrolap/images/report/2608/IMG_7423-13-08-19-10-34.jpeg')" data-title="Каждый день питомцы из приютов получают долгожданные подарки">
                        </a>
                        <div class="carousel-note">
                            <p class="mb-4">
                                Каждый день питомцы из приютов получают долгожданные подарки
                            </p>
                        </div>
                    </div>

                    <div class="b-common-item">
                        <a href="/dobrolap/images/report/0209/3.jpg" data-lightbox="photos" class="photos__link" style="background-image: url('/dobrolap/images/report/0209/3.jpg')" data-title="Разнообразные подарки ПОЛУЧИЛ фонд «буду рядом», ВСЕ хвостики, обитатели фонда, ОЧЕНЬ рады.">
                        </a>
                        <div class="carousel-note">
                            <p class="mb-4">
                                Разнообразные подарки ПОЛУЧИЛ фонд «буду рядом», ВСЕ хвостики, обитатели фонда, ОЧЕНЬ рады.
                            </p>
                        </div>
                    </div>

                    <div class="b-common-item">
                        <a href="/dobrolap/images/report/2608/lyEQwzEt0aY.jpg" data-lightbox="photos" class="photos__link" style="background-image: url('/dobrolap/images/report/2608/lyEQwzEt0aY.jpg')" data-title="Вкусные лакомства теперь в обязательном рационе питомцев">
                        </a>
                        <div class="carousel-note">
                            <p class="mb-4">
                                Вкусные лакомства теперь в обязательном рационе питомцев
                            </p>
                        </div>
                    </div>

                    <div class="b-common-item">
                        <a href="/dobrolap/images/report/0209/4.jpg" data-lightbox="photos" class="photos__link" style="background-image: url('/dobrolap/images/report/0209/4.jpg')" data-title="Спасибо всем участникам за полные корзины, в каждом магазине.">
                        </a>
                        <div class="carousel-note">
                            <p class="mb-4">
                                Спасибо всем участникам за полные корзины, в каждом магазине.
                            </p>
                        </div>
                    </div>

                    <div class="b-common-item">
                        <a href="/dobrolap/images/report/2608/IMG_9399.JPG" data-lightbox="photos" class="photos__link" style="background-image: url('/dobrolap/images/report/2608/IMG_9399.JPG')" data-title="Команда волонтеров растет с каждым днем">
                        </a>
                        <div class="carousel-note">
                            <p class="mb-4">
                                Команда волонтеров растет с каждым днем
                            </p>
                        </div>
                    </div>

                    <div class="b-common-item">
                        <a href="/dobrolap/images/report/0209/5.jpg" data-lightbox="photos" class="photos__link" style="background-image: url('/dobrolap/images/report/0209/5.jpg')" data-title="Щедрость и отзывчивость участников добролап, не знает границ.">
                        </a>
                        <div class="carousel-note">
                            <p class="mb-4">
                                Щедрость и отзывчивость участников добролап, не знает границ.
                            </p>
                        </div>
                    </div>

                    <div class="b-common-item">
                        <a href="/dobrolap/images/report/2608/Skobelevskaja3.jpg" data-lightbox="photos" class="photos__link" style="background-image: url('/dobrolap/images/report/2608/Skobelevskaja3.jpg')" data-title="Больше всего маленьким питомцам нужны ветеринарные препараты и пеленки. Теперь все будет в порядке!">
                        </a>
                        <div class="carousel-note">
                            <p class="mb-4">
                                Больше всего маленьким питомцам нужны ветеринарные препараты и пеленки. Теперь все будет в порядке!
                            </p>
                        </div>
                    </div>

                    <div class="b-common-item">
                        <a href="/dobrolap/images/report/0209/6.jpg" data-lightbox="photos" class="photos__link" style="background-image: url('/dobrolap/images/report/0209/6.jpg')" data-title="Вместе мы смогли больше!">
                        </a>
                        <div class="carousel-note">
                            <p class="mb-4">
                                Вместе мы смогли больше!
                            </p>
                        </div>
                    </div>

                    <div class="b-common-item">
                        <a href="/dobrolap/images/report/2608/IMG_9332.JPG" data-lightbox="photos" class="photos__link" style="background-image: url('/dobrolap/images/report/2608/IMG_9332.JPG')" data-title="В Центре «Собаки-поводыри» обучаются лабрадоры и ретриверы. Эти породы наиболее приспособлены для ответственной работы собаки-поводыря.">
                        </a>
                        <div class="carousel-note">
                            <p class="mb-4">
                                В Центре «Собаки-поводыри» обучаются лабрадоры и ретриверы. Эти породы наиболее приспособлены для ответственной работы собаки-поводыря.
                            </p>
                        </div>
                    </div>

                    <div class="b-common-item">
                        <a href="/dobrolap/images/report/0209/7.jpg" data-lightbox="photos" class="photos__link" style="background-image: url('/dobrolap/images/report/0209/7.jpg')" data-title="Подарок для большого друга от юного участника Добролап Алины из Воронежа.">
                        </a>
                        <div class="carousel-note">
                            <p class="mb-4">
                                Подарок для большого друга от юного участника Добролап Алины из Воронежа.
                            </p>
                        </div>
                    </div>

                    <div class="b-common-item">
                        <a href="/dobrolap/images/report/02.jpg" data-lightbox="photos" class="photos__link" style="background-image: url('/dobrolap/images/report/02.jpg')" data-title="Команда «Добролап» помогла найти Лайме и еще более 100 питомцам новую семью">
                        </a>
                        <div class="carousel-note"><p class="mb-4">Команда «Добролап» помогла<br>найти Лайме и еще
                                более 100 питомцам<br>новую семью</p></div>
                    </div>

                    <div class="b-common-item">
                        <a href="/dobrolap/images/report/0209/8.jpg" data-lightbox="photos" class="photos__link" style="background-image: url('/dobrolap/images/report/0209/8.jpg')" data-title="Подарок для хвостиков от Нонны, 10 лет, из Московской области с искренними пожеланиями!!!">
                        </a>
                        <div class="carousel-note">
                            <p class="mb-4">
                                Подарок для хвостиков от Нонны, 10 лет, из Московской области с искренними пожеланиями!!!
                            </p>
                        </div>
                    </div>

                    <div class="b-common-item">
                        <a href="/dobrolap/images/report/03.jpg" data-lightbox="photos" class="photos__link" style="background-image: url('/dobrolap/images/report/03.jpg')" data-title="Самые вкусные подарки привозят друзья «Добролап»">
                        </a>
                        <div class="carousel-note"><p class="mb-4">Самые вкусные подарки<br>привозят друзья<br>«Добролап»
                            </p></div>
                    </div>

                    <div class="b-common-item">
                        <a href="/dobrolap/images/report/0209/9.jpg" data-lightbox="photos" class="photos__link" style="background-image: url('/dobrolap/images/report/0209/9.jpg')" data-title="Небольшой подарок, со всем самым необходимым еще от одного юного участника Добролап Кирилла, из Калуги">
                        </a>
                        <div class="carousel-note">
                            <p class="mb-4">
                                Небольшой подарок, со всем самым необходимым еще от одного юного участника Добролап Кирилла, из Калуги
                            </p>
                        </div>
                    </div>

                    <div class="b-common-item">
                        <a href="/dobrolap/images/report/04.jpg" data-lightbox="photos" class="photos__link" style="background-image: url('/dobrolap/images/report/04.jpg')" data-title="Большая дружба начинается с малого: более 500 ребят стали участниками акции в 2018 году">
                        </a>
                        <div class="carousel-note"><p class="mb-4">Большая дружба начинается<br>с малого: более 500
                                ребят стали<br>участниками акции в 2018 году</p></div>
                    </div>

                    <div class="b-common-item">
                        <a href="/dobrolap/images/report/0209/10.jpg" data-lightbox="photos" class="photos__link" style="background-image: url('/dobrolap/images/report/0209/10.jpg')" data-title="Фонд «Собаки которые любят», очень рад очередным подаркам.">
                        </a>
                        <div class="carousel-note">
                            <p class="mb-4">
                                Фонд «Собаки которые любят», очень рад очередным подаркам.
                            </p>
                        </div>
                    </div>

                    <div class="b-common-item">
                        <a href="/dobrolap/images/report/05.jpg" data-lightbox="photos" class="photos__link" style="background-image: url('/dobrolap/images/report/05.jpg')" data-title="Большая радость самому приехать к питомцам, которые очень тебя ждут">
                        </a>
                        <div class="carousel-note"><p class="mb-4">Большая радость самому<br>приехать к питомцам, которые<br>очень тебя ждут</p></div>
                    </div>

                </div>
            </section>
        </div>
    </section>

   <section class="ftco-section" id="together-more">
        <div class="col-md-12">
            <div class="row justify-content-center">
                <div class="col-md-12 heading-section text-center ftco-animate">
                    <h2>Вместе мы сможем больше</h2>
                    <hr>
                </div>
            </div>
        </div>
        <div class="home-slider home-slider_images owl-carousel">
            <div class="slider-item ">
                <div class="overlay"></div>
                <div class="container">
                    <div class="row d-md-flex no-gutters slider-text align-items-end justify-content-end" data-scrollax-parent="true">
                        <div class="home-slider__img">
                            <img src="/dobrolap/images/together-more/banner-together-more1.jpg?v=1" alt="">
                        </div>
                    </div>
                </div>
            </div>
            <div class="slider-item ">
                <div class="overlay"></div>
                <div class="container">
                    <div class="row d-md-flex no-gutters slider-text align-items-end justify-content-end" data-scrollax-parent="true">
                        <div class="home-slider__img">
                            <img src="/dobrolap/images/together-more/banner-together-more7.jpg?v=1" alt="">
                        </div>
                    </div>
                </div>
            </div>
            <div class="slider-item ">
                <div class="overlay"></div>
                <div class="container">
                    <div class="row d-md-flex no-gutters slider-text align-items-end justify-content-end" data-scrollax-parent="true">
                        <div class="home-slider__img">
                            <img src="/dobrolap/images/together-more/banner-together-more5.jpg?v=1" alt="">
                        </div>
                    </div>
                </div>
            </div>
            <div class="slider-item ">
                <div class="overlay"></div>
                <div class="container">
                    <div class="row d-md-flex no-gutters slider-text align-items-end justify-content-end" data-scrollax-parent="true">
                        <div class="home-slider__img">
                            <img src="/dobrolap/images/together-more/banner-together-more4.jpg?v=1" alt="">
                        </div>
                    </div>
                </div>
            </div>
            <div class="slider-item ">
                <div class="overlay"></div>
                <div class="container">
                    <div class="row d-md-flex no-gutters slider-text align-items-end justify-content-end" data-scrollax-parent="true">
                        <div class="home-slider__img">
                            <img src="/dobrolap/images/together-more/banner-together-more3.jpg?v=1" alt="">
                        </div>
                    </div>
                </div>
            </div>
            <div class="slider-item ">
                <div class="overlay"></div>
                <div class="container">
                    <div class="row d-md-flex no-gutters slider-text align-items-end justify-content-end" data-scrollax-parent="true">
                        <div class="home-slider__img">
                            <img src="/dobrolap/images/together-more/banner-together-more2.jpg?v=1" alt="">
                        </div>
                    </div>
                </div>
            </div>
            <div class="slider-item ">
                <div class="overlay"></div>
                <div class="container">
                    <div class="row d-md-flex no-gutters slider-text align-items-end justify-content-end" data-scrollax-parent="true">
                        <div class="home-slider__img">
                            <img src="/dobrolap/images/together-more/banner-together-more6.jpg?v=1" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="ftco-section" id="raise">
        <div class="col-md-12">
            <div class="row justify-content-center">
                <div class="col-md-12 heading-section text-center ftco-animate">
                    <h2>Добрые поездки</h2>
                    <hr>
                    <h5 class="mb-4">КАЖДУЮ НЕДЕЛЮ В АВГУСТЕ МЫ ОТПРАВЛЯЛИСЬ В ГОСТИ В ПРИЮТЫ К НАШИМ ЧЕТВЕРОЛАПЫМ ДРУЗЬЯМ, ЧТОБЫ ОТВЕЗТИ НУЖНЫЕ И ДОЛГОЖДАННЫЕ ПОДАРКИ.
ВМЕСТЕ МЫ СМОГЛИ БОЛЬШЕ!</h5>
                </div>
            </div>
        </div>
        <div class="home-slider  owl-carousel">
            <div class="slider-item">
                <div class="container">
                    <div class="row d-md-flex no-gutters slider-text align-items-end justify-content-end" data-scrollax-parent="true">
                        <?/*<video controls poster="/dobrolap/images/08_24_4lapy_Cats_02_1.jpg">
                            <source src="/upload/dobrolap/08_24_4lapy_Cats_02_1.mp4">
                            <source src="/upload/dobrolap/08_24_4lapy_Cats_02_1.ogv" type="video/webm">
                            <source src="/upload/dobrolap/08_24_4lapy_Cats_02_1.webm" type="video/ogg">
                        </video>*/?>

                        <div style="position: relative; width: 100%; max-width: 916px; margin: 0 auto; height: 0; padding-bottom: 48%;">
                          <iframe style="position: absolute; left: 0; top: 0; width: 100%; height: 100%;" src="https://www.youtube.com/embed/J-v8Fv538V0" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                    </div>
                </div>
            </div>

            <div class="slider-item">
                <div class="container">
                    <div class="row d-md-flex no-gutters slider-text align-items-end justify-content-end" data-scrollax-parent="true">
                        <?/* <video controls poster="/dobrolap/images/raise-preview2.jpg">
                            <source src="/upload/dobrolap/4lapi_priut_14_08_2.mp4">
                            <source src="/upload/dobrolap/4lapi_priut_14_08_2.ogv" type="video/webm">
                            <source src="/upload/dobrolap/4lapi_priut_14_08_2.webm" type="video/ogg">
                        </video> */?>

                        <div style="position: relative; width: 100%; max-width: 916px; margin: 0 auto; height: 0; padding-bottom: 48%;">
                          <iframe style="position: absolute; left: 0; top: 0; width: 100%; height: 100%;" src="https://www.youtube.com/embed/UoW5HlZrw1I" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section><? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>