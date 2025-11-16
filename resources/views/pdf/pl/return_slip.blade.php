<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        body{
            font-family: DejaVu Sans, Arial, sans-serif;font-size: 12px;
        }
    </style>
</head>
<body>

    <table width="100%">
        <tr valign="top">
            <td>
                <img src="{{ env('CLIENT_LOGO') }}" alt="" width="100">
                <h2 style="margin: 0;"><strong>Dokument zwrotu</strong></h2>
            </td>
            <td align="right">
                <img src="{{ url('images/zalando.png') }}" alt="" width="200">
            </td>
        </tr>
        <tr>
            <td width="50%">
                <p>{{ $detail->attributes->shipping_address->first_name }} {{ $detail->attributes->shipping_address->last_name }}<br>
                {{ $detail->attributes->shipping_address->address_line_1 }}<br>
                {{ $detail->attributes->shipping_address->zip_code }} {{ $detail->attributes->shipping_address->city }}</p>
            </td>
            <td align="right" width="50%" bgcolor="#d8d8d8">
                <table width="100%" style="border: 1px solid #ddd;" cellpadding="5">
                  
                    <tr>
                        <th align="left">Nr zamówienia.:</th>
                        <td>{{ $detail->attributes->order_number }}</td>
                    </tr>
                    <tr>
                        <th align="left">Rachunek:</th>
                        <td>{{ date('d.m.Y', strtotime($detail->attributes->order_date)) }}</td>
                    </tr>
                    <tr>
                        <th align="left">Numer klienta:</th>
                        <td>{{ $detail->attributes->customer_number }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <table>
        <tr>
            <td><p>Aby odesłać artykuł, proszę postępować zgodnie z instrukcją zwrotu.
Jeśli chcesz wymienić artykuł, proszę zamówić wybrany produkt na stronie www.zalando.pl</p></td>
        </tr>
    </table>
    <table width="100%" cellpadding="5" border="1" cellspacing="0">
        
        <tr align="left">
            <th width="15%">Numer artykułu
Zalando</th>
            <th width="15%">Numer artykułu
dostawcy</th>
            <th width="30%">Nazwa artykułu</th>
            <th width="10%">Rozmiar</th>
            <th width="10%" bgcolor="#d8d8d8">Powód</th>
            <!-- <th width="20%">Stückpreis</th> -->
            <!-- <th width="15%">Gesamtbetrag</th> -->
        </tr>
        @foreach($included as $oi)
        @if($oi->type == 'OrderItem')
            @php
            
                $sizeTitle = $ean = $sku = "";
                foreach($sizes as $size):
                foreach($size as $sd):
                    if($oi->attributes->external_id == $sd['sku']):
                    $sizeTitle = $sd['title'];
                    $ean = $sd['ean'];
                    $sku = $sd['sku'];
                    endif;
                endforeach;
                endforeach;
            @endphp
            <tr>
                <td>{{ $oi->attributes->article_id }}</td>
                <td>{{$sku}}</td>
                <td>{{ $oi->attributes->description }}</td>
                <td>({{$sizeTitle}})</td>
                <td></td>
            </tr>
            @endif
        @endforeach
    </table>
    <br>
    
    <table width="100%" bgcolor="#d8d8d8">
        <tr>
            <td>
                <p style="margin:0px;"><strong>Powody zwrotu::</strong></p>
                <p>
                1 nie podoba mi się &nbsp; &nbsp; &nbsp; 3 za mały &nbsp; &nbsp; &nbsp; 5 dostawa za późno &nbsp; &nbsp; &nbsp; 9 artykuł nieprawidłowy <br>
                2 za duży &nbsp; &nbsp; &nbsp; 4 stosunek ceny do jakości &nbsp; &nbsp; &nbsp; 6 niezgodny z opisem &nbsp; &nbsp; &nbsp; 10 artykuł uszkodzony
                </p>
                <table  width="100%">
                    <tr>
                        <td width="80%">
                            <p style="font-size:14px;"><strong>*W przypadku, gdy artykuł jest uszkodzony: Gdzie znajduję się defekt? (wymagane) </strong> </p>
                        </td>
                        <td width="20%"><input type="text" width="100%" style="height: 20px";></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <br>
    <table width="100%" bgcolor="#d8d8d8">
        <tr>
            <td>
                <p style="margin:0px;"><strong>Jak dokonać zwrotu-skrócona instrukcja:</strong></p>
                <p>
                Wpisać powód zwrotu (numer) <br>
Zapakować artykuł wraz z dokumentem zwrotu <br>
Przykleić etykietę zwrotu i zanieść paczkę do punktu odbioru Poczta Polska <br>
W przypadku wymiany, zamówić wybrany produkt na stronie www.zalando.pl <br>
                </p>
                <p><strong>Wskazówka dotycząca wysyłki:</strong> Wysyłka dotyczy artykułu pochodzącego od Partnera Zalando. Proszę zatem o odesłanie przesyłki bezpośrednio do Partnera Zalando. Szeczegółowe informacje dotyczące zwrotu można znaleźć w załączonej instrucji.</p>
            </td>
        </tr>
    </table>
    
</body>
</html>