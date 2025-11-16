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
                <h2 style="margin: 0;"><strong>Modulo di restituzione</strong></h2>
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
                        <th align="left">Numero d’ordine.:</th>
                        <td>{{ $detail->attributes->order_number }}</td>
                    </tr>
                    <tr>
                        <th align="left">Fattura del:</th>
                        <td>{{ date('d.m.Y', strtotime($detail->attributes->order_date)) }}</td>
                    </tr>
                    <tr>
                        <th align="left">Numero cliente:</th>
                        <td>{{ $detail->attributes->customer_number }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <table>
        <tr>
            <td><p>Per restituire un articolo si prega di seguire le istruzioni indicate nel modulo « Istruzioni per il reso »
presente all’interno del pacco. Se si desidera sostituire l´articolo ordinando una taglia differente, si prega
di effettuare un nuovo ordine su www.zalando.it</p></td>
        </tr>
    </table>
    <table width="100%" cellpadding="5" border="1" cellspacing="0">
        
        <tr align="left">
            <th width="15%">Ref. Zalando</th>
            <th width="15%">Ref. Partner </th>
            <th width="30%">Articolo</th>
            <th width="10%">Taglia</th>
            <th width="10%" bgcolor="#d8d8d8">Motivazione</th>
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
                <p style="margin:0px;"><strong>Motivazione del Reso:</strong></p>
                <p>
                1 Non mi piace il prodotto  &nbsp; &nbsp; &nbsp; 3 Troppo piccolo &nbsp; &nbsp; &nbsp; 5 Ritardo nella consegna &nbsp; &nbsp; &nbsp; 9 Articolo errato <br>
                2 Troppo grande &nbsp; &nbsp; &nbsp; 4 Rapporto qualità-prezzo &nbsp; &nbsp; &nbsp; 6 Me lo immaginavo diverso &nbsp; &nbsp; &nbsp; 10 Prodotto difettoso
                </p>
            </td>
        </tr>
    </table>
    <br>
    <table width="100%" bgcolor="#d8d8d8">
        <tr>
            <td>
                <p style="margin:0px;"><strong>Se il prodotto è difettoso, indicare il difetto (obbligatorio):</strong></p>
                <p>
                1. Indica la motivazione del tuo reso. Inserisci uno dei numeri sopra riportati nella tabella sopra. Se, ad esempio, l'articolo fosse troppo grande scrivi 3. <br>
                2. Inserisci l'articolo (o articoli) e questa nota di reso in una scatola. <br>
                3. Attacca l'etichetta di reso all'esterno del pacco. <br>
                4. Porta il pacco presso un ufficio postale\kipoint SDA. Per trovare l'ufficio postale\kipoint Post Italiane/SDA più vicino. <br>
                5. Ricorda di fare sempre una foto all'etichetta che attacchi sul tuo reso o scriviti il codice di tracciabilità e conservalo fino a che il rimborso non sarà avvenuto. <br>
                </p>
                <p><strong>Nota per il reso di prodotti cosmetici:</strong> I prodotti devono essere resi all'interno della confezione originale. I prodotti che sono provvisti di un sigillo non potranno essere restituiti se il sigillo è stato rimosso o danneggiato. Sono comunque fatti salvi i diritti di legge di garanzia e annullamento.</p>
            </td>
        </tr>
    </table>
    
</body>
</html>