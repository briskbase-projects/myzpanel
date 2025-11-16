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
                <h2 style="margin: 0;"><strong>Retourzending</strong></h2>
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
                        <th align="left">Bestelnummer.:</th>
                        <td>{{ $detail->attributes->order_number }}</td>
                    </tr>
                    <tr>
                        <th align="left">Rekening van:</th>
                        <td>{{ date('d.m.Y', strtotime($detail->attributes->order_date)) }}</td>
                    </tr>
                    <tr>
                        <th align="left">Klantenummer:</th>
                        <td>{{ $detail->attributes->customer_number }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <table>
        <tr>
            <td><p>Om een artikel terug te sturen, gelieve de instructies onder aan de pagina te volgen. Wanneer u het artikel wilt ruilen, plaats dan alstublieft een nieuwe bestelling op Zalando.be.</p></td>
        </tr>
    </table>
    <table width="100%" cellpadding="5" border="1" cellspacing="0">
        
        <tr align="left">
            <th width="15%">Zalando Art. Nr.</th>
            <th width="15%">Leverancier Art. Nr.</th>
            <th width="30%">Benaming</th>
            <th width="10%">Maat</th>
            <th width="10%" bgcolor="#d8d8d8">Reden</th>
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
                <p style="margin:0px;"><strong>Retourreden:</strong></p>
                <p>
                1 Artikel bevalt me niet &nbsp; &nbsp; &nbsp; 3 Te klein &nbsp; &nbsp; &nbsp; 5 Levering te laat 9 Verkeerd artikel &nbsp; &nbsp; &nbsp; 9 falscher Artikel <br>
                2 Te groot &nbsp; &nbsp; &nbsp; 4 Prijs-kwaliteit verhouding &nbsp; &nbsp; &nbsp; 6 Anders dan voorgesteld &nbsp; &nbsp; &nbsp; 10 Defect artikel
                </p>
                <table  width="100%">
                    <tr>
                        <td width="80%">
                            <p style="font-size:14px;"><strong>*Wanneer het artikel defect is: waar bevindt zich het defect? (verplicht)</strong> </p>
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
                <p style="margin:0px;"><strong>Instructies voor uw retourzending:</strong></p>
                <p>
                Reden van retour invullen. <br>
                Artikel incl. origineel retourformulier verpakken. <br>
                Leg het artikel in de doos en kleef het meegeleverde retouretiket op de doos, over het originele etiket heen.
                Geef uw retourzending af bij een bpost postkantoor of postpunt. <br>
                Wilt u een artikel omruilen, gelieve dan eerst de aanwijzingen voor retouren te volgen. Vervolgens kunt u gemakkelijk en veilig uw nieuw gewenst artikel op zalando.be bestellen.
                
                 
                 
                </p>
                <p><strong>Aanwijzing voor uw retourzending:</strong> Deze zending bevat een artikel van een Zalando-Partner. Gelieve deze goederen niet aan Zalando terug te zenden, maar direct aan de Partner. Uitgebreide informatie voor de retourzending vindt u op de bijgeleverde instructies.</p>
                <p><strong>Opmerking voor het retourneren van cosmetische artikelen:</strong> Zorg ervoor dat de producten in de originele verpakking zitten wanneer u ze terugstuurt. Producten met een zegel komen niet meer in aanmerking voor retourzending als het zegel verwijderd of beschadigd is. Uw wettelijke garantie- en annuleringsrechten blijven echter onaangetast.</p>
            </td>
        </tr>
    </table>
    
</body>
</html>