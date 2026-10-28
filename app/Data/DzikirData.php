<?php

namespace App\Data;

/**
 * Dzikir pagi & petang — dari buku "Dzikir Pagi dan Petang dan
 * Sesudah Shalat Fardhu menurut Al-Qur'an dan As-Sunnah yang Shahih"
 * karya Ustadz Yazid bin Abdul Qadir Jawas rahimahullah
 * (Pustaka Imam Asy-Syafi'i, 2005; dinukil dari "Doa dan Wirid"
 * hlm. 133-155). Teks Arab + arti + dalil diverifikasi silang
 * dengan slide asli penulis dan artikel rujukan.
 *
 * Urutan mengikuti slide asli penulis (pagi 20 butir, petang 18
 * butir). Perbedaan pagi/petang: 'adada khalqih + 'ilman nafi'an
 * + istighfar 100x hanya di pagi; a'udzu bikalimatillah hanya di
 * petang; shalawat 10x di keduanya ("sepuluh kali pada pagi hari
 * dan sepuluh kali pada sore hari", HR. Ath-Thabrani).
 * Slide petang memuat doa 'afwa dua kali (halaman ganda) — di
 * sini dihitung sekali.
 */
class DzikirData
{
    public static function pagi(): array
    {
        return [
            self::kursi(),
            self::ikhlas(),
            self::falaq(),
            self::naas(),
            self::ashbahnaa(),
            self::bikaAshbahnaa(),
            self::sayyidul(),
            self::afini(),
            self::afwa(),
            self::alimal(),
            self::bismillahilladzi(),
            self::radhiitu(),
            self::yaHayyu(),
            self::fithrahPagi(),
            self::juwairiyah(),
            self::ilman(),
            self::tahlil(),
            self::tasbih(),
            self::istighfar(),
            self::shalawat(),
        ];
    }

    public static function petang(): array
    {
        return [
            self::kursi(),
            self::ikhlas(),
            self::falaq(),
            self::naas(),
            self::amsayna(),
            self::bikaAmsayna(),
            self::sayyidul(),
            self::afwa(),
            self::afini(),
            self::alimal(),
            self::bismillahilladzi(),
            self::radhiitu(),
            self::yaHayyu(),
            self::fithrahPetang(),
            self::tahlil(),
            self::tasbih(),
            self::audzu(),
            self::shalawat(),
        ];
    }

    private static function kursi(): array
    {
        return [
            "judul" => "Ayat Kursi",
            "arab" => "أَعُوذُ بِاللَّهِ مِنَ الشَّيْطَانِ الرَّجِيمِ
اللَّهُ لاَ إِلَهَ إِلاَّ هُوَ الْحَيُّ الْقَيُّومُ، لاَ تَأْخُذُهُ سِنَةٌ وَلاَ نَوْمٌ، لَهُ مَا فِي السَّمَاوَاتِ وَمَا فِي الْأَرْضِ، مَنْ ذَا الَّذي يَشْفَعُ عِنْدَهُ إِلاَّ بِإِذْنِهِ، يَعْلَمُ مَا بَيْنَ أَيْدِيهِمْ وَمَا خَلْفَهُمْ، وَلَا يُحِيطُونَ بِشَيْءٍ مِنْ عِلْمِهِ إِلاَّ بِمَا شَاءَ، وَسِعَ كُرْسِيُّهُ السَّمَاوَاتِ وَالْأَرْضَ، وَلَا يَئُودُهُ حِفْظُهُمَا، وَهُوَ الْعَلِيُّ الْعَظِيمُ ﴿٢٥٥﴾",
            "latin" => "A'udzu billahi minasy-syaithanir rajim. Allahu la ilaha illa Huwal Hayyul Qayyum, la ta'khudzuhu sinatun wa la naum, lahu ma fis-samawati wa ma fil-ardh, man dzalladzi yasyfa'u 'indahu illa bi-idznih, ya'lamu ma baina aidihim wa ma khalfahum, wa la yuhithuna bi syai'in min 'ilmihi illa bima sya', wasi'a kursiyyuhus-samawati wal-ardh, wa la ya'uduhu hifzhuhuma, wa Huwal 'Aliyyul 'Azhim.",
            "arti" => "Aku berlindung kepada Allah dari godaan syaitan yang terkutuk. Allah tidak ada Ilah (yang berhak diibadahi) melainkan Dia Yang Hidup Kekal lagi terus menerus mengurus (makhluk-Nya); tidak mengantuk dan tidak tidur. Kepunyaan-Nya apa yang ada di langit dan di bumi. Tidak ada yang dapat memberi syafa'at di sisi Allah tanpa izin-Nya. Allah mengetahui apa-apa yang (berada) di hadapan mereka, dan di belakang mereka dan mereka tidak mengetahui apa-apa dari Ilmu Allah melainkan apa yang dikehendaki-Nya. Kursi Allah meliputi langit dan bumi. Dan Allah tidak merasa berat memelihara keduanya, Allah Mahatinggi lagi Mahabesar. (QS. Al-Baqarah: 255)",
            "jumlah" => 1,
            "jumlah_label" => "1x",
            "dalil" => "Barangsiapa membacanya di pagi hari, ia dilindungi dari (gangguan) jin hingga sore hari; dan bila di sore hari, dilindungi hingga pagi hari. (HR. Al-Hakim 1/562, shahih)",
        ];
    }

    private static function ikhlas(): array
    {
        return [
            "judul" => "Surat Al-Ikhlas",
            "arab" => "بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ
قُلْ هُوَ اللَّهُ أَحَدٌ ﴿١﴾ اللَّهُ الصَّمَدُ ﴿٢﴾ لَمْ يَلِدْ وَلَمْ يُولَدْ ﴿٣﴾ وَلَمْ يَكُن لَّهُ كُفُوًا أَحَدٌ ﴿٤﴾",
            "latin" => "Qul huwallahu ahad. Allahush-shamad. Lam yalid wa lam yulad. Wa lam yakullahu kufuwan ahad.",
            "arti" => "Katakanlah, Dia-lah Allah Yang Maha Esa. Allah adalah (Rabb) yang segala sesuatu bergantung kepada-Nya. Dia tidak beranak dan tidak pula diperanakkan. Dan tidak ada seorang pun yang setara dengan-Nya. (QS. Al-Ikhlash: 1-4)",
            "jumlah" => 3,
            "jumlah_label" => "3x",
            "dalil" => "HR. Abu Dawud no. 5082, an-Nasa'i VIII/250, at-Tirmidzi no. 3575.",
        ];
    }

    private static function falaq(): array
    {
        return [
            "judul" => "Surat Al-Falaq",
            "arab" => "بِسْمِ اللّٰهِ الرَّحْمٰنِ الرَّحِيْمِ
قُلْ اَعُوْذُ بِرَبِّ الْفَلَقِ ﴿١﴾ مِنْ شَرِّ مَا خَلَقَ ﴿٢﴾ وَمِنْ شَرِّ غَاسِقٍ اِذَا وَقَبَ ﴿٣﴾ وَمِنْ شَرِّ النَّفّٰثٰتِ فِى الْعُقَدِ ﴿٤﴾ وَمِنْ شَرِّ حَاسِدٍ اِذَا حَسَدَ ﴿٥﴾",
            "latin" => "Qul a'uudzu birabbil falaq. Min syarri maa khalaq. Wa min syarri ghaasiqin idza waqab. Wa min syarrin naffathaati fil 'uqad. Wa min syarri haasidin idza hasad.",
            "arti" => "Katakanlah, Aku berlindung kepada Tuhan yang menguasai subuh (fajar), dari kejahatan (makhluk yang) Dia ciptakan, dan dari kejahatan malam apabila telah gelap gulita, dan dari kejahatan (perempuan-perempuan) penyihir yang meniup pada buhul-buhul (talinya), dan dari kejahatan orang yang dengki apabila dia dengki. (QS. Al-Falaq: 1-5)",
            "jumlah" => 3,
            "jumlah_label" => "3x",
            "dalil" => "Barangsiapa membaca tiga surat ini (Al-Ikhlas, Al-Falaq, An-Naas) setiap pagi dan sore hari, maka cukup baginya dari segala sesuatu. (HR. Abu Dawud no. 5082, at-Tirmidzi no. 3575, hasan shahih)",
        ];
    }

    private static function naas(): array
    {
        return [
            "judul" => "Surat An-Naas",
            "arab" => "بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ
قُلْ أَعُوذُ بِرَبِّ النَّاسِ ﴿١﴾ مَلِكِ النَّاسِ ﴿٢﴾ إِلَهِ النَّاسِ ﴿٣﴾ مِن شَرِّ الْوَسْوَاسِ الْخَنَّاسِ ﴿٤﴾ الَّذِي يُوَسْوِسُ فِي صُدُورِ النَّاسِ ﴿٥﴾ مِنَ الْجِنَّةِ وَ النَّاسِ ﴿٦﴾",
            "latin" => "Qul a'uudzu bi rabbin naas. Malikin naas. Ilahinaas. Min syarril waswaasil khannaas. Alladzii yuwaswisu fii shuduurin naas. Minal jinnati wannaas.",
            "arti" => "Katakanlah, Aku berlindung kepada Rabb (yang memelihara dan menguasai) manusia. Raja manusia. Sembahan (Ilah) manusia. Dari kejahatan (bisikan) syaitan yang biasa bersembunyi. Yang membisikkan (kejahatan) ke dalam dada-dada manusia. Dari golongan jin dan manusia. (QS. An-Naas: 1-6)",
            "jumlah" => 3,
            "jumlah_label" => "3x",
            "dalil" => "Barangsiapa membaca tiga surat ini (Al-Ikhlas, Al-Falaq, An-Naas) setiap pagi dan sore hari, maka cukup baginya dari segala sesuatu. (HR. Abu Dawud no. 5082, at-Tirmidzi no. 3575, hasan shahih)",
        ];
    }

    private static function sayyidul(): array
    {
        return [
            "judul" => "Sayyidul Istighfar",
            "arab" => "اَللَّهُمَّ أَنْتَ رَبِّيْ لاَ إِلَـهَ إِلاَّ أَنْتَ، خَلَقْتَنِيْ وَأَنَا عَبْدُكَ، وَأَنَا عَلَى عَهْدِكَ وَوَعْدِكَ مَا اسْتَطَعْتُ، أَعُوْذُ بِكَ مِنْ شَرِّ مَا صَنَعْتُ، أَبُوْءُ لَكَ بِنِعْمَتِكَ عَلَيَّ، وَأَبُوْءُ بِذَنْبِيْ فَاغْفِرْ لِيْ فَإِنَّهُ لاَ يَغْفِرُ الذُّنُوْبَ إِلاَّ أَنْتَ",
            "latin" => "Allahumma anta rabbi, la ilaha illa anta, khalaqtani wa ana 'abduka, wa ana 'ala 'ahdika wa wa'dika mastatha'tu. A'udzu bika min syarri ma shana'tu, abu'u laka bini'matika 'alayya wa abu'u bidzanbi, faghfirli, fainnahu la yaghfirudz-dzunuba illa anta.",
            "arti" => "Ya Allah, Engkau adalah Rabb-ku, tidak ada Ilah (yang berhak diibadahi dengan benar) kecuali Engkau, Engkau-lah yang menciptakanku. Aku adalah hamba-Mu. Aku akan setia pada perjanjianku dengan-Mu semampuku. Aku berlindung kepada-Mu dari kejelekan (apa) yang kuperbuat. Aku mengakui nikmat-Mu (yang diberikan) kepadaku dan aku mengakui dosaku, oleh karena itu, ampunilah aku. Sesungguhnya tidak ada yang dapat mengampuni dosa kecuali Engkau.",
            "jumlah" => 1,
            "jumlah_label" => "1x",
            "dalil" => "Barangsiapa membacanya dengan yakin di waktu pagi lalu meninggal sebelum masuk waktu sore, maka ia termasuk ahli Surga. (HR. Al-Bukhari no. 6306, 6323)",
        ];
    }

    private static function afini(): array
    {
        return [
            "judul" => "Doa Memohon Kesehatan Badan",
            "arab" => "اَللَّهُمَّ عَافِنِيْ فِيْ بَدَنِيْ، اَللَّهُمَّ عَافِنِيْ فِيْ سَمْعِيْ، اَللَّهُمَّ عَافِنِيْ فِيْ بَصَرِيْ، لاَ إِلَـهَ إِلاَّ أَنْتَ. اَللَّهُمَّ إِنِّي أَعُوْذُ بِكَ مِنَ الْكُفْرِ وَالْفَقْرِ، وَأَعُوْذُ بِكَ مِنْ عَذَابِ الْقَبْرِ، لاَ إِلَـهَ إِلاَّ أَنْتَ",
            "latin" => "Allahumma 'afini fi badani, Allahumma 'afini fi sam'i, Allahumma 'afini fi bashari, la ilaha illa anta. Allahumma inni a'udzu bika minal kufri wal faqri, wa a'udzu bika min 'adzabil qabri, la ilaha illa anta.",
            "arti" => "Ya Allah, selamatkanlah tubuhku (dari penyakit dan dari apa yang tidak aku inginkan). Ya Allah, selamatkanlah pendengaranku. Ya Allah, selamatkanlah penglihatanku, tidak ada Ilah yang berhak diibadahi dengan benar kecuali Engkau. Ya Allah, sesungguhnya aku berlindung kepada-Mu dari kekufuran dan kefakiran. Aku berlindung kepada-Mu dari siksa kubur, tidak ada Ilah yang berhak diibadahi dengan benar kecuali Engkau.",
            "jumlah" => 3,
            "jumlah_label" => "3x",
            "dalil" => "HR. Al-Bukhari dalam Shahiih al-Adabil Mufrad no. 701, Abu Dawud no. 5090, Ahmad V/42, hasan.",
        ];
    }

    private static function afwa(): array
    {
        return [
            "judul" => "Doa Memohon Ampunan dan Keselamatan",
            "arab" => "اَللَّهُمَّ إِنِّيْ أَسْأَلُكَ الْعَفْوَ وَالْعَافِيَةَ فِي الدُّنْيَا وَاْلآخِرَةِ، اَللَّهُمَّ إِنِّيْ أَسْأَلُكَ الْعَفْوَ وَالْعَافِيَةَ فِي دِيْنِيْ وَدُنْيَايَ وَأَهْلِيْ وَمَالِيْ اللَّهُمَّ اسْتُرْ عَوْرَاتِى وَآمِنْ رَوْعَاتِى. اَللَّهُمَّ احْفَظْنِيْ مِنْ بَيْنِ يَدَيَّ، وَمِنْ خَلْفِيْ، وَعَنْ يَمِيْنِيْ وَعَنْ شِمَالِيْ، وَمِنْ فَوْقِيْ، وَأَعُوْذُ بِعَظَمَتِكَ أَنْ أُغْتَالَ مِنْ تَحْتِيْ",
            "latin" => "Allahumma inni as-alukal 'afwa wal 'afiyata fid-dunya wal-akhirah. Allahumma inni as-alukal 'afwa wal 'afiyata fi dini wa dunyaya wa ahli wa mali. Allahummastur 'aurati wa amin rau'ati. Allahummahfazhni min baini yadayya wa min khalfi wa 'an yamini wa 'an syimali wa min fauqi, wa a'udzu bi 'azhamatika an ughtala min tahti.",
            "arti" => "Ya Allah, sesungguhnya aku memohon kebajikan dan keselamatan di dunia dan akhirat. Ya Allah, sesungguhnya aku memohon kebajikan dan keselamatan dalam agama, dunia, keluarga dan hartaku. Ya Allah, tutupilah auratku (aib) dan tentramkanlah aku dari rasa takut. Ya Allah, peliharalah aku dari depan, belakang, kanan, kiri dan dari atasku. Aku berlindung dengan kebesaran-Mu, agar aku tidak disambar dari bawahku (dibenamkan ke dalam bumi).",
            "jumlah" => 1,
            "jumlah_label" => "1x",
            "dalil" => "HR. Al-Bukhari dalam al-Adabul Mufrad no. 1200, Abu Dawud no. 5074, Ibnu Majah no. 3871, shahih.",
        ];
    }

    private static function alimal(): array
    {
        return [
            "judul" => "Doa Perlindungan dari Kejahatan Diri & Syaitan",
            "arab" => "اَللَّهُمَّ عَالِمَ الْغَيْبِ وَالشَّهَادَةِ فَاطِرَ السَّمَاوَاتِ وَاْلأَرْضِ، رَبَّ كُلِّ شَيْءٍ وَمَلِيْكَهُ، أَشْهَدُ أَنْ لاَ إِلَـهَ إِلاَّ أَنْتَ، أَعُوْذُ بِكَ مِنْ شَرِّ نَفْسِيْ، وَمِنْ شَرِّ الشَّيْطَانِ وَشِرْكِهِ، وَأَنْ أَقْتَرِفَ عَلَى نَفْسِيْ سُوْءًا أَوْ أَجُرُّهُ إِلَى مُسْلِمٍ",
            "latin" => "Allahumma 'alimal ghaibi wasy-syahadah, fathiras-samawati wal-ardh, rabba kulli syai'in wa malikahu, asyhadu alla ilaha illa anta, a'udzu bika min syarri nafsi, wa min syarri syaithani wa syirkihi, wa an aqtarifa 'ala nafsi su'an au ajurrahu ila muslim.",
            "arti" => "Ya Allah Yang Mahamengetahui yang ghaib dan yang nyata, wahai Rabb Pencipta langit dan bumi, Rabb atas segala sesuatu dan Yang Merajainya. Aku bersaksi bahwa tidak ada Ilah yang berhak diibadahi dengan benar kecuali Engkau. Aku berlindung kepada-Mu dari kejahatan diriku, syaitan dan ajakannya menyekutukan Allah, (aku berlindung kepada-Mu) dari berbuat kejelekan atas diriku atau mendorong seorang muslim kepadanya.",
            "jumlah" => 1,
            "jumlah_label" => "1x",
            "dalil" => "Nabi mengajarkan doa ini kepada Abu Bakar ash-Shiddiq. (HR. Al-Bukhari dalam al-Adabul Mufrad 1202, at-Tirmidzi no. 3392, shahih)",
        ];
    }

    private static function bismillahilladzi(): array
    {
        return [
            "judul" => "Doa Perlindungan dari Bahaya",
            "arab" => "بِسْمِ اللَّهِ الَّذِي لاَ يَضُرُّ مَعَ اسْمِهِ شَيْءٌ فِي الْأَرْضِ وَلاَ فِي السَّمَاءِ وَهُوَ السَّمِيعُ الْعَلِيمُ",
            "latin" => "Bismillahilladzi la yadhurru ma'asmihi syai'un fil-ardhi wa la fis-sama'i, wa Huwas-Sami'ul 'Alim.",
            "arti" => "Dengan nama Allah yang bila disebut, segala sesuatu di bumi dan langit tidak akan berbahaya, Dia-lah Yang Maha Mendengar lagi Maha Mengetahui.",
            "jumlah" => 3,
            "jumlah_label" => "3x",
            "dalil" => "Barangsiapa mengucapkannya tiga kali ketika pagi dan petang hari, maka tidak ada sesuatu pun yang membahayakan dirinya. (HR. Abu Dawud no. 5088, at-Tirmidzi no. 3388, shahih)",
        ];
    }

    private static function radhiitu(): array
    {
        return [
            "judul" => "Doa Ridha kepada Allah, Islam & Nabi",
            "arab" => "رَضِيْتُ بِاللَّهِ رَبًّا، وَبِالْإِسْلاَمِ دِيْنًا، وَبِمُحَمَّدٍ صَلَّى اللَّهُ عَلَيْهِ وَسَلَّمَ نَبِيًّا",
            "latin" => "Radhiitu billahi rabba, wa bil-islami dina, wa bi Muhammadin nabiyya.",
            "arti" => "Aku ridha Allah sebagai Rabb, Islam sebagai agama, dan Muhammad ﷺ sebagai nabi (dan rasul).",
            "jumlah" => 3,
            "jumlah_label" => "3x",
            "dalil" => "Barangsiapa mengucapkannya tiga kali di pagi dan petang, maka hak Allah untuk meridhainya di Hari Kiamat. (HR. Ahmad, Abu Dawud, an-Nasa'i, at-Tirmidzi, hasan)",
        ];
    }

    private static function yaHayyu(): array
    {
        return [
            "judul" => "Doa Memohon Perbaikan Urusan",
            "arab" => "يَا حَيُّ يَا قَيُّوْمُ بِرَحْمَتِكَ أَسْتَغِيْثُ، أَصْلِحْ لِيْ شَأْنِيْ كُلَّهُ وَلاَ تَكِلْنِيْ إِلَى نَفْسِيْ طَرْفَةَ عَيْنٍ",
            "latin" => "Ya Hayyu ya Qayyum, birahmatika astaghitsu, ashlih li sya'ni kullahu, wa la takilni ila nafsi tharfata 'ain.",
            "arti" => "Wahai Rabb Yang Maha Hidup, wahai Rabb Yang Berdiri Sendiri tidak butuh segala sesuatu, dengan rahmat-Mu aku minta pertolongan, perbaikilah segala urusanku dan jangan diserahkan kepadaku sekalipun sekejap mata (tanpa mendapat pertolongan dari-Mu).",
            "jumlah" => 1,
            "jumlah_label" => "1x",
            "dalil" => "HR. Al-Hakim (I/545), dishahihkan al-Albani dalam Shahiih at-Targhiib wat Tarhiib (I/417 no. 661).",
        ];
    }

    private static function tahlil(): array
    {
        return [
            "judul" => "Tahlil",
            "arab" => "لاَ إِلَهَ إِلاَّ اللَّهُ وَحْدَهُ لاَ شَرِيكَ لَهُ، لَهُ الْمُلْكُ وَلَهُ الْحَمْدُ وَهُوَ عَلَى كُلِّ شَيْءٍ قَدِيرٌ",
            "latin" => "La ilaha illallah wahdahu la syarika lahu, lahul mulku wa lahul hamdu, wa Huwa 'ala kulli syai'in qadir.",
            "arti" => "Tidak ada Ilah (yang berhak diibadahi dengan benar) selain Allah semata, tidak ada sekutu bagi-Nya. Bagi-Nya kerajaan dan bagi-Nya segala puji. Dan Dia Maha Kuasa atas segala sesuatu.",
            "jumlah" => 10,
            "pilihan" => [1, 10, 100],
            "jumlah_label" => "1x / 10x / 100x",
            "dalil" => "Bila dibaca 100x: pahala memerdekakan sepuluh budak, dicatat seratus kebaikan, dihapus seratus keburukan, dan menjadi pelindung dari syaitan pada hari itu hingga sore. (HR. Al-Bukhari no. 3293, 6403; Muslim no. 2691)",
        ];
    }

    private static function tasbih(): array
    {
        return [
            "judul" => "Tasbih",
            "arab" => "سُبْحَانَ اللَّهِ وَبِحَمْدِهِ",
            "latin" => "Subhanallahi wa bihamdihi.",
            "arti" => "Mahasuci Allah, aku memuji-Nya.",
            "jumlah" => 100,
            "jumlah_label" => "100x",
            "dalil" => "Barangsiapa mengucapkannya seratus kali dalam sehari, maka dosa-dosanya akan dihapus meskipun sebanyak buih di lautan. (HR. Al-Bukhari no. 6405, Muslim no. 2691)",
        ];
    }

    private static function shalawat(): array
    {
        return [
            "judul" => "Shalawat kepada Nabi ﷺ",
            "arab" => "اَللَّهُمَّ صَلِّ وَسَلِّمْ عَلَى نَبِيِّنَا مُحَمَّدٍ",
            "latin" => "Allahumma shalli wa sallim 'ala nabiyyina Muhammad.",
            "arti" => "Ya Allah, limpahkanlah shalawat dan salam kepada Nabi kami Muhammad.",
            "jumlah" => 10,
            "jumlah_label" => "10x",
            "dalil" => "Barangsiapa bershalawat kepadaku sepuluh kali pada pagi hari dan sepuluh kali pada sore hari, maka ia akan mendapatkan syafa'atku pada Hari Kiamat. (HR. Ath-Thabrani, dishahihkan al-Albani dalam Shahiih at-Targhiib wat Tarhiib no. 659)",
        ];
    }

    private static function ashbahnaa(): array
    {
        return [
            "judul" => "Doa Pagi Hari",
            "arab" => "أَصْبَحْنَا وَأَصْبَحَ الْمُلْكُ لِلَّهِ، وَالْحَمْدُ لِلَّهِ، لاَ إِلَـهَ إِلاَّ اللهُ وَحْدَهُ لاَ شَرِيْكَ لَهُ، لَهُ الْمُلْكُ وَلَهُ الْحَمْدُ وَهُوَ عَلَى كُلِّ شَيْءٍ قَدِيْرُ. رَبِّ أَسْأَلُكَ خَيْرَ مَا فِيْ هَذَا الْيَوْمِ وَخَيْرَ مَا بَعْدَهُ، وَأَعُوْذُ بِكَ مِنْ شَرِّ مَا فِيْ هَذَا الْيَوْمِ وَشَرِّ مَا بَعْدَهُ، رَبِّ أَعُوْذُ بِكَ مِنَ الْكَسَلِ وَسُوْءِ الْكِبَرِ، رَبِّ أَعُوْذُ بِكَ مِنْ عَذَابٍ فِي النَّارِ وَعَذَابٍ فِي الْقَبْرِ",
            "latin" => "Ashbahna wa ashbahal mulku lillah, walhamdulillah, la ilaha illallah wahdahu la syarika lahu, lahul mulku wa lahul hamdu, wa Huwa 'ala kulli syai'in qadir. Rabbi as-aluka khaira ma fi hadzal yaumi wa khaira ma ba'dahu, wa a'udzu bika min syarri ma fi hadzal yaumi wa syarri ma ba'dahu, rabbi a'udzu bika minal kasali wa su'il kibari, rabbi a'udzu bika min 'adzabin fin-nari wa 'adzabin fil-qabri.",
            "arti" => "Kami telah memasuki waktu pagi dan kerajaan hanya milik Allah, segala puji hanya milik Allah. Tidak ada ilah yang berhak diibadahi dengan benar kecuali Allah Yang Maha Esa, tiada sekutu bagi-Nya. Bagi-Nya kerajaan dan bagi-Nya pujian. Dia-lah Yang Mahakuasa atas segala sesuatu. Wahai Rabb, aku mohon kepada-Mu kebaikan di hari ini dan kebaikan sesudahnya. Aku berlindung kepada-Mu dari kejahatan hari ini dan kejahatan sesudahnya. Wahai Rabb, aku berlindung kepada-Mu dari kemalasan dan kejelekan di hari tua. Wahai Rabb, aku berlindung kepada-Mu dari siksaan di Neraka dan siksaan di kubur.",
            "jumlah" => 1,
            "jumlah_label" => "1x",
            "dalil" => "HR. Muslim no. 2723, Abu Dawud no. 5071, at-Tirmidzi 3390, shahih.",
        ];
    }

    private static function bikaAshbahnaa(): array
    {
        return [
            "judul" => "Doa Memasuki Waktu Pagi",
            "arab" => "اَللَّهُمَّ بِكَ أَصْبَحْنَا، وَبِكَ أَمْسَيْنَا، وَبِكَ نَحْيَا، وَبِكَ نَمُوْتُ وَإِلَيْكَ النُّشُوْرُ",
            "latin" => "Allahumma bika ashbahna, wa bika amsayna, wa bika nahya, wa bika namutu, wa ilaikan-nusyur.",
            "arti" => "Ya Allah, dengan rahmat dan pertolongan-Mu kami memasuki waktu pagi, dan dengan rahmat dan pertolongan-Mu kami memasuki waktu sore. Dengan rahmat dan kehendak-Mu kami hidup dan dengan rahmat dan kehendak-Mu kami mati. Dan kepada-Mu kebangkitan (bagi semua makhluk).",
            "jumlah" => 1,
            "jumlah_label" => "1x",
            "dalil" => "HR. Al-Bukhari dalam al-Adabul Mufrad no. 1199, at-Tirmidzi no. 3391, Abu Dawud no. 5068, shahih.",
        ];
    }

    private static function fithrahPagi(): array
    {
        return [
            "judul" => "Dzikir di Atas Fitrah Islam",
            "arab" => "أَصْبَحْنَا عَلَى فِطْرَةِ الْإِسْلاَمِ، وَعَلَى كَلِمَةِ الْإِخْلاَصِ، وَعَلَى دِيْنِ نَبِيِّنَا مُحَمَّدٍ صَلَّى اللَّهُ عَلَيْهِ وَسَلَّمَ، وَعَلَى مِلَّةِ أَبِيْنَا إِبْرَاهِيْمَ حَنِيْفًا مُسْلِمًا وَمَا كَانَ مِنَ الْمُشْرِكِيْنَ",
            "latin" => "Ashbahna 'ala fithratil Islam, wa 'ala kalimatil ikhlash, wa 'ala dini nabiyyina Muhammad shallallahu 'alaihi wa sallam, wa 'ala millati abina Ibrahim hanifan musliman wa ma kana minal musyrikin.",
            "arti" => "Di waktu pagi kami memegang agama Islam, kalimat ikhlas (syahadat), agama Nabi kami Muhammad ﷺ, dan agama bapak kami Ibrahim, yang berdiri di atas jalan yang lurus, muslim dan tidak tergolong orang-orang musyrik.",
            "jumlah" => 1,
            "jumlah_label" => "1x",
            "dalil" => "HR. Ahmad (III/406, 407), Ibnu As-Sunni no. 34, shahih.",
        ];
    }

    private static function juwairiyah(): array
    {
        return [
            "judul" => "Tasbih Juwairiyah",
            "arab" => "سُبْحَانَ اللَّهِ وَبِحَمْدِهِ، عَدَدَ خَلْقِهِ، وَرِضَا نَفْسِهِ، وَزِنَةَ عَرْشِهِ، وَمِدَادَ كَلِمَاتِهِ",
            "latin" => "Subhanallahi wa bihamdihi, 'adada khalqihi, wa ridha nafsihi, wa zinata 'arsyihi, wa midada kalimatihi.",
            "arti" => "Mahasuci Allah, aku memuji-Nya sebanyak makhluk-Nya, sejauh kerelaan-Nya, seberat timbangan 'Arsy-Nya, dan sebanyak tinta tulisan kalimat-Nya.",
            "jumlah" => 3,
            "jumlah_label" => "3x",
            "dalil" => "HR. Muslim no. 2726, dari Ummul Mukminin Juwairiyah radhiyallahu 'anha.",
        ];
    }

    private static function ilman(): array
    {
        return [
            "judul" => "Doa Memohon Ilmu & Rezeki",
            "arab" => "اَللَّهُمَّ إِنِّي أَسْأَلُكَ عِلْمًا نَافِعًا، وَرِزْقًا طَيِّبًا، وَعَمَلاً مُتَقَبَّلاً",
            "latin" => "Allahumma inni as-aluka 'ilman nafi'an, wa rizqan thayyiban, wa 'amalan mutaqabbalan.",
            "arti" => "Ya Allah, sesungguhnya aku memohon kepada-Mu ilmu yang bermanfaat, rezeki yang baik, dan amal yang diterima.",
            "jumlah" => 1,
            "jumlah_label" => "1x",
            "dalil" => "Dibaca setelah salam shalat Subuh. (HR. Ibnu Majah no. 925, dishahihkan al-Albani)",
        ];
    }

    private static function istighfar(): array
    {
        return [
            "judul" => "Istighfar",
            "arab" => "أَسْتَغْفِرُ اللَّهَ وَأَتُوبُ إِلَيْهِ",
            "latin" => "Astaghfirullah wa atubu ilaih.",
            "arti" => "Aku memohon ampun kepada Allah dan bertaubat kepada-Nya.",
            "jumlah" => 100,
            "jumlah_label" => "100x",
            "dalil" => "Rasulullah ﷺ dalam sehari beristighfar seratus kali. (HR. Al-Bukhari no. 6307, Muslim no. 2702)",
        ];
    }

    private static function amsayna(): array
    {
        return [
            "judul" => "Doa Petang Hari",
            "arab" => "أَمْسَيْنَا وَأَمْسَى الْمُلْكُ لِلَّهِ، وَالْحَمْدُ لِلَّهِ، لاَ إِلَـهَ إِلاَّ اللهُ وَحْدَهُ لاَ شَرِيْكَ لَهُ، لَهُ الْمُلْكُ وَلَهُ الْحَمْدُ وَهُوَ عَلَى كُلِّ شَيْءٍ قَدِيْرُ. رَبِّ أَسْأَلُكَ خَيْرَ مَا فِيْ هَذِهِ اللَّيْلَةِ وَخَيْرَ مَا بَعْدَهَا، وَأَعُوْذُ بِكَ مِنْ شَرِّ مَا فِي هَذِهِ اللَّيْلَةِ وَشَرِّ مَا بَعْدَهَا، رَبِّ أَعُوْذُ بِكَ مِنَ الْكَسَلِ وَسُوْءِ الْكِبَرِ، رَبِّ أَعُوْذُ بِكَ مِنْ عَذَابٍ فِي النَّارِ وَعَذَابٍ فِي الْقَبْرِ",
            "latin" => "Amsayna wa amsal mulku lillah, walhamdulillah, la ilaha illallah wahdahu la syarika lahu, lahul mulku wa lahul hamdu, wa Huwa 'ala kulli syai'in qadir. Rabbi as-aluka khaira ma fi hadzihil lailati wa khaira ma ba'daha, wa a'udzu bika min syarri ma fi hadzihil lailati wa syarri ma ba'daha, rabbi a'udzu bika minal kasali wa su'il kibari, rabbi a'udzu bika min 'adzabin fin-nari wa 'adzabin fil-qabri.",
            "arti" => "Kami telah memasuki waktu petang dan kerajaan hanya milik Allah, segala puji bagi Allah. Tidak ada Tuhan (yang berhak disembah) kecuali Allah Yang Maha Esa, tiada sekutu bagi-Nya. Bagi-Nya kerajaan dan bagi-Nya pujian. Dia-lah Yang Mahakuasa atas segala sesuatu. Wahai Tuhanku, aku mohon kepada-Mu kebaikan di malam ini dan kebaikan sesudahnya. Aku berlindung kepada-Mu dari kejahatan malam ini dan kejahatan sesudahnya. Wahai Tuhan, aku berlindung kepada-Mu dari kemalasan dan kejelekan di hari tua. Wahai Tuhan, aku berlindung kepada-Mu dari siksaan di Neraka dan siksaan di kubur.",
            "jumlah" => 1,
            "jumlah_label" => "1x",
            "dalil" => "HR. Muslim no. 2723, Abu Dawud no. 5071, at-Tirmidzi 3390, shahih.",
        ];
    }

    private static function bikaAmsayna(): array
    {
        return [
            "judul" => "Doa Memasuki Waktu Petang",
            "arab" => "اللَّهُمَّ بِكَ أَمْسَيْنَا وَبِكَ أَصْبَحْنَا، وَبِكَ نَحْيَا وَبِكَ نَمُوتُ، وَإِلَيْكَ الْمَصِيرُ",
            "latin" => "Allahumma bika amsayna, wa bika ashbahna, wa bika nahya, wa bika namutu, wa ilaikal-mashir.",
            "arti" => "Ya Allah, dengan rahmat dan pertolongan-Mu kami memasuki waktu petang, dan dengan rahmat dan pertolongan-Mu kami memasuki waktu pagi. Dengan rahmat dan kehendak-Mu kami hidup dan dengan rahmat dan kehendak-Mu kami mati. Dan kepada-Mu tempat kembali (bagi semua makhluk).",
            "jumlah" => 1,
            "jumlah_label" => "1x",
            "dalil" => "HR. Al-Bukhari dalam al-Adabul Mufrad, at-Tirmidzi no. 3391, Abu Dawud no. 5068, shahih.",
        ];
    }

    private static function fithrahPetang(): array
    {
        return [
            "judul" => "Dzikir di Atas Fitrah Islam",
            "arab" => "أَمْسَيْنَا عَلَى فِطْرَةِ اْلإِسْلاَمِ وَعَلَى كَلِمَةِ اْلإِخْلاَصِ، وَعَلَى دِيْنِ نَبِيِّنَا مُحَمَّدٍ صَلَّى اللهُ عَلَيْهِ وَسَلَّمَ، وَعَلَى مِلَّةِ أَبِيْنَا إِبْرَاهِيْمَ، حَنِيْفًا مُسْلِمًا وَمَا كَانَ مِنَ الْمُشْرِكِيْنَ",
            "latin" => "Amsayna 'ala fithratil Islam, wa 'ala kalimatil ikhlash, wa 'ala dini nabiyyina Muhammad shallallahu 'alaihi wa sallam, wa 'ala millati abina Ibrahim hanifan musliman wa ma kana minal musyrikin.",
            "arti" => "Di waktu petang kami berada di atas fitrah agama Islam, kalimat ikhlas, agama Nabi kami Muhammad ﷺ dan agama ayah kami, Ibrahim, yang berdiri di atas jalan yang lurus, muslim dan tidak tergolong orang-orang musyrik.",
            "jumlah" => 1,
            "jumlah_label" => "1x",
            "dalil" => "HR. Ahmad (III/406, 407), Ibnu As-Sunni no. 34, shahih.",
        ];
    }

    private static function audzu(): array
    {
        return [
            "judul" => "Doa Perlindungan dari Kejahatan Makhluk",
            "arab" => "أَعُوْذُ بِكَلِمَاتِ اللهِ التَّآمَّاتِ مِنْ شَرِّ مَا خَلَقَ",
            "latin" => "A'udzu bikalimatillahit-tammati min syarri ma khalaq.",
            "arti" => "Aku berlindung dengan kalimat-kalimat Allah yang sempurna dari kejahatan sesuatu yang diciptakan-Nya.",
            "jumlah" => 3,
            "jumlah_label" => "3x",
            "dalil" => "HR. Ahmad 11/290, an-Nasa-i dalam 'Amalul Yaum wal Lailah no. 590, Shahiih at-Targhiib wat Tarhiib 1/412 no. 652.",
        ];
    }
}
