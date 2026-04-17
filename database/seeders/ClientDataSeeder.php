<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientDataSeeder extends Seeder
{
    public function run(): void
    {
        // ─── SBF member data: reg => [name, paid_2025, paid_2026, paid_soba, paid_2027, total_paid, arrears]
        $sbfMembers = [
            1 => ['Ali Omar Bofulo', 2100, 3600, 1000, 2600, 9300, 0],
            2 => ['Joseph Mtile Lewa', 2100, 1500, 1000, 0, 4600, 0],
            3 => ['Amos Amutete Simali', 2100, 0, 0, 0, 2100, 1900],
            4 => ['Jamal Athman Omar', 2100, 0, 0, 0, 2100, 1900],
            5 => ['Norbert Cliff Rakiro', 2100, 3600, 1000, 3600, 10300, 0],
            6 => ['Paul Eric Opiyo Oranga', 2100, 3600, 1000, 0, 6700, 0],
            7 => ["Okong'o Maina Amakulie", 2100, 3600, 1000, 0, 6700, 0],
            8 => ['Timothy Jacob Omondi Onyango', 2100, 3600, 1000, 0, 6700, 0],
            9 => ['Francis Mshoto Msengeti', 2100, 900, 1000, 0, 4000, 0],
            10 => ['Gabriel Matenge Nthiwa', 2100, 3600, 1000, 0, 6700, 0],
            11 => ['Wyclife Otieno Dola', 2100, 3600, 0, 0, 5700, 1000],
            12 => ['John Oyoto Ochola', 2100, 1800, 1000, 0, 4900, 0],
            13 => ['Alex Rungua Mwadondo', 2100, 0, 0, 0, 2100, 1900],
            14 => ['Joseph Ngari Karisa', 2100, 3600, 1000, 0, 6700, 0],
            15 => ['George Ondiek Nyasudi', 2100, 3600, 1000, 0, 6700, 0],
            16 => ['John Robert Anuro Wananda', 2100, 900, 1000, 0, 4000, 0],
            17 => ['Kennedy Jacob Ochieng', 2100, 900, 0, 0, 3000, 1000],
            18 => ['Anthony Joshuah Mbela', 2100, 1800, 1000, 0, 4900, 0],
            19 => ['Kenneth Kiraga Nzai', 2100, 0, 0, 0, 2100, 1900],
            20 => ['Christopher Mwangata Mwavuna', 2100, 3600, 1000, 0, 6700, 0],
            21 => ['Isaac Awuondo', 2100, 3600, 0, 0, 5700, 1000],
            22 => ['Franklin Onyango Adiwa', 2100, 900, 0, 0, 3000, 1000],
            23 => ['Dennis Odhiambo Ouna', 2100, 3600, 1000, 0, 6700, 0],
            24 => ['Oscar Salamba Bandi', 2100, 3600, 1000, 0, 6700, 0],
            25 => ['Salim Ali Mwakuphupha', 2100, 900, 1000, 0, 4000, 0],
            26 => ['Nichollas Otieno Adolph', 2100, 1800, 0, 0, 3900, 1000],
            27 => ['Patrick Job Ogao', 2100, 900, 1000, 0, 4000, 0],
            28 => ['Luciani Albinizi Sheldon', 2100, 2100, 0, 0, 4200, 1000],
            29 => ['Stephen Musyoka Kingoo', 2100, 3600, 1000, 0, 6700, 0],
            30 => ['Linus Owino Obim', 2100, 1800, 0, 0, 3900, 1000],
            31 => ['Juma Ali Churo', 2100, 0, 0, 0, 2100, 1900],
            32 => ['Michael Keretai Mupe', 2100, 900, 0, 0, 3000, 1000],
            33 => ['Stamili Chivatsi', 2100, 900, 1000, 0, 4000, 0],
            34 => ['Anthony Njaramba Njaramba', 2100, 0, 0, 0, 2100, 1900],
            35 => ['Edmond Oyoo', 2100, 0, 0, 0, 2100, 1900],
            36 => ['Stanley Michael Lado', 2100, 900, 0, 0, 3000, 1000],
            37 => ['Amin Abdalla Ndovu Mwidau', 2100, 3600, 1000, 3600, 10300, 0],
            38 => ['Edward Obuori Onyango', 2100, 300, 0, 0, 2400, 1600],
            39 => ['Richard Onim Weke', 2100, 0, 0, 0, 2100, 1900],
            40 => ['Edward Samwel Ponda', 2100, 1500, 0, 0, 3600, 1000],
            41 => ['Bakari Baishe Bakari', 2100, 0, 0, 0, 2100, 1900],
            42 => ['Eric Ambani Gidali', 2100, 1800, 1000, 0, 4900, 0],
            43 => ['Albert Owino', 2100, 0, 0, 0, 2100, 1900],
            44 => ['Francis Kinya', 2100, 0, 0, 0, 2100, 1900],
            45 => ['Moses Karisa Baya', 2100, 600, 0, 0, 2700, 1300],
            46 => ['Sami Athman Kivatsi', 2100, 3600, 1000, 600, 7300, 0],
            47 => ['Eric Mwagogo Mvoi', 2100, 1800, 0, 0, 3900, 1000],
            48 => ['Geoffrey Wanjala', 2100, 0, 0, 0, 2100, 1900],
            49 => ['Morris Kurugo Buni', 2100, 0, 0, 0, 2100, 1900],
            50 => ['Nelson Clay Odari', 2100, 3600, 1000, 0, 6700, 0],
            51 => ['Peter Apollo Ochieng', 2100, 900, 1000, 0, 4000, 0],
            52 => ['James Oboge', 2100, 900, 0, 0, 3000, 1000],
            53 => ['Paul Odhiambo Obako', 2100, 900, 1000, 0, 4000, 0],
            54 => ['Fakii Kombo Mfaki', 2100, 900, 0, 0, 3000, 1000],
            55 => ['Victor Nyongesa Odinga', 2100, 900, 0, 0, 3000, 1000],
            56 => ['Edwin Kimbio Mwaruta', 2100, 0, 0, 0, 2100, 1900],
            57 => ['Kashero E Lewa', 2100, 900, 1000, 0, 4000, 0],
            58 => ['Philip Ochieng Otieno', 2100, 0, 0, 0, 2100, 1900],
            59 => ['Goodwill Ngongo Omondi', 600, 0, 0, 0, 600, 3400],
            60 => ['Peter Penda Chibudu', 2100, 900, 1000, 0, 4000, 0],
            61 => ['James Yonah Okendo', 2100, 3600, 0, 0, 5700, 1000],
            62 => ['Samuel Kenga Kombe', 600, 0, 0, 0, 600, 3400],
            63 => ['Kelly Omondi Owillah', 2100, 3600, 1000, 0, 6700, 0],
            64 => ['Khalfan Ndume Baya', 2100, 0, 0, 0, 2100, 1900],
            65 => ['Vincent Abin Omondi', 2100, 3600, 1000, 0, 6700, 0],
            66 => ['George Omollo Adhola', 2100, 2100, 0, 0, 4200, 1000],
            67 => ['Caleb Mutali Wetungu', 2100, 0, 0, 0, 2100, 1900],
            68 => ['Frank Onyango Ouna', 2100, 0, 0, 0, 2100, 1900],
            69 => ['Timotheo Opar Awiti', 2100, 0, 0, 0, 2100, 1900],
            70 => ['Juma Edison Harre', 2100, 0, 0, 0, 2100, 1900],
            71 => ['Patrick Wilson Amakobe', 2100, 900, 1000, 0, 4000, 0],
            72 => ['Mboya Charles Ochanda', 2100, 900, 1000, 0, 4000, 0],
            73 => ['Stephen Munyao Kavita', 2100, 600, 0, 0, 2700, 1300],
            74 => ['Athanasius Jekonia Nyangaga', 2100, 900, 0, 0, 3000, 1000],
            75 => ['George Amollo', 2100, 1200, 1000, 0, 4300, 0],
            76 => ['Ismail Kamotho Musa', 2100, 0, 0, 0, 2100, 1900],
            77 => ['Christopher Edward Ochieng Ondeke', 2100, 0, 0, 0, 2100, 1900],
            78 => ['Dahabo Mohamed Ali Faisal Dahir', 2100, 0, 0, 0, 2100, 1900],
            79 => ['Omari Abdulkadir Hassan Aziz', 2100, 900, 0, 0, 3000, 1000],
            80 => ['Steven Steven Rabuku', 2100, 0, 0, 0, 2100, 1900],
            81 => ['Robert Murihe Ziro', 2100, 3600, 1000, 0, 6700, 0],
            82 => ['Bernard Mkola Mkaza', 2100, 3600, 1000, 0, 6700, 0],
            83 => ['Eddy Tsuma Sanga', 2100, 3600, 1000, 0, 6700, 0],
            84 => ['Ernest Mwandoe Mwangombe', 2100, 0, 0, 0, 2100, 1900],
            85 => ['Robbinson Omondi Owino', 2100, 900, 0, 0, 3000, 1000],
            86 => ['Anthony Kimotho', 2100, 0, 0, 0, 2100, 1900],
            87 => ['Lewis Mcharo', 2100, 1800, 1000, 0, 4900, 0],
            88 => ['Jimmy Siwillis', 2100, 3600, 1000, 0, 6700, 0],
            89 => ['David Ziro Mwamuye', 2100, 900, 0, 0, 3000, 1000],
            90 => ['Patrick Malowa Kalema', 2100, 0, 0, 0, 2100, 1900],
            91 => ['Remmy Kinyanjui Wamweri', 2100, 0, 0, 0, 2100, 1900],
            92 => ['Mohamed Omar Alugongo', 2100, 1800, 0, 0, 3900, 1000],
            93 => ['Terence Justimure Mudanya Chazima', 2100, 3600, 1000, 0, 6700, 0],
            94 => ['Wycliffe Ishmael Gitaa', 2100, 900, 1000, 0, 4000, 0],
            95 => ['Benjamin Oyile Odegi', 2100, 0, 0, 0, 2100, 1900],
            96 => ['Joseph Chenzah Chirima', 300, 0, 0, 0, 300, 3700],
            97 => ['Steven Kea', 2100, 1000, 0, 0, 3100, 1000],
            98 => ['Moses Njogu', 2100, 0, 0, 0, 2100, 1900],
            99 => ['Ali Swadi Ramtu', 2100, 1200, 0, 0, 3300, 1000],
            100 => ['Livingstone Odhiambo Nyando', 2100, 0, 0, 0, 2100, 1900],
            101 => ['Ali Moyo Shoghosho', 2100, 0, 0, 0, 2100, 1900],
            102 => ['Mark Mkula Mgharo', 2100, 0, 0, 0, 2100, 1900],
            103 => ['Edwin Gachoka Ngugi', 2100, 900, 1000, 0, 4000, 0],
            104 => ['Mzee Wellington Ndalo', 2100, 0, 0, 0, 2100, 1900],
            105 => ['Joshua Issa Kuria', 2100, 0, 0, 0, 2100, 1900],
            106 => ['Elijah Nzanah Wambua', 2100, 900, 1000, 0, 4000, 0],
            107 => ['Amir Abdallah Hamid Mtawa', 2100, 0, 0, 0, 2100, 1900],
            108 => ['Ibrahim Khamis Athman', 2100, 0, 0, 0, 2100, 1900],
            109 => ['Paul Abiero Opondo', 2100, 0, 0, 0, 2100, 1900],
            110 => ['Stephen Kilambyo Wambua', 2100, 900, 0, 0, 3000, 1000],
            111 => ['Stephen Macharia Maina', 2100, 0, 0, 0, 2100, 1900],
            112 => ['Moses Mugo Ngori', 2100, 0, 0, 0, 2100, 1900],
            113 => ['Fidel Mwakio', 2100, 900, 0, 0, 3000, 1000],
            114 => ['Nathaniel Amani Dzombo', 2100, 3600, 0, 0, 5700, 1000],
            115 => ['George Okoth Ochiel', 1200, 0, 0, 0, 1200, 2800],
            116 => ['Habil Osodo Ogutu Kadieda', 2100, 0, 0, 0, 2100, 1900],
            117 => ['Athman Ali Wampy', 2100, 0, 0, 0, 2100, 1900],
            118 => ['William Tindi Gege', 2100, 1500, 0, 0, 3600, 1000],
            119 => ['Samuel Omondi Okech', 2100, 900, 1000, 0, 4000, 0],
            120 => ['Michael Brian Mwakireti Tole', 2100, 3600, 1000, 0, 6700, 0],
            121 => ['Abdalla Salim Mwanzuga', 2100, 0, 0, 0, 2100, 1900],
            122 => ['Felix Mukanga Musumba', 2100, 0, 0, 0, 2100, 1900],
            123 => ['John Kichunju', 2100, 900, 0, 0, 3000, 1000],
            124 => ['James Mwangi Mutunga', 2100, 0, 0, 0, 2100, 1900],
            125 => ['Mulei Muindu Muindu', 2100, 1800, 1000, 0, 4900, 0],
            126 => ['Joseph Kotolo', 2100, 1800, 1000, 0, 4900, 0],
            127 => ['Leonard Lengisho Mbelle', 2100, 0, 0, 0, 2100, 1900],
            128 => ['Fredrick Patta', 2100, 3600, 1000, 0, 6700, 0],
            129 => ['Salim Rashid Bwika', 2100, 3600, 1000, 0, 6700, 0],
            130 => ['Enock Wisdom Mwakwida', 2100, 3600, 0, 0, 5700, 1000],
            131 => ['Richard Agutu Miyumo', 2100, 0, 0, 0, 2100, 1900],
            132 => ['Thomas Kundu Japanni', 2100, 0, 0, 0, 2100, 1900],
            133 => ['Michael Mutile Kalama', 2100, 600, 0, 0, 2700, 1300],
            134 => ['Stephen Nehemiah Odhiambo Odawo', 2100, 0, 0, 0, 2100, 1900],
            135 => ['Mark Dawai', 2100, 0, 0, 0, 2100, 1900],
            136 => ['Michael Ndolo', 2100, 0, 0, 0, 2100, 1900],
            137 => ['Stephen Chilibasi Kassim', 2100, 900, 0, 0, 3000, 1000],
            138 => ['Benson Onyango Ojuju', 2100, 0, 0, 0, 2100, 1900],
            139 => ['Mohammed Salim Bady', 2100, 0, 0, 0, 2100, 1900],
            140 => ['Samuel Mugo Njoroge', 2100, 0, 0, 0, 2100, 1900],
            141 => ['Jonathan Mwango Mulu', 2100, 900, 1000, 0, 4000, 0],
            142 => ['Peter Willie Kwoba', 2100, 3600, 0, 0, 5700, 1000],
            143 => ['Michael Kiti Mwachiru', 2100, 0, 0, 0, 2100, 1900],
            144 => ['Valerian Smith Mbandi', 2100, 1800, 0, 0, 3900, 1000],
            145 => ['Martin Luther Tsalwa', 2100, 1900, 0, 0, 4000, 1000],
            146 => ['Robinson Nzavi Mwandai', 2100, 1800, 1000, 0, 4900, 0],
            147 => ['John Nzive Kithete', 2100, 3600, 1000, 1000, 7700, 0],
            148 => ['Isaiah Wambua Munguti', 2100, 900, 1000, 0, 4000, 0],
            149 => ['Omotto Odundo', 2100, 0, 0, 0, 2100, 1900],
            150 => ['Stallone Mkadi', 2100, 3600, 1000, 0, 6700, 0],
            151 => ['Kennedy Muthii Rwibi', 2100, 0, 0, 0, 2100, 1900],
            152 => ['Coleman Onyango Otage', 2100, 3600, 0, 0, 5700, 1000],
            153 => ['Mwazighe Lenjo', 2100, 0, 0, 0, 2100, 1900],
            154 => ['Abel Chigunda Gogo', 2100, 3600, 1000, 0, 6700, 0],
            155 => ['Antony Mwendia Mutero', 2100, 0, 0, 0, 2100, 1900],
            156 => ['Pascal Dume Jira', 2100, 0, 0, 0, 2100, 1900],
            157 => ['Swaleh Athuman Kilele', 2100, 0, 0, 0, 2100, 1900],
            158 => ['Solomon Kinyua Njaramba', 2100, 3600, 1000, 0, 6700, 0],
            159 => ['William Benjamin Owuor Ochilo', 2100, 0, 0, 0, 2100, 1900],
            160 => ['Juma Said Hoka', 2100, 0, 0, 0, 2100, 1900],
            161 => ['Patrick Mukombelo Kwenya', 2100, 0, 0, 0, 2100, 1900],
            162 => ['Juma Ali Mngwari', 2100, 0, 0, 0, 2100, 1900],
            163 => ['Emmanuel Jira Chifalu', 2100, 0, 0, 0, 2100, 1900],
            164 => ['Lawrence Githinji', 2100, 3600, 1000, 0, 6700, 0],
            165 => ['Maxwell Kinda Ndeje', 1200, 0, 0, 0, 1200, 2800],
            166 => ['Tamam Seif Said', 2100, 900, 1000, 0, 4000, 0],
            167 => ['Joseph Nazareth Mgwata', 2100, 0, 0, 0, 2100, 1900],
            168 => ['George Were Sumba', 2100, 900, 1000, 0, 4000, 0],
            169 => ['Kighombe Kubo Mwacharo', 2100, 3600, 0, 0, 5700, 1000],
            170 => ['Mshenga Mwacharo', 2100, 0, 0, 0, 2100, 1900],
            171 => ['George Yongo Dzimba', 2100, 900, 0, 0, 3000, 1000],
            172 => ['James Gatonye', 2100, 900, 0, 0, 3000, 1000],
            173 => ['Alexander Maina Muchiri', 2100, 900, 1000, 0, 4000, 0],
            174 => ['Farid Salim Mwakaya', 2100, 0, 0, 0, 2100, 1900],
            175 => ['Clifford Oduor Agumbi', 2100, 0, 0, 0, 2100, 1900],
            176 => ['Hassan Mohamed', 2100, 900, 1000, 0, 4000, 0],
            177 => ["Maurice Kimeu King'oto", 2100, 0, 0, 0, 2100, 1900],
            178 => ['Vincent Maina Mwangi', 2100, 0, 0, 0, 2100, 1900],
            179 => ['Patrick Nyamai Kilonzi', 2100, 0, 0, 0, 2100, 1900],
            180 => ['Maurice Otieno Obala', 2100, 0, 0, 0, 2100, 1900],
            181 => ['Bernard Okello Kokonya', 2100, 0, 0, 0, 2100, 1900],
            182 => ['Stanley Nganga Gachamba', 2100, 0, 0, 0, 2100, 1900],
            183 => ['Juma Mashobo Mwaguya', 300, 0, 0, 0, 300, 3700],
            184 => ['Peter Mwabili Mwakugu', 2100, 0, 0, 0, 2100, 1900],
            185 => ['Stephen Otieno', 2100, 0, 0, 0, 2100, 1900],
            186 => ['Joseph Ted Aywaya', 2100, 3600, 1000, 0, 6700, 0],
            187 => ['Abdalla Said Mwakutala', 2100, 900, 1000, 0, 4000, 0],
            188 => ['Gilbert Mayar Maria', 1200, 0, 0, 0, 1200, 2800],
            189 => ['James Aggrey Wasonga', 2100, 0, 0, 0, 2100, 1900],
            190 => ['George Oindo Achieng', 300, 0, 0, 0, 300, 3700],
            191 => ['Baya Benson Imani Elishah', 2100, 0, 0, 0, 2100, 1900],
            192 => ['Nicholus Gudina Kanana', 2100, 0, 0, 0, 2100, 1900],
            193 => ['Mutonyi George Karanja', 2100, 900, 0, 0, 3000, 1000],
            194 => ['Fredrick John Ogolla Rarieya', 2100, 1500, 0, 0, 3600, 1000],
            195 => ['Edwin Ziro Mbura', 2100, 1500, 0, 0, 3600, 1000],
            196 => ['Lawrence Achola', 2100, 0, 0, 0, 2100, 1900],
            197 => ['Kadara Yaa Karabu', 2100, 900, 1000, 0, 4000, 0],
            198 => ['Julius Tyson Maghanga', 2100, 900, 0, 0, 3000, 1000],
            199 => ['Said Ali Masemo', 0, 2100, 0, 0, 2100, 1000],
            200 => ['Umar Faruoq Odhiambo Olang', 0, 4600, 1000, 0, 5600, 0],
        ];

        // ─── Chakama member data: reg => [name, shares, monthly_fee, balance_feb26, national_id]
        $chakamaMembersData = [
            1 => ['KEVIN AYIRO OSALLOH', 4, 2000, -9000, '22988726'],
            2 => ['MIKE JOSIAH BULLY MARENDES', 9, 4500, -67500, '13060805'],
            3 => ['JOSEPH MTILE LEWA', 1, 500, -2000, '11264247'],
            4 => ['STANLEY MUTWII TIMONAH WAMBUA', 2, 1000, -16000, '8434602'],
            6 => ['AMIR ABDALLAH HAMID MTAWA', 1, 500, -5000, '11496941'],
            7 => ['SAMI ATHMAN KIVATSI', 1, 500, -2000, '11460583'],
            8 => ['ISAAC ODUNDO AWUONDO', 23, 11500, -23000, '4827851'],
            9 => ['JOSEPH MALINGI MWALUKOMBE', 3, 1500, -11500, '24714683'],
            10 => ['OKONGO MAINA AMAKULIE', 1, 500, -1000, '14491780'],
            11 => ['OMARI ABDULKADIR HASSAN AZIZ', 2, 1000, -18000, '11788487'],
            12 => ['ALI OMAR BOFULO', 2, 1000, 7000, '14436320'],
            13 => ['FRANCIS MSHOTE MSENGETI', 3, 1500, -12000, '13683287'],
            14 => ['HAPPY MUTHUA KIMEMIA', 10, 5000, -90000, '13358536'],
            15 => ['WYCLIFE OTIENO DOLA', 1, 500, -1000, '20032903'],
            16 => ['NATHANIEL AMANI DZOMBO', 1, 500, -1000, '13446789'],
            17 => ['EDDY TSUMA SANGA', 2, 1000, -2000, '13839996'],
            18 => ['IBRAHIM KHAMIS MUTWAFY', 5, 2500, -30000, '9774050'],
            19 => ['REMMY KINYANJUI WAMWERI', 3, 1500, -6000, '20144555'],
            20 => ['OMAR AHMED OMAR', 1, 500, -4500, '11460637'],
            21 => ['ABDALLAH KIBWANA MAINGE', 1, 500, -9000, '13839609'],
            22 => ['GIBSON MWABILI MCHANA', 4, 2000, -16000, '24471620'],
            23 => ['GERALD ISABOKE KERAMA', 2, 1000, 1000, '9988792'],
            24 => ['STEPHEN MACHARIA MAINA', 4, 2000, -34000, '13684598'],
            26 => ['MARK MKULA MGHARO', 6, 3000, -51000, '11648196'],
            27 => ['AMOS MSENGETI', 1, 500, -3000, '13357537'],
            28 => ['MASHA DANIEL MAITHA', 1, 500, -3000, '11085813'],
            29 => ['RAMADHAN WANJE ALI', 10, 5000, -55000, '24161095'],
            30 => ['PETER WATHUO KIMANI', 1, 500, -8500, '14492308'],
            31 => ['ANTHONY NJOROGE KIMOTHO', 4, 2000, -36000, '11226606'],
            32 => ['MAURICE OTIENO OBALA', 10, 5000, -60000, '10579874'],
            33 => ['CHRISTOPHER MWANGATA MWAVUNA', 1, 500, 500, '30911982'],
            34 => ['SAMUEL MUGO NJOROGE', 1, 500, -6000, '23616747'],
            35 => ['VICTOR NYONGESA ODINGA', 2, 1000, -13000, '23788994'],
            36 => ['ANDREW KIOKO KYANDIH', 2, 1000, -18000, '13594607'],
            38 => ['FREDRICK PATTA', 5, 2500, -30000, '11226338'],
            40 => ['SOLOMON KINYUA NJARAMBA', 4, 2000, -4000, '32544562'],
            41 => ['JOSEPH MAKINGU MONDI', 10, 5000, -90000, '13683811'],
            42 => ['LEWIS HENRY SAHA MCHARO', 1, 500, -7000, '10956486'],
            43 => ['ELIAKIM MBATA OWALA', 5, 2500, -45000, '10404051'],
            44 => ['ANTHONY NJARAMBA', 1, 500, -9000, '13357406'],
            45 => ['MARK WELIKHE DAWAI', 2, 1000, -2000, '13746667'],
            46 => ['WYCLIFFE ISHMAEL GITAA', 2, 1000, -18000, '13839600'],
            47 => ["JOSEPH MARTIN NYANG'AU", 22, 11000, -198000, '5244827'],
            48 => ['ELIJAH WAMBUA NZANAH', 1, 500, -8000, '13594619'],
            49 => ['Mwagongo Dzombo', 1, 500, -3000, null],
            50 => ['SIDNEY RUFAS MWAKITELE MWALANDI', 1, 500, -9000, '13357455'],
            51 => ['ELIJAH ALFRED NYAWA MWENDA', 1, 500, -4000, '9774177'],
            52 => ['JIMMY NGOI SIWILLIS', 6, 3000, -6000, '13839667'],
            53 => ['STEPHEN EMANUEL OTIENO', 2, 1000, -18000, '13594678'],
            55 => ['JAMES OBOGE', 1, 500, -8000, '13839872'],
            56 => ['MZEE WELLINGTON NDALO', 1, 500, -1000, '22299326'],
            57 => ['KIGHOMBE MWACHARO KUBO', 1, 500, -3000, '11655644'],
            58 => ['ERIC MWASAHA SIMEON DECHE', 2, 1000, -16000, '9207122'],
            59 => ['HABIL OSODO OGUTU KADIEDA', 3, 1500, -9000, '10963082'],
            61 => ['TERENCE JUSTIMORE MUDANYA CHAZIMA', 1, 500, 3000, '10727995'],
        ];

        DB::transaction(function () use ($sbfMembers, $chakamaMembersData) {
            $now = now()->toDateTimeString();

            // ─── Resolve posting groups ────────────────────────────────────────────
            $customerPostingGroupId = DB::table('customer_posting_groups')
                ->where('code', 'MEMBER')
                ->value('id');

            $vendorPostingGroupId = DB::table('vendor_posting_groups')
                ->where('code', 'MEMBER')
                ->orWhere('code', 'VENDOR')
                ->orderByRaw("CASE WHEN code = 'MEMBER' THEN 0 ELSE 1 END")
                ->value('id');

            $fundAccountId = DB::table('fund_accounts')->value('id');

            // ─── Reset number_series ───────────────────────────────────────────────
            DB::table('number_series')
                ->whereIn('code', ['MBR', 'CUST', 'VEND'])
                ->update(['last_no' => 0]);

            // ─── Disable FK checks & truncate business tables ──────────────────────
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            $tablesToTruncate = [
                'bus_members',
                'customers',
                'vendors',
                'customer_ledger_entries',
                'detailed_customer_ledger_entries',
                'gl_entries',
                'bank_ledger_entries',
                'sales_headers',
                'sales_lines',
                'purchase_headers',
                'purchase_lines',
                'claims',
                'claim_approvals',
                'claim_lines',
                'claim_attachments',
                'claim_line_attachments',
                'share_subscriptions',
                'share_billing_runs',
                'fund_withdrawals',
                'fund_withdrawal_approvals',
                'fund_withdrawal_attachments',
                'fund_transactions',
                'cash_receipts',
                'direct_expenses',
                'direct_expense_lines',
                'direct_incomes',
                'direct_income_lines',
                'vendor_ledger_entries',
                'vendor_applications',
                'customer_applications',
                'vendor_payments',
                'project_members',
                'project_milestones',
                'project_attachments',
                'project_budget_lines',
                'project_comments',
                'project_direct_costs',
                'project_status_history',
                'projects',
                'notifications',
                'mpesa_transactions',
                'bus_documents',
            ];

            foreach ($tablesToTruncate as $table) {
                DB::table($table)->truncate();
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->command->info('Tables truncated.');

            // ─── Counters ──────────────────────────────────────────────────────────
            $mbrCounter = 0;
            $custCounter = 0;
            $vendCounter = 0;
            $entryNo = 0;

            // Map: normalised_name => member_id (populated as we create SBF members)
            $sbfNameIndex = [];

            // ─── Seed SBF members ──────────────────────────────────────────────────
            $this->command->info('Seeding SBF members...');

            foreach ($sbfMembers as $regNo => $row) {
                [$name, $paid2025, $paid2026, $paidSoba, $paid2027, $totalPaid, $arrears] = $row;

                $mbrCounter++;
                $custCounter++;
                $vendCounter++;

                $memberNo = 'MBR-' . str_pad($mbrCounter, 6, '0', STR_PAD_LEFT);
                $custNo = 'CUST-' . str_pad($custCounter, 6, '0', STR_PAD_LEFT);
                $vendNo = 'VEND-' . str_pad($vendCounter, 6, '0', STR_PAD_LEFT);

                $memberId = DB::table('bus_members')->insertGetId([
                    'type' => 'member',
                    'no' => $memberNo,
                    'name' => $name,
                    'member_status' => $regNo <= 198 ? 'active' : 'waiting',
                    'is_sbf' => true,
                    'is_chakama' => false,
                    'customer_no' => $custNo,
                    'vendor_no' => $vendNo,
                    'exclude_from_billing' => false,
                ]);

                DB::table('customers')->insert([
                    'no' => $custNo,
                    'name' => $name,
                    'customer_posting_group_id' => $customerPostingGroupId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('vendors')->insert([
                    'no' => $vendNo,
                    'name' => $name,
                    'vendor_posting_group_id' => $vendorPostingGroupId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // Store in name index for Chakama cross-reference
                $normalisedName = strtolower(trim($name));
                $sbfNameIndex[$normalisedName] = [
                    'member_id' => $memberId,
                    'customer_no' => $custNo,
                ];

                // ── SBF CustomerLedgerEntry ──
                $totalBilled = $totalPaid + $arrears;

                if ($totalBilled > 0) {
                    $entryNo++;
                    $customerId = DB::table('customers')
                        ->where('no', $custNo)
                        ->value('id');

                    DB::table('customer_ledger_entries')->insert([
                        'entry_no' => $entryNo,
                        'customer_id' => $customerId,
                        'document_type' => 'invoice',
                        'document_no' => 'HIST-SBF-' . str_pad($regNo, 3, '0', STR_PAD_LEFT),
                        'posting_date' => '2025-06-01',
                        'due_date' => '2026-03-31',
                        'amount' => $totalBilled,
                        'remaining_amount' => $arrears,
                        'is_open' => $arrears > 0,
                        'dimension' => 'sbf',
                        'created_at' => $now,
                    ]);
                }
            }

            $this->command->info("SBF: {$mbrCounter} members, {$entryNo} ledger entries created.");

            // ─── Ensure Chakama billing schedule ──────────────────────────────────
            $chakamaSchedule = DB::table('share_billing_schedules')
                ->where('name', 'Chakama Monthly Contribution')
                ->first();

            if (! $chakamaSchedule) {
                $scheduleId = DB::table('share_billing_schedules')->insertGetId([
                    'name' => 'Chakama Monthly Contribution',
                    'price_per_share' => 500.00,
                    'acres_per_share' => 0,
                    'billing_frequency' => 'monthly',
                    'is_default' => false,
                    'is_active' => true,
                    'fund_account_id' => $fundAccountId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $scheduleId = $chakamaSchedule->id;
            }

            $this->command->info("Chakama billing schedule id: {$scheduleId}");

            // ─── Seed Chakama members ──────────────────────────────────────────────
            $this->command->info('Seeding Chakama members...');

            $chakamaMemberCount = 0;
            $chakamaEntryCount = 0;

            foreach ($chakamaMembersData as $regNo => $row) {
                [$chkName, $shares, $monthlyFee, $balanceFeb26, $nationalId] = $row;

                $normalisedChkName = strtolower(trim($chkName));

                // Try to find matching SBF member by name
                $existingSbf = $sbfNameIndex[$normalisedChkName] ?? null;

                if ($existingSbf) {
                    // Update existing SBF member with Chakama flag
                    DB::table('bus_members')
                        ->where('id', $existingSbf['member_id'])
                        ->update([
                            'is_chakama' => true,
                            'identity_no' => $nationalId,
                            'identity_type' => $nationalId ? 'national_id' : null,
                        ]);

                    $memberId = $existingSbf['member_id'];
                    $custNo = $existingSbf['customer_no'];
                } else {
                    // New Chakama-only member
                    $mbrCounter++;
                    $custCounter++;
                    $vendCounter++;

                    $memberNo = 'MBR-' . str_pad($mbrCounter, 6, '0', STR_PAD_LEFT);
                    $custNo = 'CUST-' . str_pad($custCounter, 6, '0', STR_PAD_LEFT);
                    $vendNo = 'VEND-' . str_pad($vendCounter, 6, '0', STR_PAD_LEFT);

                    $memberId = DB::table('bus_members')->insertGetId([
                        'type' => 'member',
                        'no' => $memberNo,
                        'name' => $chkName,
                        'identity_no' => $nationalId,
                        'identity_type' => $nationalId ? 'national_id' : null,
                        'member_status' => 'active',
                        'is_sbf' => false,
                        'is_chakama' => true,
                        'customer_no' => $custNo,
                        'vendor_no' => $vendNo,
                        'exclude_from_billing' => false,
                    ]);

                    DB::table('customers')->insert([
                        'no' => $custNo,
                        'name' => $chkName,
                        'customer_posting_group_id' => $customerPostingGroupId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    DB::table('vendors')->insert([
                        'no' => $vendNo,
                        'name' => $chkName,
                        'vendor_posting_group_id' => $vendorPostingGroupId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $chakamaMemberCount++;
                }

                // ── ShareSubscription ──
                $totalAmount = $monthlyFee * 18; // 18 months Sep 2024 – Feb 2026
                $amountPaid = $totalAmount - max($balanceFeb26, 0);
                $subscriptionStatus = $balanceFeb26 > 0 ? 'pending_payment' : 'active';

                DB::table('share_subscriptions')->insert([
                    'no' => 'CHK-SUB-' . str_pad($regNo, 3, '0', STR_PAD_LEFT),
                    'member_id' => $memberId,
                    'billing_schedule_id' => $scheduleId,
                    'number_of_shares' => $shares,
                    'price_per_share' => 500.00,
                    'total_amount' => $totalAmount,
                    'amount_paid' => $amountPaid,
                    'status' => $subscriptionStatus,
                    'is_first_share' => false,
                    'is_nominee' => false,
                    'subscribed_at' => '2024-09-01',
                    'next_billing_date' => '2026-03-01',
                    'number_series_code' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // ── Chakama CustomerLedgerEntry (invoice) ──
                $customerId = DB::table('customers')
                    ->where('no', $custNo)
                    ->value('id');

                $entryNo++;
                $chakamaEntryCount++;

                DB::table('customer_ledger_entries')->insert([
                    'entry_no' => $entryNo,
                    'customer_id' => $customerId,
                    'document_type' => 'invoice',
                    'document_no' => 'HIST-CHK-' . str_pad($regNo, 3, '0', STR_PAD_LEFT),
                    'posting_date' => '2024-09-01',
                    'due_date' => '2026-02-28',
                    'amount' => $totalAmount,
                    'remaining_amount' => max($balanceFeb26, 0),
                    'is_open' => $balanceFeb26 > 0,
                    'dimension' => 'chakama',
                    'created_at' => $now,
                ]);

                // ── Credit entry if balance_feb26 < 0 (overpaid) ──
                if ($balanceFeb26 < 0) {
                    $entryNo++;
                    $chakamaEntryCount++;

                    DB::table('customer_ledger_entries')->insert([
                        'entry_no' => $entryNo,
                        'customer_id' => $customerId,
                        'document_type' => 'payment',
                        'document_no' => 'HIST-CHK-CR-' . str_pad($regNo, 3, '0', STR_PAD_LEFT),
                        'posting_date' => '2024-09-01',
                        'due_date' => '2026-02-28',
                        'amount' => $balanceFeb26,
                        'remaining_amount' => $balanceFeb26,
                        'is_open' => true,
                        'dimension' => 'chakama',
                        'created_at' => $now,
                    ]);
                }
            }

            $this->command->info("Chakama: {$chakamaMemberCount} new members, {$chakamaEntryCount} ledger entries.");

            // ─── Update number_series last_no ─────────────────────────────────────
            DB::table('number_series')->where('code', 'MBR')->update(['last_no' => $mbrCounter]);
            DB::table('number_series')->where('code', 'CUST')->update(['last_no' => $custCounter]);
            DB::table('number_series')->where('code', 'VEND')->update(['last_no' => $vendCounter]);

            $this->command->info("Number series updated — MBR: {$mbrCounter}, CUST: {$custCounter}, VEND: {$vendCounter}.");
            $this->command->info('ClientDataSeeder completed successfully.');
        });
    }
}
