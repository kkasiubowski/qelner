<?php
/**
 * Plugin Name: Qelner - Rezerwacja stolików i menu restauracji
 * Plugin URI: http://qelner.pl
 * Description: Wtyczka do rezerwacji stolików i przeglądania menu restauracji z opcją zamówienia.
 * Version: 1.0
 * Author: Twój Nick
 * Author URI: http://qelner.pl
 */

// Zabezpieczenie przed bezpośrednim dostępem
if (!defined('WPINC')) {
    die;
}

// Rejestracja i ładowanie skryptów
function qelner_enqueue_scripts() {
    
    wp_enqueue_style('qelner-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0', 'all');
    wp_enqueue_script('qelner-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), null, true);
    // Przekazanie zmiennej z URL do obsługi AJAX w WordPressie do skryptu
    wp_localize_script('qelner-script', 'myAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
}

add_action('wp_enqueue_scripts', 'qelner_enqueue_scripts');

// Rejestracja shortcodów
function qelner_register_shortcodes() {
    add_shortcode('qelner_select_table', 'qelner_select_table_shortcode');
    add_shortcode('qelner_menu', 'qelner_menu_shortcode');
}

add_action('init', 'qelner_register_shortcodes');

// Funkcje obsługujące shortcody
function qelner_select_table_shortcode($atts) {
    ob_start();
    include 'select-table.php';
    return ob_get_clean();
}

function qelner_menu_shortcode($atts) {
    ob_start();
    include 'menu-page.php';
    return ob_get_clean();
}

// Instalacja wtyczki (tworzenie tabel w bazie danych)
function qelner_install() {
    require_once plugin_dir_path(__FILE__) . 'install.php';
    qelner_install_db();
}

register_activation_hook(__FILE__, 'qelner_install');

function przetworzSkladniki($idSkladnikow, $table_skladniki, $asArray = false) {
    global $wpdb;
    $results = [];
    $idSkladnikowArray = explode(',', $idSkladnikow);
    foreach ($idSkladnikowArray as $id) {
        $id = trim($id); // Usuwanie ewentualnych spacji
        // Pobieranie nazwy i ceny składnika
        $skladnik = $wpdb->get_row($wpdb->prepare("SELECT nazwa_skladnika, skladnik_cena FROM $table_skladniki WHERE id_skladnika = %d", $id));
        if (!empty($skladnik)) {
            if ($asArray) {
                // Zwraca tablicę ID => ['nazwa' => nazwa, 'cena' => cena], jeśli $asArray jest prawdziwe
                $results[$id] = ['nazwa' => $skladnik->nazwa_skladnika, 'cena' => $skladnik->skladnik_cena];
            } else {
                // Zwraca ciąg nazw składników oddzielonych przecinkami, jeśli $asArray jest fałszywe
                $results[] = $skladnik->nazwa_skladnika;
            }
        }
    }

    if ($asArray) {
        return $results;
    } else {
        return implode(', ', $results);
    }
}



function qelner_pokaz_elementy_koszyka_z_rachunkami_ajax() {
    global $wpdb;
    $id_stolika = isset($_POST['id_stolika']) ? intval($_POST['id_stolika']) : 0;

    $elementy_koszyka = $wpdb->get_results($wpdb->prepare(
        "SELECT ek.*, m.nazwa_dania FROM {$wpdb->prefix}qelner_elementy_koszyka ek
         LEFT JOIN {$wpdb->prefix}qelner_menu m ON ek.id_dania = m.id_dania
         WHERE ek.id_stolika = %d", 
        $id_stolika
    ));

    foreach ($elementy_koszyka as &$element) {
        $element->skladniki_do_dodania_nazwy = przetworzSkladnikiNaNazwy($element->skladniki_do_dodania, $wpdb);
        $element->skladniki_do_usuniecia_nazwy = przetworzSkladnikiNaNazwy($element->skladniki_do_usuniecia, $wpdb);
    }

    $dostepne_rachunki = $wpdb->get_results("SELECT id_rachunku, imie, metoda_platnosci FROM {$wpdb->prefix}qelner_rachunek_dzielony");

    wp_send_json_success(['elementy_koszyka' => $elementy_koszyka, 'dostepne_rachunki' => $dostepne_rachunki]);
}

function przetworzSkladnikiNaNazwy($skladniki, $wpdb) {
    if (empty($skladniki)) {
        return [];
    }
    $ids = explode(',', $skladniki);
    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $sql = "SELECT nazwa_skladnika FROM {$wpdb->prefix}qelner_skladniki WHERE id_skladnika IN ($placeholders)";
    $nazwySkladnikow = $wpdb->get_col($wpdb->prepare($sql, $ids));
    return $nazwySkladnikow;
}

add_action('wp_ajax_qelner_pokaz_elementy_koszyka_z_rachunkami', 'qelner_pokaz_elementy_koszyka_z_rachunkami_ajax');
add_action('wp_ajax_nopriv_qelner_pokaz_elementy_koszyka_z_rachunkami', 'qelner_pokaz_elementy_koszyka_z_rachunkami_ajax');


function zapisz_rachunek_indywidualny_ajax() {
    global $wpdb;
    $idStolika = isset($_POST['id_stolika']) ? intval($_POST['id_stolika']) : 0;
    $napiwek = isset($_POST['napiwek']) ? $_POST['napiwek'] : '0';
    $metodaPlatnosci = isset($_POST['metoda_platnosci']) ? $_POST['metoda_platnosci'] : '';

    // Wstawienie nowego rachunku do tabeli rachunków dzielonych
    $wpdb->insert(
        "{$wpdb->prefix}qelner_rachunek_dzielony",
        array(
            'id_stolika' => $idStolika,
            'napiwek' => $napiwek,
            'metoda_platnosci' => $metodaPlatnosci
        ),
        array('%d', '%s', '%s')
    );

    // Sprawdzenie, czy wstawienie się powiodło
    if($wpdb->insert_id) {
        // Pobranie ID nowo utworzonego rachunku
        $idRachunku = $wpdb->insert_id;

        // Aktualizacja elementów koszyka, dodanie ID rachunku do odpowiednich elementów
        $aktualizacja = $wpdb->update(
            "{$wpdb->prefix}qelner_elementy_koszyka",
            array('id_rachunku' => $idRachunku),
            array('id_stolika' => $idStolika),
            array('%d'), // format wartości
            array('%d')  // format warunku WHERE
        );

        // Sprawdzenie, czy aktualizacja się powiodła
        if($aktualizacja !== false) {
            wp_send_json_success('Rachunek indywidualny zapisany i przypisany do elementów koszyka.');
        } else {
            wp_send_json_error('Wystąpił błąd podczas aktualizacji elementów koszyka.');
        }
    } else {
        wp_send_json_error('Wystąpił błąd podczas zapisywania rachunku indywidualnego.');
    }

    wp_die(); // Zawsze wywołuj wp_die() na końcu funkcji obsługującej AJAX, aby zakończyć działanie skryptu
}

add_action('wp_ajax_zapisz_rachunek_indywidualny', 'zapisz_rachunek_indywidualny_ajax');
add_action('wp_ajax_nopriv_zapisz_rachunek_indywidualny', 'zapisz_rachunek_indywidualny_ajax');



function qelner_zapisz_wybor_rachunku_ajax() {
    global $wpdb;
    // Sprawdzenie bezpieczeństwa, np. nonce, zostało pominięte dla uproszczenia
    $idStolika = isset($_POST['id_stolika']) ? intval($_POST['id_stolika']) : 0;
    $wyboryRachunkow = isset($_POST['wybory_rachunkow']) ? $_POST['wybory_rachunkow'] : array();

    foreach ($wyboryRachunkow as $idElementuKoszyka => $idRachunku) {
        $aktualizacja = $wpdb->update(
            "{$wpdb->prefix}qelner_elementy_koszyka",
            array('id_rachunku' => intval($idRachunku)),
            array('id_elementu_koszyka' => intval($idElementuKoszyka)),
            array('%d'),
            array('%d')
        );

        if (false === $aktualizacja) {
            wp_send_json_error('Wystąpił błąd podczas aktualizacji.');
            wp_die();
        }
    }

    wp_send_json_success('Rachunki zostały zaktualizowane.');
    wp_die();
}

add_action('wp_ajax_qelner_zapisz_wybor_rachunku', 'qelner_zapisz_wybor_rachunku_ajax');
add_action('wp_ajax_nopriv_qelner_zapisz_wybor_rachunku', 'qelner_zapisz_wybor_rachunku_ajax');

function qelner_zloz_zamowienie_ajax() {
    global $wpdb;
    $idStolika = isset($_POST['id_stolika']) ? intval($_POST['id_stolika']) : 0;

    // Pobieranie danych z tabeli wp_qelner_elementy_koszyka
    $elementyKoszyka = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}qelner_elementy_koszyka WHERE id_stolika = %d", $idStolika), ARRAY_A);

    // Przygotowanie tablicy do przechowywania unikalnych ID rachunków
    $idRachunkow = [];

    foreach ($elementyKoszyka as $element) {
        // Dodajemy ID rachunku do tablicy, jeśli już nie istnieje
        if (!in_array($element['id_rachunku'], $idRachunkow)) {
            $idRachunkow[] = $element['id_rachunku'];
        }

        // Pobranie danych rachunku
        $rachunek = $wpdb->get_row($wpdb->prepare("SELECT imie, metoda_platnosci, napiwek FROM {$wpdb->prefix}qelner_rachunek_dzielony WHERE id_rachunku = %d", $element['id_rachunku']), ARRAY_A);

        $daneDoWstawienia = [
            'id_elementu_koszyka' => $element['id_elementu_koszyka'],
            'id_stolika' => $element['id_stolika'],
            'id_dania' => $element['id_dania'],
            'rozmiar_nazwa' => $element['rozmiar_nazwa'],
            'wybor_12_nazwa' => $element['wybor_12_nazwa'],
            'wybor_34_nazwa' => $element['wybor_34_nazwa'],
            'wybor_56_nazwa' => $element['wybor_56_nazwa'],
            'skladniki_do_dodania' => $element['skladniki_do_dodania'],
            'skladniki_do_usuniecia' => $element['skladniki_do_usuniecia'],
            'ilosc' => $element['ilosc'],
            'cena' => $element['cena'],
            'id_rachunku' => $element['id_rachunku'],
            'imie' => $rachunek['imie'],
            'metoda_platnosci' => $rachunek['metoda_platnosci'],
            'napiwek' => $rachunek['napiwek'],
            'czas_zlozenia_zamowienia' => current_time('mysql', 1),
            'ip_zamawiajacego' => $_SERVER['REMOTE_ADDR'],

        ];

        // Zapisywanie do tabeli wp_qelner_zamowienia
        $wpdb->insert("{$wpdb->prefix}qelner_zamowienia", $daneDoWstawienia);
    }

$wpdb->delete("{$wpdb->prefix}qelner_elementy_koszyka", ['id_stolika' => $idStolika]);

    // Usuwanie rekordów z wp_qelner_rachunek_dzielony dla każdego id_rachunku
    foreach ($idRachunkow as $idRachunku) {
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}qelner_rachunek_dzielony WHERE id_rachunku = %d AND EXISTS (SELECT 1 FROM {$wpdb->prefix}qelner_elementy_koszyka WHERE id_stolika = %d AND id_rachunku = {$wpdb->prefix}qelner_rachunek_dzielony.id_rachunku)", $idRachunku, $idStolika));
    }    
    
    wp_send_json_success('Zamówienie zostało złożone.');
    wp_die(); // Zawsze kończ funkcję obsługi AJAX wp_die()
}
add_action('wp_ajax_qelner_zloz_zamowienie', 'qelner_zloz_zamowienie_ajax');
add_action('wp_ajax_nopriv_qelner_zloz_zamowienie', 'qelner_zloz_zamowienie_ajax');


function qelner_kelner_panel_shortcode() {
    // Dodajemy kontener, w którym będą dynamicznie ładowane zamówienia
    return '<div id="qelner-kelner-panel">Ładowanie zamówień...</div>';
}
add_shortcode('qelner_kelner_panel', 'qelner_kelner_panel_shortcode');

function qelner_pobierz_zamowienia_ajax() {
    global $wpdb;
    $zamowienia = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}qelner_zamowienia WHERE status = 'Niepotwierdzone' ORDER BY czas_zlozenia_zamowienia DESC");

    foreach ($zamowienia as $zamowienie) {
        // Pobieranie nazwy dania
        $zamowienie->nazwa_dania = $wpdb->get_var($wpdb->prepare(
            "SELECT nazwa_dania FROM wp_qelner_menu WHERE id_dania = %d",
            $zamowienie->id_dania
        ));

        // Pobieranie i przetwarzanie składników do dodania
        if (!empty($zamowienie->skladniki_do_dodania)) {
            $idsDodania = explode(',', $zamowienie->skladniki_do_dodania);
            $nazwyDodania = $wpdb->get_col("SELECT nazwa_skladnika FROM wp_qelner_skladniki WHERE id_skladnika IN (" . implode(',', array_map('intval', $idsDodania)) . ")");
            $zamowienie->skladniki_do_dodania_nazwy = implode(', ', $nazwyDodania);
        } else {
            $zamowienie->skladniki_do_dodania_nazwy = '';
        }

        // Pobieranie i przetwarzanie składników do usunięcia
        if (!empty($zamowienie->skladniki_do_usuniecia)) {
            $idsUsuniecia = explode(',', $zamowienie->skladniki_do_usuniecia);
            $nazwyUsuniecia = $wpdb->get_col("SELECT nazwa_skladnika FROM wp_qelner_skladniki WHERE id_skladnika IN (" . implode(',', array_map('intval', $idsUsuniecia)) . ")");
            $zamowienie->skladniki_do_usuniecia_nazwy = implode(', ', $nazwyUsuniecia);
        } else {
            $zamowienie->skladniki_do_usuniecia_nazwy = '';
        }
    }

    wp_send_json_success($zamowienia);
}
add_action('wp_ajax_qelner_pobierz_zamowienia', 'qelner_pobierz_zamowienia_ajax');
add_action('wp_ajax_nopriv_qelner_pobierz_zamowienia', 'qelner_pobierz_zamowienia_ajax');





function qelner_pokaz_propozycje_dan_ajax() {
    global $wpdb;
    $id_stolika = isset($_POST['id_stolika']) ? intval($_POST['id_stolika']) : 0;

    // Pobranie ID dań, które są już w koszyku
    $dania_w_koszyku = $wpdb->get_col($wpdb->prepare(
        "SELECT id_dania FROM {$wpdb->prefix}qelner_elementy_koszyka WHERE id_stolika = %d",
        $id_stolika
    ));

    // Pobranie propozycji dań z wyłączeniem tych, które są już w koszyku
    $propozycje_dan = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}qelner_menu WHERE id_dania NOT IN (" . implode(',', array_map('intval', $dania_w_koszyku)) . " OR 0) AND id_kategorii_dania IN (3, 4, 5) AND czy_pokazywac = 1 ORDER BY RAND() LIMIT 3"
));


    wp_send_json_success($propozycje_dan);
}

add_action('wp_ajax_qelner_pokaz_propozycje_dan', 'qelner_pokaz_propozycje_dan_ajax');
add_action('wp_ajax_nopriv_qelner_pokaz_propozycje_dan', 'qelner_pokaz_propozycje_dan_ajax');


function usunZKoszyka_ajax() {
    global $wpdb;
    $id_elementu_koszyka = isset($_POST['id_elementu_koszyka']) ? intval($_POST['id_elementu_koszyka']) : 0;

    if ($id_elementu_koszyka) {
        $tabela_koszyka = $wpdb->prefix . 'qelner_elementy_koszyka';
        $result = $wpdb->delete($tabela_koszyka, array('id_elementu_koszyka' => $id_elementu_koszyka), array('%d'));
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    } else {
        wp_send_json_error();
    }

    wp_die(); // Zakończenie działania AJAX
}

add_action('wp_ajax_usunZKoszyka_ajax', 'usunZKoszyka_ajax');
add_action('wp_ajax_nopriv_usunZKoszyka_ajax', 'usunZKoszyka_ajax');


function qelner_pokaz_koszyk_ajax() {
    global $wpdb;
    $id_stolika = isset($_POST['id_stolika']) ? intval($_POST['id_stolika']) : 0;

    $tabela_koszyka = $wpdb->prefix . 'qelner_elementy_koszyka';
    $elementy_koszyka = $wpdb->get_results($wpdb->prepare("SELECT * FROM $tabela_koszyka WHERE id_stolika = %d", $id_stolika));

    wp_send_json_success($elementy_koszyka);
}

add_action('wp_ajax_pokaz_koszyk', 'qelner_pokaz_koszyk_ajax');
add_action('wp_ajax_nopriv_pokaz_koszyk', 'qelner_pokaz_koszyk_ajax');

function qelner_pokaz_koszyk_ajax_z_nazwami() {
    global $wpdb;
    $id_stolika = isset($_POST['id_stolika']) ? intval($_POST['id_stolika']) : 0;
    $tabela_koszyka = $wpdb->prefix . 'qelner_elementy_koszyka';
    $tabela_menu = $wpdb->prefix . 'qelner_menu';
    $tabela_skladniki = $wpdb->prefix . 'qelner_skladniki';

    $elementy_koszyka = $wpdb->get_results($wpdb->prepare("SELECT ek.*, m.nazwa_dania FROM $tabela_koszyka ek LEFT JOIN $tabela_menu m ON ek.id_dania = m.id_dania WHERE ek.id_stolika = %d", $id_stolika));

    foreach ($elementy_koszyka as $element) {
        if (!empty($element->skladniki_do_dodania)) {
            $skladnikiIds = explode(',', $element->skladniki_do_dodania);
            $nazwySkladnikow = [];
            foreach ($skladnikiIds as $id) {
                $nazwa = $wpdb->get_var($wpdb->prepare("SELECT nazwa_skladnika FROM $tabela_skladniki WHERE id_skladnika = %d", $id));
                if (!empty($nazwa)) {
                    $nazwySkladnikow[] = $nazwa;
                }
            }
            $element->skladniki_do_dodania = implode(', ', $nazwySkladnikow);
        }
        if (!empty($element->skladniki_do_usuniecia)) {
            $skladnikiIds = explode(',', $element->skladniki_do_usuniecia);
            $nazwySkladnikow = [];
            foreach ($skladnikiIds as $id) {
                $nazwa = $wpdb->get_var($wpdb->prepare("SELECT nazwa_skladnika FROM $tabela_skladniki WHERE id_skladnika = %d", $id));
                if (!empty($nazwa)) {
                    $nazwySkladnikow[] = $nazwa;
                }
            }
            $element->skladniki_do_usuniecia = implode(', ', $nazwySkladnikow);
        }
    }

    wp_send_json_success($elementy_koszyka);
}

add_action('wp_ajax_pokaz_koszyk_z_nazwami', 'qelner_pokaz_koszyk_ajax_z_nazwami');
add_action('wp_ajax_nopriv_pokaz_koszyk_z_nazwami', 'qelner_pokaz_koszyk_ajax_z_nazwami');




add_action('wp_ajax_zapisz_rachunek_dzielony', 'zapiszRachunekDzielonyAjax');
add_action('wp_ajax_nopriv_zapisz_rachunek_dzielony', 'zapiszRachunekDzielonyAjax');

function zapiszRachunekDzielonyAjax() {
    global $wpdb;
    $idStolika = $_POST['id_stolika'];
    $imiona = $_POST['imiona'];
    $metodyPlatnosci = $_POST['metodyPlatnosci'];
    $napiwki = $_POST['napiwki'];

    foreach($imiona as $index => $imie) {
        $metodaPlatnosci = $metodyPlatnosci[$index];
        $napiwek = $napiwki[$index];
        // Tutaj zapis do bazy danych
        $wpdb->insert(
            'wp_qelner_rachunek_dzielony',
            array(
                'id_stolika' => $idStolika,
                'imie' => $imie,
                'metoda_platnosci' => $metodaPlatnosci,
                'napiwek' => $napiwek
            ),
            
        );
    }

    wp_send_json_success('Rachunek zapisany.');
    wp_die();
}










function aktualizuj_ilosc_ajax() {
    global $wpdb;
    $id_elementu_koszyka = isset($_POST['id_elementu_koszyka']) ? intval($_POST['id_elementu_koszyka']) : 0;
    $nowaIlosc = isset($_POST['ilosc']) ? intval($_POST['ilosc']) : 0;

    if($id_elementu_koszyka > 0 && $nowaIlosc > 0) {
        $tabela_koszyka = $wpdb->prefix . 'qelner_elementy_koszyka';
        $wpdb->update(
            $tabela_koszyka,
            ['ilosc' => $nowaIlosc],
            ['id_elementu_koszyka' => $id_elementu_koszyka],
            ['%d'],
            ['%d']
        );

        wp_send_json_success(['message' => 'Ilość zaktualizowana']);
    } else {
        wp_send_json_error(['message' => 'Nieprawidłowe dane']);
    }

    wp_die();
}

add_action('wp_ajax_aktualizuj_ilosc_ajax', 'aktualizuj_ilosc_ajax');
add_action('wp_ajax_nopriv_aktualizuj_ilosc_ajax', 'aktualizuj_ilosc_ajax');

function qelner_load_dishes_ajax() {
    global $wpdb;
    $id_kategorii = isset($_POST['id_kategorii']) ? intval($_POST['id_kategorii']) : 0;

    $dania = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}qelner_menu WHERE id_kategorii_dania = %d AND czy_pokazywac = 1", $id_kategorii));

    wp_send_json_success($dania);
}

add_action('wp_ajax_load_dishes', 'qelner_load_dishes_ajax');
add_action('wp_ajax_nopriv_load_dishes', 'qelner_load_dishes_ajax');



function dodajDoKoszyka_ajax() {
    global $wpdb;

    // Odbieranie danych przesłanych metodą POST
    $idStolika = isset($_POST['id_stolika']) ? intval($_POST['id_stolika']) : 0;
    $idDania = isset($_POST['id_dania']) ? intval($_POST['id_dania']) : 0;
    $rozmiarNazwa = isset($_POST['rozmiar']) ? sanitize_text_field($_POST['rozmiar']) : '';
    $rozmiarCena = isset($_POST['rozmiar_cena']) ? floatval($_POST['rozmiar_cena']) : 0;
    $wybor12Nazwa = isset($_POST['wybor_12']) ? sanitize_text_field($_POST['wybor_12']) : '';
    $wybor12Cena = isset($_POST['wybor_12_cena']) ? floatval($_POST['wybor_12_cena']) : 0;
    $wybor34Nazwa = isset($_POST['wybor_34']) ? sanitize_text_field($_POST['wybor_34']) : '';
    $wybor34Cena = isset($_POST['wybor_34_cena']) ? floatval($_POST['wybor_34_cena']) : 0;
    $wybor56Nazwa = isset($_POST['wybor_56']) ? sanitize_text_field($_POST['wybor_56']) : '';
    $wybor56Cena = isset($_POST['wybor_56_cena']) ? floatval($_POST['wybor_56_cena']) : 0;
  $skladnikiDoDodania = isset($_POST['skladniki_do_dodania']) ? json_decode(stripslashes($_POST['skladniki_do_dodania']), true) : array();
$skladnikiDoUsuniecia = isset($_POST['skladniki_do_usuniecia']) ? json_decode(stripslashes($_POST['skladniki_do_usuniecia']), true) : array();
$skladnikiDoDodaniaStr = implode(',', $skladnikiDoDodania);
$skladnikiDoUsunieciaStr = implode(',', $skladnikiDoUsuniecia);



    $ilosc = isset($_POST['ilosc']) ? intval($_POST['ilosc']) : 1;
    $cena = isset($_POST['cena_koncowa']) ? floatval($_POST['cena_koncowa']) : 0;
    // ID rachunku można pominąć lub ustawić na wartość domyślnąany w tym kontekście
    $idRachunku = null; 

    // Wstawianie danych do tabeli wp_qelner_elementy_koszyka
    $result = $wpdb->insert(
        $wpdb->prefix . 'qelner_elementy_koszyka',
        array(
            'id_stolika' => $idStolika,
            'id_dania' => $idDania,
            'rozmiar_nazwa' => $rozmiarNazwa,
            'rozmiar_cena' => $rozmiarCena,
            'wybor_12_nazwa' => $wybor12Nazwa,
            'wybor_12_cena' => $wybor12Cena,
            'wybor_34_nazwa' => $wybor34Nazwa,
            'wybor_34_cena' => $wybor34Cena,
            'wybor_56_nazwa' => $wybor56Nazwa,
            'wybor_56_cena' => $wybor56Cena,
            'skladniki_do_dodania' => $skladnikiDoDodaniaStr,
        'skladniki_do_usuniecia' => $skladnikiDoUsunieciaStr,
            'ilosc' => $ilosc,
            'cena' => $cena,
            'id_rachunku' => $idRachunku
        ),
        array(
            '%d', '%d', '%s', '%f', '%s', '%f', '%s', '%f', '%s', '%f', '%s', '%s', '%d', '%f', '%d'
        )
    );

    if($result) {
        echo 'Dodano do koszyka';
    } else {
        echo 'Wystąpił błąd przy dodawaniu do koszyka';
    }

    wp_die(); // Zakończenie działania AJAX
}

add_action('wp_ajax_dodajDoKoszyka_ajax', 'dodajDoKoszyka_ajax');
add_action('wp_ajax_nopriv_dodajDoKoszyka_ajax', 'dodajDoKoszyka_ajax');





// Funkcja obsługująca zapytanie AJAX
function pokazSzczegolyDania_ajax() {
    global $wpdb;
    $id_dania = intval($_POST['id_dania']);
    $table_name = $wpdb->prefix . 'qelner_menu';
    $table_skladniki = $wpdb->prefix . 'qelner_skladniki';
    // Pobierz szczegóły dania z bazy danych
    $dish = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id_dania = %d", $id_dania));

if ($dish) {
    // Podstawowe informacje
    echo '<button onclick="zamknijSzczegolyDania()" style="float: right; font-size: 20px; line-height: 20px; cursor: pointer;">&times;</button>';
    echo '<h3><b>' . esc_html($dish->nazwa_dania) . '</b></h3>';
    echo '<p>Cena podstawowa: <b>' . esc_html($dish->danie_cena) . ' zł</b></p>';
    echo '<p>' . esc_html($dish->opis_dania) . '</p>';


    $skladniki = przetworzSkladniki($dish->id_skladnikow, $table_skladniki);
        if (!empty($skladniki)) {
            echo '<p><b>Składniki:</b> ' . $skladniki . '</p>';
        }

$skladnikiDoDodania = przetworzSkladniki($dish->id_skladnikow_do_dodania, $table_skladniki, true);
        if (!empty($skladnikiDoDodania)) {
            echo '<p><b>Składniki do dodania:</b></p>';
            foreach ($skladnikiDoDodania as $id => $skladnik) {
                echo '<div><label>';
                echo '<input type="checkbox" class="skladnik" name="skladnikiDoDodania[]" value="' . esc_attr($id) . '" data-cena="' . esc_attr($skladnik['cena']) . '"> ';
                echo esc_html($skladnik['nazwa']);
                echo '</label></div>';
            }
        }


// Generowanie checkboxów dla składników do dodania
  $skladnikiDoUsuniecia = przetworzSkladniki($dish->id_skladnikow_do_usuniecia, $table_skladniki, true);
        if (!empty($skladnikiDoUsuniecia)) {
            echo '<p><b>Składniki do usunięcia:</b></p>';
            foreach ($skladnikiDoUsuniecia as $id => $skladnik) {
                echo '<div><label>';
                echo '<input type="checkbox" class="skladnik" name="skladnikiDoUsuniecia[]" value="' . esc_attr($id) . '" data-cena="' . esc_attr($skladnik['cena']) . '"> ';
                echo esc_html($skladnik['nazwa']);
                echo '</label></div>';
            }
        }
        
      

       
    if ($dish->czy_rozmiary) {
    echo '<select name="rozmiar" id="rozmiarDania">';
    // Rozmiar 1
    if (!empty($dish->rozmiar_1_nazwa)) {
        echo '<option value="' . esc_attr($dish->rozmiar_1_nazwa) . '" data-cena="' . esc_attr($dish->rozmiar_1_cena) . '">' . esc_html($dish->rozmiar_1_nazwa) . ' - ' . esc_html($dish->rozmiar_1_cena) . ' zł</option>';
    }
    // Rozmiar 2
    if (!empty($dish->rozmiar_2_nazwa)) {
        echo '<option value="' . esc_attr($dish->rozmiar_2_nazwa) . '" data-cena="' . esc_attr($dish->rozmiar_2_cena) . '">' . esc_html($dish->rozmiar_2_nazwa) . ' - ' . esc_html($dish->rozmiar_2_cena) . ' zł</option>';
    }
    // Rozmiar 3
    if (!empty($dish->rozmiar_3_nazwa)) {
        echo '<option value="' . esc_attr($dish->rozmiar_3_nazwa) . '" data-cena="' . esc_attr($dish->rozmiar_3_cena) . '">' . esc_html($dish->rozmiar_3_nazwa) . ' - ' . esc_html($dish->rozmiar_3_cena) . ' zł</option>';
    }
    echo '</select>';
}

// Opcje wyboru
if ($dish->czy_wybor_12) {
    echo '<select name="wybor_12" id="wybor_12">';
    // Wybór 1
    if (!empty($dish->wybor_1_nazwa)) {
        echo '<option value="' . esc_attr($dish->wybor_1_nazwa) . '" data-cena="' . esc_attr($dish->wybor_1_cena) . '">' . esc_html($dish->wybor_1_nazwa) . ' - ' . esc_html($dish->wybor_1_cena) . ' zł</option>';
    }
    // Wybór 2
    if (!empty($dish->wybor_2_nazwa)) {
        echo '<option value="' . esc_attr($dish->wybor_2_nazwa) . '" data-cena="' . esc_attr($dish->wybor_2_cena) . '">' . esc_html($dish->wybor_2_nazwa) . ' - ' . esc_html($dish->wybor_2_cena) . ' zł</option>';
    }
    echo '</select>';
}

if ($dish->czy_wybor_34) {

    echo '<select name="wybor_34" id="wybor_34">';
    // Wybór 3
    if (!empty($dish->wybor_3_nazwa)) {
        echo '<option value="' . esc_attr($dish->wybor_3_nazwa) . '" data-cena="' . esc_attr($dish->wybor_3_cena) . '">' . esc_html($dish->wybor_3_nazwa) . ' - ' . esc_html($dish->wybor_3_cena) . ' zł</option>';
    }
    // Wybór 4
    if (!empty($dish->wybor_4_nazwa)) {
        echo '<option value="' . esc_attr($dish->wybor_4_nazwa) . '" data-cena="' . esc_attr($dish->wybor_4_cena) . '">' . esc_html($dish->wybor_4_nazwa) . ' - ' . esc_html($dish->wybor_4_cena) . ' zł</option>';
    }
    echo '</select>';
}

if ($dish->czy_wybor_56) {

    echo '<select name="wybor_56" id="wybor_56">';
    // Wybór 5
    if (!empty($dish->wybor_5_nazwa)) {
        echo '<option value="' . esc_attr($dish->wybor_5_nazwa) . '" data-cena="' . esc_attr($dish->wybor_5_cena) . '">' . esc_html($dish->wybor_5_nazwa) . ' - ' . esc_html($dish->wybor_5_cena) . ' zł</option>';
    }
    // Wybór 6
    if (!empty($dish->wybor_6_nazwa)) {
        echo '<option value="' . esc_attr($dish->wybor_6_nazwa) . '" data-cena="' . esc_attr($dish->wybor_6_cena) . '">' . esc_html($dish->wybor_6_nazwa) . ' - ' . esc_html($dish->wybor_6_cena) . ' zł</option>';
    }
    echo '</select>';
}



    

    // Dodatkowe opcje wyboru, jeśli istnieją
    // Powtórz powyższe dla wybor_34 i wybor_56, jeśli potrzebne
echo '<p>';
    // Atrybuty dania
   if ($dish->czy_wegetarianskie) {
    echo 'Wegetariańskie, ';
}
if ($dish->czy_weganskie) {
    echo 'Wegańskie, ';
}
if ($dish->czy_ostre) {
    echo 'Ostre, ';
}
if ($dish->czy_promocja) {
    echo 'Promocja, ';
}
if ($dish->czy_oferta_limitowana) {
    echo 'Oferta limitowana, ';
}
if ($dish->czy_zestaw) {
    echo 'Zestaw, ';
}
if ($dish->czy_bestseller) {
    echo 'Bestseller, ';
}
if ($dish->czy_nowe) {
    echo 'Nowość. ';
}
echo '</p>';

    // Alergeny
    if (!empty($dish->alergeny)) {
        echo '<p><b>Alergeny:</b> ' . esc_html($dish->alergeny) . '</p>';
    }

    // Zdjęcia
    echo '<div>';
     for ($i = 1; $i <= 3; $i++) {
            if (!empty($dish->{'zdjecie_' . $i})) {
                echo '<img src="' . esc_url($dish->{'zdjecie_' . $i}) . '" alt="Zdjęcie dania" style="max-width:100%; height:auto;">';
            }
        }
    echo '<p><b>Cena końcowa:</b> <span id="cenaKońcowa" data-cena-podstawowa="' . esc_attr($dish->danie_cena) . '">' . esc_html($dish->danie_cena) . '</span></p>';
    echo '<button class="dodaj-do-koszyka-button" data-id="' . esc_attr($id_dania) . '">Dodaj do koszyka</button>';

    echo '</div>';
} else {
    echo 'Szczegóły dania nie są dostępne.';
}


    wp_die(); // Zakończenie działania AJAX
}
// Rejestracja akcji AJAX dla zalogowanych i niezalogowanych użytkowników
add_action('wp_ajax_pokazSzczegolyDania', 'pokazSzczegolyDania_ajax');
add_action('wp_ajax_nopriv_pokazSzczegolyDania', 'pokazSzczegolyDania_ajax');