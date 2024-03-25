<?php
// Sprawdzenie, czy plik jest ładowany w kontekście WordPressa
if (!defined('ABSPATH')) {
    exit; // Nie pozwala na bezpośrednie wywołanie pliku
}

global $wpdb;

// Pobranie stolików z bazy danych
$stoliki = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}qelner_stoliki WHERE czy_pokazywac_stolik = 1");

// Sprawdzenie, czy formularz został przesłany
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_stolika'])) {
    // Przekierowanie do strony menu z id stolika
    $id_stolika = intval($_POST['id_stolika']);
    $menu_page_url = "https://www.qelner.pl/qelner?id_stolika=$id_stolika";

    echo "<script>window.location.href = '{$menu_page_url}';</script>";
    exit;
}

?>

<div class="qelner-select-table">
    <h2>Wybierz swój stolik:</h2>
    <form action="" method="POST">
        <?php foreach ($stoliki as $stolik): ?>
            <div class="qelner-table">
                <input type="radio" id="stolik<?php echo $stolik->id_stolika; ?>" name="id_stolika" value="<?php echo $stolik->id_stolika; ?>">
                <label for="stolik<?php echo $stolik->id_stolika; ?>">
                    Stolik nr <?php echo $stolik->numer_stolika; ?>
                    <?php if (!empty($stolik->opis)): ?>
                        - <?php echo $stolik->opis; ?>
                    <?php endif; ?>
                </label>
            </div>
        <?php endforeach; ?>
        <button type="submit">Przejdź do menu</button>
    </form>
</div>
