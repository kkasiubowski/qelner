<?php
// Sprawdzenie, czy plik jest ładowany w kontekście WordPressa
if (!defined('ABSPATH')) {
    exit; // Nie pozwala na bezpośrednie wywołanie pliku
}

global $wpdb;

// Pobieranie id stolika przekazanego metodą POST (powinno być zabezpieczone w praktycznym użyciu)
$id_stolika = isset($_POST['id_stolika']) ? intval($_POST['id_stolika']) : 0;

// Pobieranie danych menu z bazy danych
$dania = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}qelner_menu WHERE czy_pokazywac = 1");
$kategorie = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}qelner_kategorie");




// Pobieranie elementów koszyka dla danego stolika
$elementy_koszyka = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}qelner_elementy_koszyka WHERE id_stolika = %d", $id_stolika));
?>




<div class="qelner-menu">

<div id="qelner-koszyk-przycisk-container">
    <button id="przycisk-koszyk">Koszyk</button>
</div>
<div id="main-koszyk-container" style="display:none;">
<div id="koszyk-container"></div>
    <div id="total-price-container">Łączna cena: 0 zł</div>
    <button id="twoj-przycisk-dalej">Dalej</button>
</div>

    
    <div id="propozycje-dan-container">
        <button id="przejdz-do-rachunku" style="display:none;">Przejdź do rachunku</button>
    </div>

    <div id="dish-details-container" style="display:none;"></div>
    <!-- Tutaj będą wstawiane propozycje dań przez JavaScript -->
</div> 

    <h2>Menu</h2>
    <div id="categories-container">
    <?php foreach ($kategorie as $kategoria): ?>
        <button class="category-button" data-id="<?php echo $kategoria->id_kategorii; ?>">
            <?php echo esc_html($kategoria->nazwa_kategorii); ?>
        </button>
    <?php endforeach; ?>
</div>

<div id="dishes-container"></div>


</div>
