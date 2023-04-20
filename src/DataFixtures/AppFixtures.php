<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $cookCourse = new Course();
        $cookCourse
            ->setCode('cooking-1')
            ->setName('Уроки приготовления пищи для новичков')
            ->setDescription('Умение хорошо готовить приносит всеобщее признание кулинару и гастрономическое у
            довольствие окружающим. Поварской талант может быть даром или результатом упражнений и экспериментов 
            на кухне. Главное – научиться готовить несколько вариантов закусок, супов, гарниров, горячего, салатов 
            и десертов. Из базового набора блюд возможно составить множество вариаций сложного меню для обедов, 
            завтраков и ужинов.');

        $lesson = new Lesson();
        $lesson
            ->setName('Какие ножи использовать')
            ->setContent('Самый важный прибор на кухне — это нож шеф-повар. 
            Он должен идеально подходить под вашу руку, чтобы потом в процессе работы вы меньше уставали. 
            У него должен быть сбалансирован вес.')
            ->setNumber(1);
        $manager->persist($lesson);
        $cookCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Правильная заточка ножа')
            ->setContent('Что делать, если нож совсем стал тупым и им невозможно пользоваться? 
            А как мы знаем, такой нож приносит намного больше травм, чем наточенный. 
            Поэтому для заточки нужно использовать электрическую точилку.')
            ->setNumber(2);
        $manager->persist($lesson);
        $cookCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Разделочные доски')
            ->setContent('На кухне также должны быть разделочные доски. Причем их должно быть несколько.')
            ->setNumber(3);
        $manager->persist($lesson);
        $cookCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Чем опасны деревянные предметы на кухне')
            ->setContent('На кухне не должно быть никакого дерева!
            Да, это красиво и стильно. Но это неправильно с технологической точки зрения.
            Ни в одном ресторане не допускается использовать дерево.
            И это явно неспроста!
            Обычно на кухне рабочая поверхность стола состоит из мрамора.
             Это идеальный вариант, который пригоден для работы с любыми продуктами.')
            ->setNumber(4);
        $manager->persist($lesson);
        $cookCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Техника безопасности на кухне при готовке — кратко')
            ->setContent('Обязательно обратите внимание на эти кулинарные советы, 
            так как они напрямую влияют на технику безопасности.
            При работе с плитой запрещено проверять руками состояние рабочей поверхности и посуды.
            При добавлении жидкости на сильно разогретую поверхность, нужно иметь осторожность чтобы не обжечься паром.
            При жарке на разогретом масле, необходимо сначала просушить продукт на салфетке 
            чтобы не появились брызги. Потом переложить его при помощи лопатки.')
            ->setNumber(5);
        $manager->persist($lesson);
        $cookCourse->addLesson($lesson);

        $manager->persist($cookCourse);


        $carCourse = new Course();
        $carCourse
            ->setCode('car-1')
            ->setName('Сборник советов для начинающих водителей')
            ->setDescription('Человеку, который ранее никогда не садился за руль, 
            на начальном этапе вождение может показаться достаточно сложной наукой. Однако все не так страшно.');

        $lesson = new Lesson();
        $lesson
            ->setName('Упражнения для новичков.')
            ->setContent('Начинающим водителям очень сложно преодолеть страх и научиться быстро чувствовать машину. 
            Чтобы набрать небольшой опыт, в этом следует выполнять различные не сложные упражнения.
             Такие как, парковка задним ходом между двух препятствий и удерживание автомобиля 
             в наклонном положении только
              с помощью газа и сцепления. Каждому новичку не раз пригодятся такие навыки “на большой дороге”.')
            ->setNumber(1);
        $manager->persist($lesson);
        $carCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Продумывайте маршрут.')
            ->setContent('Собираясь в поездку, обязательно изучите карту своего пути, уделяя большое
             внимание прилегающим улицам, и старайтесь запомнить их названия. Рекомендуется проехать 
             интересующий маршрут с инструктором, либо с водителем, имеющим достаточный стаж вождения, чтобы он смог
              прокомментировать вам дорожные условия и наличия каких – либо недостатков. Выделите несколько выходных
               своих дней для изучения часто проезжающих вами маршрутов и проштудируйте их на практике, обращая внимание
               на дорожные знаки, лучший способ перестроения и т.д. Это поможет чувствовать себя увереннее за рулем в 
               будние дни без помощников.')
            ->setNumber(2);
        $manager->persist($lesson);
        $carCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Уверенность в себе — гарантия успеха.')
            ->setContent('В первую очередь, прежде чем сесть за руль автомобиля, следует быть спокойным и выдержанным
             — ничто не должно вас беспокоить во время поездки. Ни в коем случае не садитесь за руль, 
             если чувствуете какие-либо недомогания. Как только вы завели свой автомобиль, настройте зеркала,
              устройтесь поудобнее, пристегнитесь и…в добрый путь! Каждый начинающий водитель и не только, боится 
              поцарапать свою или чужую машину. Рекомендуется не жалеть денег на оформление страховки. 
              Если у вас подержанный автомобиль, будет достаточно оформить только гражданскую ответственность. 
              После оформления страховки вы не будете “шарахаться” от мимо проезжающего лихача 
              или бояться зацепить чужую машину.')
            ->setNumber(3);
        $manager->persist($lesson);
        $carCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName(' Все начинали с «чайников».')
            ->setContent('Не каждый новичок готов признать себя “чайником”. Но не боитесь носить этот титул!
             Каждый автолюбитель, каждый профессиональный водитель, даже пилоты «Формулы- 1» 
             когда-то были на вашем месте. Чтобы избежать множества проблем и облегчить себе вождение по городу, 
             установите на стекле заднего вида желтый восклицательный знак. Это предупредит других участников движения,
              что перед ними «молодой» водитель. Они будут снисходительно относиться к вашим ошибкам, 
              не будут подпирать вас сзади, будут пропускать при перестроении, не станут сигналить вам на светофорах,
               если вы замешкались при трогании.')
            ->setNumber(4);
        $manager->persist($lesson);
        $carCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Не спешите.')
            ->setContent('Чтобы стать профессиональным водителем, надо обладать двумя важными качествами 
            — это осмысленность действий за рулем и спокойствие. Помните никогда не надо 
            суетиться за рулем и не паниковать. Если вы заглохли, спокойно заводите своего «железного коня».
             Не получилось? Обязательно включите аварийные сигналы.
             Не подавайтесь провокациям, сзади сигналящим и поторапливающим водителям. Соблюдайте спокойствие.')
            ->setNumber(5);
        $manager->persist($lesson);
        $carCourse->addLesson($lesson);

        $manager->persist($carCourse);


        $cleanCourse = new Course();
        $cleanCourse
            ->setCode('$cleanCourse-1')
            ->setName('Уборка дома для начинающих')
            ->setDescription('Лето заканчивается, а это значит, что настало время провести г
            енеральную уборку с мытьем окон и стен, стиркой штор, чисткой мебели и радиаторов.');

        $lesson = new Lesson();
        $lesson
            ->setName('Открываем окна.')
            ->setContent('Генеральную уборку стоит начать с проветривания, чтобы не дышать химикатами
             и дать воздуху очистится естественным образом. 
             Если на улице холодно, каждую комнату можно проветривать по 10 минут, 
             пока вы находитесь в другом помещении. Например, пока вы прибираетесь в ванной.')
            ->setNumber(1);
        $manager->persist($lesson);
        $cleanCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Начинаем уборку с «чистого листа»')
            ->setContent('На этом этапе нужно: Снять все шторы и занавески и отправить их в стиральную машину 
            (пока они будут стираться, мы помоем окна);
             Собрать все грязное бельё в доме (в том числе постельный, столовый и банный текстиль) 
             и отправить его в корзину для белья; Собрать весь мусор в доме (
             испорченные продукты, пустые баночки в ванной, порванные носки, поломанные и просто устаревшие вещи), 
             а потом вынести мешки за пределы квартиры или на время оставить в прихожей; Собрать всю грязную посуду 
             в доме и загрузить ее в посудомойку/раковину; Собрать вещи, 
             которые лежат не на своих местах и аккуратно разложить;
              Убрать паутинки под потолком, если они есть.')
            ->setNumber(2);
        $manager->persist($lesson);
        $cleanCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Моем окна')
            ->setContent('Итак, пока шторы и занавески стираются, займемся окнами. 
            Сначала промываем их обычным мыльным раствором, затем чистой водой и, наконец,
             протираем чистой сухой тряпкой из микрофибры.')
            ->setNumber(3);
        $manager->persist($lesson);
        $cleanCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName(' Моем стены.')
            ->setContent('Сначала убираем всё, что висит на стенах. Далее, 
            обернув швабру влажной тряпкой или полотенцем, проходимся ею по стенам.')
            ->setNumber(4);
        $manager->persist($lesson);
        $cleanCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Протираем пыль')
            ->setContent('Вот чек-лист всего того, что подлежит влажной уборке:
             Все лампы, светильники и люстры; Подоконники; Радиаторы, трубы; Зеркала,
              стеклянные поверхности; Аксессуары и предметы интерьера (вазы, рамки и пр.);
               Фасады и внутренние поверхности корпусной мебели; Бытовая техника; Карнизы;
                Плинтусы, двери, наличники; Мягкая мебель (пылесосим со специальной насадкой,
                 кожаную обивку моем со специальным мягким средством); Антресоли; Листья растений.')
            ->setNumber(5);
        $manager->persist($lesson);
        $cleanCourse->addLesson($lesson);

        $manager->persist($cleanCourse);

        $manager->flush();
    }
}
