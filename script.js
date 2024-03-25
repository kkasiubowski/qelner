function zamknijSzczegolyDania() {
    jQuery('#dish-details-container').hide();
}

function zamknijOkno() {
    jQuery('#propozycje-dan-container').hide();
}
function ensureHttps(url) {
    if (!url.match(/^[a-zA-Z]+:\/\//)) {
        url = 'https://' + url;
    }
    return url;
}







jQuery(document).ready(function($) {

    function pobierzZamowienia() {
    $.ajax({
        url: myAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'qelner_pobierz_zamowienia',
        },
        success: function(response) {
            if(response.success) {
                var zamowieniaGrupowane = {};
                response.data.forEach(function(zamowienie) {
                    var kluczStolika = zamowienie.id_stolika + '|' + zamowienie.czas_zlozenia_zamowienia;
                    var kluczKlienta = zamowienie.imie + '|' + zamowienie.metoda_platnosci + '|' + zamowienie.napiwek;
                    
                    if (!zamowieniaGrupowane[kluczStolika]) {
                        zamowieniaGrupowane[kluczStolika] = {zamowienia: {}, cenaCalkowita: 0};
                    }
                    
                    if (!zamowieniaGrupowane[kluczStolika].zamowienia[kluczKlienta]) {
                        zamowieniaGrupowane[kluczStolika].zamowienia[kluczKlienta] = {zamowienia: [], cenaRachunku: 0};
                    }
                    
                    zamowieniaGrupowane[kluczStolika].cenaCalkowita += parseFloat(zamowienie.cena);
                    zamowieniaGrupowane[kluczStolika].zamowienia[kluczKlienta].zamowienia.push(zamowienie);
                    zamowieniaGrupowane[kluczStolika].zamowienia[kluczKlienta].cenaRachunku += parseFloat(zamowienie.cena);
                });

                var zamowieniaHTML = '';
                Object.keys(zamowieniaGrupowane).forEach(function(kluczStolika) {
                    var [idStolika, czasZlozenia] = kluczStolika.split('|');
                    zamowieniaHTML += '<div class="grupa-stolik">' +
                                        '<h2>Stolik nr: ' + idStolika + ' - ' + czasZlozenia + ' - Cena całkowita: ' + zamowieniaGrupowane[kluczStolika].cenaCalkowita.toFixed(2) + ' zł</h2>';
                    
                    Object.keys(zamowieniaGrupowane[kluczStolika].zamowienia).forEach(function(kluczKlienta) {
                        var [imie, metodaPlatnosci, napiwek] = kluczKlienta.split('|');
                        zamowieniaHTML += '<div class="grupa-klienta">' +
                                            '<h3>' + imie + ' - ' + metodaPlatnosci + ' - Napiwek: ' + napiwek + ' zł - Cena rachunku: ' + zamowieniaGrupowane[kluczStolika].zamowienia[kluczKlienta].cenaRachunku.toFixed(2) + ' zł</h3>';
                        
                        zamowieniaGrupowane[kluczStolika].zamowienia[kluczKlienta].zamowienia.forEach(function(zamowienie) {
                            var skladnikiDoDodaniaHTML = zamowienie.skladniki_do_dodania_nazwy ? '<p>Do dodania: <b>' + zamowienie.skladniki_do_dodania_nazwy + '</b></p>' : '';
                            var skladnikiDoUsunieciaHTML = zamowienie.skladniki_do_usuniecia_nazwy ? '<p>Do usunięcia: <b>' + zamowienie.skladniki_do_usuniecia_nazwy + '</b></p>' : '';
                            
                            zamowieniaHTML += '<div class="zamowienie">' +
                                              '<p><b>' + zamowienie.nazwa_dania + '</b></p>' +
                                              '<p>Rozmiar: ' + zamowienie.rozmiar_nazwa + '</p>' +
                                              '<p>Wybory: ' + 
                                              (zamowienie.wybor_12_nazwa ? zamowienie.wybor_12_nazwa + ', ' : '') +
                                              (zamowienie.wybor_34_nazwa ? zamowienie.wybor_34_nazwa + ', ' : '') +
                                              (zamowienie.wybor_56_nazwa ? zamowienie.wybor_56_nazwa : '') + '</p>' +
                                              skladnikiDoDodaniaHTML + skladnikiDoUsunieciaHTML +
                                              '<p>Ilość: <b>' + zamowienie.ilosc + '</b>, Cena: <b>' + zamowienie.cena + ' zł</b></p>' +
                                              '</div>';
                        });
                        
                        zamowieniaHTML += '</div>'; // Koniec grupy klienta
                    });
                    
                    zamowieniaHTML += '</div>'; // Koniec grupy stolika
                });
                
                $('#qelner-kelner-panel').html(zamowieniaHTML);
            } else {
                $('#qelner-kelner-panel').html('<p>Brak aktualnych zamówień.</p>');
            }
        }
    });
}

// Uruchamiamy pobieranie zamówień co kilka sekund
setInterval(pobierzZamowienia, 5000);


$('#przycisk-koszyk').on('click', function() {
    // Sprawdza, czy koszyk jest aktualnie ukryty
    if ($('#main-koszyk-container').is(':hidden')) {
        $('#main-koszyk-container').slideDown(); // Powoli pokazuje koszyk, jeśli jest ukryty
    } else {
        $('#main-koszyk-container').slideUp(); // Powoli ukrywa koszyk, jeśli jest widoczny
    }
});

    

    
    
function pokazElementyKoszykaZWyboremRachunku() {
    var idStolika = pobierzIdStolikaZURL();

    $.ajax({
        url: myAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'qelner_pokaz_elementy_koszyka_z_rachunkami',
            id_stolika: idStolika
        },
        success: function(response) {
            if (response.success) {
                var html = '<div id="podzial-rachunku-dzielonego" style="display: block;">'; // Upewnij się, że kontener będzie widoczny
                response.data.elementy_koszyka.forEach(function(element) {
                    html += '<div><span><b>' + element.nazwa_dania + '</b></span></br>';
                    html += '<span>' + element.wybor_12_nazwa + '</span>, ';
                    html += '<span>' + element.wybor_34_nazwa + '</span>, ';
                    html += '<span>' + element.wybor_56_nazwa + '</span> - ';
                    html += '<span>' + element.cena + ' zł</span>';
                    
                    
                   html += '<select id="wybor-platnosci-do-dania" data-id-elementu="' + element.id_elementu_koszyka + '">';

                    response.data.dostepne_rachunki.forEach(function(rachunek) {
                        html += '<option value="' + rachunek.id_rachunku + '">' + rachunek.imie + ' - ' + rachunek.metoda_platnosci + ' (ID:' + rachunek.id_rachunku + ')</option>';
                    });
                    html += '</select></div>';
                });
                html += '<button id="zapisz-wybor-rachunku">Zapisz</button>';

                html += '</div>';

                $('#dish-details-container').after(html); // Wstawia HTML bezpośrednio po #dish-details-container
            }
        }
    });
}
    
                
function zapiszWyborRachunku() {
    var idStolika = pobierzIdStolikaZURL();
    var wyboryRachunkow = {};
    $('#podzial-rachunku-dzielonego select').each(function() {
        var idElementuKoszyka = $(this).data('id-elementu');
        var idRachunku = $(this).val();
        wyboryRachunkow[idElementuKoszyka] = idRachunku;
    });

    $.ajax({
        url: myAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'qelner_zapisz_wybor_rachunku',
            id_stolika: idStolika,
            wybory_rachunkow: wyboryRachunkow
        },
        success: function(response) {
            if(response.success) {
                alert('Rachunki zostały zaktualizowane.');
                // Czyści całą zawartość diva przed dodaniem nowego przycisku
                $('#podzial-rachunku-dzielonego').empty().append('<button id="zloz-zamowienie">Złóż zamówienie</button>');
                // Tutaj możesz dodać obsługę kliknięcia dla nowego przycisku "Złóż zamówienie" jeśli jest potrzebna
            } else {
                alert('Wystąpił problem przy zapisywaniu wyboru rachunków.');
            }
        }
    });
}







// Możesz dodać ten kod wewnątrz $(document).ready(function($) {...}); w script.js
$(document).on('click', '#zapisz-wybor-rachunku', function() {
    zapiszWyborRachunku();
});


$(document).on('click', '#zloz-zamowienie', function() {
    var idStolika = pobierzIdStolikaZURL(); // Załóżmy, że masz taką funkcję
    $.ajax({
        url: myAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'qelner_zloz_zamowienie',
            id_stolika: idStolika
        },
        success: function(response) {
            alert('Zamówienie zostało złożone.');
            // Tutaj możesz dodać kod do odświeżenia strony lub wyświetlenia komunikatu użytkownikowi
        }
    });
});


    
    
    function pokazFormularzRachunkuDzielonego() {
    // Usuwamy zawartość kontenera i przygotowujemy formularz
    $('#propozycje-dan-container').empty();
    var formularzHTML =     '<button onclick="zamknijOkno()" style="float: right; font-size: 20px; line-height: 20px; cursor: pointer;">&times;</button>' + 
                            '<div class="rachunek-dzielony-kontener">' +
                            '<div class="rachunek-dzielony-header">' +
                                'Wybierz liczbę rachunków' +
                                '<button id="zmniejsz-liczbe-osob">-</button>' +
                                '<span id="liczba-osob">2</span>' +
                                '<button id="zwieksz-liczbe-osob">+</button>' +
                            '</div>' +
                            '<div id="rachunek-dzielony-lista"></div>' +
                            '<button id="dalej-rachunek-dzielony">Przejdź do podziału dań</button>' +
                        '</div>';
    $('#propozycje-dan-container').html(formularzHTML).show();
    aktualizujListeOsob(2);

    // Obsługa przycisków + i -
    $('#zwieksz-liczbe-osob').click(function() {
        var liczbaOsob = parseInt($('#liczba-osob').text());
        liczbaOsob++;
        $('#liczba-osob').text(liczbaOsob);
        aktualizujListeOsob(liczbaOsob);
    });

    $('#zmniejsz-liczbe-osob').click(function() {
        var liczbaOsob = parseInt($('#liczba-osob').text());
        if(liczbaOsob > 2) {
            liczbaOsob--;
            $('#liczba-osob').text(liczbaOsob);
            aktualizujListeOsob(liczbaOsob);
        }
    });
    
     $('#dalej-rachunek-dzielony').click(function() {
    zapiszRachunekDzielony();
    $('#propozycje-dan-container').hide(); // Ukrywa poprzedni kontener
    $('#podzial-rachunku-dzielonego').show(); // Pokazuje kontener podziału rachunku
    pokazElementyKoszykaZWyboremRachunku();
    
});

}




    function pokazFormularzRachunkuIndywidualnego() {
    // Usuwamy zawartość kontenera i przygotowujemy formularz
    $('#propozycje-dan-container').empty();
    var listaHTML = 
            '<button onclick="zamknijOkno()" style="float: right; font-size: 20px; line-height: 20px; cursor: pointer;">&times;</button>' + 
               '<h2>Wybierz metodę płatności i dodaj napiwek</h2>' +
               '<input type="number" id="napiwek-indywidualny" placeholder="Napiwek (opcjonalnie)">' +
               '<select id="metoda-platnosci-indywidualna">' +
               '<option value="gotowka">Gotówka</option>' +
               '<option value="karta">Karta</option>' +
               '</select>' +
               '<button id="zapisz-rachunek-indywidualny">Zapisz rachunek indywidualny</button>';
    $('#propozycje-dan-container').html(listaHTML).show();


    
    
     $('#zapisz-rachunek-indywidualny').click(function() {
    zapiszRachunekIndywidualny();
   $('#propozycje-dan-container').empty().append('<button onclick="zamknijOkno()" style="float: right; font-size: 20px; line-height: 20px; cursor: pointer;">&times;</button></br><button id="zloz-zamowienie">Złóż zamówienie</button>');

    
});

}








function aktualizujListeOsob(liczbaOsob) {
    var listaHTML = '';
    for(var i = 1; i <= liczbaOsob; i++) {
        listaHTML += '<div class="rachunek-dzielony-pozycja">' +
                        '<input type="text" placeholder="Imię" class="rachunek-dzielony-imie">' +
                        '<select class="rachunek-dzielony-metoda-platnosci">' +
                            '<option value="gotowka">Gotówka</option>' +
                            '<option value="karta">Karta</option>' +
                        '</select>' +
                        '<input type="number" step="any" min="0" class="rachunek-dzielony-napiwek" placeholder="Napiwek">' +
                    '</div>';
    }
    $('#rachunek-dzielony-lista').html(listaHTML);
}

// Dodajemy tę funkcję, aby obsłużyć kliknięcie przycisku "Rachunek dzielony"
$(document).on('click', '#rachunek-dzielony', function() {
    pokazFormularzRachunkuDzielonego();
});

$(document).on('click', '#na-jeden-rachunek', function() {
    pokazFormularzRachunkuIndywidualnego();
});








function zapiszRachunekDzielony() {
    var idStolika = pobierzIdStolikaZURL();
    var imiona = $('.rachunek-dzielony-imie').map(function() { return $(this).val(); }).get();
    var metodyPlatnosci = $('.rachunek-dzielony-metoda-platnosci').map(function() { return $(this).val(); }).get();
    var napiwki = $('.rachunek-dzielony-napiwek').map(function() { return $(this).val(); }).get();
    $.ajax({
        url: myAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'zapisz_rachunek_dzielony',
            id_stolika: idStolika,
            imiona: imiona,
            metodyPlatnosci: metodyPlatnosci,
            napiwki: napiwki
        },
        success: function(response) {
            if(response.success) {

            } else {
                alert('Wystąpił problem przy zapisywaniu rachunku.');
            }
        }
    });
}


function zapiszRachunekIndywidualny() {
    var napiwek = $('#napiwek-indywidualny').val();
    var metodaPlatnosci = $('#metoda-platnosci-indywidualna').val();
    var idStolika = pobierzIdStolikaZURL();

    $.ajax({
        url: myAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'zapisz_rachunek_indywidualny',
            id_stolika: idStolika,
            napiwek: napiwek,
            metoda_platnosci: metodaPlatnosci
        },
        success: function(response) {
            if(response.success) {
               $('#podzial-rachunku-dzielonego').empty().append('<button id="zloz-zamowienie">Złóż zamówienie</button>');
            } else {
                alert('Wystąpił problem przy zapisywaniu rachunku indywidualnego.');
            }
        }
    });
}









    
    
    
    
    function pobierzIdStolikaZURL() {
        var params = new URLSearchParams(window.location.search);
        return params.get('id_stolika'); // Pobiera wartość 'id_stolika' z URL
    }

function pokazPropozycjeDan() {
    var idStolika = pobierzIdStolikaZURL(); // Upewnij się, że masz taką funkcję zdefiniowaną

    $.ajax({
        url: myAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'qelner_pokaz_propozycje_dan',
            id_stolika: idStolika
        },
        success: function(response) {
            if (response.success) {
                
                $('#propozycje-dan-container').empty().append('<h2>Propozycje dań</h2><button onclick="zamknijOkno()" style="float: right; font-size: 20px; line-height: 20px; cursor: pointer;">&times;</button>');
                response.data.forEach(function(danie) {
                    var danieDiv = $('<div>').addClass('propozycja-dania');
                    var fullImagePath = ensureHttps(danie.zdjecie_1);
                    danieDiv.append($('<img>').attr('src', fullImagePath));
                    danieDiv.append($('<h3>').text(danie.nazwa_dania));
                    danieDiv.append($('<p>').text('Cena: ' + danie.danie_cena + ' zł'));
                    danieDiv.append($('<button>').addClass('details-button').data('id', danie.id_dania).text('Szczegóły'));

                    $('#propozycje-dan-container').append(danieDiv);
                    
                });
                $('#propozycje-dan-container').append('<button id="przejdz-do-rachunku" class="przejdz-do-rachunku">Przejdź do rachunku</button>');
                // Dodajemy przycisk "Przejdź do rachunku"
                

                // Obsługa kliknięcia przycisku "Przejdź do rachunku"
                $('#przejdz-do-rachunku').on('click', function() {
                    $('#propozycje-dan-container').empty(); // Możemy ukryć lub usunąć zawartość kontenera
                    pokazOpcjePlatnosci(); // Pokazujemy nowy kontener z opcjami płatności
                });

                $('#propozycje-dan-container').show(); // Pokazuje kontener z propozycjami
            }
        }
    });
}







function pokazOpcjePlatnosci() {
    // Tworzymy i pokazujemy opcje płatności
    var opcjePlatnosciHTML = '<div class="opcje-platnosci">' +
                            '<button onclick="zamknijOkno()" style="float: right; font-size: 20px; line-height: 20px; cursor: pointer;">&times;</button>' + 
                             '<button id="na-jeden-rachunek">Na jeden rachunek</button>' +
                             '<button id="rachunek-dzielony">Rachunek dzielony</button>' +
                             '</div>';
    $('#propozycje-dan-container').html(opcjePlatnosciHTML).show();
}


    
    
        $('.category-button').on('click', function() {
        var idKategorii = $(this).data('id');
        $.ajax({
            url: myAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'load_dishes',
                id_kategorii: idKategorii
            },
            success: function(response) {
                if (response.success) {
                    $('#dishes-container').empty();
                    response.data.forEach(function(danie) {
                        var imagePath = danie.zdjecie_1; // np. "qelner.pl/wp-content/uploads/obraz.jpg"
                        var fullImagePath = ensureHttps(imagePath);
                        var dishHTML = '<div class="danie">';
                        var dishHTML = '<img src="' + fullImagePath + '" alt="Opis obrazu">';

                        dishHTML += '<h3>' + danie.nazwa_dania + '</h3>';
                        dishHTML += '<p>Cena: ' + danie.danie_cena + ' zł</p>';
                        dishHTML += '<button class="details-button" data-id="' + danie.id_dania + '">Szczegóły</button>';
                        dishHTML += '</div>';
                        $('#dishes-container').append(dishHTML);
                    });
                }
            }
        });
    });
    setTimeout(function() {
        $('.category-button').first().click();
    }, 100); // Małe opóźnienie, aby upewnić się, że wszystko zostało załadowane
    
    
   
    
    
     $('#twoj-przycisk-dalej').on('click', function() {
        pokazPropozycjeDan();
    });
    
    function usunZKoszyka(idElementuKoszyka) {
    if (!idElementuKoszyka) {
        console.error('ID elementu koszyka jest wymagane.');
        return;
    }

    $.ajax({
        url: myAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'usunZKoszyka_ajax',
            id_elementu_koszyka: idElementuKoszyka
        },
        success: function(response) {
            if (response.success) {
                alert('Usunięto z koszyka!');
                location.reload(); // Odświeżanie strony, aby zaktualizować widok koszyka
            } else {
                alert('Błąd podczas usuwania z koszyka.');
            }
        }
    });
}

// Rejestracja zdarzenia kliknięcia
$('#koszyk-container').on('click', '.usun-z-koszyka-button', function() {
    var idElementuKoszyka = $(this).data('id');
    usunZKoszyka(idElementuKoszyka);
});

    
   

 

        function pobierzKoszyk(idStolika) {
        if (!idStolika) {
            console.error('ID stolika nie jest określone.');
            return;
        }

        $.ajax({
            url: myAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'pokaz_koszyk_z_nazwami',
                id_stolika: idStolika
            },
            success: function(response) {
                if (response.success) {
                    $('#koszyk-container').empty(); // Czyści poprzednią zawartość koszyka
                    let totalCena = 0; // Zmienna na sumę cen
                    response.data.forEach(function(element) {
                        var elementHTML = '<div class="element-koszyka">';
                        elementHTML += element.nazwa_dania ? '<p><b>' + element.nazwa_dania + '</b></p>' : '';
                        elementHTML += element.rozmiar_nazwa ? '<p>' + element.rozmiar_nazwa : '';
                        elementHTML += element.wybor_12_nazwa ? ', ' + element.wybor_12_nazwa : '';
                        elementHTML += element.wybor_34_nazwa ? ', ' + element.wybor_34_nazwa : '';
                        elementHTML += element.wybor_56_nazwa ? ', ' + element.wybor_56_nazwa + '</p>' : '';
                        elementHTML += element.skladniki_do_dodania ? '<p>Dodano: ' + element.skladniki_do_dodania + '</p>' : '';
                        elementHTML += element.skladniki_do_usuniecia ? '<p>Usunięto: ' + element.skladniki_do_usuniecia + '</p>' : '';
                        elementHTML += '<p> Ilość: ' + '<input type="number" class="ilosc-produktu" data-id="' + element.id_elementu_koszyka + '" value="' + element.ilosc + '" min="1">';
                        elementHTML += element.cena ? '<p>Cena: ' + element.cena + ' zł</p>' : '';
                        elementHTML += '<button class="usun-z-koszyka-button" data-id="' + element.id_elementu_koszyka + '">Usuń</button>';
                        elementHTML += '</div>';
                        totalCena += element.cena * element.ilosc;
                        $('#koszyk-container').append(elementHTML);
                    });
                     $('#total-price-container').text('Łączna cena: ' + totalCena.toFixed(2) + ' zł');
                } else {
                    console.error('Błąd podczas pobierania danych koszyka.');
                }
            },
            error: function() {
                console.error('Błąd AJAX.');
            }
        });
    }
    
        var idStolika = pobierzIdStolikaZURL(); // Upewnij się, że masz taką funkcję zdefiniowaną
    pobierzKoszyk(idStolika);
    
      $('#koszyk-container').on('change', '.ilosc-produktu', function() {
        let idElementuKoszyka = $(this).data('id');
        let nowaIlosc = $(this).val();

        aktualizujIloscProduktu(idElementuKoszyka, nowaIlosc);
    });



    function aktualizujIloscProduktu(idElementuKoszyka, nowaIlosc) {
    $.ajax({
        url: myAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'aktualizuj_ilosc_ajax',
            id_elementu_koszyka: idElementuKoszyka,
            ilosc: nowaIlosc
        },
        success: function(response) {
            if (response.success) {
                // Ponowne pobranie koszyka, aby zaktualizować łączną cenę
                let idStolika = pobierzIdStolikaZURL(); 
                obliczCeneKoncowa(); 
                pobierzKoszyk(idStolika);

            } else {
                alert('Nie udało się zaktualizować ilości.');
            }
        }
    });
}
    
    function obliczCeneKoncowa() {
        let cenaPodstawowa = parseFloat($('#cenaKońcowa').attr('data-cena-podstawowa')) || 0; // Dodajemy || 0, aby uniknąć NaN

        let dodatkowaCena = 0;

        // Składniki do dodania
        $('input[name="skladnikiDoDodania[]"]:checked').each(function() {
            dodatkowaCena += parseFloat($(this).data('cena')) || 0; // Dodajemy || 0, aby uniknąć NaN
        });

        // Rozmiary
        dodatkowaCena += parseFloat($('select[name="rozmiar"] option:selected').data('cena')) || 0; // Dodajemy || 0, aby uniknąć NaN

        // Wybory
        ['wybor_12', 'wybor_34', 'wybor_56'].forEach(function(wybor) {
            dodatkowaCena += parseFloat($('select[name="' + wybor + '"] option:selected').data('cena')) || 0; // Dodajemy || 0, aby uniknąć NaN
        });

        // Ustawienie ceny końcowej
        $('#cenaKońcowa').text((cenaPodstawowa + dodatkowaCena).toFixed(2) + ' zł');
    }
    

    
    
    
    
    
   
function dodajDoKoszyka(idDania) {
    // Pobieranie ID stolika z URL
    const params = new URLSearchParams(window.location.search);
    let idStolika = params.get('id_stolika'); // 'id_stolika' to nazwa parametru w URL

    if (!idStolika) {
        console.error('ID stolika nie zostało znalezione.');
        return;
    }

    let cenaKoncowa = $('#cenaKońcowa').text().replace(' zł', '');
    let rozmiar = $('select[name="rozmiar"] option:selected').val();
    let rozmiarCena = $('select[name="rozmiar"] option:selected').data('cena');
    let wybor_12 = $('select[name="wybor_12"] option:selected').val();
    let wybor_12_cena = $('select[name="wybor_12"] option:selected').data('cena');
    let wybor_34 = $('select[name="wybor_34"] option:selected').val();
    let wybor_34_cena = $('select[name="wybor_34"] option:selected').data('cena');
    let wybor_56 = $('select[name="wybor_56"] option:selected').val();
    let wybor_56_cena = $('select[name="wybor_56"] option:selected').data('cena');

    // Składniki do dodania/usunięcia
    let skladnikiDoDodania = [];
    $('input[name="skladnikiDoDodania[]"]:checked').each(function() {
        skladnikiDoDodania.push($(this).val());
    });
        let skladnikiDoUsuniecia = [];
    $('input[name="skladnikiDoUsuniecia[]"]:checked').each(function() {
        skladnikiDoUsuniecia.push($(this).val());
    });

    $.ajax({
        url: myAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'dodajDoKoszyka_ajax',
            id_dania: idDania,
            id_stolika: idStolika,
            cena_koncowa: cenaKoncowa,
            rozmiar: rozmiar,
            rozmiar_cena: rozmiarCena,
            wybor_12: wybor_12,
            wybor_12_cena: wybor_12_cena,
            wybor_34: wybor_34,
            wybor_34_cena: wybor_34_cena,
            wybor_56: wybor_56,
            wybor_56_cena: wybor_56_cena,
            skladniki_do_usuniecia: JSON.stringify(skladnikiDoUsuniecia),
            skladniki_do_dodania: JSON.stringify(skladnikiDoDodania)

        },
        success: function(response) {
            location.reload();
            alert('Dodano do koszyka!');
        }
    });
}


    
// Obsługa kliknięcia przycisku "Dodaj do koszyka" z delegowaniem
$('#dish-details-container').on('click', '.dodaj-do-koszyka-button', function() {
    var idDania = $(this).data('id');
    dodajDoKoszyka(idDania);
});


    $(document).on('click', '.details-button', function(e) {
        e.preventDefault();
        var dishId = $(this).data('id');

        $.ajax({
            url: myAjax.ajaxurl,
            type: 'POST',
            data: {
                'action': 'pokazSzczegolyDania',
                'id_dania': dishId,
            },
            success: function(response) {
                $('#dish-details-container').html(response).show();

                // Ustawienie ceny podstawowej jako data-atrybut dla łatwego dostępu
                $('#cenaKońcowa').attr('data-cena-podstawowa', $('#cenaKońcowa').text().replace(' zł', ''));

                // Re-inicjalizacja obliczeń ceny końcowej i ponowna rejestracja zdarzeń
                obliczCeneKoncowa();
                $('input[name="skladnikiDoDodania[]"], select[name="rozmiar"], select[name="wybor_12"], select[name="wybor_34"], select[name="wybor_56"]').off('change').on('change', obliczCeneKoncowa);
            }
        });
    });
});
 