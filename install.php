<?php
function qelner_install_db() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    // Tabela menu
    $table_menu = $wpdb->prefix . 'qelner_menu';
    $sql_menu = "CREATE TABLE $table_menu (
        id_dania mediumint(9) NOT NULL AUTO_INCREMENT,
        czy_pokazywac boolean NOT NULL,
        nazwa_dania text NOT NULL,
        id_kategorii_dania mediumint(9) NOT NULL,
        id_skladnikow text,
        id_skladnikow_do_dodania text,
        id_skladnikow_do_usuniecia text,
        czy_rozmiary boolean NOT NULL,
        rozmiar_1_nazwa text,
        rozmiar_1_cena decimal(10,2),
        rozmiar_2_nazwa text,
        rozmiar_2_cena decimal(10,2),
        rozmiar_3_nazwa text,
        rozmiar_3_cena decimal(10,2),
        czy_wybor_12 boolean NOT NULL,
        wybor_1_nazwa text,
        wybor_1_cena decimal(10,2),
        wybor_2_nazwa text,
        wybor_2_cena decimal(10,2),
        czy_wybor_34 boolean NOT NULL,
        wybor_3_nazwa text,
        wybor_3_cena decimal(10,2),
        wybor_4_nazwa text,
        wybor_4_cena decimal(10,2),
        czy_wybor_56 boolean NOT NULL,
        wybor_5_nazwa text,
        wybor_5_cena decimal(10,2),
        wybor_6_nazwa text,
        wybor_6_cena decimal(10,2),
        czy_wegetarianskie boolean NOT NULL,
        czy_weganskie boolean NOT NULL,
        czy_ostre boolean NOT NULL,
        czy_promocja boolean NOT NULL,
        czy_oferta_limitowana boolean NOT NULL,
        czy_zestaw boolean NOT NULL,
        czy_bestseller boolean NOT NULL,
        czy_nowe boolean NOT NULL,
        opis_dania text,
        alergeny text,
        danie_cena decimal(10,2),
        zdjecie_1 text,
        zdjecie_2 text,
        zdjecie_3 text,
        PRIMARY KEY  (id_dania)
    ) $charset_collate;";
    dbDelta( $sql_menu );

    // Tabela składników
    $table_skladniki = $wpdb->prefix . 'qelner_skladniki';
    $sql_skladniki = "CREATE TABLE $table_skladniki (
        id_skladnika mediumint(9) NOT NULL AUTO_INCREMENT,
        nazwa_skladnika text NOT NULL,
        id_kategorii_skladnika mediumint(9) NOT NULL,
        max_ilosc smallint(5) NOT NULL,
        skladnik_cena decimal(10,2),
        skladnik_zdjecie text,
        PRIMARY KEY  (id_skladnika)
    ) $charset_collate;";
    dbDelta( $sql_skladniki );

    // Tabela koszyków
    $table_koszyki = $wpdb->prefix . 'qelner_koszyki';
    $sql_koszyki = "CREATE TABLE $table_koszyki (
        id_koszyka mediumint(9) NOT NULL AUTO_INCREMENT,
        id_stolika mediumint(9) NOT NULL,
        czas_utworzenia datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        ip text NOT NULL,
        PRIMARY KEY  (id_koszyka)
    ) $charset_collate;";
    dbDelta( $sql_koszyki );

    // Tabela elementów koszyka
    $table_elementy_koszyka = $wpdb->prefix . 'qelner_elementy_koszyka';
    $sql_elementy_koszyka = "CREATE TABLE $table_elementy_koszyka (
        id_elementu_koszyka mediumint(9) NOT NULL AUTO_INCREMENT,
        id_koszyka mediumint(9) NOT NULL,
        id_dania mediumint(9) NOT NULL,
        rozmiar_nazwa text,
        rozmiar_cena decimal(10,2),
        wybor_12_nazwa text,
        wybor_12_cena decimal(10,2),
        wybor_34_nazwa text,
        wybor_34_cena decimal(10,2),
        wybor_56_nazwa text,
        wybor_56_cena decimal(10,2),
        id_skladnikow_do_dodania text,
        id_skladnikow_do_usuniecia text,
        ilosc smallint(5) NOT NULL,
        cena decimal(10,2),
        id_rachunku mediumint(9),
        PRIMARY KEY  (id_elementu_koszyka)
    ) $charset_collate;";
    dbDelta( $sql_elementy_koszyka );

    // Tabela stolików
    $table_stoliki = $wpdb->prefix . 'qelner_stoliki';
    $sql_stoliki = "CREATE TABLE $table_stoliki (
        id_stolika mediumint(9) NOT NULL AUTO_INCREMENT,
        numer_stolika smallint(5) NOT NULL,
        opis text,
        qr_code text,
        czy_pokazywac_stolik boolean NOT NULL DEFAULT 1,
        PRIMARY KEY  (id_stolika)
    ) $charset_collate;";
    dbDelta( $sql_stoliki );

    // Tabela kategorii
    $table_kategorie = $wpdb->prefix . 'qelner_kategorie';
    $sql_kategorie = "CREATE TABLE $table_kategorie (
        id_kategorii mediumint(9) NOT NULL AUTO_INCREMENT,
        nazwa_kategorii text NOT NULL,
        PRIMARY KEY  (id_kategorii)
    ) $charset_collate;";
    dbDelta( $sql_kategorie );
    
    // Wstawienie danych do tabeli wp_qelner_menu
$wpdb->insert($table_menu, array(
    'czy_pokazywac' => 1,
    'nazwa_dania' => 'Pizza Margarita',
    'id_kategorii_dania' => 1,
    'id_skladnikow' => '1',
    'id_skladnikow_do_dodania' => '1,2,3,4,5',
    'czy_rozmiary' => 1,
    'rozmiar_1_nazwa' => 'Mała (22 cm)',
    'rozmiar_1_cena' => 0.00,
    'rozmiar_2_nazwa' => 'Średnia (32 cm)',
    'rozmiar_2_cena' => 5.00,
    'rozmiar_3_nazwa' => 'Duża (42 cm)',
    'rozmiar_3_cena' => 12.00,
    'czy_wybor_12' => 1,
    'wybor_1_nazwa' => 'Ciasto cienkie',
    'wybor_1_cena' => 0.00,
    'wybor_2_nazwa' => 'Ciasto grube',
    'wybor_2_cena' => 3.00,
    'czy_wybor_34' => 1,
    'wybor_3_nazwa' => 'Zwykłe brzegi',
    'wybor_3_cena' => 0.00,
    'wybor_4_nazwa' => 'Ser w brzegach',
    'wybor_4_cena' => 5.00,
    'czy_wybor_56' => 0,
    'czy_wegetarianskie' => 1,
    'czy_weganskie' => 0,
    'czy_ostre' => 0,
    'czy_promocja' => 0,
    'czy_oferta_limitowana' => 0,
    'czy_zestaw' => 0,
    'czy_bestseller' => 1,
    'czy_nowe' => 1,
    'opis_dania' => 'Włoska pizza z serem',
    'alergeny' => 'mleko, ser',
    'danie_cena' => 22.90,
    'zdjecie_1' => 'qelner.pl/wp-content/plugins/qelner/img/pizzamargarita1.png',
    'zdjecie_2' => 'qelner.pl/wp-content/plugins/qelner/img/pizzamargarita2.png',
    'zdjecie_3' => 'qelner.pl/wp-content/plugins/qelner/img/pizzamargarita3.png',
));

$wpdb->insert($table_menu, array(
    'czy_pokazywac' => 1,
    'nazwa_dania' => 'Pizza Capriciosa',
    'id_kategorii_dania' => 1,
    'id_skladnikow' => '1,2,3',
    'id_skladnikow_do_dodania' => '1,2,3,4,5',
    'id_skladnikow_do_usuniecia' => '2,3',
    'czy_rozmiary' => 1,
    'rozmiar_1_nazwa' => 'Mała (22 cm)',
    'rozmiar_1_cena' => 0.00,
    'rozmiar_2_nazwa' => 'Średnia (32 cm)',
    'rozmiar_2_cena' => 5.00,
    'rozmiar_3_nazwa' => 'Duża (42 cm)',
    'rozmiar_3_cena' => 12.00,
    'czy_wybor_12' => 1,
    'wybor_1_nazwa' => 'Ciasto cienkie',
    'wybor_1_cena' => 0.00,
    'wybor_2_nazwa' => 'Ciasto grube',
    'wybor_2_cena' => 3.00,
    'czy_wybor_34' => 1,
    'wybor_3_nazwa' => 'Zwykłe brzegi',
    'wybor_3_cena' => 0.00,
    'wybor_4_nazwa' => 'Ser w brzegach',
    'wybor_4_cena' => 5.00,
    'czy_wybor_56' => 0,
    'czy_wegetarianskie' => 0,
    'czy_weganskie' => 0,
    'czy_ostre' => 0,
    'czy_promocja' => 0,
    'czy_oferta_limitowana' => 0,
    'czy_zestaw' => 0,
    'czy_bestseller' => 1,
    'czy_nowe' => 1,
    'opis_dania' => 'Włoska pizza z serem, szynką i pieczarkami',
    'alergeny' => 'mleko, ser, pieczarki',
    'danie_cena' => 27.90,
    'zdjecie_1' => 'qelner.pl/wp-content/plugins/qelner/img/pizzacapriciosa1.png',
    'zdjecie_2' => 'qelner.pl/wp-content/plugins/qelner/img/pizzacapriciosa2.png',
    'zdjecie_3' => 'qelner.pl/wp-content/plugins/qelner/img/pizzacapriciosa3.png',
));

$wpdb->insert($table_menu, array(
    'czy_pokazywac' => 1,
    'nazwa_dania' => 'Pizza Salami',
    'id_kategorii_dania' => 1,
    'id_skladnikow' => '1,4',
    'id_skladnikow_do_dodania' => '1,2,3,4,5',
    'id_skladnikow_do_usuniecia' => '4',
    'czy_rozmiary' => 1,
    'rozmiar_1_nazwa' => 'Mała (22 cm)',
    'rozmiar_1_cena' => 0.00,
    'rozmiar_2_nazwa' => 'Średnia (32 cm)',
    'rozmiar_2_cena' => 5.00,
    'rozmiar_3_nazwa' => 'Duża (42 cm)',
    'rozmiar_3_cena' => 12.00,
    'czy_wybor_12' => 1,
    'wybor_1_nazwa' => 'Ciasto cienkie',
    'wybor_1_cena' => 0.00,
    'wybor_2_nazwa' => 'Ciasto grube',
    'wybor_2_cena' => 3.00,
    'czy_wybor_34' => 1,
    'wybor_3_nazwa' => 'Zwykłe brzegi',
    'wybor_3_cena' => 0.00,
    'wybor_4_nazwa' => 'Ser w brzegach',
    'wybor_4_cena' => 5.00,
    'czy_wybor_56' => 0,
    'czy_wegetarianskie' => 0,
    'czy_weganskie' => 0,
    'czy_ostre' => 1,
    'czy_promocja' => 0,
    'czy_oferta_limitowana' => 0,
    'czy_zestaw' => 0,
    'czy_bestseller' => 1,
    'czy_nowe' => 1,
    'opis_dania' => 'Włoska pizza z serem i salami',
    'alergeny' => 'mleko, ser',
    'danie_cena' => 25.90,
    'zdjecie_1' => 'qelner.pl/wp-content/plugins/qelner/img/pizzasalami1.png',
    'zdjecie_2' => 'qelner.pl/wp-content/plugins/qelner/img/pizzasalami2.png',
    'zdjecie_3' => 'qelner.pl/wp-content/plugins/qelner/img/pizzasalami3.png',
));

// Wstawienie danych do tabeli wp_qelner_skladniki
$skladniki = [
    ['Ser', 1, 2, 3.00, 'qelner.pl/wp-content/plugins/qelner/img/sericon.png'],
    ['Szynka', 1, 2, 5.00, 'qelner.pl/wp-content/plugins/qelner/img/szynkaicon.png'],
    ['Pieczarki', 1, 2, 3.00, 'qelner.pl/wp-content/plugins/qelner/img/pieczarkiicon.png'],
    ['Salami', 1, 2, 5.00, 'qelner.pl/wp-content/plugins/qelner/img/salamiicon.png'],
    ['Ananas', 1, 2, 6.00, 'qelner.pl/wp-content/plugins/qelner/img/ananasicon.png']
];

foreach ($skladniki as $skladnik) {
    list($nazwa, $kategoria, $max_ilosc, $cena, $zdjecie) = $skladnik;
    $wpdb->insert($table_skladniki, array(
        'nazwa_skladnika' => $nazwa,
        'id_kategorii_skladnika' => $kategoria,
        'max_ilosc' => $max_ilosc,
        'skladnik_cena' => $cena,
        'skladnik_zdjecie' => $zdjecie
    ));
}

// Wstawienie danych do tabeli wp_qelner_kategorie
$kategorie = [
    ['Pizza'],
    ['Makarony'],
    ['Dodatki'],
    ['Napoje']
];

foreach ($kategorie as $kategoria) {
    $wpdb->insert($table_kategorie, array(
        'nazwa_kategorii' => $kategoria[0]
    ));
}

// Wstawienie danych do tabeli stolików
$stoliki = [
    ['numer_stolika' => 1, 'opis' => 'Przy oknie', 'qr_code' => 'url_do_kodu_qr_1', 'czy_pokazywac_stolik' => 1],
    ['numer_stolika' => 2, 'opis' => 'W rogu', 'qr_code' => 'url_do_kodu_qr_2', 'czy_pokazywac_stolik' => 1],
    ['numer_stolika' => 3, 'opis' => 'Przy barze', 'qr_code' => 'url_do_kodu_qr_3', 'czy_pokazywac_stolik' => 1],
    ['numer_stolika' => 4, 'opis' => 'Na środku', 'qr_code' => 'url_do_kodu_qr_4', 'czy_pokazywac_stolik' => 1],
    ['numer_stolika' => 5, 'opis' => 'Przy wejściu', 'qr_code' => 'url_do_kodu_qr_5', 'czy_pokazywac_stolik' => 1],
];

foreach ($stoliki as $stolik) {
    $wpdb->insert(
        $table_stoliki, // Nazwa tabeli stolików
        array(
            'numer_stolika' => $stolik['numer_stolika'],
            'opis' => $stolik['opis'],
            'qr_code' => $stolik['qr_code'],
            'czy_pokazywac_stolik' => $stolik['czy_pokazywac_stolik']
        )
    );
}


    
}
